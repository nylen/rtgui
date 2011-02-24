<?php
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
require_once 'config.php';
require_once 'functions.php';
rtgui_session_start();
import_request_variables('gp', 'r_');

// Try using alternative XMLRPC library from http://sourceforge.net/projects/phpxmlrpc/
// (see http://code.google.com/p/rtgui/issues/detail?id=19)
if(!function_exists('xml_parser_create')) {
  require_once 'xmlrpc.inc';
  require_once 'xmlrpc_extension_api.inc';
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
<script type="text/javascript">
// Configuration variables
var config = {
  refreshInterval: <?php echo $refresh_interval; ?>,
  diskAlertThreshold: <?php echo $alertthresh; ?>,
  useGroups: <?php echo $use_groups ? 1 : 0; ?>,
  debugTab: <?php echo $debugtab ? 1 : 0; ?>,
  dateAddedFormat: '<?php echo addslashes($date_added_format); ?>'
};
var current = {
  view: 'main',
  filters: {},
  sortVar: 'name',
  sortDesc: false,
  error: false
};
var defaultFilterText = 'Filter';

var data = <?php echo $data_str; ?>;
</script>
<?php
include_script('jquery.js');
include_script('jquery.form.js');
include_script('jquery-ui-1.8.9-files/js/jquery-ui-1.8.9.custom.min.js');
include_script('json2.min.js');
include_script('php.min.js');
include_script('patience_sort.js');
include_script('functions.js');
include_script('templates.js');
include_script('index.js');
?>
<!--[if lt IE 8]>
<link rel="stylesheet" type="text/css" href="ie.css" />
<script type="text/javascript" src="ie.js"></script>
<![endif]-->
<title><?php echo $site_title; ?></title>
<?php
include_stylesheet('jquery-ui-1.8.9-files/css/ui-darkness/jquery-ui-1.8.9.custom.css');
include_stylesheet('style.css', true);
include_stylesheet('dialog.css', true);
?>
</head>
<body>
  <div id="wrap">
    <form action="control.php" method="post" name="control" id="control-form">
      <div id="header">
        <div id="error"></div>
        <h1><a href="./">rt<span class=green>gui</span></a></h1><br/>
<?php
if(is_array($header_links) && count($header_links)) {
  echo "<div id=\"header-links\">\n(Links: \n";
  $first = true;
  foreach($header_links as $title => $href) {
    if(!$first) {
      echo " | \n";
    }
    echo "<a href=\"$href\">$title</a>";
    $first = false;
  }
  echo ")\n</div>\n";
}
?>
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

      <div id="navcontainer">
        <div id="filter-container">
          <input type="text" name="filter" id="filter" />
          <a id="clear-filter" href="#" class="btn-clear-filter" title="clear filter"></a>
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
      </div><!-- id="navcontainer" -->

      <div id="dialog"></div>

      <div id="torrents-header">
        <div class="headcol column-name-grp">
          <a class="sort" href="#" rel="name:asc:true">Name</a>|<a class="sort" href="#" rel="group:asc">Grp</a>
        </div>
        <div class="headcol column-status">
          <a class="sort" href="#" rel="status:asc">Status</a>
        </div>
        <div class="headcol column-done">
          <a class="sort" href="#" rel="percent_complete:asc">Done</a>
        </div>
        <div class="headcol column-remain">
          <a class="sort" href="#" rel="bytes_remaining:desc">Remain</a>
        </div>
        <div class="headcol column-size">
          <a class="sort" href="#" rel="size_bytes:desc:true">Size</a>
        </div>
        <div class="headcol column-down">
          <a class="sort" href="#" rel="down_rate:desc">Down</a>
        </div>
        <div class="headcol column-up">
          <a class="sort" href="#" rel="up_rate:desc">Up</a>
        </div>
        <div class="headcol column-seeded">
          <a class="sort" href="#" rel="up_total:asc:true">Seeded</a>
        </div>
        <div class="headcol column-ratio">
          <a class="sort" href="#" rel="ratio:asc:true">Ratio</a>
        </div>
        <div class="headcol column-peers">
          <a class="sort" href="#" rel="peers_summary:desc">Peers</a>
        </div>
        <div class="headcol column-priority">
          <a class="sort" href="#" rel="priority_str:asc">Pri</a>
        </div>
        <div class="headcol column-tracker-date">
          <a class="sort" href="#" rel="tracker_hostname:asc">Trk</a>|<a class="sort" href="#" rel="date_added:desc:true">Date</a>
        </div>
      </div><!-- id="torrents-header" -->

      <div class="spacer"></div>

      <div class="container">
<?php if($debugtab) { ?>
        <pre id="debug" style="display: none;">&nbsp;</pre>
<?php } ?>
        <div id="torrents">
          <div class="row" id="t-none">
            <div class="namecol" align="center"><p>&nbsp;</p>No torrents to display.<p>&nbsp;</p></div>
          </div>
        </div>
      </div><!-- class="container" -->

      <div class="bottomtab">
        <input type="button" class="select-all themed" value="Select All" />
        <input type="button" class="unselect-all themed" value="Unselect All" />
        <select name="bulkaction" class="themed">
          <optgroup label="With Selected...">
            <option value="stop">Stop</option>
            <option value="start">Start</option>
            <option value="delete">Delete</option>
            <option value="hashcheck">Re-check</option>
          </optgroup>
          <optgroup label="Set Priority...">
            <option value="pri_high">High</option>
            <option value="pri_normal">Normal</option>
            <option value="pri_low">Low</option>
            <option value="pri_off">Off</option>
          </optgroup>
        </select>
        <input type="submit" value="Go" class="themed" />
        <input type="checkbox" id="leave-checked" name="leave_checked" />
        <label for="leave-checked" class="gray-text">Leave torrents checked</label>
      </div><!-- class="bottomtab" -->

    </form><!-- id="control-form" -->

    <div id="footer">
      <div align="center" class="smalltext">
        <a href="http://libtorrent.rakshasa.no/" target="_blank">
          rTorrent client <?php echo rtorrent_xmlrpc('system.client_version'); ?>
           / lib <?php echo rtorrent_xmlrpc('system.library_version'); ?>
        </a> |
        <a href="rssfeed.php">RSS Feed</a> |
        Page created in <?php echo round(microtime(true) - $execstart, 3) ?> secs.<br />
        Based on <a href="http://rtgui.googlecode.com" target="_blank">rtGui v0.2.7</a> by Simon Hall &copy; 2007-2008<br />
        Modifications by James Nylen, Gurvan Guezennec &copy; 2010-2011
      </div>
    </div><!-- id="footer" -->

  </div><!-- id="wrap" -->
</body>
</html>
