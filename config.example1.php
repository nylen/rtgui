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
$scgi_host = 'htpc';
$scgi_port = 5202;
// To connect to a local socket:
// $scgi_local = '/path/to/socket/file';
$scgi_timeout = 5; // seconds

// Site title (change from rtGui if you have multiple)
$site_title = 'rtGui (htpc)';

// rtorrent 'watch' directory (used for upload torrent)
$watchdir="/media/htpc/bit.torrents/";

// Path to report disk usage
$downloaddir="/media/htpc/bit.torrents/";

// Threshold for disk usage alert (%)
$alertthresh=15;

// Time between ajax calls - default 5000 (5 secs).   Disable with 0
$refresh_interval = 5000;

// URL to your rtGui installation (used in RSS feed).  Include trailing slash.
$rtguiurl="http://".$_SERVER["HTTP_HOST"]."/rtgui/";

// Speeds for the download cap settings dialog.
$defspeeds=array(5,10,15,20,30,40,50,60,70,80,90,100,125,150,200,250,300,400,500,600,700,800,900,1000,1500,2000,5000,10000);

// Start download immediately after loading torrent
$load_start=TRUE;

// Enable debug tabs
#$debugtab=TRUE;

// Tracker colour hilighting...
// Format is array(hexcolour, URL, URL, ...) The URL is a string to match identifiy tracker URL
// Add as many arrays as needed.
$tracker_hilite_default="#900";   // Default colour
$tracker_hilite[]=array("#990000","ibiblio.org","etree.org");
#$tracker_hilite[]=array("#006699","another.com","tracker.mytracker.net","mytracker.com");
#$tracker_hilite[]=array("#996600","moretrackers.com");
$tracker_hilite[]=array("#885500","what.cd");
$tracker_hilite[]=array("#436101","already.be");


// Define your RSS feeds here - you can have as many as you like.   Used in the feedreader
// Feed name, feed URL, Direct download links? (0/1)
$feeds[]=array("ibiblio.org","http://torrent.ibiblio.org/feed.php?blockid=3",0);
$feeds[]=array("etree","http://bt.etree.org/rss/bt_etree_org.rdf",0);
$feeds[]=array("Utwente","http://borft.student.utwente.nl/%7Emike/oo/bt.rss",1);

// Date format to use for "date added" display (see http://php.net/date)
$date_added_format = 'n/j/Y'; # American format
#$date_added_format = 'j/n/Y'; # European format

// Netscape-format cookies file to be used when downloading .torrent files
$cookies_file = 'cookies.txt';

// Temporary directory to be used for adding .torrent files
$tmp_add_dir = 'tmp';


/* Define whether to use torrent groups.  A torrent group should split your torrents
 * up into categories, and should not change over the lifetime of the torrent.  A
 * good use case for torrent groups is if you have rTorrent watching several folders
 * for .torrent files, and putting the downloads in separate places.  The provided
 * get_torrent_group() function will handle that situation.
 */
$use_groups = true;
$all_groups = array('tv', 'movies');
$default_group = 'tv';
function get_torrent_group($t) {
  return basename($t['is_multi_file'] ? dirname($t['directory']) : $t['directory']);
}

/* If rTorrent is running on another PC, you can define the get_local_torrent_path($path)
 * function to change a remote path for a .torrent file into a local path (this means the
 * remote directory containing your .torrent files has to be mounted on the web server).
 * This will make "date added" work properly since it is based on the modification date
 * of the .torrent file tied to each download.
 */
function get_local_torrent_path($path) {
  return str_replace('/media/bit.torrents/', '/media/htpc/bit.torrents/', $path);
}

/* Define a function that will be run every time a page is requested.  It can be used to
 * check if rTorrent is running, or to mount the rTorrent directories if rTorrent is
 * running on another machine.
 */
function on_page_requested() {
  require_once 'session.php';
  rtgui_session_start();
  if(!$_SESSION['mounted']) {
    exec('mount | grep //htpc/bit.torrents && mount | grep //htpc/rtorrent || sudo mount-htpc 2>&1', $out, $err);
    if($err) {
      die('<h1>Could not mount rTorrent directories</h1><pre>' . implode("\n", $out) . '</pre>');
    }
    $_SESSION['mounted'] = true;
  }
}

/* Functions to make the directory browser work on a remote PC (the functions provided
 * here rely on the fact that /media/rtorrent is mounted at /media/htpc/rtorrent)
 */
function dirbrowser_translate($dir) {
  // Just a helper function - not called by dirbrowser code
  return str_replace('/media/rtorrent/', '/media/htpc/rtorrent/', $dir);
}
function dirbrowser_scandir($dir) {
  return @scandir(dirbrowser_translate($dir));
}
function dirbrowser_isdir($dir) {
  return is_dir(dirbrowser_translate($dir));
}
function dirbrowser_isrootdir($dir) {
  return (rtrim($dir, '/') == '/media/rtorrent');
}

// Define some links that will be shown in the header
$header_links = array(
  'main' => '../main/'
);

?>
