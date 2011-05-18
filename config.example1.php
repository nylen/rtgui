<?php
//
//  This file is part of rtGui.  http://rtgui.googlecode.com/
//  Copyright (C) 2007-2008 Simon Hall.
//
//  rtGui is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, either version 3 of the License, or
//  (at your option) any later version.
//
//  rtGui is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with rtGui.  If not, see <http://www.gnu.org/licenses/>.


// Connection information for your local RPC/rTorrent connection:
$scgi_local = '/tmp/rtorrent-james.sock';
$scgi_timeout = 5; // seconds

// Site title (change from 'rtGui' if you have multiple)
$site_title = 'rtGui';

// rtorrent 'watch' directory (used for uploading torrents)
$watch_dir = '/media/bit.torrents/';

// Start download immediately after loading torrent
$load_start = true;

// Default values for settings that the user can change
$default_user_settings = array(
  // Theme to use for site, unless it is changed by the user or the user-agent
  'theme' => 'default',

  // Time between ajax calls - default 5000 (5 secs).  Disable with 0
  'refresh_interval' => 5000,

  // Default sort variable
  'sort_var' => 'name',

  // Whether to sort descending by default ('yes' or 'no')
  'sort_desc' => 'no'
);

// Path to report disk usage
$download_dir = '/media/1000/';

// Threshold for disk usage alert (%)
$disk_alert_threshold = 15;

// Speeds (in KB) for the download/upload cap in the settings dialog.
$cap_speeds = array(
  5, 10, 15, 20, 30, 40, 50, 60, 70, 80, 90,
  100, 125, 150, 200, 250, 300, 400,
  500, 600, 700, 800, 900,
  1024, 1536, 2048, 5120, 10240
);

// Enable debug tabs
$debug_mode = false;

// Tracker color hilighting...
// Format is array(hexcolor, URL, URL, ...) The URL is a string to match identifiy tracker URL
// Add as many arrays as needed.
$tracker_highlight_default = '#900';   // Default color
$tracker_highlight[] = array('#990000', 'ibiblio.org', 'etree.org');
#$tracker_highlight[] = array('#006699', 'another.com', 'tracker.mytracker.net', 'mytracker.com');
#$tracker_highlight[] = array('#996600', 'moretrackers.com');
$tracker_highlight[] = array('#885500', 'what.cd');
$tracker_highlight[] = array('#436101', 'already.be');


// Define your RSS feeds here - you can have as many as you like.   Used in the feedreader
// Feed name, feed URL, Direct download links? (0/1)
$feeds[] = array('ibiblio.org', 'http://torrent.ibiblio.org/feed.php?blockid=3', 0);
$feeds[] = array('etree', 'http://bt.etree.org/rss/bt_etree_org.rdf', 0);
$feeds[] = array('Utwente', 'http://borft.student.utwente.nl/%7Emike/oo/bt.rss', 1);

// Date format to use for "date added" display (see http://php.net/date)
$date_added_format = 'n/j/Y'; # American format
#$date_added_format = 'j/n/Y'; # European format

// Netscape-format cookies file to be used when downloading .torrent files
$cookies_file = 'cookies.txt';

// Temporary directory to be used for adding .torrent files
$tmp_add_dir = 'tmp';

// Private storage directory to be used for storing information like torrent tags
$private_storage_dir = 'private';

// Define a list of tags that should always be shown in the tag editing menu and the
// add torrents dialog, even if no torrents are using them.
$always_show_tags = array('music', 'other-music', 'linux', 'windows', 'other');

/* If the get_watchdir_from_tags() function exists, it will be used to set the watch
 * directory for a newly added torrent.  It takes a single argument which is an array
 * of the tags that the user has added to this torrent.  Its default behavior should
 * be to return $watch_dir.
 *
 * NOTE: If this function exists and does NOT return $watch_dir for a given torrent,
 * then the program will behave as if $load_start above is set to false.  This means
 * that rTorrent must be set up to watch the destination directory for new files, and
 * there may be a delay of a few seconds in between when a torrent is added and when
 * it shows up in the list.
 */
//function get_watchdir_from_tags($tags) { ... }

/* Define a function that will be run every time a page is requested.  It can be used to
 * check if rTorrent is running, or to mount the rTorrent directories if rTorrent is
 * running on another machine.
 */
//function on_page_requested() { ... }

// Define an action to take when adding a torrent
//function on_add_torrent($name, $hash, $tags, $filename) { ... }

?>
