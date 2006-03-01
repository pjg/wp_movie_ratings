<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>iMDB movie ratings</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
<link rel="stylesheet" type="text/css" media="screen" href="add_movie.css" />
<script type="text/javascript" src="prototype.js"></script>
<script type="text/javascript" src="effects.js"></script>
<script type="text/javascript" src="add_movie.js"></script>
</head>
<body>

<form id="movie_data" action="post">

<p>
<label for="url">iMDB link:</label>
<input type="text" name="url" id="url" class="text" />
</p>

<p>
<label>Movie rating:</label>
</p>

<ul class="star-rating" id="rating">
<li><a id="rating1" href="#" title="Rate this movie 1 star out of 10" class="one-star"></a></li>
<li><a id="rating2" href="#" title="Rate this movie 2 stars out of 10" class="two-stars"></a></li>
<li><a id="rating3" href="#" title="Rate this movie 3 stars out of 10" class="three-stars"></a></li>
<li><a id="rating4" href="#" title="Rate this movie 4 stars out of 10" class="four-stars"></a></li>
<li><a id="rating5" href="#" title="Rate this movie 5 stars out of 10" class="five-stars"></a></li>
<li><a id="rating6" href="#" title="Rate this movie 6 stars out of 10" class="six-stars"></a></li>
<li><a id="rating7" href="#" title="Rate this movie 7 stars out of 10" class="seven-stars"></a></li>
<li><a id="rating8" href="#" title="Rate this movie 8 stars out of 10" class="eight-stars"></a></li>
<li><a id="rating9" href="#" title="Rate this movie 9 stars out of 10" class="nine-stars"></a></li>
<li><a id="rating10" href="#" title="Rate this movie 10 stars out of 10" class="ten-stars"></a></li>
<li><img id="loading" src="loading.gif" alt="Sending request... please wait." style="display: none" /></li>
</ul>

</form>

<div id="message" style="display: none"></div>

</body>
</html>
