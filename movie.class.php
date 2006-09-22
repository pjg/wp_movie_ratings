<?php

class Movie {
    var $_id;               # 1 (database id for movie rating)
    var $_url;              # http://imdb.com/title/tt0133093/
    var $_url_short;        # 0133093
    var $_replacement_url;  # http://www.rottentomatoes.com/m/1086960-the_matrix/
    var $_title;            # The Matrix (1999)
    var $_rating;           # 10
    var $_review;           # Truly a masterpiece.
    var $_watched_on;       # 2006-03-01 23:15

    var $_wpdb;             # wordpress database handle
    var $_table;            # database table name
    var $_table_prefix;     # just the wordpress database table prefix


    # constructor
    function Movie($url=null, $rating=null, $review=null, $title=null, $replacement_url=null, $watched_on=null, $id=null) {
        $this->_url = rawurldecode(trim($url));
        $this->_rating = intval($rating);
        $this->_review = trim($review);
        $this->_title = $title;
        $this->_replacement_url = rawurldecode(trim($replacement_url));
        $this->_watched_on = $watched_on;
        $this->_id = $id;
    }


    # wordpress' database handler and table prefix
    function set_database($wpdb, $table_prefix) {
        $this->_wpdb = $wpdb;
        $this->_table_prefix = $table_prefix;
        $this->_table = $table_prefix . "movie_ratings";
    }


    # check if we have a valid imdb.com link
    function parse_parameters() {
        if (preg_match("/^http:\/\/(.*)imdb\.com\/title\/tt([0-9]{7})(\/){0,1}$/i", $this->_url, $matches)) {
            if (($this->_rating > 0) && ($this->_rating < 11)) {
                $this->_url_short = $matches[2];
                $this->_url = 'http://imdb.com/title/tt' . $this->_url_short . '/';
                $msg = "";
            }
            else $msg = '<div id="message" class="error fade"><p><strong>Error: wrong movie rating.</strong></p></div>';
        }
        else $msg = '<div id="message" class="error fade"><p><strong>Error: wrong imdb link.</strong></p></div>';

        return $msg;
    }


    # get title from imdb.com
    function get_title() {
        $req = new WP_HTTP_Request($this->_url);
        $imdb = $req->DownloadToString();
        preg_match("/<title>(.+)<\/title>/i", $imdb, $title_matches);
        $this->_title = $title_matches[1];

        if ($this->_title == "") return '<div id="message" class="error fade"><p><strong>Error while retrieving the title of the movie from imdb.</strong></p></div>';
        else return "";
    }


    # get current time using Wordpress' time offset
    function get_current_time() {
        # 2006-03-05 01:03:44
        return gmstrftime("%Y-%m-%d %H:%M:%S", time() + (3600 * get_option("gmt_offset")));
    }


    # save movie rating to the database
    function save() {
        $watched_on = ($this->_watched_on == null ? $this->get_current_time() : $this->_watched_on);

        # insert into db (we make sure that special characters are properly escaped, but not double escaped)
        $title = (get_magic_quotes_runtime() == 0 ? addslashes($this->_title) : $this->_title);
        $review = (get_magic_quotes_runtime() == 0 ? addslashes($this->_review) : $this->_review);

        # encode &amp; separately for review (which allows HTML code) (this is important when adding movies via admin panel)
        $review = str_replace(" & ", " &amp; ", $review);

        # encode &amp; in replacement url
        $replacement_url = str_replace("&", "&amp;", $this->_replacement_url);

        $this->_wpdb->hide_errors();
        $this->_wpdb->query("INSERT INTO $this->_table (title, imdb_url_short, rating, review, replacement_url, watched_on) VALUES ('$title', '$this->_url_short', $this->_rating, '$review', '$replacement_url', '$watched_on');");

        $this->_wpdb->show_errors();

        if ($this->_wpdb->rows_affected == 1) {

            # Send pingerati ping
            if (get_option("wp_movie_ratings_ping_pingerati") == "yes") $this->send_ping();

            return '<div id="message" class="updated fade"><p><strong>' . rawurlencode(stripslashes($this->_title)) . ' rated ' . $this->_rating . '/10 saved.</strong></p></div>';
        } else {
            $mysql_error = mysql_error();
            $msg = "";

            if (strpos($mysql_error, "Duplicate entry") === false) $msg = ' not added. ' . $mysql_error;
            else $msg = ' is already rated';

            return '<div id="message" class="error fade"><p><strong>Error: ' . rawurlencode(stripslashes($this->_title)) . $msg . '.</strong></p></div>';
        }
    }


    # update movie data
    function update_from_post() {
		# parse imdb url
		$this->_url = rawurldecode(trim($_POST["url"]));
		$this->_rating = intval($_POST["rating"]);
		$msg = $this->parse_parameters();
		
		# stop if wrong imdb url
		if (strlen($msg) > 0) return $msg;

        $this->_title = htmlspecialchars($_POST["title"]);
        $this->_review = str_replace(" & ", " &amp; ", $_POST["review"]);
        $this->_replacement_url = trim(str_replace("&", "&amp;", $_POST["replacement_url"]));
        $this->_watched_on = $_POST["watched_on"];
        $this->_wpdb->query("UPDATE $this->_table SET imdb_url_short='$this->_url_short', title='$this->_title', rating=$this->_rating, review='$this->_review', replacement_url='$this->_replacement_url', watched_on='$this->_watched_on' WHERE id=$this->_id LIMIT 1");
        $this->_wpdb->show_errors();

        if ($this->_wpdb->rows_affected > 0) {

            # Send ping to pingerati.net
            if (get_option("wp_movie_ratings_ping_pingerati") == "yes") $this->send_ping();

            return '<div id="message" class="updated fade"><p><strong>' . stripslashes($this->_title) . ' rated ' . $this->_rating . '/10 updated.</strong></p></div>';
        } else {
            return '<div id="message" class="error fade"><p><strong>Error: ' . stripslashes($this->_title) . ' not updated.</strong></p></div>';
        }
    }


    # send ping to pingerati.net (with blog's homepage as argument)
    function send_ping() {
		# Decide whether ping with movies page address or just blog home address
		$page_url = get_option("wp_movie_ratings_page_url");
		$link = preg_replace("/http[s]*:\/\//", "", trailingslashit((strlen($page_url) > 0 ? $page_url : get_option("home"))));

		# GET
        $req = new WP_HTTP_Request("http://reviews.pingerati.net/ping/" . $link);
        $req->DownloadToString();
    }


    # find movie
    function get_movie_by_id($id) {
        $arr = $this->get_movies(array("type" => "one", "id" => $id));
        if (count($arr) == 1) {
            return $arr[0];
        } else return null;
    }


    # get latest movies
    function get_latest_movies($count) {
        return $this->get_movies(array("type" => "latest", "count" => $count));
    }


    # get all movies
    function get_all_movies($order_by = "title", $direction = "ascending", $start = null, $limit = null) {
        return $this->get_movies(array("type" => "all", "order_by" => $order_by, "direction" => $direction, "start" => $start, "limit" => $limit));
    }


    # get movies
    # options:
    #   "type" => "one"/"latest"/"all"
    #   "count" => 1-n
    #   "order_by" => "title"/"watched_on"
    #   "direction" => "ASC"/"DESC"
	#   "start" => pagination start
	#   "limit" => number of movies per page
    function get_movies($options = array()) {
        $movies = array();

        # get 20 latest movies is the default
        $type = (isset($options["type"]) ? $options["type"] : "latest");

        if ($type == "one") {
            $id = (isset($options["id"]) ? $options["id"] : 1);
            $count = 1;
        }

        if ($type == "latest") {
            $order_by = (isset($options["order_by"]) ? $options["order_by"] : "watched_on");
            $direction = (isset($options["direction"]) ? $options["direction"] : "DESC");
            $count = (isset($options["count"]) ? $options["count"] : 20);
        }

        if ($type == "all") {
            $order_by = (isset($options["order_by"]) ? $options["order_by"] : "title");
            $direction = (isset($options["direction"]) ? $options["direction"] : "ASC");
			$start = intval((isset($options["start"]) && $options["start"] != null) ? $options["start"] : 0);
			$limit = intval((isset($options["limit"]) && $options["limit"] != null) ? $options["limit"] : get_option("wp_movie_ratings_pagination_limit"));		
		}

        # Bulding SQL query
        $date_format = "%Y-%m-%d %H:%i" . ($type == "one" ? ":%s" : "");
        $sql  = "SELECT id, title, imdb_url_short, rating, review, replacement_url, DATE_FORMAT(watched_on, '$date_format') AS watched_on FROM $this->_table ";
        if ($type == "one") $sql .= " WHERE id=$id ";
        
		# default second sort is by date -> important when sorting by rating, so we get the newest movies with same rating first
        if ($type != "one") $sql .= " ORDER BY " . $order_by . " " . $direction . ", watched_on DESC ";

		# limit for latest movies
        if (in_array($type, array("latest", "one"))) $sql .= " LIMIT " . intval($count);

		# pagination
		if ($type == "all") $sql .= " LIMIT $start, $limit;";

        $results = $this->_wpdb->get_results($sql);

        if ($results) {
            foreach ($results as $r) {
                $movie = new Movie("http://imdb.com/title/tt" . $r->imdb_url_short . "/", $r->rating, stripslashes($r->review), stripslashes($r->title), $r->replacement_url, $r->watched_on, $r->id);
                $movie->set_database($this->_wpdb, $this->_table_prefix);
                array_push($movies, $movie);
            }
        }

        return $movies;
    }


    # delete movie
    function delete() {
        if ($this->_wpdb->query("DELETE FROM $this->_table WHERE id=$this->_id LIMIT 1;")) return '<div id="message" class="updated fade"><p><strong>Movie rating deleted.</strong></p></div>';
        else return '<div id="message" class="error fade"><p><strong>Error: something weird happened and I could not delete this movie rating.</strong></p></div>';
    }


    # various statistics
    function get_watched_movies_count($range) {
        if (($range == "total-average") || ($range == "first-rated") || ($range == "last-rated")) {
            $first_id = $this->_wpdb->get_var("SELECT id FROM $this->_table ORDER BY watched_on ASC LIMIT 1;");
            $last_id = $this->_wpdb->get_var("SELECT id FROM $this->_table ORDER BY watched_on DESC LIMIT 1;");

            # division by zero fix
            $first_id = ($first_id == "" ? 0 : $first_id);
            $last_id = ($last_id == "" ? 0 : $last_id);
        }

        if ($range == "total-average") {
            $days_first = $this->_wpdb->get_var("SELECT TO_DAYS(watched_on) FROM $this->_table WHERE id=$first_id;");
            $days_last = $this->_wpdb->get_var("SELECT TO_DAYS(watched_on) FROM $this->_table WHERE id=$last_id;");

            # division by zero fix
            $days_diff = $days_last - $days_first;
            $days = ($days_diff == 0 ? 1 : $days_diff);

            $query = "SELECT (COUNT(id)/$days) AS count FROM $this->_table ";
        }
        else if ($range == "first-rated") {
            $query = "SELECT watched_on FROM $this->_table WHERE id=$first_id;";
        }
        else if ($range == "last-rated") {
            $query = "SELECT watched_on FROM $this->_table WHERE id=$last_id;";
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


    # average movie rating
    function get_average_movie_rating() {
        return $this->_wpdb->get_var("SELECT AVG(rating) FROM $this->_table");
    }


    # show movie
    function show($img_path, $options = array()) {
        # output
        $o = "";

        # parse arugments
        $include_review = (isset($options["include_review"]) ? $options["include_review"] : get_option("wp_movie_ratings_include_review"));
        $expand_review = (isset($options["expand_review"]) ? $options["expand_review"] : get_option("wp_movie_ratings_expand_review"));
        $text_ratings = (isset($options["text_ratings"]) ? $options["text_ratings"] : get_option("wp_movie_ratings_text_ratings"));
        $sidebar_mode = (isset($options["sidebar_mode"]) ? $options["sidebar_mode"] : get_option("wp_movie_ratings_sidebar_mode"));
        $five_stars_ratings = (isset($options["five_stars_ratings"]) ? $options["five_stars_ratings"] : get_option("wp_movie_ratings_five_stars_ratings"));
		$highlight = (isset($options["highlight"]) ? $options["highlight"] : get_option("wp_movie_ratings_highlight"));
        $page_mode = (isset($options["page_mode"]) ? $options["page_mode"] : "no");

        if (!is_plugin_page()) {
            # shorten the title
            $char_limit = get_option("wp_movie_ratings_char_limit");

            if (strlen($this->_title) <= $char_limit) $title_short = $this->_title;
            else {
                # cut at limit
                $title_short = substr($this->_title, 0, $char_limit);

                # find last space char: " "
                $last_space_position = strrpos($title_short, " ");

                # cut at the last space
                $title_short = substr($title_short, 0, $last_space_position) . "...";
            }
        } else $title_short = $this->_title;

        # Admin options
        if (is_plugin_page()) {
            $o .= "<form method=\"post\" action=\"\">\n";
            $o .= "<input type=\"hidden\" name=\"id\" value=\"" . $this->_id . "\" />\n";
        }

        $o .= "<div class=\"hreview\">\n";

        $o .= "<p class=\"item\">";

        # Toggle review for page mode
        if (($page_mode == "yes") && ($include_review == "yes") && ($this->_review != "")) {
            # Plus sign (action: expand review)
            $o .= "<img onclick=\"toggle_review('review" . $this->_id . "'); return false\" src=\"$img_path" . "plus.gif\" alt=\"Show the review\"" . ($expand_review == "yes" ? " style=\"display: none\"" : "") . " />";

            # Minus sign (action: collapse review)
            $o .= "<img onclick=\"toggle_review('review" . $this->_id . "'); return false\" src=\"$img_path" . "minus.gif\" alt=\"Hide the review\"" . ($expand_review == "no" ? " style=\"display: none\"" : "") . " />";
        }

        # Movie title
        $o .= "<a class=\"url fn\" href=\"";
        $o .= (strlen($this->_replacement_url) > 0 ? $this->_replacement_url : $this->_url);
        $o .= "\" title=\"$this->_title\n";
        $o .= "Watched and reviewed on $this->_watched_on\">$title_short</a>\n";

        # Edit link
        if (!is_plugin_page()) {
            $user = wp_get_current_user();
            if ($user->ID && ($user->user_level >= 8)) {
                $o .= "<a class=\"edit\" href=\"" . get_settings('siteurl') . "/wp-admin/edit.php?page=wp_movie_ratings.php&amp;action=edit&amp;id=" . $this->_id . "\">e</a>\n";
            }
        }

        # Text rating
        $o .= "<span class=\"rating" . ($this->_rating == 9 ? " half_light" : "") . ($this->_rating == 10 ? " highlight" : "") . "\"><span class=\"value\">";
        $o .= ($five_stars_ratings == "yes" ? ($this->_rating / 2) : $this->_rating);
        $o .= "</span>/<span class=\"best\">";
        $o .= ($five_stars_ratings == "yes" ? "5" : "10");
        $o .= "</span></span>\n";

        # Admin options
        if (is_plugin_page()) {
            $o .= "<input class=\"button\" type=\"submit\" name=\"action\" value=\"edit\" />\n";
            $o .= "<input class=\"button\" type=\"submit\" name=\"action\" value=\"delete\" onclick=\"return delete_confirmation()\" />\n";
        }

        $o .= "</p>\n";

        $o .= "<acronym class=\"dtreviewed\" title=\"" . str_replace(" ", "T", $this->_watched_on) . "\">$this->_watched_on</acronym>\n";

        # Stars rating using images
        if ($text_ratings == "no") {
            $o .= "<div class=\"rating_stars\">\n";

            if ($five_stars_ratings == "yes") {
                for ($i=1; $i<6; $i++) {
                    if ($this->_rating == ($i*2 - 1)) {
						$img_name = "half_star" . ($highlight == "yes" ? ($this->_rating == 9 ? "_half_light" : "") : "") . ".gif";
						$img_alt = "+";
					}
                    else if ($this->_rating >= ($i*2)) {
						$img_name = "full_star" . ($highlight == "yes" ? ($this->_rating == 10 ? "_highlight" : "") . ($this->_rating == 9 ? "_half_light" : "") : "") . ".gif";
						$img_alt = "*";
					}
                    else { 
						$img_name = "empty_star.gif";
						$img_alt = "";
					}
					$o .= "<img src=\"$img_path" . "$img_name\" alt=\"$img_alt\" />\n";
                }
            } else {
                for ($i=1; $i<11; $i++) {
                    if ($this->_rating >= $i) {
						$img_name = "full_star" . ($highlight == "yes" ? ($this->_rating == 10 ? "_highlight" : "") . ($this->_rating == 9 ? "_half_light" : "") : "") . ".gif";
						$img_alt = "*";
					} else {
						$img_name = "empty_star.gif";
						$img_alt = "";
					}
					$o .= "<img src=\"$img_path" . "$img_name\" alt=\"$img_alt\" />\n";
                }
            }

            $o .= "</div>\n";
        }

        # Review
        if (($include_review == "yes") && ($this->_review != "")) {
            $o .= "<p class=\"description\" id=\"review" . $this->_id . "\"";
            if ($page_mode == "yes") $o .= " style=\"display: " . ($expand_review == "yes" ?    "block" : "none") . "\"";
            $o .= ">$this->_review</p>";
        }

        # hReview version
        $o .= "<span class=\"version\">0.3</span>";

        $o .= "</div>\n";

        if (is_plugin_page()) $o .= "</form>\n";

        return $o;
    }


    # Show html form
    function show_add_edit_form($action) {
        $five_stars_ratings = get_option("wp_movie_ratings_five_stars_ratings");
?>
<form method="post" action="">

<?php if ($action == "Update") echo "<input type=\"hidden\" name=\"id\" value=\"" . $this->_id . "\" />"; ?>

<table class="optiontable">

<?php  ?>
<tr valign="top">
<th scope="row"><label for="url">iMDB link:</label></th>
<td><input type="text" name="url" id="url" class="text" size="40" value="<?= $this->_url ?>" />
<br />
Must be a valid <a href="http://imdb.com/">imdb.com</a> link.</td>
</tr>

<?php if ($action == "Update") { ?>
<tr valign="top">
<th scope="row"><label for="title">Title:</label></th>
<td><input type="text" name="title" id="title" class="text" size="46" value="<?= $this->_title ?>" />
<br />
You <em>really</em> should not be editing the title.
</td>
</tr>
<?php } ?>

<tr valign="top">
<th scope="row"><label for="rating">Movie rating:</label></th>
<td>
<select name="rating" id="rating">
<?php
for($i=1; $i<11; $i++) {
    echo "<option value=\"$i\"";
    if (($i == $this->_rating) || (($i==7) && ($this->_rating == null))) echo " selected=\"selected\"";
    echo ">" . ($five_stars_ratings == "yes" ? ($i/2) : $i) . "</option>\n";
}
?>
</select>
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="review">Short review:</label></th>
<td>
<textarea name="review" id="review" rows="3" cols="45">
<?= $this->_review ?>
</textarea>
<br />
HTML code is allowed in the review.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="replacement_url">Replacement link:</label></th>
<td>
<input type="text" name="replacement_url" id="replacement_url" class="text" size="40" value="<?= $this->_replacement_url ?>" />
<br />
Type additional movie link if you don't want to display <a href="http://imdb.com/">imdb</a> links.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="watched_on">Watched on:</label></th>
<?php $watched_on = ($this->_watched_on == null ? $this->get_current_time() : $this->_watched_on); ?>
<td><input type="text" name="watched_on" id="watched_on" class="text" size="23" value="<?= $watched_on ?>" />
<br />
Remember to use correct date format (<code>YYYY-MM-DD HH:MM:SS</code>) when setting custom dates.</td>
</tr>

</table>

<p class="submit">
<?php if ($action == "Update") echo "<input type=\"submit\" name=\"action\" value=\"Delete &raquo;\" onclick=\"return delete_confirmation()\" />\n"; ?>
<input type="submit" name="action" value="<?= $action ?> movie rating &raquo;" />
</p>

</form>

<?php
    }
}
?>