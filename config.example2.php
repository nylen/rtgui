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

// Site title (change from rtGui if you have multiple)
$site_title = 'rtGui (main)';

// rtorrent 'watch' directory (used for upload torrent)
$watchdir="/media/bit.torrents/";

// Path to report disk usage
$downloaddir="/media/1000/";

// Threshold for disk usage alert (%)
$alertthresh=15;

// Time between ajax calls - default 5000 (5 secs).   Disable with 0
$refresh_interval = 5000;

// URL to your rtGui installation (used in RSS feed).  Include trailing slash.
$rtguiurl="http://".$_SERVER["HTTP_HOST"]."/rtgui/main/";

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
$all_groups = array('music', 'other-music', 'linux', 'windows', 'porn', 'other');
$default_group = 'music';
function get_torrent_group($t) {
  return basename($t['is_multi_file'] ? dirname($t['directory']) : $t['directory']);
}

/* Define a function that will be run every time a page is requested.  It can be used to
 * check if rTorrent is running, or to mount the rTorrent directories if rTorrent is
 * running on another machine.
 */
// function on_page_requested() { ... }

// Define some links that will be shown in the header
$header_links = array(
  'htpc' => '../htpc/',
  'music users' => '/music-users/'
);

// Define an action to take when adding a torrent
function on_add_torrent($name, $hash, $group, $filename) {
  if($group == 'other-music'
  && $fp = fopen('/media/rtorrent/users.txt', 'ab')) {
    $user = $_SERVER['REMOTE_USER'];
    // This is probably for Drew
    if(!$user || $user == 'james') {
      $user = 'drew';
    }
    fwrite($fp, "$hash,$user,$name\n");
    fclose($fp);
  }
}

?>
