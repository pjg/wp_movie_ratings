# WP Movie Ratings

WP Movie Ratings is a wordpress plugin that makes rating movies very easy. At
its core is a bookmarklet, which combined with the Internet Movie Database
(imdb.com) and a little bit of AJAX magic lets you rate movies with just
one click. Also, there is no need to write the title of the movie as it is
automatically fetched from imdb. Optionally, you can also write a short review
for each movie. The output from this plugin is a list of recently watched
movies, which you can put anywhere on your blog (it's a matter of one simple
function call from the template).

The published movie reviews are hReview (http://microformats.org/wiki/hreview)
compliant.

Plugin page: http://pawelgoscicki.com/projects/wp-movie-ratings/


## Requirements

* Wordpress 2.7 or newer
* FTP, SSH or SCP access to your wordpress blog (so you can upload this
  plugin).


## Installation

1. Download the plugin.

2. Extract the contents of the .tar.gz/.zip file into the Wordpress plugins
   directory (usually wp-content/plugins). Alternatively you can upload
   them using ftp. You should have a new directory there called
   wp_movie_ratings (just along the akismet directory)

3. Activate the plugin by going into your administration panel and selecting
   Plugins from the menu and clicking the Activate button for the WP Movie
   Ratings.

4. Go to the Manage section in the administration panel and see the new Movies
   menu option there (you'll find the bookmarklet there too). Rate at least
   one movie (using the bookmarklet or the administration page).

5. Go to the Options section in the administration panel and under the Movies
   tab customize this plugin's options.

6. Customize your theme:

   * go into Widget Area
   * click "Add a Widget"
   * click on "Movie Ratings"
   * click on "Save & Publish"

   If you want to have movie ratings listed in the sidebar, you must, of
   course, edit the sidebar.php file from your current theme. Alternatively
   you can create a new post/page and type it there:

   You should also create a page listing all movie reviews. Create a new Page in
   Wordpress and put the following as the contents:

   `[[wp_movie_ratings_page]]`

   It will create a listing of all rated movies sorted by title.

7. Go to your blog and see the movie ratings!


## Upgrading

1. Download the newest version of the plugin
   (http://pawelgoscicki.com/files/wp_movie_ratings.tar.gz).

2. Go to the Plugins section of the administration panel and deactivate
   the WP Movie Ratings plugin. Don't worry as it will NOT delete any movie
   ratings.

3. Go to the wp_movie_ratings plugins directory and delete all of the plugin
   files (should be wp-content/plugins/wp_movie_ratings). It will NOT delete
   any movie ratings. Be aware though, that if you've made any changes to the
   plugin, you might want to consider a backup.

4. Extract the contents of the .tar.gz/.zip file into the wp_movie_ratings
   directory (wp-content/plugins/wp_movie_ratings).

5. Go to the Plugins section of the administration panel and activate the
   WP Movie Ratings plugin (notice that higher version number of the plugin).
   The deactivation/activation cycle is required because of the new options
   that need to be written into the wordpress database (during activation).

6. Enjoy life with the newest version of the WP Movie Ratings plugin.


## License

Copyright (c) 2006-2019 by Paweł Gościcki, http://pawelgoscicki.com/

Available under the GNU General Public License (GPL) version 2 or later.
http://www.gnu.org/licenses/gpl.html

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
