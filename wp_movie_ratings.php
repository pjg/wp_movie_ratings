<?php
/*
Plugin Name: WP Movie Ratings
Version: 1.0.1
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

include_once(dirname(__FILE__) . "/httprequest.class.php");
include_once(dirname(__FILE__) . "/movie.class.php");

# Plugin installation function
function wp_movie_ratings_install() {
	global $table_prefix, $wpdb, $user_level;

	# usually: wp_movie_ratings
	$table_name = $table_prefix . "movie_ratings";

	# only special users can install plugins
	if ($user_level < 8) { return; }

	# first installation
	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {

		$sql = "CREATE TABLE ".$table_name." (
			id int(11) unsigned NOT NULL auto_increment,
			title varchar(255) NOT NULL default '',
			imdb_url_short varchar(10) NOT NULL default '',
			rating tinyint(2) unsigned NOT NULL default '0',
			review text,
			watched_on datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			UNIQUE KEY (imdb_url_short)
		);";

		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

		dbDelta($sql);
	}
}





# Show latest movie ratings
# Params:
#	$count - number of movies to show; if equals -1 it will read the number from the options saved in the database
#   $options - optional parameters as hash array
#		'text_ratings' -> text ratings (like 5/10) or images of stars (true/false); if not specified it will read from the options saved in the database

# TODO: change 6 into -1 (to read from db)

function wp_movie_ratings_show($count = 6, $options = array()) {
	global $wpdb, $table_prefix;

	# parse options
	# TODO: read it from database
	$text_ratings = (isset($options["text_ratings"]) ? $options["text_ratings"] : false);
	$include_review = (isset($options["include_review"]) ? $options["include_review"] : false);

	# plugin path
	$siteurl = get_option("siteurl");
	if ($siteurl[strlen($siteurl)-1] != "/") $siteurl .= "/";
	$tmp_array = parse_url($siteurl . "wp-content/plugins/" . dirname(plugin_basename(__FILE__)) . "/");
	$plugin_path = $tmp_array["path"];

	$m = new Movie();
	$m->set_database($wpdb, $table_prefix);

	# TODO: remove this senselessness
	if (is_plugin_page()) {
		$movies = $m->get_latest_movies(intval($count));
	} else {
		$movies = $m->get_latest_movies(intval($count));
	}

	# love advert
	echo "<!-- The list of recently watched movies below is a result of WP Movie Ratings wordpress plugin by Paul Goscicki. -->\n";
	echo "<!-- Find it at: http://paulgoscicki.com/projects/wp-movie-ratings/ -->\n";

	# css file
	echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . $plugin_path;
	echo (is_plugin_page() ? "admin_page" : basename(__FILE__, ".php")) . ".css" . "\" />\n";

	echo "<div id=\"wp_movie_ratings\">\n";
	echo "<h2>Movies I've watched recently:</h2>\n";
	echo "<ul" . ($text_ratings ? " class=\"text_ratings\"" : "") .  ">\n";

	$i = 0; # row alternator
	foreach($movies as $movie) {
		echo "<li" . ((++$i % 2) == 0 ? " class=\"odd\"" : "") . ">\n";
		echo "<div class=\"hreview\">\n";
		echo "<span class=\"version\">0.3</span>\n";
		$movie->show($plugin_path, array("include_review" => $include_review, "text_ratings" => $text_ratings));
		echo "</div>\n";
		echo "</li>\n";
	}

	if (count($movies) == 0) echo "<li>No movies rated yet! Go and rate some. Now.</li>\n";

	echo "</ul>\n";
	echo "</div>\n";
}

# Add 'Movies' page to Wordpress' Manage menu
function wp_movie_ratings_add_management_page() {
    if (function_exists('add_management_page')) {
		  add_management_page('Movies', 'Movies', 8, basename(__FILE__), 'wp_movie_ratings_management_page');
    }
}

# Manage Movies administration page
function wp_movie_ratings_management_page() {
	global $table_prefix, $wpdb;

	# Get title of the movie and save its rating in the database
	if (isset($_POST["url"]) && isset($_POST["rating"])) {
		$review = (isset($_POST["review"]) ? $_POST["review"] : "");
		$movie = new Movie($_POST["url"], $_POST["rating"], $review);
		$msg = $movie->parse_parameters();
		if ($msg == "") {
			$msg = $movie->get_title();
			if ($msg == "")	{
				$movie->set_database($wpdb, $table_prefix);
				$msg = $movie->save();
			}
		}
		echo rawurldecode($msg);
	}

?>

<div class="wrap">

<form method="post" action="">
<h2>Add new movie rating</h2>
<p>
<label for="url">iMDB link:</label>
<input type="text" name="url" id="url" class="text" size="40" />
</p>

<p>
<label for="rating">Movie rating:</label>
<select name="rating" id="rating">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4">4</option>
<option value="5">5</option>
<option value="6">6</option>
<option value="7" selected="selected">7</option>
<option value="8">8</option>
<option value="9">9</option>
<option value="10">10</option>
</select>
</p>

<p><label for="review">Short review:</label>
<textarea name="review" id="review" rows="3" cols="45">
</textarea>
</p>

<div class="submit" style="text-align: left">
  <input type="submit" name="info_update" value="Add new movie rating &gt;&gt;" />
</div>

</form>

<?php

wp_movie_ratings_show(20, array("text_ratings" => true, "include_review" => false));
$m = new Movie();
$m->set_database($wpdb, $table_prefix);

?>

<h2>Statistics</h2>

<?php

$total = $m->get_watched_movies_count("total");
$total_avg = $m->get_watched_movies_count("total-average");

# division by zero bugfix
# TODO: change this code to calculate days from the database, not by divisions
$days = ($total_avg == 0 ? 1 : round($total/$total_avg));

$last_30_days_avg = $m->get_watched_movies_count("last-30-days") / 30;
$last_7_days_avg = $m->get_watched_movies_count("last-7-days") / 7;

?>

<p>Total number of rated movies: <strong><?= $total ?></strong>
(average of <strong><?= $total_avg ?></strong> movies per day; <strong><?= $days ?></strong> days of movie ratings).</p>

<p>Average of <strong><? printf("%.4f", $last_30_days_avg); ?></strong> movies per day for the past <strong>30</strong> days (<strong><? printf("%.4f", $last_7_days_avg); ?></strong> for the past <strong>7</strong> days).</p>

<p>This month: <strong><?= $m->get_watched_movies_count("month") ?></strong>
(last month: <strong><?= $m->get_watched_movies_count("last-month") ?></strong>).</p>

<p>This year: <strong><?= $m->get_watched_movies_count("year") ?></strong>
(last year: <strong><?= $m->get_watched_movies_count("last-year") ?></strong>).</p>

<?php

$min_id = $wpdb->get_var("SELECT id FROM wp_movie_ratings ORDER BY watched_on ASC LIMIT 1;");
$max_id = $wpdb->get_var("SELECT id FROM wp_movie_ratings ORDER BY watched_on DESC LIMIT 1;");

?>

<p>First movie rated on: <strong><?= $wpdb->get_var("SELECT watched_on FROM wp_movie_ratings WHERE id=$min_id;")?></strong>.</p>
<p>Last movie rated on: <strong><?= $wpdb->get_var("SELECT watched_on FROM wp_movie_ratings WHERE id=$max_id;")?></strong>.</p>

<h2>Firefox bookmarklet</h2>

<p>Add the following link to your Bookmarklets folder so you can rate your movies without visiting Wordpress administration page. You must be <strong>logged in</strong> to your Wordpress blog for it to work, though.</p>

<?php

$siteurl = get_option("siteurl");
if ($siteurl[strlen($siteurl)-1] != "/") $siteurl .= "/";
$pluginurl = $siteurl . "wp-content/plugins/" . dirname(plugin_basename(__FILE__)) . "/";

?>
<p><a href="javascript:(function(){open('<?= $pluginurl ?>add_movie.html?url='+escape(location.href),'<?= basename(__FILE__, ".php") ?>','toolbar=no,width=432,height=335')})()" title="Add movie rating bookmarklet">Add movie rating bookmarklet</a></p>

</div>

<?php
}

# Hook for plugin installation
register_activation_hook(__FILE__, 'wp_movie_ratings_install');

# Add action for administration menu
add_action('admin_menu', 'wp_movie_ratings_add_management_page');

?>