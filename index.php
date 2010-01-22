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

$execstart = microtime(true);
session_start();
include "config.php";
include "functions.php";
import_request_variables("gp", "r_");

// Try using alternative XMLRPC library from http://sourceforge.net/projects/phpxmlrpc/
// (see http://code.google.com/p/rtgui/issues/detail?id=19)
if(!function_exists('xml_parser_create')) {
   include("xmlrpc.inc");
   include("xmlrpc_extension_api.inc");
}

// Sort out the session variables for sort order, sort key and current view...
$_SESSION['sortkey']        = (isset($r_setsortkey)       ? $r_setsortkey       : "name");
$_SESSION['sortord']        = (isset($r_setsortord)       ? $r_setsortord       : "asc");
$_SESSION['view']           = (isset($r_setview)          ? $r_setview          : "main");
$_SESSION['tracker_filter'] = (isset($r_settrackerfilter) ? $r_settrackerfilter : "");

if(isset($r_reload)) {
  unset($_SESSION['lastget']);
}
if(!isset($_SESSION['refresh'])) {
  $_SESSION['refresh'] = $defaultrefresh;
}
if(!isset($r_debug)) {
  $r_debug = 0;
}

$globalstats = get_global_stats();
$rates = get_global_rates();
$global_down_rate = format_bytes($rates[0]['ratedown'], "0 B");
$global_up_rate = format_bytes($rates[0]['rateup'], "0 B");
$global_down_limit = format_bytes($globalstats['download_cap'], "unlim");
$global_up_limit = format_bytes($globalstats['upload_cap'], "unlim");

$disk_percent = round($rates[0]['diskspace'] / disk_total_space($downloaddir) * 100);
$disk_alert_class = ($disk_percent <= $alertthresh ? ' class="diskalert"' : '');
$disk_free = format_bytes($rates[0]['diskspace']);
$disk_total = format_bytes(disk_total_space($downloaddir));



// Get the list of torrents downloading
$data = get_full_list($_SESSION['view']);

// Get tracker URL for each torrent - this does an RPC query for every torrent - might be heavy on server so you might want to disable in config.php
if($displaytrackerurl && is_array($data)) {
   foreach($data as $key => $item) {
      $data[$key]['tracker_url'] = tracker_url($item['hash']);
   }
}

// Sort the list
if(is_array($data)) {
   if (strtolower($_SESSION['sortord']=="asc")) {
      $sortkey=$_SESSION['sortkey'];
      usort($data,'sort_matches_asc');
   } else {
      $sortkey=$_SESSION['sortkey'];
      usort($data,'sort_matches_desc');
   }
} else {
   $data=array();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="submodal/subModal.css" />
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="submodal/common.js"></script>
<script type="text/javascript" src="submodal/subModal.js"></script>
<script type="text/javascript" src="rtgui.js"></script>
<script type="text/javascript" language="Javascript">
$(function() {
	setInterval("ajax('<?php echo $_SESSION['view']; ?>')", <?php echo $refresh_interval ?>);
});
</script>
<title>rtGui</title>
<link href="style.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="wrap">

  <div id="header">
    <h1><a href='index.php?reload=1'>rt<span class=green>gui</span></a></h1><br/>

    <div id="boxright">
      <p>
        Down: 
        <span class='inline download' id='glob_down_rate'><?php echo $global_down_rate ?>/s</span>
        <span class='smalltext'>[<?php echo $global_down_limit ?>/s]</span>
        &nbsp;&nbsp;&nbsp;
        Up: 
        <span class='inline upload' id='glob_up_rate'><?php echo $global_up_rate ?>/s</span>
        <span class='smalltext'>[<?php echo $global_up_limit ?>/s]</span>
      </p>
<?php if(isset($downloaddir)) { ?>
      <div id='glob_diskfree'<?php echo $disk_alert_class ?>>
        Disk Free: <?php echo $disk_free ?>
        / <?php echo $disk_total ?>
        (<?php echo $disk_percent ?>%)
      </div>
<?php } ?>
      <p>
        <a class="submodal-600-520" href="settings.php">Settings</a>
        | <a class="submodal-700-500" href="add-torrent.php">Add Torrent</a>
      </p>
    </div><!-- id="boxright" -->
  </div><!-- id="header" -->

<form action='control.php' method='post' name='control'>
<div id='navcontainer' style='clear:both;'>

<ul id="navlist">
<?php
$views = array("All", "Started", "Stopped", "Complete", "Incomplete", "Seeding");
foreach($views as $v) {
  $test = ($v == "All" ? "main" : strtolower($v));
  $id = ($_SESSION['view'] == $test ? ' id="current"' : '');
  echo "<li><a$id href=\"?setview=$test\">$v</a></li>\n";
}
if($debugtab) {
   echo '<li><a'.($r_debug ? ' id="current"' : '')." href=\"?setview=main&amp;debug=1\">Debug</a></li>\n";
}
?>
</ul>
</div>

<div class ="container">
<?php
// The headings, with sort links...
$uparr = '<img src="images/uparrow.gif" height=8 width=5 alt="Ascending" />';
$downarr = '<img src="images/downarrow.gif" height=8 width=5 alt="Descending" />';

$cols = array(
  'name'             => 'Name',
  'status_string'    => 'Status',
  'percent_complete' => 'Done',
  'bytes_diff'       => 'Remain',
  'size_bytes'       => 'Size',
  'down_rate'        => 'Down',
  'up_rate'          => 'Up',
  'up_total'         => 'Seeded',
  'ratio'            => 'Ratio',
  'peers_connected'  => 'Peers',
  'priority_str'     => 'Pri',
  'tracker_url'      => 'Trk',
);

foreach($cols as $k => $v) {
  $sort_order = (($_SESSION['sortkey'] == $k xor $_SESSION['sortord'] == 'asc') ? 'asc' : 'desc');
  $width = ($k == 'priority_str' ? 84 : 89);
  if($k != 'tracker_url' || !$displaytrackerurl) {
    echo "<div class=\"headcol\" style=\"width: ${width}px;\">";
  }
  echo "<a href=\"?setsortkey=$k&amp;setsortord=$sort_order\">$v</a> ";
  if($_SESSION['sortkey'] == $k) {
    echo ($_SESSION['sortord'] == 'asc' ? $uparr : $downarr);
  }
  if($k == 'priority_str' && $displaytrackerurl) {
    echo "/ ";
  } else {
    echo "</div>\n";
  }  
}
?>

<div class="spacer"></div>

<?php
if($r_debug) {
  echo "<br><pre>" . htmlspecialchars(var_export($data, true)) . "</pre>\n";
}

// List the torrents...
$totcount = 0;
$thisrow = "row1";

foreach($data AS $item) {
   if(!$_SESSION['tracker_filter'] || @stristr($item['tracker_url'],$_SESSION['tracker_filter']) !== false) {
      $totcount++;
      echo "<div class='$thisrow'>\n";
      
      $statusstyle = ($item['complete'] ? 'complete' : 'incomplete');
      $statusstyle .= ($item['is_active'] ? 'active' : 'inactive');

      $eta = "";
      if($item['down_rate'] > 0) {
         $eta = format_eta(($item['size_bytes']-$item['completed_bytes'])/$item['down_rate']);
      }

      echo "<div class=\"namecol\" id=\"t".$item['hash']."name\">\n";
      // Tracker URL
      if($displaytrackerurl) {
         $urlstyle = $tracker_hilite_default;
         foreach($tracker_hilite as $hilite) {
            foreach($hilite as $thisurl) { 
               if(stristr($item['tracker_url'],$thisurl) !== false) {
                 $urlstyle = $hilite[0];
               }
            }
         }
         echo "<div class=\"trackerurl\" id=\"tracker\"><a style=\"color: $urlstyle ;\" id=\"tracker\" href=\"?settrackerfilter=".$item['tracker_url'].'">'.$item['tracker_url'].'</a>&nbsp;</div>';
      }
      
      // Torrent name
      echo "<input type='checkbox' name='select[]' value='" . $item['hash'] . "'  /> ";
      echo "<a class=\"submodal-600-520 $statusstyle\" href=\"view.php?hash=" . $item['hash'] . "\">";
      echo htmlspecialchars($item['name'], ENT_QUOTES) . "</a>\n";
      echo "</div>\n";

      // message...
      echo "<div class='errorcol' id='t" . $item['hash'] . "message'>\n";
      if($eta != "") {
        echo "$eta remaining... ";
      }
      if($item['message'] != "") {
        echo $item['message'] . "\n";
      }
      echo "</div>\n";

      // Stop/start controls...
      echo "<div class='datacol'  style='width:89px;'>\n";
      echo "<a href='control.php?hash=".$item['hash']."&amp;cmd=".($item['is_active']==1 ? "stop" : "start")."'>".($item['is_active']==1 ? "<img alt='Stop torrent' border=0 src='images/stop.gif' width=16 height=16 />" : "<img alt='Start torrent' border=0 src='images/start.gif' width=16 height=16 />")."</a> \n";
      echo "<a href='control.php?hash=".$item['hash']."&amp;cmd=delete' onClick='return confirm(\"Delete torrent - are you sure? (This will not delete data from disk)\");'><img align='bottom' alt='Delete torrent' border=0 src='images/delete.gif' width=16 height=16 /></a> \n";
      echo "<a class='submodal-600-520' href='view.php?hash=".$item['hash']."'><img alt='Torrent info' src='images/view.gif' width=16 height=16 /></a><br/>\n";
      echo "</div>\n";
      
      // Stats row...
      echo "<div class='datacol' style='width:89px;' id='t".$item['hash']."status_string'><img src='images/".$statusstyle.".gif' width=10 height=9 alt='Status' />".$item['status_string']."</div>\n";
      echo "<div class='datacol' style='width:89px;' id='t".$item['hash']."percent_complete'>".$item['percent_complete']." %<br/>".percentbar(@round(($item['percent_complete']/2)))."</div>\n";
      echo "<div class='datacol' style='width:89px;' id='t".$item['hash']."bytes_diff'>&nbsp;".completed_bytes_diff($item['size_bytes'],$item['completed_bytes'])."</div>\n";
      echo "<div class='datacol' style='width:89px;' id='t".$item['hash']."size_bytes'>&nbsp;".format_bytes($item['size_bytes'])."</div>\n";
      echo "<div class='datacol download' style='width:89px;' id='t".$item['hash']."down_rate'>&nbsp;".format_bytes($item['down_rate'])."</div>\n";
      echo "<div class='datacol upload' style='width:89px;' id='t".$item['hash']."up_rate'>&nbsp;".format_bytes($item['up_rate'])."</div>\n";
      echo "<div class='datacol' style='width:89px;' id='t".$item['hash']."up_total'>&nbsp;".format_bytes($item['up_total'])."</div>\n";
      echo "<div class='datacol' style='width:70px;' id='t".$item['hash']."ratio'>&nbsp;".@round(($item['ratio']/1000),2)."</div>\n";
      echo "<div class='datacol' style='width:105px;' id='t".$item['hash']."peers'>".$item['peers_connected']."/".$item['peers_not_connected']." (".$item['peers_complete'].")"."</div>\n";
      echo "<div class='datacollast' style='width:70px;' id='t".$item['hash']."priority_str'>".$item['priority_str']."</div>\n";
      echo "<div class=spacer> </div>\n";

      echo "</div>\n"; // end of thisrow div
      $thisrow = ($thisrow == 'row1' ? 'row2' : 'row1');
   }
}

// Display message if no torrents to list...
if(!$data || !$totcount) {
?>
<div class='row1'>
  <div class='namecol' align='center'><p>&nbsp;</p>No torrents to display.<p>&nbsp;</p></div>
</div>
<?php } ?>

</div><!-- id="container" -->

<div class="bottomtab">
  <input type="button" value="Select All" onClick="checkAll(this.form)" />
  <input type="button" value="Unselect All" onClick="uncheckAll(this.form)" />

  <select name="bulkaction" >
  <optgroup label="With Selected...">
  <option value="stop">Stop</option>
  <option value="start">Start</option>
  <option value="delete">Delete</option>
  </optgroup>
  <optgroup label="Set Priority...">
  <option value="pri_high">High</option>
  <option value="pri_normal">Normal</option>
  <option value="pri_low">Low</option>
  <option value="pri_off">Off</option>
  </optgroup>
  </select>
  <input type="submit" value="Go" />
</div><!-- class="bottomtab" -->

</form>

<p>&nbsp;</p>
<div align="center" class="smalltext">
<a href="http://libtorrent.rakshasa.no/" target="_blank">
  rTorrent <?php echo $globalstats["client_version"] . "/" . $globalstats["library_version"] ?>
</a>
<a href="rssfeed.php">RSS Feed</a> | 
Page created in <?php echo round(microtime(true)-$execstart, 3) ?> secs.<br/>
<a href="http://rtgui.googlecode.com" target="_blank">rtGui v0.2.7</a> - by Simon Hall 2007-2008
</div>
</div>
</body>
</html>
