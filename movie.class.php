<?php 

class Movie
{
	var $_url;
	var $_url_short;
	var $_title;
	var $_rating;
  
	// constructor
	function Movie($url, $rating)
	{
		$this->_url = trim($url);
		$this->_rating = $rating;
	}

	// get title
	function get_title()
	{
		# http://us.imdb.com/title/tt0424205/
		if (preg_match("/^http:\/\/(us\.|uk\.|akas\.){0,1}imdb\.com\/title\/tt([0-9]{7})(\/){0,1}$/i", $this->_url, $url_matches))
		{
			$this->_url_short = $url_matches[2];
			$req = new HTTPRequest($this->_url);
			$imdb = $req->DownloadToString();

			preg_match("/<title>(.+)<\/title>/i", $imdb, $title_matches);
			$this->_title = $title_matches[1];
		}
	}

	# save to database
	function save()
	{
		if ($this->_title != "")
		{
			$link = mysql_connect('localhost', 'root', '');
			mysql_select_db("wp_movies");
			mysql_query("INSERT INTO movies (title, imdb_url_short, rating, created_on, updated_on) VALUES ('$this->_title', '$this->_url_short', $this->_rating, NOW(), NOW())");
		}
	}

	# print on screen
	function show()
	{
		?>
		<p>URL: <a href="<?= $this->_url ?>"><?= $this->_url ?></a></p>
		<p>URL_short: <strong><?= $this->_url_short ?></strong></p>
		<p>Rating: <strong><?= $this->_rating ?></strong></p>
		<p>Title: <strong><?= $this->_title ?></strong></p>
		<?
	}
}

?>