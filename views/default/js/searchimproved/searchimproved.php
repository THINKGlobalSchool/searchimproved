<?php
/**
 * Elgg Search Improved JS library
 *
 * @package SearchImproved
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 *
 * See: http://jqueryui.com/autocomplete/
 */
?>
//<script>
elgg.provide('elgg.searchimproved');

elgg.searchimproved.contentSearchActive = false;
elgg.searchimproved.searchInput = false;
elgg.searchimproved.prefetchData = null;
elgg.searchimproved.searchLimit = 5;
elgg.searchimproved.searchEndpoint = elgg.get_site_url() + 'searchimproved';
elgg.searchimproved.prefetchEndpoint = elgg.get_site_url() + 'searchprefetch';

// Init
elgg.searchimproved.init = function() {
	elgg.searchimproved.searchFocus(null);
	elgg.searchimproved.initSearchInput();
}


// Init the search input
elgg.searchimproved.initSearchInput = function() {
	// Init search
	if (!elgg.searchimproved.searchInput && $('.search-input').length > 0) {
		elgg.searchimproved.searchInput = $('.search-input').autocomplete({
			source: elgg.searchimproved.searchSource,
			autoFocus: true,
			delay: 450, // Delay before searching
			minLength: 2,
			html: "html",
			position: { my: "right top", at: "right bottom", of: '.search-input', collision: "none", offset: "18px" },
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
		var si = elgg.searchimproved.searchInput.data('uiAutocomplete');

		// Override _renderMenu to display categories and custom CSS
		si._renderMenu = function(ul, items) {

			// Change class on item
			ul.attr('class', 'searchimproved-autocomplete');

			var that = this,
			currentCategory = "";
			$.each(items, function(index, item) {
				if (item.category != currentCategory) {

					if (item.category == 'empty') {
						ul.append($("<li class='searchimproved-autocomplete-empty-category'></li>").data("item.autocomplete", {}));
					} else {
						ul.append($("<li class='searchimproved-autocomplete-category'>" + item.category + "</li>").data("item.autocomplete", {}));
					}
					currentCategory = item.category;
				}

				that._renderItemData(ul, item);
			});

			// Remove additional items
			$('.searchimproved-more-results').closest('li.ui-menu-item').remove();
		
			var more_item = elgg.searchimproved.getEmptyItem($('.search-input').val());

			// Render additional item
			this._renderItemData(ul, more_item);

		};

		// Override _suggest to request additional item
		si._suggest = function( items ) {
			var ul = this.menu.element
				.empty()
				.zIndex( this.element.zIndex() + 1 );
			this._renderMenu( ul, items );

			var that = this;

			// Get our search term
			var term = $('.search-input').val();

			// Remove any 'search more' items
			$('.searchimproved-more-results').remove();

			// Add ajax loader
			$('.searchimproved-autocomplete').append("<li class='elgg-ajax-loader' style='height: 50px;'></li>");

			if (elgg.searchimproved.contentSearchActive == false) {
				// Set contentSearchActive to true to prevent dupes
				elgg.searchimproved.contentSearchActive = true;

				// Use search endpoint to retrieve objects/content
				elgg.getJSON(elgg.searchimproved.searchEndpoint, {
					data: {
						term: term,
						limit: elgg.searchimproved.searchLimit,
						match_on: 'objects'
					},
					success: function(data) {
						// Remove ajax loader
						$('.searchimproved-autocomplete > .elgg-ajax-loader').remove();

						// Add items and refresh menu
						that._renderMenu( ul, data);
						that.menu.refresh();
						that._resizeMenu();
						ul.position( $.extend({
							of: that.element
						}, that.options.position ));

						elgg.searchimproved.contentSearchActive = false;
					}
				});
			}

			this.menu.refresh();

			// size and position menu
			ul.show();
			this._resizeMenu();
			ul.position( $.extend({
				of: this.element
			}, this.options.position ));

			if ( this.options.autoFocus ) {
				this.menu.next( new $.Event("mouseover") );
			}
		}
	}
}

/**	
 * Search/autocomplete source
 */
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
		if (el.name.toLowerCase().indexOf(req.term.toLowerCase()) >= 0) {
			return true;
		}
		return false;
	});

	// Sort elements
	user_result.sort(prefetchSort);

	// Splice the array down
	user_result.splice(elgg.searchimproved.searchLimit, user_result.length +1);

	// Search on group prefetch data
	var group_result = $.grep(elgg.searchimproved.prefetchData.groups, function(el, idx) {
		if (el.name.toLowerCase().indexOf(req.term.toLowerCase()) >= 0) {
			return true;
		}
		return false;
	});

	// Sort group elements
	group_result.sort(prefetchSort);

	// Splice the array down
	group_result.splice(elgg.searchimproved.searchLimit, group_result.length +1);
 	
 	// Merge group/user result sets
 	var results = user_result.concat(group_result);

 	if (results.length === 0) {
 		results.push({'category':'empty'});
 	} 

	// Add extra source items
	resp(results);
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

/**
 * Build an empty 'search more' item
 *
 * @param string term
 * @return object
 */
elgg.searchimproved.getEmptyItem = function(term) {
	// Create a search more link
	var $search_more = $(document.createElement('a'));
	$search_more.addClass('searchimproved-more-results');
	$search_more.html(elgg.echo('searchimproved:label:seemore',[term]));

	// Build additional item
	var more_item = {
		'name': elgg.echo('searchimproved:noresults'),
		'label': $search_more,
		'category': 'empty',
		'url': elgg.get_site_url() + "search?q=" + term + "&search_type=all",
		'value': null,
	};

	return more_item;
}

elgg.register_hook_handler('init', 'system', elgg.searchimproved.init);
//elgg.register_hook_handler('loaded', 'topbar_ajax', elgg.searchimproved.searchFocus, 1000);