<?php
/**
 * Elgg Search Improved CSS
 *
 * @package SearchImproved
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2013
 * @link http://www.thinkglobalschool.com/
 *
 * See:  http://cssarrowplease.com/  for awesome arrow/notches
 */
?>


/*** SEARCH BOX/ARROW-NOTCH ***/ 
.searchimproved-autocomplete {
	width: 400px !important;
	background: #ffffff;
	border: 2px solid #999;
	overflow: visible;
	box-shadow: 1px 1px 15px #333;
	-moz-box-shadow: 1px 1px 15px #333;
	-webkit-box-shadow: 1px 1px 15px #333;
}

.searchimproved-autocomplete {
/*	display: block !important;*/
}

.searchimproved-autocomplete:after, .searchimproved-autocomplete:before {
	bottom: 100%;
	border: solid transparent;
	content: " ";
	height: 0;
	width: 0;
	position: absolute;
	pointer-events: none;
}

.searchimproved-autocomplete:after {
	border-color: rgba(255, 255, 255, 0);
	border-bottom-color: #DDD;
	border-width: 15px;
	left: 50%;
	margin-left: 51px;
}
.searchimproved-autocomplete:before {
	border-color: rgba(153, 153, 153, 0);
	border-bottom-color: #999;
	border-width: 18px;
	left: 50%;
	margin-left: 48px;
}

/*** SEARCH RESULTS/ITEMS/CATEGORIES ***/
.searchimproved-autocomplete .ui-menu-item  > a {
	display: block;
	cursor: pointer;
	text-decoration: none !important;
}

.searchimproved-autocomplete .ui-menu-item  > a > .elgg-image-block {
	margin: 0;
	padding: 4px 0 6px;
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

/*** ACTIVE/HOVER STATES ***/
.searchimproved-autocomplete #ui-active-menuitem {
	text-decoration: none;
	background: #EEE;
	display: block;
}

.searchimproved-autocomplete .ui-state-hover,
.searchimproved-autocomplete .ui-state-active {
	border: 0;	
	background: none;
}