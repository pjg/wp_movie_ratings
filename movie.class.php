<?php

class Movie {
  var $_id;               # 1 (database id for movie rating)
  var $_url;              # https://www.imdb.com/title/tt0133093/
  var $_url_short;        # 0133093
  var $_replacement_url;  # https://www.rottentomatoes.com/m/matrix
  var $_title;            # The Matrix (1999)
  var $_rating;           # 10
  var $_review;           # Truly a masterpiece.
  var $_watched_on;       # 2006-03-01 23:15

  var $_wpdb;             # wordpress database handler
  var $_table;            # database table name (usually wp_movie_ratings)


  # constructor
  function __construct($url=null, $rating=null, $review=null, $title=null, $replacement_url=null, $watched_on=null, $id=null) {
    global $wpdb;
    $this->_url = rawurldecode(trim($url));
    $this->_rating = intval($rating);
    $this->_review = trim($review);
    $this->_title = $title;
    $this->_replacement_url = rawurldecode(trim($replacement_url));
    $this->_watched_on = $watched_on;
    $this->_id = $id;
    $this->_wpdb = $GLOBALS['wpdb'];
    $this->_table = $wpdb->prefix . "movie_ratings";
  }


  # check if we have a valid movie rating
  function parse_rating() {
    if (($this->_rating >= 0) && ($this->_rating < 11)) $msg = "";
    else $msg = '<div id="message" class="error fade"><p><strong>Error: wrong movie rating.</strong></p></div>';
  }


  # check if we have a valid imdb.com link
  function parse_imdb_url() {
    if (preg_match("/^https?:\/\/(.*)imdb\.com\/title\/tt([0-9]{7,8})(\/){0,1}$/i", $this->_url, $matches)) {
      $this->_url_short = $matches[2];
      $this->_url = 'https://www.imdb.com/title/tt' . $this->_url_short . '/';
      return "";
    } else return '<div id="message" class="error fade"><p><strong>Error: wrong imdb link.</strong></p></div>';
  }


  # get title from imdb.com
  function get_title() {
    $req = new WP_HTTP_Request($this->_url);
    $imdb = $req->DownloadToString();
    preg_match('/<meta.+?content=\"(.+?) - IMDb\"/i', $imdb, $title_matches);
    $this->_title = $title_matches[1];

    if (empty($this->_title)) return '<div id="message" class="error fade"><p><strong>Error while retrieving the title of the movie from imdb.</strong></p></div>';
    else return "";
  }


  # get current time using Wordpress' time offset
  function get_current_time() {
    # 2006-03-05 01:03:44
    return gmstrftime("%Y-%m-%d %H:%M:%S", time() + (3600 * get_option("gmt_offset")));
  }


  # save movie rating to the database
  function save() {
    $this->_title = wp_movie_ratings_real_unescape_string($this->_title);
    $title_screen = wp_movie_ratings_real_escape_string($this->_title, array("encode_html" => true, "output" => "screen"));
    $title_db = wp_movie_ratings_real_escape_string($this->_title, array("encode_html" => true, "output" => "database"));

    # encode &amp; separately for review (which allows HTML code) (this is important when adding movies via admin panel)
    # and it fucking suxx
    $this->_review = str_replace(" & ", " &amp; ", wp_movie_ratings_real_unescape_string($this->_review));
    $review_db = wp_movie_ratings_real_escape_string($this->_review, array("output" => "database"));

    # encode &amp; in replacement url
    $replacement_url = str_replace("&amp;", "&", $this->_replacement_url); # so we a 'common base'
    $replacement_url = str_replace("&", "&amp;", $replacement_url); # encode all '&'

    $watched_on = ($this->_watched_on == null ? $this->get_current_time() : $this->_watched_on);

    $this->_wpdb->hide_errors();
    $this->_wpdb->query("INSERT INTO $this->_table (title, imdb_url_short, rating, review, replacement_url, watched_on) VALUES ('$title_db', '$this->_url_short', $this->_rating, '$review_db', '$replacement_url', '$watched_on');");

    $this->_wpdb->show_errors();

    if ($this->_wpdb->rows_affected == 1) {
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
    # check user submitted rating & imdb url
    $this->_url = wp_movie_ratings_utf8_raw_url_decode(trim($_POST["url"]));
    $this->_rating = intval($_POST["rating"]);

    # wrong rating
    $msg = $this->parse_rating();
    if (!empty($msg)) return $msg;

    # wrong imdb link (if entered)
    if (!empty($this->_url)) {
      $msg = $this->parse_imdb_url();
      if (!empty($msg)) return $msg;
    }

    $this->_title = wp_movie_ratings_real_unescape_string($_POST["title"]);
    $title_screen = wp_movie_ratings_real_escape_string($this->_title, array("encode_html" => true, "output" => "screen"));
    $title_db = wp_movie_ratings_real_escape_string($this->_title, array("encode_html" => true, "output" => "database"));

    # str_replace here is just so fucking wrong... (no idea how to do it better, though)
    $this->_review = str_replace(" & ", " &amp; ", $_POST["review"]);
    $review_db = wp_movie_ratings_real_escape_string($this->_review, array("output" => "database"));

    $this->_replacement_url = wp_movie_ratings_utf8_raw_url_decode(trim($_POST["replacement_url"]));
    $this->_watched_on = wp_movie_ratings_real_unescape_string($_POST["watched_on"]);
    $watched_on_db = wp_movie_ratings_real_escape_string($this->_watched_on, array("output" => "database"));

    $sql = "UPDATE $this->_table SET imdb_url_short='$this->_url_short', title='$title_db', rating=$this->_rating, review='$review_db', replacement_url='$this->_replacement_url', watched_on='$watched_on_db' WHERE id=$this->_id LIMIT 1";

    $this->_wpdb->query($sql);
    $this->_wpdb->show_errors();

    if ($this->_wpdb->rows_affected > 0) {
      return '<div id="message" class="updated fade"><p><strong>' . $title_screen . ' rated ' . $this->_rating . '/10 updated.</strong></p></div>';
    } else {
      return '<div id="message" class="error fade"><p><strong>Error: ' . $title_screen . ' not updated.</strong></p></div>';
    }
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
  function get_all_movies($order_by = "title", $order_direction = "ASC", $start = null, $limit = null) {
    return $this->get_movies(array("type" => "all", "order_by" => $order_by, "order_direction" => $order_direction, "start" => $start, "limit" => $limit));
  }


  # get only 'not rated' movies
  function get_not_rated_movies($order_by = "title", $order_direction = "ASC", $start = null, $limit = null) {
    return $this->get_movies(array("type" => "not_rated", "order_by" => $order_by, "order_direction" => $order_direction, "start" => $start, "limit" => $limit));
  }


  # get only 'rated' movies
  function get_rated_movies($order_by = "title", $order_direction = "ASC", $start = null, $limit = null) {
    return $this->get_movies(array("type" => "rated", "order_by" => $order_by, "order_direction" => $order_direction, "start" => $start, "limit" => $limit));
  }


  # get movies
  # options:
  #   "type" => "one"/"latest"/"all"/"not_rated"/"rated"
  #   "count" => 1-n
  #   "order_by" => "title"/"watched_on"
  #   "order_direction" => "ASC"/"DESC"
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
      $order_direction = (isset($options["order_direction"]) ? $options["order_direction"] : "DESC");
      $count = (isset($options["count"]) ? $options["count"] : 20);
    }

    if ($type == "all") {
      $order_by = (isset($options["order_by"]) ? $options["order_by"] : "title");
      $order_direction = (isset($options["order_direction"]) ? $options["order_direction"] : "ASC");
      $start = intval((isset($options["start"]) && $options["start"] != null) ? $options["start"] : 0);
      $limit = intval((isset($options["limit"]) && $options["limit"] != null) ? $options["limit"] : get_option("wp_movie_ratings_pagination_limit"));
    }

    if (($type == "not_rated") || ($type == "rated")) {
      $order_by = (isset($options["order_by"]) ? $options["order_by"] : "title");
      $order_direction = (isset($options["order_direction"]) ? $options["order_direction"] : "ASC");
      $start = intval((isset($options["start"]) && $options["start"] != null) ? $options["start"] : 0);
      $limit = intval((isset($options["limit"]) && $options["limit"] != null) ? $options["limit"] : get_option("wp_movie_ratings_count"));
    }

    # Bulding SQL query
    $date_format = "%Y-%m-%d %H:%i" . ($type == "one" ? ":%s" : "");
    $sql  = "SELECT id, title, imdb_url_short, rating, review, replacement_url, DATE_FORMAT(watched_on, '$date_format') AS watched_on FROM $this->_table ";
    if ($type == "one") {
      $sql .= " WHERE id=$id ";
    } elseif ($type == "not_rated") {
      $sql .= " WHERE rating=0 ";
    } elseif ($type == "rated") {
      $sql .= " WHERE rating>0 ";
    }

    # default second sort is by date -> important when sorting by rating, so we get the newest movies with same rating first
    if ($type != "one") $sql .= " ORDER BY " . $order_by . " " . $order_direction . ", watched_on DESC ";

    # limit for latest movies
    if (in_array($type, array("latest", "one"))) $sql .= " LIMIT " . intval($count);

    # pagination
    if (($type == "all") || ($type == "not_rated") || ($type == "rated")) $sql .= " LIMIT $start, $limit;";

    $results = $this->_wpdb->get_results($sql);

    if ($results) {
      foreach ($results as $r) {
        $url = (!empty($r->imdb_url_short) ? "https://www.imdb.com/title/tt" . $r->imdb_url_short . "/" : "");
        $movie = new Movie($url, $r->rating, wp_movie_ratings_real_unescape_string($r->review), wp_movie_ratings_real_unescape_string($r->title), $r->replacement_url, $r->watched_on, $r->id);
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
    $char_limit = (isset($options["char_limit"]) ? $options["char_limit"] : get_option("wp_movie_ratings_char_limit"));

    if (!is_plugin_page()) {
      # shorten the title
      if (strlen($this->_title) <= $char_limit) $title_short = $this->_title;
      else {
        # cut at limit
        $title_short = substr($this->_title, 0, $char_limit);

        # find last space char: " "
        $last_space_position = strrpos($title_short, " ");

        # cut at the last space and add three dots
        $title_short = substr($title_short, 0, $last_space_position) . "...";
      }
    } else $title_short = $this->_title;

    # Admin options
    if (is_plugin_page()) $o .= "<form method=\"post\" action=\"\">\n";

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
    $is_url = ((!empty($this->_replacement_url) || !empty($this->_url)) ? true : false);
    if ($is_url) {
      $o .= "<a class=\"url fn\" href=\"";
      $o .= (!empty($this->_replacement_url) ? $this->_replacement_url : $this->_url);
      $o .= "\" title=\"$this->_title\n";
      $o .= "Watched and reviewed on $this->_watched_on\">";
    }
    $o .= $title_short;
    if ($is_url) $o .= "</a>";
    $o .= "\n"; # !important (gives space after movie's title regardless of the link)

    # Edit link
    if (!is_plugin_page()) {
      $user = wp_get_current_user();
      if ($user->ID && ($user->user_level >= 8)) {
        $o .= "<a class=\"edit\" href=\"" . get_settings('siteurl') . "/wp-admin/tools.php?page=wp_movie_ratings_management&amp;action=edit&amp;id=" . $this->_id . "\">e</a>\n";
      }
    }

    # Text rating (will be displayed in the administration panel; will be hidden via css in the blog if text_ratings options is not set; for movies rated 0 (not rated) span ratings tags will not be printed in the html code at all (on the blog)
    if (is_plugin_page() && ($this->_rating == 0)) {
      $o .= "<span class=\"rating\">Not rated</span>";
    } elseif ($this->_rating != 0) {
      $o .= "<span class=\"rating" . ($this->_rating == 9 ? " half_light" : "") . ($this->_rating == 10 ? " highlight" : "") . "\"><span class=\"value\">";
      $o .= ($five_stars_ratings == "yes" ? ($this->_rating / 2) : $this->_rating);
      $o .= "</span>/<span class=\"best\">";
      $o .= ($five_stars_ratings == "yes" ? "5" : "10");
      $o .= "</span></span>\n";
    }

    # Admin options
    if (is_plugin_page()) {
      $o .= "<input type=\"hidden\" name=\"id\" value=\"" . $this->_id . "\" />\n";
      $o .= "<input class=\"button\" type=\"submit\" name=\"action\" value=\"edit\" />\n";
      $o .= "<input class=\"button\" type=\"submit\" name=\"action\" value=\"delete\" onclick=\"return delete_confirmation()\" />\n";
    }

    $o .= "</p>\n";

    $o .= "<acronym class=\"dtreviewed\" title=\"" . str_replace(" ", "T", $this->_watched_on) . "\">$this->_watched_on</acronym>\n";

    # Stars rating using images
    if ($text_ratings == "no" && ($this->_rating != 0)) {
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

  # light version of movie listing (used in widget)
  function show_light($img_path, $options = array()) {
    # output
    $o = "";

    # parse arugments
    $text_ratings = (isset($options["text_ratings"]) ? $options["text_ratings"] : get_option("wp_movie_ratings_text_ratings"));
    $five_stars_ratings = (isset($options["five_stars_ratings"]) ? $options["five_stars_ratings"] : get_option("wp_movie_ratings_five_stars_ratings"));
    $highlight = (isset($options["highlight"]) ? $options["highlight"] : get_option("wp_movie_ratings_highlight"));

    # Movie title
    $is_url = ((!empty($this->_replacement_url) || !empty($this->_url)) ? true : false);
    if ($is_url) {
      $o .= "<a href=\"";
      $o .= (!empty($this->_replacement_url) ? $this->_replacement_url : $this->_url);
      $o .= "\" title=\"$this->_title\n";
      $o .= "(watched and reviewed on $this->_watched_on)\">";
    }

    $o .= "<small>" . $this->_title;

    if ($is_url) $o .= "</a>";
    $o .= "\n"; # !important (gives space after movie's title regardless of the link)

    # Edit link
    if (!is_plugin_page()) {
      $user = wp_get_current_user();
      if ($user->ID && ($user->user_level >= 8)) {
        $o .= "<a class=\"edit\" href=\"" . get_settings('siteurl') . "/wp-admin/tools.php?page=wp_movie_ratings_management&amp;action=edit&amp;id=" . $this->_id . "\">e</a>\n";
      }
    }

    $o .= "</small>\n";

    # Text rating
    if (($text_ratings == "yes") && ($this->_rating != 0)) {
      $o .= "<span class=\"rating" . ($this->_rating == 9 ? " half_light" : "") . ($this->_rating == 10 ? " highlight" : "") . "\"><span class=\"value\">";
      $o .= ($five_stars_ratings == "yes" ? ($this->_rating / 2) : $this->_rating);
      $o .= "</span>/<span class=\"best\">";
      $o .= ($five_stars_ratings == "yes" ? "5" : "10");
      $o .= "</span></span>\n";
    }

    # Stars rating using images
    if ($text_ratings == "no" && ($this->_rating != 0)) {
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
    if ($this->_review != "") {
      $o .= "<p><small>" . $this->_review . "</small></p>";
    }

    return $o;
  }


  # Show html form
  function show_add_edit_form($action) {
    $five_stars_ratings = get_option("wp_movie_ratings_five_stars_ratings");
?>
<form method="post" action="">

<?php if ($action == "Update") echo "<input type=\"hidden\" name=\"id\" value=\"" . $this->_id . "\" />"; ?>

<table class="form-table">

<tr valign="top">
<th scope="row"><label for="url">iMDB link:</label></th>
<td><input type="text" name="url" id="url" class="text" size="50" value="<?php echo $this->_url ?>" />
<br />
Must be a valid <a href="https://www.imdb.com/">imdb</a> link (but may be left empty so that the <strong>replacement link</strong> is used instead).</td>
</tr>

<tr valign="top">
<th scope="row"><label for="title">Title:</label></th>
<td><input type="text" name="title" id="title" class="text" size="50" value="<?php echo $this->_title ?>" />
<br />
<?php if ($action == "Update") { ?>
You <em>really</em> should not be editing the title.
<?php } else { ?>
If you leave the title empty and enter a correct <a href="https://www.imdb.com/">imdb</a> link, the title will be fetched automatically.
<?php } ?>
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="rating">Movie rating:</label></th>
<td>
<select name="rating" id="rating">
<?php
for($i=0; $i<11; $i++) {
  echo "<option value=\"$i\"";
  if ($this->_rating == $i) echo " selected=\"selected\"";
  echo ">";
  if ($i == 0) echo "Not yet rated"; else echo ($five_stars_ratings == "yes" ? ($i/2) : $i);
  echo "</option>\n";
}
?>
</select>
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="review">Short review:</label></th>
<td>
<textarea name="review" id="review" cols="80" rows="5">
<?php echo $this->_review ?>
</textarea>
<br />
HTML code allowed.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="replacement_url">Replacement link:</label></th>
<td>
<input type="text" name="replacement_url" id="replacement_url" class="text" size="50" value="<?php echo $this->_replacement_url ?>" />
<br />
Type additional movie link if you don't want to display <a href="https://www.imdb.com/">imdb</a> links or when the movie is not listed on imdb.
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="watched_on">Watched on:</label></th>
<?php $watched_on = ($this->_watched_on == null ? $this->get_current_time() : $this->_watched_on); ?>
<td><input type="text" name="watched_on" id="watched_on" class="text" size="25" value="<?php echo $watched_on ?>" />
<br />
Remember to use correct date format (<code>YYYY-MM-DD HH:MM:SS</code>) when setting custom dates.</td>
</tr>

</table>

<p class="submit">
<?php if ($action == "Update") echo "<input class=\"button\" type=\"submit\" name=\"action\" value=\"Delete movie review »\" onclick=\"return delete_confirmation()\" />\n"; ?>
<input class="button-primary" type="submit" name="action" value="<?php echo $action ?> movie review »" />
</p>

</form>

<?php
  }
}
?>
