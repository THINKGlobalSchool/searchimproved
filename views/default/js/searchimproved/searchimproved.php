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
	$('.search-input').live('focus', elgg.searchimproved.searchFocus);
}

// Init the search input
elgg.searchimproved.initSearchInput = function() {
	// Init search
	if (!elgg.searchimproved.searchInput && $('.search-input').length > 0) {
		elgg.searchimproved.searchInput = $('.search-input').autocomplete({
			//source: elgg.searchimproved.searchEndpoint + '?limit=' + elgg.searchimproved.searchLimit,
			source: function(req, resp) {
				//console.log(req);
				var result = $.grep(elgg.searchimproved.prefetchData, function(el, idx) {
					if (el.name.toLowerCase().indexOf(req.term) >= 0) {
						return true;
					}
					if (el.username && el.username.toLowerCase().indexOf(req.term) >= 0) {
						return true;
					} 

					return false;
				});

				resp(result);
			},
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

elgg.searchimproved.searchFocus = function(event) {
	if (!elgg.searchimproved.prefetchData) {
		elgg.getJSON(elgg.searchimproved.prefetchEndpoint, {
			success: function(data) {
				elgg.searchimproved.prefetchData = data;
				console.log(elgg.searchimproved.prefetchData);
				elgg.searchimproved.initSearchInput();
			}
		});
	}
}

elgg.register_hook_handler('init', 'system', elgg.searchimproved.init);