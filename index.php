<?php

# Get title of the movie and save its rating in the database
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
			$movie = new Movie($matches[2], $rating);
			$msg = $movie->get_title();
			
			if (strlen($msg) < 1) $msg = $movie->save();
		}
		else $msg = '<p class="error">Error: wrong rating.</p>';
	}
	else $msg = '<p class="error">Error: wrong imdb link.</p>';

	echo $msg;
}

?>