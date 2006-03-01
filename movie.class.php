<?php

class Movie
{
	var $_url;             # http://us.imdb.com/title/tt0424205/
	var $_url_short;       # 0424205
	var $_title;           # Joyeux Noël (2005)
	var $_rating;          # 10
	var $_watched_on;      # 2006-03-01 23:15

	var $_link;            # database connection handle
	var $_db;              # database handle
	var $_char_limit = 45; # limit on number of characters in the movie's title (so it won't collapse the page)

	# constructor
	function Movie($url_short=null, $rating=null, $title=null, $watched_on=null)
	{
		$this->_url_short = $url_short;
		$this->_url = 'http://imdb.com/title/tt' . $this->_url_short . '/';
		$this->_rating = $rating;
		$this->_title = $title;
		$this->_watched_on = $watched_on;
	}

	# connect to the database
	function _connect_to_database()
	{
		$this->_link = mysql_connect('localhost', 'root', '');
		if (!$this->_link) return false;
		else return true;
	}

	# select database
	function _select_database()
	{
		$this->_db = mysql_select_db("wp_movies");
		if (!$this->_db) return false;
		else return true;
	}

	# get title from imdb.com
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

	# save movie rating to the database
	function save()
	{
		if (!$this->_connect_to_database()) return '<p class="error">Error: could not connect to the database.</p>';
		if (!$this->_select_database()) return '<p class="error">Error: could not select the database.</p>';

		$result = mysql_query("INSERT INTO wp_movies (title, imdb_url_short, rating, created_on, updated_on) VALUES ('$this->_title', '$this->_url_short', $this->_rating, NOW(), NOW())");
		if (!$result) return '<p class="error">Error: could not add record to the database.</p>';

		# str_replace is to drop the 'magic quotes' (they tend to be here)
		return '<p>' . rawurlencode(str_replace("''", "'", $this->_title)) . ' rated ' . $this->_rating . '/10 saved.';
	}

	# get latest movies
	function get_latest_movies()
	{
		if (!$this->_connect_to_database()) return '<p class="error">Error: could not connect to the database.</p>';
		if (!$this->_select_database()) return '<p class="error">Error: could not select the database.</p>';

		$movies = array();

		$result = mysql_query("SELECT title, imdb_url_short, rating, DATE_FORMAT(created_on, '%Y-%m-%d %H:%i') AS watched_on FROM wp_movies ORDER BY id DESC LIMIT 7");
		if (!$result) return $movies;
		
		while ($row = mysql_fetch_array($result))
		{
			$movie = new Movie($row["imdb_url_short"], $row["rating"], $row["title"], $row["watched_on"]);
			array_push($movies, $movie);
		}
		return $movies;
	}
	
	# show movie
	function show()
	{
		# shorten the title
		if (strlen($this->_title) <= $this->_char_limit) $title_short = $this->_title;
		else
		{
			# cut at limit
			$title_short = substr($this->_title, 0, $this->_char_limit);
			# find last space char: " "
			$last_space_position = strrpos($title_short, " ");
			# cut at last space
			$title_short = substr($title_short, 0, $last_space_position) . "...";
		}

		?><a href="<?= $this->_url ?>" title="<?= $this->_title . "\n" ?>Watched on <?= $this->_watched_on ?>"><?= $title_short ?></a><? echo "\n";
		
		for($i=1; $i<11; $i++)
		{
			if ($this->_rating >= $i) { ?><img src="full_star.gif" alt="Full star gives one rating point" /><? echo "\n"; }
			else { ?><img src="empty_star.gif" alt="Empty star gives no rating points" /><? echo "\n"; }
		}
	}
}

?>