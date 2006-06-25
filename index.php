<?php

# WP Movie Ratings XML RPC Server (aka server dealing with AJAX requests).
# Used for:
#   1. Fetching movie titles from imdb.com;
#   2. Saving movie ratings in the database;

if (isset($_POST["url"]) && isset($_POST["rating"]))
{
	require_once("httprequest.class.php");
	require_once("movie.class.php");

	$url = rawurldecode(trim($_POST["url"]));
	$rating = intval($_POST["rating"]);
	$msg = "";

	if (preg_match("/^http:\/\/(us\.|uk\.|akas\.){0,1}imdb\.com\/title\/tt([0-9]{7})(\/){0,1}$/i", $url, $matches))
	{
		if (($rating > 0) && ($rating < 11))
		{
			if ($user_level > 10) {
				$movie = new Movie($matches[2], $rating);
				$msg = $movie->get_title();
				
				if (strlen($msg) < 1) $msg = $movie->save();
			}
			else $msg = '<p class="error">Error: userlevel too low. You must be an admin to rate movies.</p>';
		}
		else $msg = '<p class="error">Error: wrong rating.</p>';
	}
	else $msg = '<p class="error">Error: wrong imdb link.</p>';

	echo $msg;
}

?>