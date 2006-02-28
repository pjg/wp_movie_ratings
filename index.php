<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>iMDB movie ratings</title>
<link rel="stylesheet" type="text/css" media="screen" href="imdb.css" />
</head>
<body>

<select name="rating">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4">4</option>
<option value="5">5</option>
<option value="6">6</option>
<option value="7">7</option>
<option value="8">8</option>
<option value="9">9</option>
<option value="10" selected="selected">10</option>
</select>
</p>

</div>

<?php

require_once("movie.class.php");
require_once("httprequest.class.php");

$movie = new Movie((isset($_POST["url"]) ? $_POST["url"] : ""), (isset($_POST["rating"]) ? $_POST["rating"] : ""));
$movie->get_title();
$movie->save();
$movie->show();

?>



</body>
</html>



