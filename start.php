<?php
/**
 * Elgg Search Improved start.php
 *
 * @package SearchImproved
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2013
 * @link http://www.thinkglobalschool.com/
 *
 */

elgg_register_event_handler('init', 'system', 'searchimproved_init');

// Init wall posts
function searchimproved_init() {
	// Register library
	elgg_register_library('elgg:searchimproved', elgg_get_plugins_path() . 'searchimproved/lib/searchimproved.php');
	//elgg_load_library('elgg:searchimproved');

	// Extend main CSS
	elgg_extend_view('css/elgg', 'css/searchimproved/css');

	// Register JS Lib
	$js = elgg_get_simplecache_url('js', 'searchimproved/searchimproved');
	elgg_register_simplecache_view('js/searchimproved/searchimproved');
	elgg_register_js('elgg.searchimproved', $js);
	elgg_load_js('elgg.searchimproved');

	// Load autocomplete JS
	elgg_load_js('elgg.autocomplete');
	elgg_load_js('jquery.ui.autocomplete.html');

	// Extend topbar_ajax view (from tgstheme)
	elgg_extend_view('page/elements/topbar_ajax', 'searchimproved/topbar');

	// Register custom search page handler
	elgg_register_page_handler('searchimproved', 'searchimproved_page_handler');

	// Entity menu hook for search results
	elgg_register_plugin_hook_handler('register', 'menu:entity', 'searchimproved_entity_menu_handler', 999999);
}

/**
 * Page handler for autocomplete search endpoint.
 *
 * Other options include:
 *     match_on	   string all or array(groups|users|entities)
 *     limit       int    default is 10
 *
 * @param array $page
 * @return string JSON string is returned and then exit
 * @access private
 */
function searchimproved_page_handler($page) {
	elgg_push_context('searchimproved_results');

	$dbprefix = elgg_get_config('dbprefix');

	if (!$q = get_input('term', get_input('q'))) {
		exit;
	}

	$q = sanitise_string($q);

	// replace mysql vars with escaped strings
	$q = str_replace(array('_', '%'), array('\_', '\%'), $q);

	$match_on = get_input('match_on', 'all');

	if (!is_array($match_on)) {
		$match_on = array($match_on);
	}

	// all = users and groups
	if (in_array('all', $match_on)) {
		$match_on = array('users', 'groups', 'objects');
	}

	$limit = sanitise_int(get_input('limit', 10));

	// Arrays to hold match_type sql
	$types = array();
	$results = array();
	$joins = array();
	$where_ors = array();

	// Build match type sql
	foreach ($match_on as $match_type) {
		switch ($match_type) {
			case 'users':
				$types[] = 'user';
				// Don't search for last name unless user is logged in
				if (elgg_is_logged_in()) {
					$logged_in_search = "OR ue.name LIKE '% $q%'";
				}
				$joins[] = "LEFT JOIN {$dbprefix}users_entity ue on e.guid = ue.guid";
				$where_ors[] = "((e.type = 'user' AND ue.banned = 'no') AND ue.name LIKE '$q%' $logged_in_search OR ue.username LIKE '$q%')";
				break;
			case 'groups':
				// don't return results if groups aren't enabled.
				if (!elgg_is_active_plugin('groups')) {
					continue;
				}
				$types[] = 'group';
				$joins[] = "LEFT JOIN {$dbprefix}groups_entity ge on e.guid = ge.guid";
				//$where_ors[] = "(e.type = 'group' AND (ge.name LIKE '$q%' OR ge.name LIKE '% $q%' OR ge.description LIKE '% $q%'))";
				$where_ors[] = "(e.type = 'group' AND (ge.name LIKE '$q%' OR ge.name LIKE '% $q%'))";
				break;
			case 'objects':
				$types[] = 'object';
				$joins[] = "LEFT JOIN {$dbprefix}objects_entity oe on e.guid = oe.guid";
				$subtype_sql = elgg_get_entity_type_subtype_where_sql('e', array('object'), get_registered_entity_types('object'), null);
				$where_ors[] =  "{$subtype_sql} AND (oe.title LIKE '$q%' OR oe.title LIKE '% $q%' OR oe.description LIKE '% $q%')";
				break;
			default: 
				// Unknown match type
				header("HTTP/1.0 400 Bad Request", true);
				echo "livesearch: unknown match_on of $match_type";
				exit;
				break;
		}
	}

	// Get config info
	$site_guid = elgg_get_site_entity()->guid;
	$suffix = get_access_sql_suffix('e');

	// Implode joins
	$joins_sql = implode(' ', $joins);

	// Implode where ors
	$wheres_sql = implode (' OR ', $where_ors);

	// Implode types field 
	$types_sql = "'" . implode("', '", $types) . "'";

	// Build Query
	$query = "SELECT e.* FROM elgg_entities e 
			 $joins_sql 
			 WHERE ($wheres_sql) 
			 AND (e.site_guid IN ($site_guid)) 
			 AND ((e.enabled='yes')) 
			 AND $suffix ORDER BY FIELD(e.type, {$types_sql}), e.time_created DESC LIMIT 0, 14";

	// Grab entities
	$entities = _elgg_fetch_entities_from_sql($query);

	// Use simpleicon views for entities (from modules plugin, for now)
	set_input('ajaxmodule_listing_type', 'simpleicon');

	// Build json content for entity
	foreach ($entities as $entity) {

		$output = elgg_view_list_item($entity, array(
			'use_hover' => false,
			'class' => 'elgg-autocomplete-item',
		));

		$icon = elgg_view_entity_icon($entity, 'tiny', array(
			'use_hover' => false,
		));

		$result = array(
			'type' => $match_type,
			'guid' => $entity->guid,
			'label' => $output,
			'icon' => $icon,
			'url' => $entity->getURL(),
			'category' => elgg_echo('searchimproved:category:' . $entity->getType())
		);

		$results[$entity->guid] = $result;
	}

	// 'More results' link
	$search_more_link = elgg_view('output/url', array(
		'text' => elgg_echo('searchimproved:label:seemore', array($q)),
		'href' => elgg_normalize_url("search?q=$q&search_type=all"),
		'class' => 'searchimproved-more-results'
	));

	// Return a no result category if search came up dry
	if (count($results) == 0) {
		$category = elgg_echo('searchimproved:noresults');
	} else {
		// Set category to empty
		$category = 'empty';
	}

	$results[-1] = array(
		'name' => 'no_results',
		'label' => $search_more_link,
		'value' => null,
		'category' => $category
	);

	header("Content-Type: application/json");
	echo json_encode(array_values($results));

	elgg_pop_context();
	exit;
}

/**
 * Remove items entity menus in search results
 */ 
function searchimproved_entity_menu_handler($hook, $type, $return, $params) {
	if (elgg_in_context('searchimproved_results')) {
		return array();
	}
}

