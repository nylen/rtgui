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

// By default, only completed torrents returned.
// To change, use:
//   rssfeed.php?view=xxx
// Where xxx=
//   main  (all torrents)
//   started
//   stopped
//   complete
//   incomplete
//   seeding

include "config.php";
include "functions.php";
import_request_variables("gp","r_");

if (!isset($r_view)) {
   $r_view="complete";
}

// header:
header('Content-Type: text/xml');
echo "<?xml version=\"1.0\"?>";
echo "<rss version=\"2.0\">";
echo "<channel>";
echo "<title>rtGui rss feed</title>";
echo "<description>Latest info from your rTorrent/rtGui system</description>";
echo "<generator>rtGui - http://rtgui.googlecode.com/ </generator>";
echo "<link>".$rtguiurl."</link>";
echo "<lastBuildDate>".date("r")."</lastBuildDate>";

$data=get_full_list($r_view);

if (is_array($data)) {
   $sortkey="state_changed";
   usort($data,'sort_matches_desc');
   $last=0;
   foreach($data AS $item) {
      if ($item['state_changed']>$last) $last=$item['state_changed'];
   }
   echo "<lastBuildDate>".date("r",$last)."</lastBuildDate>";

   foreach($data AS $item) {
      echo "<item>";
      echo "<title>".($item['complete']==1 ? "[Complete] " : "[Incomplete] ").htmlspecialchars($item['name'])."</title>";
      echo "<description>";
      echo htmlspecialchars($item['tied_to_file'])." (".format_bytes($item['size_bytes']).")";
      echo "</description>";
      echo "<pubDate>".date("r",$item['state_changed'])."</pubDate>";
      echo "<guid>".$rtguiurl."view.php?hash=".$item['hash']."</guid>";
      echo "</item>";
   }
}
echo "</channel>";
echo "</rss>";
?>
