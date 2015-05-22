<?php
/**
 * Elgg Search Improved CSS
 *
 * @package SearchImproved
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 *
 */
?>


/*** SEARCH BOX ***/ 
.searchimproved-autocomplete {
	width: 400px !important;
	background: #ffffff;
	border: 2px solid #999;
	overflow: visible;
	box-shadow: 1px 1px 15px #333;
	-moz-box-shadow: 1px 1px 15px #333;
	-webkit-box-shadow: 1px 1px 15px #333;
}

/*** SEARCH RESULTS/ITEMS/CATEGORIES ***/
.searchimproved-autocomplete .ui-menu-item  > a {
	display: block;
	cursor: pointer;
	text-decoration: none !important;
}

.searchimproved-autocomplete .ui-menu-item  > a > .elgg-image-block {
	margin: 0;
	padding: 4px 4px 6px;
}

.searchimproved-autocomplete .searchimproved-autocomplete-category {
	padding: 4px;
	background: #DDD;
	border-top: 1px solid #CCC;
	border-bottom: 1px solid #CCC;
	font-size: 0.9em;
	text-transform: uppercase;
}

.searchimproved-autocomplete .searchimproved-autocomplete-category:first-child {
	border-top: none;
}

.searchimproved-more-results {
	background: #FFF;
	display: block;
	padding: 5px;
	text-align: center;
	border-top: 1px dotted #CCC;
}

/*** LOADING ***/
.elgg-search input[type="text"].ui-autocomplete-loading {
	background: #FFF url(<?php echo elgg_get_site_url(); ?>mod/searchimproved/graphics/search_loading.gif) no-repeat 98% center;
}

.ui-widget-content.elgg-ajax-loader {
	background: #FFF url(<?php echo elgg_get_site_url(); ?>_graphics/ajax_loader_bw.gif) no-repeat center center;
}

/*** ACTIVE/HOVER STATES ***/
.searchimproved-autocomplete #ui-active-menuitem {
	text-decoration: none;
	background: #EEE;
	display: block;
}

.searchimproved-autocomplete .ui-state-hover,
.searchimproved-autocomplete .ui-state-focus,
.searchimproved-autocomplete .ui-state-active {
	border: 0;	
	background: none;
}