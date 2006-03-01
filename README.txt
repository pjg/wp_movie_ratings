
Database table structure:
-------------------------

CREATE TABLE `wp_movies` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `imdb_url_short` varchar(10) NOT NULL default '',
  `rating` tinyint(2) unsigned NOT NULL default '0',
  `created_on` timestamp NOT NULL default '0000-00-00 00:00:00',
  `updated_on` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
