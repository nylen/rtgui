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

// rtorrent .torrent file directory (where new torrents' .torrent files will go)
$torrent_dir = '/media/bit.torrents/';

// rtorrent download directory (where new torrents' data will go)
$download_dir = '/media/rtorrent/';

// Start downloading new torrents immediately
$load_start = true;

// Default values for settings that the user can change in the web interface
$default_user_settings = array(
  // Theme to use for site, unless it is changed by the user or the user-agent
  'theme' => 'default',

  // Whether to show hidden torrents (if allowed by $can_hide_unhide below)
  'show_hidden' => true,

  // Time between ajax calls - default 5000 (5 secs).  Disable with 0
  'refresh_interval' => 5000,

  // Default sort variable
  'sort_var' => 'name',

  // Whether to sort descending by default ('yes' or 'no')
  'sort_desc' => 'no'
);

// Path to report disk usage
$disk_usage_dir = '/media/1000/';

// Threshold for disk usage alert (%)
$disk_alert_threshold = 15;

// Directory that serves as the dir browser root - users will not be allowed to
// browse below here.
$dir_browser_root = '/media/rtorrent';

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
$always_show_tags = array('music', 'other-music', 'linux', 'windows', 'mac', 'other', 'books');

// Determine whether to allow hiding or unhiding torrents.
$can_hide_unhide = ($_SERVER['REMOTE_USER'] == 'james' || !$_SERVER['REMOTE_USER']);

if ($can_hide_unhide) {
  $always_show_tags[] = '_hidden';
}

/* If the get_torrent_dir_from_tags() function exists, it will be used to set
 * the .torrent file directory for a newly added torrent.  It takes a single
 * argument which is an array of the tags that the user has added to this
 * torrent.  Its default behavior should be to return $torrent_dir.
 */
//function get_torrent_dir_from_tags($tags) { ... }

/* If the get_download_dir_from_tags() function exists, it will be used to set
 * the download directory for a newly added torrent.  It takes a single
 * argument which is an array of the tags that the user has added to this
 * torrent.  It should return false to indicate that the default directory
 * should be used for a new torrent.
 */
//function get_download_dir_from_tags($tags) { ... }

/* If the get_custom1_from_tags() function exists, it will be used to set the
 * custom1 value for a newly added torrent.  It takes a single argument which
 * is an array of the tags that the user has added to this torrent.  It should
 * return false to indicate that the custom1 value should not be set for a new
 * torrent.
 */
//function get_custom1_from_tags($tags) { ... }

/* The get_*_from_tags functions above may be implemented in such a way that
 * they return directories that have not been previously used by rTorrent and
 * therefore may not exist.  The program can create them, but it needs to know
 * the appropriate permissions.  Note that both the webserver user and the user
 * running rTorrent will need to write to the created directory.
 */
// The mode to set for newly created directories, or false for no change
// NOTE: This must be a string containing a mode ('755', not 0755 or 755)!
$create_dir_mode = '775';
// The group that should own newly created directories, or false for no change
// NOTE: The user that runs rTorrent MUST be a member of this group!
$create_dir_group = 'rtorrent';

/* Define a function that will be run every time a page is requested.  For
 * example, it can be used to mount the rTorrent directories if rTorrent is
 * running on another machine.
 */
//function on_page_requested() { ... }

?>
