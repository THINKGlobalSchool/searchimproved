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
elgg_register_event_handler('ready', 'system', 'searchimproved_generate_user_cache');

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

	// Register user/group prefetch handler
	elgg_register_page_handler('searchprefetch', 'searchimproved_prefetch_handler');

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

	$entities = array();

	// Build match type sql
	foreach ($match_on as $match_type) {
		switch ($match_type) {
			case 'users':
				$user_options = array(
					'type' => 'user',
					'joins' => array(
						"JOIN {$dbprefix}users_entity ue on e.guid = ue.guid"
					),
					'wheres' => array(
						"(ue.banned = 'no'AND (ue.name LIKE '$q%' $logged_in_search OR ue.username LIKE '$q%'))"
					),
					'limit' => $limit
				);

				$users = elgg_get_entities($user_options);
				$entities = array_merge($entities, $users);
				break;
			case 'groups':
				// don't return results if groups aren't enabled.
				if (!elgg_is_active_plugin('groups')) {
					continue;
				}

				$group_options = array(
					'type' => 'group',
					'joins' => array(
						"JOIN {$dbprefix}groups_entity ge on e.guid = ge.guid"
					),
					'wheres' => array(
						"(ge.name LIKE '$q%' OR ge.name LIKE '% $q%')"
					),
					'limit' => $limit
				);

				$groups = elgg_get_entities($group_options);
				$entities = array_merge($entities, $groups);
				break;
			case 'objects':
				$object_options = array(
					'type' => 'object',
					'subtypes' => get_registered_entity_types('object'),
					'joins' => array(
						"JOIN {$dbprefix}objects_entity oe on e.guid = oe.guid"
					),
					'wheres' => array(
						"(oe.title LIKE '$q%' OR oe.title LIKE '% $q%' OR oe.description LIKE '% $q%')"
					),
					'limit' => $limit
				);

				$objects = elgg_get_entities($object_options);
				$entities = array_merge($entities, $objects);
				break;
			default: 
				// Unknown match type
				header("HTTP/1.0 400 Bad Request", true);
				echo "livesearch: unknown match_on of $match_type";
				exit;
				break;
		}
	}

	// Use simpleicon views for entities (from modules plugin, for now)
	set_input('ajaxmodule_listing_type', 'simpleicon');

	// Build json content for entity
	foreach ($entities as $entity) {

		$output = elgg_view_list_item($entity, array(
			'use_hover' => false,
			'class' => 'elgg-autocomplete-item',
		));

		$result = array(
			'guid' => $entity->guid,
			'label' => $output,
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
 * Page handler for search prefetch. Grabs all users and accessible groups
 *
 * @param array $page
 * @return string JSON string is returned and then exit
 * @access private
 */
function searchimproved_prefetch_handler($page) {
	$results = array();
	$users = elgg_get_config('users_cache');

	$results = $users;

	$groups = elgg_get_entities(array(
		'type' => 'group',
		'limit' => 0
	));

	$group_results = array();

	elgg_push_context('searchimproved_results');
	foreach ($groups as $group) {
		$group_results[] = array(
			'guid' => $group->guid,
			'name' => $group->name,
			'category' => 'group',
			'url' => $group->getURL(),
			'label' => elgg_view_list_item($group, array('use_hover' => false,'class' => 'elgg-autocomplete-item'))
		);
	}
	elgg_pop_context();

	$results = array_merge($results, $group_results);
	
	echo json_encode($results);
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

/**
 * Save system wide user cache
 */
function searchimproved_generate_user_cache() {
	global $CONFIG;
	
	// Try to load users from the cache
	if ($CONFIG->system_cache_enabled) {
		$users_cache = unserialize(elgg_load_system_cache('users_cache'));
	}

	// Nothing in cache, or cache is disabled
	if (!$users_cache) {
		$users = elgg_get_entities(array(
			'type' => 'user',
			'limit' => 0,
			'joins' => array(
				"JOIN {$CONFIG->dbprefix}users_entity ue on e.guid = ue.guid"
			),
			'wheres' => array(
				"(ue.banned = 'no')"
			)
		));

		$users_cache = array();

		elgg_push_context('searchimproved_results');
		foreach ($users as $user) {
			$users_cache[] = array(
				'guid' => $user->guid,
				'name' => $user->name,
				//'username' => $user->username,
				'url' => $user->getURL(),
				'category' => 'user',
				'label' => elgg_view_list_item($user, array('use_hover' => false,'class' => 'elgg-autocomplete-item'))
			);
		}
		elgg_pop_context();
		
		// Save to cache, if enabled
		if ($CONFIG->system_cache_enabled) {
			elgg_save_system_cache('users_cache', serialize($users_cache));
		}
	}

	// Set config variable
	elgg_set_config('users_cache', $users_cache);

	return TRUE;
}

