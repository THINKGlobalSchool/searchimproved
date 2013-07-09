<?php
/**
 * Elgg Search Improved JS library
 *
 * @package SearchImproved
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2013
 * @link http://www.thinkglobalschool.com/
 *
 * See: http://jqueryui.com/autocomplete/
 */
?>
//<script>
elgg.provide('elgg.searchimproved');

elgg.searchimproved.searchInput = false;
elgg.searchimproved.prefetchData = null;
elgg.searchimproved.searchLimit = 5;
elgg.searchimproved.searchEndpoint = elgg.get_site_url() + 'searchimproved';
elgg.searchimproved.prefetchEndpoint = elgg.get_site_url() + 'searchprefetch';

// Init
elgg.searchimproved.init = function() {
	//$('.search-input').live('mouseover', elgg.searchimproved.searchFocus);
}

// Init the search input
elgg.searchimproved.initSearchInput = function() {
	// Init search
	if (!elgg.searchimproved.searchInput && $('.search-input').length > 0) {
		elgg.searchimproved.searchInput = $('.search-input').autocomplete({
			source: elgg.searchimproved.searchSource,
			autoFocus: true,
			delay: 2, // Delay before searching
			minLength: 2,
			html: "html",
			//appendTo: '.elgg-menu-item-search',
			position: { my: "right top", at: "right bottom", of: '.search-input', collision: "none", offset: "18px" },
			open: function (event, ui) {
				//
			},
			close : function (event, ui) {
				/** DEBUG, KEEP OPEN! **/
				// val = $(".search-input").val();
				// $(".search-input").autocomplete( "search", val );
				// return false;
				/** END DEBUG **/
			}, 
			focus: function( event, ui) {
				// Do nothing on item focus
				return false;
			},
			select: function( event, ui) {
				// Navigate to the item url
				window.location.href = ui.item.url;
				return false;
			}
		});

		// Get widget instance
		var si = elgg.searchimproved.searchInput.data('autocomplete');

		// Override _renderMenu to display categories and custom CSS
		si._renderMenu = function(ul, items) {
			// Change class on item
			ul.attr('class', 'searchimproved-autocomplete');

			var that = this,
			currentCategory = "";
			$.each(items, function(index, item) {
				if (item.category != currentCategory) {

					if (item.category == 'empty') {
						ul.append("<li class='searchimproved-autocomplete-empty-category'></li>");
					} else {
						ul.append("<li class='searchimproved-autocomplete-category'>" + item.category + "</li>");
					}
					currentCategory = item.category;
				}

				that._renderItem(ul, item);
			});
		};
	}
}

elgg.searchimproved.searchSource = function(req, resp) {
	// Sort function
	var prefetchSort = function(a, b) {
		var search = req.term.toLowerCase();
		var a_lower = a.name.toLowerCase();
		var b_lower = b.name.toLowerCase();

	 	var a_idx = a.name.toLowerCase().indexOf(search);
		var b_idx = b.name.toLowerCase().indexOf(search);

		// Compare index of search term
		if (a_idx > b_idx) {
			return 1;
		} else if (a_idx < b_idx) {
			return -1;
		} else {
			// If same index, compare names alphabetically
			if (a.name.toLowerCase() < b.name.toLowerCase()) {
				return -1;
			} else if (a.name.toLowerCase() > b.name.toLowerCase()) {
				return 1;
			} else {
				return 0;
			}
		}
	}

	// Search on user prefetch data
	var user_result = $.grep(elgg.searchimproved.prefetchData.users, function(el, idx) {
		if (el.name.toLowerCase().indexOf(req.term) >= 0) {
			return true;
		}
		return false;
	});

	// Sort elements
	user_result.sort(prefetchSort);

	// Splice the array down
	user_result.splice(5, user_result.length +1);

	// Search on group prefetch data
	var group_result = $.grep(elgg.searchimproved.prefetchData.groups, function(el, idx) {
		if (el.name.toLowerCase().indexOf(req.term) >= 0) {
			return true;
		}
		return false;
	});

	// Sort group elements
	group_result.sort(prefetchSort);

	// Splice the array down
	group_result.splice(5, group_result.length +1);

	// Create a search more link
	var $search_more = $(document.createElement('a'));
	$search_more.addClass('searchimproved-more-results');
	$search_more.html(elgg.echo('searchimproved:label:seemore',[req.term]));
	//$search_more.attr('href', elgg.get_site_url() + "search?q=" + req.term + "&search_type=all");
 	
 	// Merge group/user result sets
 	var results = user_result.concat(group_result);

 	// Empty category
 	var category = 'empty';

 	if (results.length === 0) {
 		category = elgg.echo('searchimproved:noresults');
 	}

	var source_items = [{
		'name': 'No Results',
		'label': $search_more,
		'category': category,
		'url': elgg.get_site_url() + "search?q=" + req.term + "&search_type=all",
		'value': null,
	}]

	// Add extra source items
	resp(results.concat(source_items));
}

elgg.searchimproved.searchFocus = function(event) {
	if (!elgg.searchimproved.prefetchData) {
		elgg.getJSON(elgg.searchimproved.prefetchEndpoint, {
			success: function(data) {
				elgg.searchimproved.prefetchData = data;
				elgg.searchimproved.initSearchInput();
				console.log(elgg.searchimproved.prefetchData);
			}
		});
	}
}

elgg.register_hook_handler('init', 'system', elgg.searchimproved.init);
elgg.register_hook_handler('init', 'system', elgg.searchimproved.searchFocus);