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

require_once 'config.php';
require_once 'functions.php';

import_request_variables('gp', 'r_');

if(isset($r_select)) {
  $tabs = array($r_select);
  $only_one_tab = !isset($r_alltabs);
} else {
  $tabs = array('files', 'tracker', 'peers', 'torrent', 'storage', 'debug');
  $only_one_tab = false;
}
$active_tab = $tabs[0];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<title>rtGui</title>
<script type="text/javascript">
var currentHash = '<?php echo $r_hash; ?>';
</script>
<?php
include_stylesheet('common.css', true);
include_stylesheet('form-controls.css', true);
include_stylesheet('dialogs.css', true);
include_stylesheet('tipTip.css', true);
include_script('jquery.js');
include_script('jquery.tipTip.js');
include_script('confirmMessages.js');
include_script('view.js');



// Get torrent info...  (get all downloads, then filter out just this one by the hash)
$all_torrents = get_all_torrents(true);

$this_torrent = false;
foreach($all_torrents as $torrent) {
  if($r_hash == $torrent['hash']) $this_torrent = $torrent;
}

if(!$this_torrent) {
  // probably the current torrent was just deleted
  die('<script>top.hideDialog(true);</script></body></html>');
}

$status_style = ($this_torrent['complete']  ? 'complete' : 'incomplete')
              . ($this_torrent['is_active'] ? 'active'   : 'inactive');

if($_GET['dialog']) {
  $dialog_query_str = '&amp;dialog=1';
} else {
  echo "<h3>$this_torrent[name]</h3>\n";
  $dialog_query_str = '';
}

?>
</head>
<body class="modal">
<?php



// ------------------------------------------------
// --- Controls
// ------------------------------------------------
?>
  <div class="controlcontainer">
<?php
$command = ($this_torrent['is_active'] ? 'stop' : 'start');
$Command = ucfirst($command);
echo <<<HTML
    <input type="button" value="$Command"
      rel="$command" id="btn-$command"
      class="button$command command-button themed" />

HTML;
?>
    <input type="button" value="Delete"
      rel="delete" id="btn-delete"
      class="buttondel command-button confirm themed" />
    <input type="button" value="Hash check"
      rel="hashcheck" id="btn-hash-check"
      class="buttonhashcheck command-button themed" />
    <input type="button" value="Refresh"
      id="btn-refresh"
      class="buttonrefresh themed" />

  </div>

  <div id="navcontainer">
    <ul id="navlist">
<?php
$tab_link_class = ($only_one_tab ? 'tab' : 'tab tab-multi');
$tab_link_class_active = ($only_one_tab ? 'tab current' : 'tab tab-multi current');
$tab_div_class = ($only_one_tab ? 'tab' : 'tab hidden');
$tab_div_class_active = 'tab';

$all_tabs = array('Files', 'Tracker', 'Peers', 'Torrent', 'Storage');
if($debug_mode) {
  $all_tabs[] = 'Debug';
}
foreach($all_tabs as $tab) {
  $select = strtolower($tab);
  $class = ($active_tab == $select ? $tab_link_class_active : $tab_link_class);
  echo <<<HTML
      <li><a class="$class" rel="$select" href="?select=$select&amp;hash=$r_hash$dialog_query_str">$tab</a></li>

HTML;
}
?>
    </ul>
  </div>



<?php
foreach($tabs as $r_select) {

  $this_class = ($r_select == $active_tab ? $tab_div_class_active : $tab_div_class);
  echo <<<HTML
  <div class="$this_class" id="tab-$r_select">

HTML;



  // ------------------------------------------------
  // --- Files tab
  // ------------------------------------------------

  if($r_select == 'files') {
     $data = get_file_list($r_hash);
?>
    <div class="container">
      <div class="modalheadcol">
        <div class="headcol" style="width:190px; border:none;">Filename</div>
        <div class="floatright">
          <div class="headcol" style="width:90px;">Size</div>
          <div class="headcol" style="width:90px;">Done</div>
          <div class="headcol" style="width:90px;">Chunks</div>
          <div class="headcol" style="width:90px;">Priority</div>
        </div>
      </div>
      <form action="control.php" method="post">
<?php
     $thisrow = 'row';
     $index = 0;
     foreach($data as $item) {
       $path = mb_wordwrap($item['get_path'],90,"<br />\n",TRUE);
       $bytes = format_bytes($item['get_size_bytes']);
       $percent = @round(($item['get_completed_chunks']/$item['get_size_chunks'])*100);
       $percent_bar = percentbar($item['get_completed_chunks'] / $item['get_size_chunks'] * 100);
       $selected = array();
       foreach(range(0, 2) as $i) {
         $selected[$i] = ($item['get_priority'] == $i ? ' selected="selected"' : '');
       }
       echo <<<HTML
        <div class="$thisrow">
          <div class="namecol">$path</div>
          <div class="floatright">
            <div class="datacol smalltext" style="width:90px;">$bytes</div>
            <div class="datacol smalltext" style="width:90px;">
              $percent%<br />
              $percent_bar
            </div>
            <div class="datacol smalltext" style="width:90px;">
              $item[get_completed_chunks] / $item[get_size_chunks]
            </div>
            <div class="datacollast smalltext" style="width:90px;">
              <select name="set_fpriority[$index]" class="mediumtext themed">
                <option value="0"$selected[0]>Off</option>
                <option value="1"$selected[1]>Normal</option>
                <option value="2"$selected[2]>High</option>
              </select>
              <input type="hidden" name="hash" value="$r_hash" />
            </div>
          </div>
          <div class="spacer">&nbsp;</div>
        </div>

HTML;
        $index++;
     }
?>

        <div align="right" class="tab-bottom">
          <input type="submit" class="themed" value="Save" />
        </div>
      </form>
    </div>
<?php
  }



  // ------------------------------------------------
  // --- Tracker tab
  // ------------------------------------------------
  if($r_select == 'tracker') {
    $data = get_tracker_list($r_hash);
?>
    <div class="container">
      <div class="modalheadcol">
        <div class="headcol" style="width: 156px; border:none;">URL</div>
        <div class="floatright">
          <div class="headcol" style="width: 124px;">Last</div>
          <div class="headcol" style="width: 90px;">Interval</div>
          <div class="headcol" style="width: 90px;">Scrapes</div>
          <div class="headcol" style="width: 90px;">Enabled</div>
        </div>
      </div>
<?php
    $thisrow = 'row';
    foreach($data as $item) {
      $url = mb_wordwrap($item['get_url'],90,"<br />\n",TRUE);
      $last_scrape_time = ($item['get_scrape_time_last'] > 0
        ? date('Y-m-d g:ia', @round($item['get_scrape_time_last']))
        : 'never');
      $scrape_interval = @round($item['get_normal_interval'] / 60);
      $is_enabled = ($item['is_enabled'] ? 'Yes' : 'No');
      echo <<<HTML
      <div class="$thisrow">
        <div class="namecol">$url</div>
        <div class="floatright">
          <div class="datacol smalltext" style="width:124px;">$last_scrape_time</div>
          <div class="datacol smalltext" style="width:90px;">$scrape_interval</div>
          <div class="datacol smalltext" style="width:90px;">$item[get_scrape_complete]</div>
          <div class="datacollast smalltext" style="width:90px;">$is_enabled</div>
        </div>
        <div class="spacer">&nbsp;</div>
      </div>

HTML;
    }
?>
    </div>
<?php
  }



  // ------------------------------------------------
  // --- Peers tab
  // ------------------------------------------------
  if($r_select == 'peers') {
    $data=get_peer_list($r_hash);
?>
    <div class="container">
      <div class="modalheadcol">
        <div class="headcol" style="width:190px; border:none;">Address</div>
        <div class="floatright">
          <div class="headcol" style="width:90px;">Complete</div>
          <div class="headcol" style="width:90px;">Download</div>
          <div class="headcol" style="width:90px;">Upload</div>
          <div class="headcol" style="width:90px;">Peer</div>
        </div>
      </div>
<?php
    $thisrow = 'row';
    foreach($data as $item) {
      $flags = ($item['is_encrypted']  ? 'enc ' : '')
             . ($item['is_incoming']   ? 'inc ' : '')
             . ($item['is_obfuscated'] ? 'obf ' : '')
             . ($item['is_snubbed']    ? 'snb ' : '');
      $flags = (trim($flags) ? "Flags: $flags" : '');
      $percent_bar = percentbar($item['get_completed_percent']);
      $rates = array();
      foreach(array('down', 'up', 'peer') as $i) {
        $rates[$i] = ($item["get_${i}_rate"]
          ? format_bytes($item["get_${i}_rate"]) . '/sec<br />'
          : '')
          . format_bytes($item["get_${i}_total"]);
      }
      echo <<<HTML
      <div class="$thisrow">
        <div class="namecol smalltext">
          <a href="http://whois.arin.net/rest/nets;q=$item[get_address]?showDetails=true&showARIN=false"
            target="_blank" class="ip-address" data-ip="$item[get_address]">
            $item[get_address]
          </a>:
          $item[get_port]&nbsp;&nbsp;<i>$item[get_client_version]</i>&nbsp;&nbsp;$flags
        </div>
        <div class="floatright">
          <div class="datacol smalltext" style="width:90px;">
            &nbsp;$item[get_completed_percent]%<br />
            $percent_bar
          </div>
          <div class="datacol smalltext download" style="width:90px;">&nbsp;$rates[down]</div>
          <div class="datacol smalltext upload" style="width:90px;">&nbsp;$rates[up]</div>
          <div class="datacol smalltext" style="width:90px;">&nbsp;$rates[peer]</div>
        </div>
        <div class="spacer">&nbsp;</div>
      </div>
HTML;
     }
?>
    </div>
<?php
  }



  // ------------------------------------------------
  // --- Torrent tab
  // ------------------------------------------------
  if($r_select == 'torrent') {
    $status_flags = ($this_torrent['complete'] ? 'Complete ' : 'Incomplete ');
    if($this_torrent['is_hash_checked'])  $status_flags .= '&middot; Hash Checked ';
    if($this_torrent['is_hash_checking']) $status_flags .= '&middot; Hash Checking ';
    if($this_torrent['is_multi_file'])    $status_flags .= '&middot; Multi-file ';
    if($this_torrent['is_open'])          $status_flags .= '&middot; Open ';
    if($this_torrent['is_private'])       $status_flags .= '&middot; Private ';

    $status_style = ($this_torrent['complete']  ? 'complete' : 'incomplete')
                  . ($this_torrent['is_active'] ? 'active'   : 'inactive');

    $name = mb_wordwrap($this_torrent['name'], 60, "<br />\n", true);

    $selected = array();
    foreach(range(0, 3) as $i) {
      $selected[$i] = ($this_torrent['priority'] == $i ? ' selected="selected"' : '');
    }
    $formatted = array();
    foreach(array('completed_bytes', 'size_bytes', 'down_rate', 'down_total', 'up_rate', 'up_total') as $i) {
      $formatted[$i] = format_bytes($this_torrent[$i]);
    }
    $percent_bar = percentbar($this_torrent['percent_complete']);
    $ratio = number_format($this_torrent['ratio'] / 1000, 2);

    echo <<<HTML
    <div class="container">
      <table border=0 cellspacing=0 cellpadding=5 class="maintable" width="100%">
        <tr class="row">
          <td class="datacol" align=right><b>Name</b></td>
          <td><span class="torrenttitle $status_style">$name</span></td>
        </tr>
        <tr class="row">
          <td class="datacol" align=right><b>Status</b></td>
          <td><img src="images/$status_style.gif" width=10 height=9 alt="Status" />$this_torrent[status]</td>
        </tr>

        <tr class="row">
          <td class="datacol" align=right><b>Priority</b></td>
          <td>
            <form action="control.php" method="post">
              <input type="hidden" name="hash" value="$this_torrent[hash]" />
              <select name="set_tpriority" class="themed">
                <option value="0"$selected[0]>Off</option>
                <option value="1"$selected[1]>Low</option>
                <option value="2"$selected[2]>Normal</option>
                <option value="3"$selected[3]>High</option>
              </select>
              <input type="submit" class="themed" value="Set" />
            </form>
          </td>
        </tr>

        <tr class="row">
          <td class="datacol" align="right"><b>Status Flags</b></td>
          <td>$status_flags</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Message</b></td>
          <td>$this_torrent[message]</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Completed Bytes</b></td>
          <td>$formatted[completed_bytes]</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Size</b></td>
          <td>$formatted[size_bytes]</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Complete</b></td>
          <td><div class="datacollast">$this_torrent[percent_complete]%<br />$percent_bar</div>
        <tr class="row"><td class="datacol" align="right"><b>Down Rate</b></td><td>$formatted[down_rate]</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Down Total</b></td>
          <td>$formatted[down_total]</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Up Rate</b></td>
          <td>$formatted[up_rate]</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Up Total</b></td>
          <td>$formatted[up_total]</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Peers connected</b></td>
          <td>$this_torrent[peers_connected]</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Peers not connected</b></td>
          <td>$this_torrent[peers_not_connected]</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Peers complete</b></td>
          <td>$this_torrent[peers_complete]</td>
        </tr>
        <tr class="row">
          <td class="datacol" align="right"><b>Ratio</b></td>
          <td>$ratio</td>
        </tr>
      </table>
    </div>

HTML;
  }



  // ------------------------------------------------
  // --- Storage tab
  // ------------------------------------------------
  if($r_select == 'storage') {
    $sel_dir = $this_torrent['directory'];
    $torrent_dir = '';
    if($this_torrent['is_multi_file']) {
      $slash_pos   = strrpos($this_torrent['directory'],'/');
      $sel_dir     = substr( $this_torrent['directory'], 0, $slash_pos);
      $torrent_dir = substr( $this_torrent['directory'], $slash_pos);
    }
    $torrent_dir = htmlentities($torrent_dir, ENT_QUOTES, 'UTF-8');
    if(isset($r_dir)) $sel_dir = $r_dir;

    $sel_dir_encode = urlencode($sel_dir);
    $torrent_dir_encode = urlencode($torrent_dir);

    echo <<<HTML
    <div class="container">
      <fieldset>
        <legend>Current Directory</legend>

        <div id="current-dir">
          <span id="sel-dir">$sel_dir</span><span class="gray">$torrent_dir</span>
        </div>

        <form action="control.php" method="post" name="directory" id="directory-form">

HTML;
    if($this_torrent['is_active']) {
      echo <<<HTML
          <p>
            <input type="submit" name="setdir" class="themed" value="Set directory" disabled="disabled" />&nbsp;
            <i>Torrent must be stopped before changing directory.</i>
          </p>

HTML;
    } else {
      echo <<<HTML
          <input type="hidden" name="hash" value="$this_torrent[hash]" />
          <input type="hidden" name="newdir" id="new-dir" value="">
          <input type="submit" name="setdir" class="themed" value="Set directory" />

HTML;
    }
    echo <<<HTML
        </form>
      </fieldset>

      <iframe
        src="dirbrowser.php?dir=$sel_dir_encode&amp;hilitedir=$torrent_dir_encode"
        frameborder="0" width="100%" height="300px">
      </iframe>

      <br>&nbsp;
    </div>

HTML;
  }



  // ------------------------------------------------
  // --- Debug tab
  // ------------------------------------------------
  if($debug_mode && $r_select == 'debug') {
?>
    <pre class="medtext">
      <h2>Torrent</h2>
<?php print_r($this_torrent); ?>

      <h2>Files</h2>
<?php print_r(get_file_list($r_hash)); ?>

      <h2>Peers</h2>
<?php print_r(get_peer_list($r_hash)); ?>

      <h2>Tracker</h2>
<?php print_r(get_tracker_list($r_hash)); ?>

    </pre>
<?php
 }



echo <<<HTML
  </div><!-- id="tab-$r_select" -->




HTML;
} // end tab loop
?>

</body>
</html>
