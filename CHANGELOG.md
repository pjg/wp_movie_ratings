# WP Movie Ratings CHANGELOG

## Unreleased

- compatibility with PHP v8
- support imdb IDs over 100 mln
- improve rendering performance for large lists when in page mode and sorting movies by `watched_on` date
- improve/fix rendering time of large lists when in page mode on recent Wordpress versions (6.3.3+) by converting plus/minus images to inlined SVGs

## Release 1.8 (2019-10-13)

- small visual tweaks to the widget
- make plugin work with https imdb links
- use www.imdb.com for all imdb links/operations (SSL certificate for akas.imdb.com no longer works)
- fix/tweak IMDb titles parsing
- support imdb IDs over 10 mln

## Relase 1.7 (2015-11-19)
- widgetization: added possiblity to display 'Recent ratings' in a widget
- fetch original movie titles from IMDb (ie: use akas.imdb.com instead of just imdb.com) and display links to akas.imdb.com (instead of imdb.com)
- fixed: make movies page pagination links XHTML standards compliant (& => &amp;)
- compatibility/deprecation fixes (some of them at least...)

## Release 1.6 (2010-10-14)

- full compatibility with Wordpress 2.7.*, 2.8.*, 2.9.* and 3.0.* (this release breaks compatibility with older versions)
- fixed: when 'only_rated' or 'only_unrated' option was used the 'count' argument the for wp_movie_ratings_show() function was ignored
- removed automatic pingerati.net pinging (pingerati.net is AWOL)
- fixed: fetching movie title from IMDb (after IMDb has changed their website)

## Release 1.5 (2008-05-18)

- added option to add movies without rating them (to create a list of owned just not yet seen dvds, for example) (only through the administration panel)
- added option to select all 'only_rated' or 'only_not_rated' movies using the wp_movie_ratings_show() function call
- char_limit option can now be passed as a parameter to the wp_movie_ratings_show() function call
- fixed problems making the movie reviews pages non XHTML compliant
- fixed error which prevented certain users from activating the plugin (the database table was not created)
- fixed XHTML validation error in the bookmarklet

## Release 1.4 (2006-11-18)

- fixed lots of rendering issues (problems in IE, Blue-k2 theme, Golden Gray theme, reviews not expanding, etc.)
- completely rewritten encoding module, so now international characters as well as HTML should work correctly
- fix for Markdown markup - you can now use the alternate tag: <!--wp_movie_ratings_page--> to create a movie ratings page
- new feature: if no imdb link is given, the plugin will not look for the movie's title on imdb. As a drawback, there can be now movies without imdb links (and without titles...)
- new feature: ability to edit imdb links for already rated movies
- new feature: added pagination in page mode (so movie reviews can now span among several pages)
- added option to disable pingerati.net pinging
- added option to highlight top rated movies
- added link from the recently rated movies list to the page with all movie ratings
- movies are now grouped together by month when sorting by view date in page mode
- added default movies sorting options for page mode
- added a new database field with optional link that is used instead of imdb
- added option to initially display expanded reviews in page mode
- added ability to edit movies directly from the displayed ratings list (in page mode too)

## Release 1.3 (2006-08-04)

- lots of CSS fixes to make the plugin compatible with more Wordpress themes
- added reviews when in page mode
- added pingerati.net pinging when adding/changing movie rating
- you can now edit the title of the movie
- added sorting options in page mode (sort by title, rating, view date)
- added error message when rating a movie using bookmarklet without being logged in
- text ratings now obey 5-stars ratings option and display appropriate ratings when this option is set
- admin page now respects 5-stars movie ratings when adding a new movie
- plugin options now feature additional descriptions explaining what each option means

## Release 1.2 (2006-07-14)

- you can now edit and delete your movie ratings
- added option to customize the dialogue title for movie ratings box via Wordpress options panel
- added option to display movie ratings using 5 stars instead of 10
- ability to set custom date the film was watched on (instead of assuming the current date) and display movie ratings based on this date instead of id
- new statistic: average movie rating for all rated movies
- fixed SQL errors when there are no movies rated
- fixed hardcoded wordpress table names which could have resulted in errors during installation and usage (no longer, hopefully)
- fixed plugin activation problems on Windows machines
- magic quotes/strip slashes fixes
- added separate page listing all movies (just create a new wordpress page and add this tag there: [[wp_movie_ratings_page]])

## Release 1.1 (2006-06-27)

- sidebar mode, where movie ratings are on a separate line, so the box can be as thin as 170px for star ratings (images) and 100px for text ratings
- renamed HTTPRequest class to WP_HTTP_Request to avoid conflicts with other HTTPRequest classes from other plugins
- new options panel which lets you set different display options (like the aforementioned sidebar mode)
- option to display movie ratings using just text as an alternative to the images with stars
- fixed CSS problems with Internet Explorer (now works properly even with IE 5.0)
- css inclusion moved to <head> so that the generated pages that include the output from this plugin can now be XHTML compliant
- removed nested SQL queries so that the plugin now works with older versions of MySQL

## Release 1.0.1 (2006-06-18)

- fixed division by zero bug in the statistics when there are no movies rated

## Release 1.0 (2006-06-15)

- initial release
