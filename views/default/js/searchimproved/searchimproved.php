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

elgg.searchimproved.searchLimit = 4;

// Init
elgg.searchimproved.init = function() {
	elgg.searchimproved.initSearchInput();
}

// Init the search input
elgg.searchimproved.initSearchInput = function() {
	// Init search
	if (!elgg.searchimproved.searchInput && $('.search-input').length > 0) {
		elgg.searchimproved.searchInput = $('.search-input').autocomplete({
			source: elgg.get_site_url() + 'searchimproved?limit=' + elgg.searchimproved.searchLimit,
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

elgg.register_hook_handler('init', 'system', elgg.searchimproved.init);