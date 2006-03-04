<?php
/*
Plugin Name: Movie ratings
Version: 1.0.0
Plugin URI: http://paulgoscicki.com/
Description: Rate movies that you've seen recently and display short list of those movies on your blog (kottke.org style). iMDB used to automatically fetch movie titles.
Author: Paul Goscicki
Author URI: http://paulgoscicki.com/
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
			created_on timestamp NOT NULL default '0000-00-00 00:00:00',
			updated_on timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY (imdb_url_short)
		);";

		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);
	}
}

# Show latest movie ratings
function wp_movie_ratings_show()
{
	global $wpdb, $table_prefix;

	# image path
	$siteurl = get_option("siteurl");
	if ($siteurl[strlen($siteurl)-1] != "/") $siteurl .= "/";
	$tmp_array = parse_url($siteurl . "wp-content/plugins/" . dirname(plugin_basename(__FILE__)) . "/");
	$img_path = $tmp_array["path"];

	$m = new Movie();
	$m->set_database($wpdb, $table_prefix);
	$movies = $m->get_latest_movies(30);

	if (!is_plugin_page())
	{
		$css_path = $img_path . basename(__FILE__, ".php") . ".css";
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$css_path\" />\n";
	}

	echo "<div id=\"wp_movie_ratings\">\n";
	echo "<h2>Movies I've watched recently:</h2>\n";
	echo "<ul>\n";
	$i = 0; # row alternator
	foreach($movies as $movie)
	{
		echo "<li" . (($i++ % 2) == 0 ? " class=\"odd\"" : "") . ">\n";
		$movie->show($img_path);
		echo "</li>\n";
	}
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
		$movie = new Movie($_POST["url"], $_POST["rating"]);
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
	 
<div class="submit" style="text-align: left">
  <input type="submit" name="info_update" value="Add new movie rating &gt;&gt;" />
</div>

</form>

<? wp_movie_ratings_show() ?>

</div>

<?php
}


# Hook for plugin installation
register_activation_hook(__FILE__, 'wp_movie_ratings_install');

# Add action for administration menu
add_action('admin_menu', 'wp_movie_ratings_add_management_page');

?>
