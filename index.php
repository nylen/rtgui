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
include 'config.php';
include 'functions.php';
include 'functions_html.php';
import_request_variables('gp', 'r_');

// Try using alternative XMLRPC library from http://sourceforge.net/projects/phpxmlrpc/
// (see http://code.google.com/p/rtgui/issues/detail?id=19)
if(!function_exists('xml_parser_create')) {
  include('xmlrpc.inc');
  include('xmlrpc_extension_api.inc');
}

if(!isset($r_debug)) {
  $r_debug = 0;
}

// Get the list of torrents downloading
$data = get_all_torrents();

// Turn it into JSON and format it somewhat nicely
$data_str = json_encode($data);
$data_str = preg_replace('@("[0-9A-F]{40}":)@', "\n\\1", $data_str);
$data_str = str_replace("}},\"", "}\n},\"", $data_str);

// Set the session variable for json.php
$_SESSION['last_data'] = $data;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="submodal/subModal.css" />
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="json2.min.js"></script>
<script type="text/javascript" src="php.min.js"></script>
<script type="text/javascript" src="submodal/common.js"></script>
<script type="text/javascript" src="submodal/subModal.js"></script>
<script type="text/javascript" src="rtgui.js"></script>
<script type="text/javascript" language="Javascript">
var lastData = {};
var data = <?php echo $data_str; ?>;

$(function() {
  updateHTML(data, true);
  setInterval(updateData, <?php echo $refresh_interval ?>);
});

var diskAlertThreshold = <?php echo $alertthresh; ?>;
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
        <span class="inline download" id="total_down_rate">??</span>
        <span class="smalltext" id="total_down_limit">??</span>
        &nbsp;&nbsp;&nbsp;
        Up: 
        <span class="inline upload" id="total_up_rate">??</span>
        <span class="smalltext" id="total_up_limit">??</span>
      </p>
<?php if(isset($downloaddir)) { ?>
      <div>
        Disk Free: <span id="disk_free">??</span>
        / <span id="disk_total">??</span>
        (<span id="disk_percent">??</span>)
      </div>
<?php } ?>
      <p>
        <a class="submodal-600-520" href="settings.php">Settings</a>
        | <a class="submodal-700-500" href="add-torrent.php">Add Torrent</a>
      </p>
    </div><!-- id="boxright" -->
  </div><!-- id="header" -->

<form action="control.php" method="post" name="control">
<div id="navcontainer" style="clear:both;">

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

<div class="container">
<?php echo get_torrent_headers(); ?>

<div class="spacer"></div>

<?php
if($r_debug) {
  echo "<br><pre id=\"debug\"></pre>\n";
}
?>

<div class='row1' id="t-none">
  <div class='namecol' align='center'><p>&nbsp;</p>No torrents to display.<p>&nbsp;</p></div>
</div>

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
  rTorrent client <?php echo rtorrent_xmlrpc('system.client_version'); ?>
   / lib <?php echo rtorrent_xmlrpc('system.library_version'); ?>
</a> | 
<a href="rssfeed.php">RSS Feed</a> | 
Page created in <?php echo round(microtime(true) - $execstart, 3) ?> secs.<br/>
<a href="http://rtgui.googlecode.com" target="_blank">rtGui v0.2.7</a> - by Simon Hall 2007-2008
</div>
</div>
</body>
</html>
