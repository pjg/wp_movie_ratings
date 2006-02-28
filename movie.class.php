<?php 

class Movie
{
	var $_url;        # http://us.imdb.com/title/tt0424205/
	var $_url_short;  # 0424205
	var $_title;      # Joyeux Noël (2005) 
	var $_rating;     # 1-10
  
	# constructor
	function Movie($url, $rating)
	{
		$this->_url_short = $url;
		$this->_url = 'http://imdb.com/title/tt' . $this->_url_short . '/';
		$this->_rating = $rating;
	}

	# get title
	function get_title()
	{
		$req = new HTTPRequest($this->_url);
		$imdb = $req->DownloadToString();
		preg_match("/<title>(.+)<\/title>/i", $imdb, $title_matches);
		$this->_title = $title_matches[1];

		if ($this->_title == "")
		{
			$msg = '<p class="error">Error while retrieving the title of the movie.</p>';
			return $msg;
		}
		else return '';
	}

	# save to database
	function save()
	{
		$link = mysql_connect('localhost', 'root', '');
		if (!$link) return '<p class="error">Error: could not connect to the database.</p>';

		$db = mysql_select_db("wp_movies");
		if (!$db) return '<p class="error">Error: could not select the database.</p>';

		$result = mysql_query("INSERT INTO movies (title, imdb_url_short, rating, created_on, updated_on) VALUES ('$this->_title', '$this->_url_short', $this->_rating, NOW(), NOW())");
		if (!$result) return '<p class="error">Error: could not add record to the database.</p>';

		return '<p>' . rawurlencode($this->_title) . ' rated ' . $this->_rating . '/10 saved.';
	}

	# debug information
	function debug()
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