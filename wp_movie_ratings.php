<?php
/*
Plugin Name: Movie ratings
Version: 1.0.0
Plugin URI: http://paulgoscicki.com/
Description: Rate movies that you've seen recently and display short list of those movies on your blog (kottke.org style). iMDB used to automatically fetch movie titles.
Author: Paul Goscicki
Author URI: http://paulgoscicki.com/
*/

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
			PRIMARY KEY (id)
		);";

		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);
	}
}


function wp_movie_ratings_show()
{
	#<link rel="stylesheet" type="text/css" media="screen" href="wp_movie_ratings.css" />

	# do not display additional quotes in strings from the database
	#set_magic_quotes_runtime(0);

	#require_once("movie.class.php");

	$link = mysql_connect('localhost', 'root', '');
	if (!$link) echo '<p class="error">Error: could not connect to the database.</p>';

	$db = mysql_select_db("wp_movies");
	if (!$db) echo '<p class="error">Error: could not select the database.</p>';

	$m = new Movie();
	$movies = $m->get_latest_movies();

	$i = 0;

	echo "<div id=\"wp_movie_ratings\">\n";
	echo "<h1>Movies I've watched recently:</h1>\n";
	echo "<ul>\n";
	foreach($movies as $movie)
	{
		echo "<li" . (($i++ % 2) == 0 ? " class=\"odd\"" : "") . ">\n";
		$movie->show();
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


function wp_movie_ratings_management_page() {
	if (isset($_POST['info_update'])) {
		# Get title of the movie and save its rating in the database
		if (isset($_POST["url"]) && isset($_POST["rating"]))
		{ 
			#include_once(ABSPATH . 'wp-content/plugins/' . $plugin); 
			include_once(ABSPATH . 'wp-content/plugins/wp_movie_ratings/' . "httprequest.class.php");
			include_once(ABSPATH . 'wp-content/plugins/wp_movie_ratings/' . "movie.class.php");

			$url = rawurldecode(trim($_POST["url"]));
			$rating = intval($_POST["rating"]);

			$msg = "";

			if (preg_match("/^http:\/\/(.*)imdb\.com\/title\/tt([0-9]{7})(\/){0,1}$/i", $url, $matches))
			{
				if (($rating > 0) && ($rating < 11))
				{
					$movie = new Movie($matches[2], $rating);
					$msg = $movie->get_title();
					
					if (strlen($msg) < 1) $msg = $movie->save();
				}
				else $msg = '<div class="error"><p><strong>Error: wrong rating.</strong></p></div>';
			}
			else $msg = '<div class="error"><p><strong>Error: wrong imdb link.</strong></p></div>';

			echo rawurldecode($msg);


		# div class=updated/error
		}
	}
?>

<div class=wrap>

<form method="post">
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

<h2>Last added movies</h2>

 </div><?php
}



# Hook for plugin installation
register_activation_hook(__FILE__, 'wp_movie_ratings_install');

# Add action for administration menu
add_action('admin_menu', 'wp_movie_ratings_add_management_page');

?>
