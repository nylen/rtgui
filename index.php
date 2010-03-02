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
if(!$_SESSION) session_start();
include 'config.php';
include 'functions.php';
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

// Reset saved torrents data (if any)
$_SESSION['persistent'] = array();

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
<link rel="stylesheet" type="text/css" href="jqModal.css" />
<script type="text/javascript">
// Configuration variables
var config = {
  refreshInterval: <?php echo $refresh_interval; ?>,
  diskAlertThreshold: <?php echo $alertthresh; ?>,
  useGroups: <?php echo $use_groups ? 1 : 0; ?>,
  useDateAdded: <?php echo $use_date_added ? 1 : 0; ?> 
};
var current = {
  view: 'main',
  filters: {},
  sortVar: null,
  sortDesc: false,
  error: false
};
</script>
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="jquery.form.js"></script>
<script type="text/javascript" src="jqModal.js"></script>
<script type="text/javascript" src="json2.min.js"></script>
<script type="text/javascript" src="php.min.js"></script>
<script type="text/javascript" src="patience_sort.js"></script>
<script type="text/javascript" src="functions.js"></script>
<script type="text/javascript" src="templates.js"></script>
<script type="text/javascript" src="events.js"></script>
<!--[if lt IE 8]>
<link rel="stylesheet" type="text/css" href="ie.css" />
<script type="text/javascript" src="ie.js"></script>
<![endif]-->
<script type="text/javascript">
var data = <?php echo $data_str; ?>;

$(function() {
  $('#dialog').jqm();
  updateTorrentsHTML(data, true);
  current.refreshIntervalID = setInterval(updateTorrentsData, config.refreshInterval);
});
</script>
<title>rtGui</title>
<link href="style.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="wrap">
  <div id="error"></div>

  <div id="header">
  
    <h1><a href="./">rt<span class=green>gui</span></a></h1><br/>
    <!--[if lt IE 8]>
      <span id="ie">
        Please, go get a
        <a href="http://www.google.com/chrome">real</a>
        <a href="http://www.mozilla.com/firefox">browser</a>...
      </span>
    <![endif]-->
    
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
        Showing <span id="t-count-visible">??</span> 
        of <span id="t-count-all">??</span> torrents
        | <a class="dialog" rel="400:300" href="settings.php">Settings</a>
        | <a class="dialog" rel="700:500" href="add-torrents-form.php">Add torrent(s)</a>
      </p>
    </div><!-- id="boxright" -->
  </div><!-- id="header" -->

<form action="control.php" method="post" name="control" id="control-form">
<div id="navcontainer">

<div id="filters-container">
  <span id="filters-label">Filter:</span>
  <input type="text" id="filters" value="" />
  <a href="#" id="clear-filters"><img src="images/cross.gif" /></a>
</div>

<ul id="navlist">
<?php
$views = array('All', 'Started', 'Stopped', 'Active', 'Inactive', 'Complete', 'Incomplete', 'Seeding');
foreach($views as $name) {
  $view = ($name == 'All' ? 'main' : strtolower($name));
  $class = ($name == 'All' ? 'view current' : 'view');
  echo "<li><a class=\"$class\" href=\"#\" rel=\"$view\">$name</a></li>\n";
}
if($debugtab) {
   echo "<li><a href=\"#\" id=\"debug-tab\">Debug</a></li>\n";
}
?>
</ul>

</div>

<div id="dialog" class="jqmWindow"></div>

<div class="container">

<div id="torrents-header">
<?php
// Generate header links
// variable_name     => ColName:width:add-class (default :89px:[none])
$cols = array(
  '+name!'            => 'Name:104',
  '+group'            => 'Grp',
  '+status'           => 'Status:74',
  '+percent_complete' => 'Done',
  '-bytes_remaining'  => 'Remain',
  '-size_bytes!'      => 'Size',
  '-down_rate'        => 'Down',
  '-up_rate'          => 'Up',
  '+up_total!'        => 'Seeded:94',
  '+ratio!'           => 'Ratio:50',
  '-date_added!'      => 'Age:50',
  '-peers_summary'    => 'Peers:68',
  '+priority_str'     => 'Pri',
  '+tracker_hostname' => 'Trk',
);

foreach($cols as $k => $v) {
  $order = ($k[0] == '+' ? 'asc' : 'desc');
  $k = substr($k, 1);
  $reorder = '';
  if(substr($k, strlen($k)-1) == '!') {
    $reorder = ':true';
    $k = substr($k, 0, strlen($k)-1);
  }
  
  if($k == 'group' && !$use_groups) {
    continue;
  }
  $arr = explode(':', $v);
  if(count($arr) < 2) {
    $arr[1] = 89;
  }
  $vis = ($k == 'date_added' && !$use_date_added ? ' visibility: hidden;' : '');
  $class = trim("headcol $arr[2]");
  if($k != 'tracker_hostname' && $k != 'group') {
    echo "<div class=\"$class\" style=\"width: ${arr[1]}px;$vis\">";
  }
  echo "<a class=\"sort\" href=\"#\" rel=\"$k:$order$reorder\">$arr[0]</a>";
  echo ($k == 'priority_str' || ($k == 'name' && $use_groups) ? "/" : "</div>\n");
}
?>
</div>
<div class="spacer"></div>

<?php if($debugtab) { ?>
<pre id="debug">&nbsp;</pre>
<?php } ?>

<div id="torrents">

<div class='row1' id="t-none">
  <div class='namecol' align='center'><p>&nbsp;</p>No torrents to display.<p>&nbsp;</p></div>
</div>

</div><!-- id="torrents" -->

</div><!-- id="container" -->

<div class="bottomtab">
  <input type="button" class="select-all" value="Select All" />
  <input type="button" class="unselect-all" value="Unselect All" />

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
Page created in <?php echo round(microtime(true) - $execstart, 3) ?> secs.<br />
Based on <a href="http://rtgui.googlecode.com" target="_blank">rtGui v0.2.7</a> - by Simon Hall 2007-2008<br />
Modifications by James Nylen 2010
</div>
</div>
</body>
</html>
