<?php
/*
Plugin Name: WP Movie Ratings
Version: 1.3
Plugin URI: http://paulgoscicki.com/projects/wp-movie-ratings/
Author: Paul Goscicki
Author URI: http://paulgoscicki.com/
Description: Wordpress movie rating plugin, which lets you easily rate movies
you've seen recently and display a short list of those movies on your blog
(ala kottke.org style). Internet Movie Database (imdb.com) is used to
automatically fetch movie titles. 1-click movie rating is possible using
Firefox bookmarklet (included) while browsing the imdb.com pages.
*/

/*
Copyright (c) 2006 by Paul Goscicki http://paulgoscicki.com/

Available under the GNU General Public License (GPL) version 2 or later.
http://www.gnu.org/licenses/gpl.html

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

# Anti-hack
if (!defined('ABSPATH')) die("No cheating!");

include_once(dirname(__FILE__) . "/wp_http_request.class.php");
include_once(dirname(__FILE__) . "/movie.class.php");

# Plugin installation function
function wp_movie_ratings_install() {
	global $table_prefix, $wpdb, $user_level;

	# usually: wp_movie_ratings
	$table_name = $table_prefix . "movie_ratings";

	# only special users can install plugins
	if ($user_level < 8) { return; }

	# INSTALLAION -> Create the movie ratings table (first install)
	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
			id int(11) NOT NULL auto_increment,
			title varchar(255) NOT NULL default '',
			imdb_url_short varchar(10) NOT NULL default '',
			rating tinyint(2) unsigned NOT NULL default '0',
			review text,
			replacement_url varchar(255) default '',
			watched_on datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			UNIQUE KEY (imdb_url_short)
		);";

		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);

	# UPGRADE -> Add column not present in versions 1.0 - 1.3
	} else {
		$found = false;
		$new_column = "replacement_url";
		$table_fields = $wpdb->get_results("DESCRIBE $table_name;");
		
		foreach($table_fields as $table_field) {
			if ($table_field->Field == $new_column) $found = true;
		}

		if (!$found) $wpdb->query("ALTER TABLE $table_name ADD COLUMN $new_column varchar(255) default '';");
	}

	# plugin options
	add_option('wp_movie_ratings_count', 6, 'Number of displayed movie ratings (default)', 'no');
	add_option('wp_movie_ratings_text_ratings', 'no', 'Display movie ratings as text or as images (stars)', 'no');
	add_option('wp_movie_ratings_include_review', 'yes', 'Include review when displaying movie ratings?', 'no');
	add_option('wp_movie_ratings_expand_review', 'no', 'Initially show expanded reviews when in page mode?', 'no');
	add_option('wp_movie_ratings_order_by', 'title', 'Default movies order when in page mode', 'no');
	add_option('wp_movie_ratings_order_direction', 'ASC', 'Default movies order direction when in page mode', 'no');
	add_option('wp_movie_ratings_char_limit', 44, 'Display that many characters when the movie title is too long to fit', 'no');
	add_option('wp_movie_ratings_sidebar_mode', 'no', 'Display rating below movie title as to not use too much space', 'no');
	add_option('wp_movie_ratings_five_stars_ratings', 'no', 'Display ratings using 5 stars instead of 10', 'no');
	add_option('wp_movie_ratings_highlight', 'yes', 'Highlight top rated movies?', 'no');
	add_option('wp_movie_ratings_dialog_title', 'Movies I\'ve watched recently:', 'Dialog title for movie ratings box', 'no');
	add_option('wp_movie_ratings_page_url', '', 'Movie ratings page url', 'no');
	add_option('wp_movie_ratings_ping_pingerati', 'yes', 'Ping pingerati.net with movie reviews', 'no');
	add_option('wp_movie_ratings_pagination_limit', 100, 'Display that many movies per page when using pagination in page mode', 'no');
}


# Get web server plugin path -> "relative" or "absolute"
function get_plugin_path($type) {
	$siteurl = get_option("siteurl");
	if ($siteurl[strlen($siteurl)-1] != "/") $siteurl .= "/";
	$path = $siteurl . "wp-content/plugins/" . dirname(plugin_basename(__FILE__)) . "/";
	if ($type == "absolute") return $path;
	else {
		$tmp_array = parse_url($path);
		return $tmp_array["path"];
	}
}


# Include CSS/JS in the HEAD of the html page
function wp_movie_ratings_head_inclusion() {
	$plugin_path = get_plugin_path("absolute");

	# CSS inclusion
	echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . $plugin_path;
	echo (is_plugin_page() ? "admin_page" : basename(__FILE__, ".php")) . ".css" . "\" />\n";

	# JS inclusion
	echo "<script type=\"text/javascript\" src=\"" . $plugin_path . "wp_movie_ratings.js\"></script>\n";
}


# Change [[wp_movie_ratings_page]] into movie ratings list
function parse_wp_movie_ratings_tags($content = "") {
	# get rid of the unnecessary p/pre/div/h1/h2/h3 tags, which make the movie ratings page non XHTML compliant
	$tmp = preg_replace("/<(p|pre|div|h1|h2|h3)[\s]*(class=\".*?\")*>(\[\[wp_movie_ratings_page\]\])[\s]*<\/(p|pre|div|h1|h2|h3)>/", "$3", $content);
	# parse the movie ratings tag
	$tmp = str_replace("[[wp_movie_ratings_page]]", wp_movie_ratings_get(null, array("page_mode" => "yes")), $tmp); // . wp_movie_ratings_get_statistics("brief")
	return $tmp;
}


# Pass-through function
function wp_movie_ratings_show($count = null, $options = array()) {
	echo wp_movie_ratings_get($count, $options);
}


# Get latest movie ratings (get HTML code)
# Params:
#	$count - number of movies to show; if equals -1 it will read the number from the options saved in the database
#   $options - optional parameters as hash array (if not specified, they will be read from the database)
#		'text_ratings' -> text ratings (like 5/10) or images of stars ('yes'/'no')
#       'include_review' -> include review with each movie rating ('yes'/'no')
#		'expand_review' -> initially display expanded reviews when in page mode
#	    'sidebar_mode' -> compact view for sidebar mode ('yes'/'no')
#	    'five_stars_ratings' -> display movie ratings using 5 stars instead of 10 ('yes'/'no')
#       'page_mode' -> display all movie ratings on a separate page (with additional options, etc.)
#       'highlight' -> will highlight the stars of top rated movies
function wp_movie_ratings_get($count = null, $options = array()) {
	global $wpdb, $table_prefix;

	# output
	$o = "";

	# parse function parameters
	if ($count == null) $count = get_option("wp_movie_ratings_count");
	$text_ratings = (isset($options["text_ratings"]) ? $options["text_ratings"] : get_option("wp_movie_ratings_text_ratings"));
	$include_review = (isset($options["include_review"]) ? $options["include_review"] : get_option("wp_movie_ratings_include_review"));
	$expand_review = (isset($options["expand_review"]) ? $options["expand_review"] : get_option("wp_movie_ratings_expand_review"));
	$sidebar_mode = (isset($options["sidebar_mode"]) ? $options["sidebar_mode"] : get_option("wp_movie_ratings_sidebar_mode"));
	$five_stars_ratings = (isset($options["five_stars_ratings"]) ? $options["five_stars_ratings"] : get_option("wp_movie_ratings_five_stars_ratings"));
	$highlight = (isset($options["highlight"]) ? $options["highlight"] : get_option("wp_movie_ratings_highlight"));
	$page_mode = (isset($options["page_mode"]) ? $options["page_mode"] : "no");
	$page_url = get_option("wp_movie_ratings_page_url");

	# parse query parameters for page mode (sorting options) (title/rating/watched_on && ASC/DESC)
	if ($page_mode == "yes") {
		if (isset($_GET["sort"])) $order_by = $_GET["sort"]; else $order_by = get_option("wp_movie_ratings_order_by");
		if (isset($_GET["descending"])) $order_direction = "DESC";
		else if (isset($_GET["ascending"])) $order_direction = "ASC";
		else $order_direction = get_option("wp_movie_ratings_order_direction");

		# pagination logic
		$current_page = (isset($_GET["movies_page"]) ? $_GET["movies_page"] : 1);
		$limit = get_option("wp_movie_ratings_pagination_limit");
		$start = ($current_page - 1) * $limit;
	}

	$m = new Movie();
	$m->set_database($wpdb, $table_prefix);

	if ($page_mode == "yes") {
		$movies = $m->get_all_movies($order_by, $order_direction, $start, $limit);
	} else $movies = $m->get_latest_movies(intval($count));

	# love advert
	$o .= "\n<!-- Recently watched movies list by WP Movie Ratings wordpress plugin: http://paulgoscicki.com/projects/wp-movie-ratings/ -->\n";

	# html container
	$classes = ($page_mode == "yes" ? "page_mode " : "");
	$classes .= ($sidebar_mode == "yes" ? "sidebar_mode " : "");
	if (strlen($classes) > 0) $classes = substr($classes, 0, strlen($classes)-1); # drop last space

	$o .= "<div id=\"wp_movie_ratings\"" . (strlen($classes) > 0 ? " class=\"$classes\"" : "") . ">\n";

	# sorting options for page mode
	if ($page_mode == "yes") {

		$link = $_SERVER["REQUEST_URI"];

		# drop everything after '#' (including '#')
		if (strpos($link, "#")) $link = substr($link, 0, strpos($link, "#"));

		# clear link from my stuff
		$link = preg_replace("/(&|\?)*sort=(title|rating|watched_on)&(ascending|descending)/", "", $link);
		$link = preg_replace("/(&|\?)*movies_page=[0-9]*/", "", $link);

		# put ? or &amp; at the end of the link depending on the situation
		if (strpos($link, "?")) $link .= "&amp;";
		else $link .= "?";

		# create appropriate sorting links
		$link_t = $link_r = $link_w = $link;

		$link_t .= "sort=title&amp;" . ((($order_by == "title") && ($order_direction == "ASC")) ? "descending" : "ascending");
		$link_r .= "sort=rating&amp;" . ((($order_by == "rating") && ($order_direction == "DESC")) ? "ascending" : "descending");
		$link_w .= "sort=watched_on&amp;" . ((($order_by == "watched_on") && ($order_direction == "DESC")) ? "ascending" : "descending");

		$o .= "<p id=\"sort_options\">Sort list by: \n";
		$o .= "<a href=\"$link_t\">title" . ($order_by == "title" ? " <span class=\"bullet\">&" . ($order_direction == "ASC" ? "u" : "d") . "arr;</span>" : "") . "</a> | \n";
		$o .= "<a href=\"$link_r\">rating" . ($order_by == "rating" ? " <span class=\"bullet\">&" . ($order_direction == "ASC" ? "u" : "d") . "arr;</span>" : "") . "</a> | \n";
		$o .= "<a href=\"$link_w\">view date" . ($order_by == "watched_on" ? " <span class=\"bullet\">&" . ($order_direction == "ASC" ? "u" : "d") . "arr;</span>" : "") . "</a>\n";
		$o .= "</p>\n";
	}

	# dialog title
	$dialog_title = stripslashes(get_option("wp_movie_ratings_dialog_title"));
	if (($page_mode != "yes") && (strlen($dialog_title) > 0)) $o .= "<h2 id=\"reviews_title\">$dialog_title</h2>\n";

	$o .= "<ul id=\"reviews\"" . ($text_ratings == "yes" ? " class=\"text_ratings\"" : "") . ">\n";

	if (count($movies) == 0) $o .= "<li>No movies rated yet! Go and rate some. Now.</li>\n";
	else {
		$i = 0; # row alternator
		$separator = ""; # used when sorting by view date when in page mode
		$separator_last = "";
		
		foreach($movies as $movie) {

			# Separator logic
			if (($page_mode == "yes") && ($order_by == "watched_on")) {
				$separator = substr($movie->_watched_on, 0, 7);
				if (($i == 0) || ($separator != $separator_last)) {
					$o .= "<li class=\"separator\">";
					$o .= "<h3" . ($i == 0 ? " class=\"first\"" : "") . ">";
					$o .= date("F, Y", mktime(1, 1, 0, substr($separator, 5, 2), 1, substr($separator, 0, 4)));
					$o .= "</h3></li>\n";
				}
				$separator_last = $separator;
			}

			# Movie display
			$o .= "<li" . ((++$i % 2) == 0 ? " class=\"odd\"" : "") . ">\n";
			$o .= $movie->show(get_plugin_path("absolute"), array("include_review" => $include_review, "text_ratings" => $text_ratings, "sidebar_mode" => $sidebar_mode, "five_stars_ratings" => $five_stars_ratings, "highlight" => $highlight, "page_mode" => $page_mode));
			$o .= "</li>\n";
		}
	}

	$o .= "</ul>\n";


	# Pagination
	if ($page_mode == "yes") {
		$total_movies = $m->get_watched_movies_count("total");
		$total_pages = ceil($total_movies / $limit);

		# display only if $limit is less than $total, so the pagination makes sense
		if ($limit < $total_movies) {

			$link = $_SERVER["REQUEST_URI"];

			# drop everything after '#' (including '#')
			if (strpos($link, "#")) $link = substr($link, 0, strpos($link, "#"));

			# cleanup of my sh*t
			$link = preg_replace("/(&|\?)*movies_page=[0-9]*/", "", $link);
			
			# put ? or &amp; at the end of the link depending on the situation
			if (strpos($link, "?")) $link .= "&amp;";
			else $link .= "?";


			$o .= "<div id=\"pagination\"><p>";

			# prev button
			if ($current_page > 1) $o .= "<a class=\"next_prev\" href=\"" . $link . "movies_page=" . ($current_page - 1) . "\">"; else $o .= "<em>";
			$o .= "&larr; previous";
			if ($current_page > 1) $o .= "</a> "; else $o .= "</em> ";
			$o .= "\n";

			# pages
			for ($i = 1; $i <= $total_pages; $i++) {
				if ($current_page != $i) $o .= "<a href=\"" . $link . "movies_page=" . $i . "\">"; else $o .= "<em id=\"current\">";
				$o .= $i;
				if ($current_page != $i) $o .= "</a> "; else $o .= "</em> ";
				$o .= "\n";
			}
			
			# next button
			if ($current_page < $total_pages) $o .= "<a class=\"next_prev\" href=\"" . $link . "movies_page=" . ($current_page + 1) . "\">"; else $o .= "<em>";
			$o .= "next &rarr;";
			if ($current_page < $total_pages) $o .= "</a>"; else $o .= "</em>";
			$o .= "\n";


			$o .= "</p></div>\n";
		}
	}




	# Please do not remove the love ad. Thank you :)
	if ($page_mode == "yes") $o .= "<p id=\"link_love\">List generated by <a href=\"http://paulgoscicki.com/projects/wp-movie-ratings/\">WP Movie Ratings</a>.</p>\n";
	else if (strlen($page_url) > 0) $o .= "<p id=\"page_url\"><a href=\"$page_url\">Movie ratings archive &raquo;</a></p>\n";

	$o .= "</div>\n";

	return $o;
}


# Show statistics for watched movies (Pass-through function)
function wp_movie_ratings_show_statistics($type = "brief") {
	echo wp_movie_ratings_get_statistics($type);
}


# Get statistics
function wp_movie_ratings_get_statistics($type = "brief") {
	global $wpdb, $table_prefix;

	$o = "";
	$m = new Movie();
	$m->set_database($wpdb, $table_prefix);

	$total = $m->get_watched_movies_count("total");
	$total_avg = $m->get_watched_movies_count("total-average");

	# division by zero bugfix
	# TODO: change this code to calculate days from the database, not by divisions
	$days = ($total_avg == 0 ? 1 : round($total/$total_avg));

	$last_30_days_avg = $m->get_watched_movies_count("last-30-days") / 30;
	$last_7_days_avg = $m->get_watched_movies_count("last-7-days") / 7;

	if ($type == "detailed") echo "<h2>Statistics</h2>\n";

	$o .= "<p>Total number of rated movies: <strong>$total</strong> (";
	if ($type == "detailed") $o .= "average of <strong>$total_avg</strong> movies per day; ";
	$o .= "<strong>$days</strong> days of movie ratings).</p>\n";

	if ($type == "detailed") $o .= "<p>Average of <strong>" . sprintf("%.4f", $last_30_days_avg) . "</strong> movies per day for the past <strong>30</strong> days (<strong>" . sprintf("%.4f", $last_7_days_avg) . "</strong> for the past <strong>7</strong> days).</p>\n";

	$o .= "<p>Average movie rating: <strong>" . $m->get_average_movie_rating() . "</strong>.</p>\n";

	if ($type == "detailed") {
		$o .= "<p>This month: <strong>" . $m->get_watched_movies_count("month") . "</strong> (last month: <strong>" . $m->get_watched_movies_count("last-month") . "</strong>).</p>\n";
		$o .= "<p>This year: <strong>" . $m->get_watched_movies_count("year") . "</strong> (last year: <strong>" . $m->get_watched_movies_count("last-year") . "</strong>).</p>\n";
		$o .= "<p>First movie rated on: <strong>" . $m->get_watched_movies_count("first-rated") . "</strong>.</p>\n";
		$o .= "<p>Last movie rated on: <strong>" . $m->get_watched_movies_count("last-rated") . "</strong>.</p>\n";
	}

	return $o;
}


# Add 'Movies' page to Wordpress' Manage menu
function wp_movie_ratings_add_management_page() {
    if (function_exists('add_management_page')) {
		  add_management_page('Movies', 'Movies', 8, basename(__FILE__), 'wp_movie_ratings_management_page');
    }
}


# Add 'Movies' page to Wordpress' Options menu
function wp_movie_ratings_add_options_page() {
    if (function_exists('add_options_page')) {
		  add_options_page('Movies', 'Movies', 8, basename(__FILE__), 'wp_movie_ratings_options_page');
    }
}


# Manage Movies administration page
function wp_movie_ratings_management_page() {
	global $table_prefix, $wpdb;

	# DATABASE -> ADD NEW MOVIE
	# Get title of the movie and save its rating in the database
	if (isset($_POST["action"]) && (substr(strtolower($_POST["action"]), 0, 3) == "add")) {
		$review = (isset($_POST["review"]) ? $_POST["review"] : "");
		$replacement_url = (isset($_POST["replacement_url"]) ? $_POST["replacement_url"] : "");
		$watched_on = (isset($_POST["watched_on"]) ? $_POST["watched_on"] : null);
		$movie = new Movie($_POST["url"], $_POST["rating"], $review, null, $replacement_url, $_POST["watched_on"]);
		$msg = $movie->parse_parameters();
		if ($msg == "") {
			$msg = $movie->get_title();
			if ($msg == "")	{
				$movie->set_database($wpdb, $table_prefix);
				$msg = $movie->save();
			}
		}
		echo rawurldecode($msg);
		$m = new Movie(); # new 'empty' movie object
	}

	# DATABASE -> DELETE MOVIE
	if (isset($_POST["action"]) && (substr(strtolower($_POST["action"]), 0, 6) == "delete")) {
		$m = new Movie();
		$m->set_database($wpdb, $table_prefix);
		$movie = $m->get_movie_by_id($_POST["id"]);
		if ($movie != null)	echo $movie->delete();
		else echo '<div id="message" class="error fade"><p><strong>Error: no movie to delete.</strong></p></div>';

		$m = new Movie(); # new 'empty' movie object
	}

	# DATABASE -> UPDATE MOVIE DATA
	if (isset($_POST["action"]) && (substr(strtolower($_POST["action"]), 0, 6) == "update")) {
		$movie = new Movie();
		$movie->set_database($wpdb, $table_prefix);
		$m = $movie->get_movie_by_id($_POST["id"]);
		if (isset($_POST["url"]) && isset($_POST["title"]) && isset($_POST["rating"]) && isset($_POST["review"]) && isset($_POST["replacement_url"]) && isset($_POST["watched_on"])) echo $m->update_from_post();
	}

	# EDIT MOVIE
	if ((isset($_POST["action"]) && ($_POST["action"] == "edit")) || (isset($_GET["action"]) && ($_GET["action"] == "edit"))) {
		$movie = new Movie();
		$movie->set_database($wpdb, $table_prefix);
		$m = $movie->get_movie_by_id( (isset($_POST["id"]) ? $_POST["id"] : (isset($_GET["id"]) ? $_GET["id"] : 0) ));
		$dialog_title = "Edit";
		$action = "Update";
	} else { # ADD MOVIE
		$dialog_title = "Add new";
		$action = "Add new";
		$m = new Movie(); # new 'empty' movie object
	}

	$dialog_title .= " movie rating.";
?>

<div class="wrap">
<h2><?= $dialog_title ?></h2>

<?php

$m->show_add_edit_form($action);
wp_movie_ratings_show(20, array("text_ratings" => "yes", "include_review" => "no", "sidebar_mode" => "no"));
wp_movie_ratings_show_statistics("detailed");

?>

<h2>Firefox bookmarklet</h2>

<p>Add the following link to your Bookmarklets folder so you can rate your movies without visiting Wordpress administration page. You must be <strong>logged in</strong> to your Wordpress blog for it to work, though.</p>
<p><a href="javascript:(function(){open('<?= get_plugin_path("absolute") ?>add_movie.html?url='+escape(location.href),'<?= basename(__FILE__, ".php") ?>','toolbar=no,width=432,height=335')})()" title="Add movie rating bookmarklet">Add movie rating bookmarklet</a></p>

</div>

<?php
}


# Get all plugin's options
function get_plugin_options() {
	$options = array();
	$options["count"] = get_option("wp_movie_ratings_count");
	$options["text_ratings"] = get_option("wp_movie_ratings_text_ratings");
	$options["include_review"] = get_option("wp_movie_ratings_include_review");
	$options["expand_review"] = get_option("wp_movie_ratings_expand_review");
	$options["order_by"] = get_option("wp_movie_ratings_order_by");
	$options["order_direction"] = get_option("wp_movie_ratings_order_direction");
	$options["char_limit"] = get_option("wp_movie_ratings_char_limit");
	$options["sidebar_mode"] = get_option("wp_movie_ratings_sidebar_mode");
	$options["five_stars_ratings"] = get_option("wp_movie_ratings_five_stars_ratings");
	$options["highlight"] = get_option("wp_movie_ratings_highlight");
	$options["dialog_title"] = get_option("wp_movie_ratings_dialog_title");
	$options["page_url"] = get_option("wp_movie_ratings_page_url");
	$options["ping_pingerati"] = get_option("wp_movie_ratings_ping_pingerati");
	$options["pagination_limit"] = get_option("wp_movie_ratings_pagination_limit");
	return $options;
}


# WP Movie Ratings options page
function wp_movie_ratings_options_page() {
	global $table_prefix, $wpdb;

	# Save options in the database
	if (isset($_POST["wp_movie_ratings_count"]) && isset($_POST["wp_movie_ratings_text_ratings"])) {
		update_option("wp_movie_ratings_count", $_POST["wp_movie_ratings_count"]);
		update_option("wp_movie_ratings_text_ratings", $_POST["wp_movie_ratings_text_ratings"]);
		update_option("wp_movie_ratings_include_review", $_POST["wp_movie_ratings_include_review"]);
		update_option("wp_movie_ratings_expand_review", $_POST["wp_movie_ratings_expand_review"]);
		update_option("wp_movie_ratings_order_by", $_POST["wp_movie_ratings_order_by"]);
		update_option("wp_movie_ratings_order_direction", $_POST["wp_movie_ratings_order_direction"]);
		update_option("wp_movie_ratings_char_limit", $_POST["wp_movie_ratings_char_limit"]);
		update_option("wp_movie_ratings_sidebar_mode", $_POST["wp_movie_ratings_sidebar_mode"]);
		update_option("wp_movie_ratings_five_stars_ratings", $_POST["wp_movie_ratings_five_stars_ratings"]);
		update_option("wp_movie_ratings_highlight", $_POST["wp_movie_ratings_highlight"]);
		update_option("wp_movie_ratings_dialog_title", stripslashes($_POST["wp_movie_ratings_dialog_title"]));
		update_option("wp_movie_ratings_page_url", stripslashes($_POST["wp_movie_ratings_page_url"]));
		update_option("wp_movie_ratings_ping_pingerati", $_POST["wp_movie_ratings_ping_pingerati"]);
		update_option("wp_movie_ratings_pagination_limit", $_POST["wp_movie_ratings_pagination_limit"]);
		echo "<div id=\"message\" class=\"updated fade\"><p>Options updated</p></div>\n";
	}

	$plugin_options = get_plugin_options();
?>

<div class="wrap">
<h2>WP Movie Ratings options</h2>

<form method="post" action="">

<fieldset class="options">
<legend>General options</legend>
<table class="optiontable">

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_dialog_title">Title for movie ratings box:</label></th>
<td><input type="text" name="wp_movie_ratings_dialog_title" id="wp_movie_ratings_dialog_title" class="text" size="50" value="<?= stripslashes($plugin_options["dialog_title"]) ?>"/><br />
Leave empty if you don't want any title at all.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_char_limit">Cut movie title at:</label></th>
<td><input type="text" name="wp_movie_ratings_char_limit" id="wp_movie_ratings_char_limit" class="text" size="2" value="<?= $plugin_options["char_limit"] ?>"/> character.<br />
Display that many characters when the movie title is too long to fit.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_page_url">Movie ratings page url:</label></th>
<td><input type="text" name="wp_movie_ratings_page_url" id="wp_movie_ratings_page_url" class="text" size="50" value="<?= stripslashes($plugin_options["page_url"]) ?>"/><br />
If you enter the link (absolute) to the page listing all movie ratings it will create a link from movie ratings box to full archive.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_ping_pingerati_yes">Ping pingerati?</label></th>
<td>
<input type="radio" value="yes" id="wp_movie_ratings_ping_pingerati_yes" name="wp_movie_ratings_ping_pingerati"<?= ($plugin_options["ping_pingerati"] == "yes" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_ping_pingerati_yes">yes</label>
<input type="radio" value="no" id="wp_movie_ratings_ping_pingerati_no" name="wp_movie_ratings_ping_pingerati"<?= ($plugin_options["ping_pingerati"] == "no" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_ping_pingerati_no">no</label><br />
Will ping <a href="http://pingerati.net/">pingerati.net</a> for every new movie review you make.
</td>
</tr>

</table>
</fieldset>


<fieldset class="options">
<legend>Display options</legend>
<table class="optiontable">

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_count">Number of displayed movie ratings:</label></th>
<td><input type="text" name="wp_movie_ratings_count" id="wp_movie_ratings_count" class="text" size="2" value="<?= $plugin_options["count"] ?>"/><br />
Display that many latest movie ratings.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_text_ratings_yes">Text movie ratings?</label></th>
<td>
<input type="radio" value="yes" id="wp_movie_ratings_text_ratings_yes" name="wp_movie_ratings_text_ratings"<?= ($plugin_options["text_ratings"] == "yes" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_text_ratings_yes">yes</label>
<input type="radio" value="no" id="wp_movie_ratings_text_ratings_no" name="wp_movie_ratings_text_ratings"<?= ($plugin_options["text_ratings"] == "no" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_text_ratings_no">no</label><br />
Display text ratings (ie: <strong>5/10</strong>) instead of images.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_include_review_yes">Display reviews?</label></th>
<td>
<input type="radio" value="yes" id="wp_movie_ratings_include_review_yes" name="wp_movie_ratings_include_review"<?= ($plugin_options["include_review"] == "yes" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_include_review_yes">yes</label>
<input type="radio" value="no" id="wp_movie_ratings_include_review_no" name="wp_movie_ratings_include_review"<?= ($plugin_options["include_review"] == "no" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_include_review_no">no</label>
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_sidebar_mode_yes">Sidebar mode:</label></th>
<td>
<input type="radio" value="yes" id="wp_movie_ratings_sidebar_mode_yes" name="wp_movie_ratings_sidebar_mode"<?= ($plugin_options["sidebar_mode"] == "yes" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_sidebar_mode_yes">yes</label>
<input type="radio" value="no" id="wp_movie_ratings_sidebar_mode_no" name="wp_movie_ratings_sidebar_mode"<?= ($plugin_options["sidebar_mode"] == "no" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_sidebar_mode_no">no</label><br />
Movie rating will be displayed in a new line.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_five_stars_ratings_yes">5 stars ratings?</label></th>
<td>
<input type="radio" value="yes" id="wp_movie_ratings_five_stars_ratings_yes" name="wp_movie_ratings_five_stars_ratings"<?= ($plugin_options["five_stars_ratings"] == "yes" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_five_stars_ratings_yes">yes</label>
<input type="radio" value="no" id="wp_movie_ratings_five_stars_ratings_no" name="wp_movie_ratings_five_stars_ratings"<?= ($plugin_options["five_stars_ratings"] == "no" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_five_stars_ratings_no">no</label><br />
Display ratings using 5 stars instead of 10.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_highlight_yes">Highlight top rated movies?</label></th>
<td>
<input type="radio" value="yes" id="wp_movie_ratings_highlight_yes" name="wp_movie_ratings_highlight"<?= ($plugin_options["highlight"] == "yes" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_highlight_yes">yes</label>
<input type="radio" value="no" id="wp_movie_ratings_highlight_no" name="wp_movie_ratings_highlight"<?= ($plugin_options["highlight"] == "no" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_highlight_no">no</label><br />
Will highlight movies rated 9 and 10 (4,5 and 5 for five stars mode).
</td>
</tr>

</table>
</fieldset>


<fieldset class="options">
<legend>Page mode options</legend>
<table class="optiontable">

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_expand_review_yes">Expand reviews in page mode?</label></th>
<td>
<input type="radio" value="yes" id="wp_movie_ratings_expand_review_yes" name="wp_movie_ratings_expand_review"<?= ($plugin_options["expand_review"] == "yes" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_expand_review_yes">yes</label>
<input type="radio" value="no" id="wp_movie_ratings_expand_review_no" name="wp_movie_ratings_expand_review"<?= ($plugin_options["expand_review"] == "no" ? " checked=\"checked\"" : "") ?> />
<label for="wp_movie_ratings_expand_review_no">no</label><br />
Initially show expanded reviews when in page mode.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_order_by">Sort movies by:</label></th>
<td>
<select name="wp_movie_ratings_order_by" id="wp_movie_ratings_order_by">
<option value="title"<?= ($plugin_options["order_by"] == "title" ? "selected=\"selected\"" : ""); ?>>title</option>
<option value="rating"<?= ($plugin_options["order_by"] == "rating" ? "selected=\"selected\"" : ""); ?>>rating</option>
<option value="watched_on"<?= ($plugin_options["order_by"] == "watched_on" ? "selected=\"selected\"" : ""); ?>>view date</option>
</select>
<select name="wp_movie_ratings_order_direction" id="wp_movie_ratings_order_direction">
<option value="ASC"<?= ($plugin_options["order_direction"] == "ASC" ? "selected=\"selected\"" : ""); ?>>ascending</option>
<option value="DESC"<?= ($plugin_options["order_direction"] == "DESC" ? "selected=\"selected\"" : ""); ?>>descending</option>
</select>
when in page mode.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="wp_movie_ratings_pagination_limit">Max movies per page:</label></th>
<td><input type="text" name="wp_movie_ratings_pagination_limit" id="wp_movie_ratings_pagination_limit" class="text" size="2" value="<?= $plugin_options["pagination_limit"] ?>"/><br />
Display that many movies per page when in page mode (otherwise paginate the page).
</td>
</tr>

</table>
</fieldset>

<p class="submit"><input type="submit" name="submit" value="Update Options &raquo;" /></p>

</form>

</div>

<?php
}

# Hook for plugin installation
add_action('activate_' . dirname(plugin_basename(__FILE__)) . '/' . basename(plugin_basename(__FILE__)), 'wp_movie_ratings_install');

# Add actions for admin panel
add_action('admin_menu', 'wp_movie_ratings_add_management_page');
add_action('admin_menu', 'wp_movie_ratings_add_options_page');

# CSS/JS inclusion in HEAD
add_action('wp_head', 'wp_movie_ratings_head_inclusion');
add_action('admin_head', 'wp_movie_ratings_head_inclusion');

# Filter [[wp_movie_ratings_page]] tag in page mode
add_filter("the_content", "parse_wp_movie_ratings_tags");

?>