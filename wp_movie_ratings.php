<link rel="stylesheet" type="text/css" media="screen" href="wp_movie_ratings.css" />

<?php

# do not display additional quotes in strings from the database
set_magic_quotes_runtime(0);

require_once("movie.class.php");

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

?>
