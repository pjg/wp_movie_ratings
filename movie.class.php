<?php

class Movie {
    var $_url;             # http://imdb.com/title/tt0133093/
    var $_url_short;       # 0133093
    var $_title;           # The Matrix (1999)
    var $_rating;          # 10
    var $_review;          # Truly a masterpiece.
    var $_watched_on;      # 2006-03-01 23:15

    var $_wpdb;            # wordpress database handle
    var $_table;           # database table name
    var $_char_limit = 44; # limit on number of characters in the movie's title when displaying (so it won't collapse)

    # constructor
    function Movie($url=null, $rating=null, $review=null, $title=null, $watched_on=null) {
        $this->_url = rawurldecode(trim($url));
        $this->_rating = intval($rating);
        $this->_review = trim($review);
        $this->_title = $title;
        $this->_watched_on = $watched_on;
    }

    # wordpress' database handler and table prefix
    function set_database($wpdb, $table_prefix) {
        $this->_wpdb = $wpdb;
        $this->_table = $table_prefix . "movie_ratings";
    }

    # check if we have a valid imdb.com link
    function parse_parameters() {
        $msg = "";

        if (preg_match("/^http:\/\/(.*)imdb\.com\/title\/tt([0-9]{7})(\/){0,1}$/i", $this->_url, $matches)) {
            if (($this->_rating > 0) && ($this->_rating < 11)) {
                $this->_url_short = $matches[2];
                $this->_url = 'http://imdb.com/title/tt' . $this->_url_short . '/';
                return "";
            }
            else $msg = '<div id="message" class="error fade"><p><strong>Error: wrong movie rating.</strong></p></div>';
        }
        else $msg = '<div id="message" class="error fade"><p><strong>Error: wrong imdb link.</strong></p></div>';

        return $msg;
    }

    # get title from imdb.com
    function get_title() {
        $req = new HTTPRequest($this->_url);
        $imdb = $req->DownloadToString();
        preg_match("/<title>(.+)<\/title>/i", $imdb, $title_matches);
        $this->_title = $title_matches[1];

        if ($this->_title == "") {
            $msg = '<div id="message" class="error fade"><p><strong>Error while retrieving the title of the movie.</strong></p></div>';
            return $msg;
        }
        else return "";
    }

    # save movie rating to the database
    function save() {

        # stare, bieze czas serwera
        # $watched_on = date("Y-m-d H:i:s");

        # 2006-03-05 01:03:44
        $gmt_offset = get_option("gmt_offset");
        $watched_on = gmstrftime("%Y-%m-%d %H:%M:%S", time() + (3600 * $gmt_offset));

        # insert into db
        $this->_wpdb->hide_errors();
        $this->_wpdb->query("INSERT INTO $this->_table (title, imdb_url_short, rating, review, watched_on) VALUES ('" . addslashes($this->_title) . "', '$this->_url_short', $this->_rating, '$this->_review', '$watched_on');");

        $this->_wpdb->show_errors();

        if ($this->_wpdb->rows_affected == 1) {
            # str_replace is to drop the 'magic quotes' (they tend to be here)
            return '<div id="message" class="updated fade"><p><strong>' . rawurlencode(str_replace("''", "'", $this->_title)) . ' rated ' . $this->_rating . '/10 saved.</strong></p></div>';
        } else {
            $mysql_error = mysql_error();
            $msg = "";

            if (strpos($mysql_error, "Duplicate entry") === false) $msg = ' not added. ' . $mysql_error;
            else $msg = ' is already rated';

            return '<div id="message" class="error fade"><p><strong>Error: ' . rawurlencode(str_replace("''", "'", $this->_title)) . $msg . '.</strong></p></div>';
        }
    }

    # get latest movies
    function get_latest_movies($count) {
        $movies = array();
        $results = $this->_wpdb->get_results("SELECT title, imdb_url_short, rating, review, DATE_FORMAT(watched_on, '%Y-%m-%d %H:%i') AS watched_on FROM $this->_table ORDER BY id DESC LIMIT " . intval($count));

        if ($results) {
            foreach ($results as $r) {
                $movie = new Movie("http://imdb.com/title/tt" . $r->imdb_url_short . "/", $r->rating, $r->review, $r->title, $r->watched_on);
                array_push($movies, $movie);
            }
        }

        return $movies;
    }

    # get total number of watched/rated movies
    function get_watched_movies_count($range) {
        if ($range == "total-average") {
			$min_id = $this->_wpdb->get_var("SELECT id FROM wp_movie_ratings ORDER BY watched_on ASC LIMIT 1;");
			$max_id = $this->_wpdb->get_var("SELECT id FROM wp_movie_ratings ORDER BY watched_on DESC LIMIT 1;");

            $days_first = $this->_wpdb->get_var("SELECT TO_DAYS(watched_on) FROM wp_movie_ratings WHERE id=$min_id;");
            $days_last = $this->_wpdb->get_var("SELECT TO_DAYS(watched_on) FROM wp_movie_ratings WHERE id=$max_id;");

            # division by zero bugfix
            $days_diff = $days_last - $days_first;
            $days = ($days_diff == 0 ? 1 : $days_diff);

            $query = "SELECT (COUNT(id)/$days) AS count FROM $this->_table ";
        }
        else $query = "SELECT COUNT(id) AS count FROM $this->_table ";

        switch ($range) {
            case "last-30-days" : $tmp_date = mktime(0, 0, 0, date("m"), date("j")-30, date("Y"));
                                  $cond = "WHERE watched_on >= '" . date("Y-m-d", $tmp_date) . "'";
                                  break;
            case "last-7-days"  : $tmp_date = mktime(0, 0, 0, date("m"), date("j")-7, date("Y"));
                                  $cond = "WHERE watched_on >= '" . date("Y-m-d", $tmp_date) . "'";
                                  break;
            case "month"        : $cond = "WHERE MONTH(watched_on)=" . date("n") . " AND YEAR(watched_on)=" . date("Y");
                                  break;
            case "last-month"   : $tmp_date = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                                  $cond = "WHERE MONTH(watched_on)=" . date("n", $tmp_date) . " AND YEAR(watched_on)=" . date("Y", $tmp_date);
                                  break;
            case "year"         : $cond = "WHERE YEAR(watched_on)=" . date("Y");
                                  break;
            case "last-year"    : $cond = "WHERE YEAR(watched_on)=" . date("Y", mktime(0, 0, 0, 1, 1, date("Y")-1));
                                  break;
            case "total"        : $cond = "";
                                  break;
            case "total-average": $cond = "";
                                  break;
            default             : $cond = "";
                                  break;
        }

        return $this->_wpdb->get_var($query . $cond);
    }

    # show movie
    function show($img_path, $with_review=false) {
        if (!is_plugin_page()) {
            # shorten the title
            if (strlen($this->_title) <= $this->_char_limit) $title_short = $this->_title;
            else {
                # cut at limit
                $title_short = substr($this->_title, 0, $this->_char_limit);

                # find last space char: " "
                $last_space_position = strrpos($title_short, " ");

                # cut at last space
                $title_short = substr($title_short, 0, $last_space_position) . "...";
            }
        } else $title_short = $this->_title;


        ?><p class="item"><a class="url fn" href="<?= $this->_url ?>" title="<?= $this->_title . "\n" ?>Watched and reviewed on <?= $this->_watched_on ?>"><?= $title_short ?></a></p><? echo "\n";

        ?><acronym class="dtreviewed" title="<?= str_replace(" ", "T", $this->_watched_on) ?>"><?= $this->_watched_on ?></acronym><? echo "\n";

        echo "<div class=\"rating_stars\">\n";

        echo "<p class=\"rating\"><span class=\"value\">" . $this->_rating . "</span> stars out of <span class=\"best\">10</span></p>\n";

        # Reverse Polish notation (because of the float: right; in css)
        for ($i=1; $i<11; $i++) {
            if ($this->_rating >= $i) { ?><img src="<?= $img_path ?>full_star.gif" alt="*" /><? echo "\n"; }
            else { ?><img src="<?= $img_path ?>empty_star.gif" alt="" /><? echo "\n"; }
        }
        echo "</div>\n";

        if (($with_review) && ($this->_review != "")) echo "<p class=\"description\">" . $this->_review . "</p>\n";
    }
}

?>