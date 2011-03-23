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
rtgui_session_start();
import_request_variables('gp', 'r_');

if(!isset($_SESSION['refresh'])) {
  $_SESSION['refresh'] = 5000; // TODO: allow themes to override this
}
if(isset($r_set_refresh)) {
  $_SESSION['refresh'] = $r_set_refresh;
}

if(isset($r_set_max_up) || isset($r_set_max_down)) {
   rtorrent_xmlrpc('set_upload_rate', array($r_set_max_up));
   rtorrent_xmlrpc('set_download_rate', array($r_set_max_down));
}

$download_cap = rtorrent_xmlrpc('get_download_rate');
$upload_cap = rtorrent_xmlrpc('get_upload_rate');

if(isset($r_submit)) {
   echo "<script>window.top.hideDialog(true);</script>";
   die();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<title>rtGui</title>
<?php
include_stylesheet('style.css', true);
include_stylesheet('dialog.css', true);
?>
</head>
<body class="modal">
<?php if(!$_GET['dialog']) { ?>
  <h3>Settings</h3>
<?php } ?>
  <form method="post" action="settings.php">
    <div id="options">

      <p><label for="set-refresh">Refresh interval : </label>
      <select name="set_refresh" class="themed" id="set-refresh">
<?php
foreach(array(
  0 => 'Off',
  2000 => '2 secs',
  5000 => '5 secs',
  10000 => '10 secs',
  20000 => '20 secs',
  30000 => '30 secs',
  60000 => '1 min',
  300000 => '5 mins',
  60000 => '10 mins'
) as $ms => $txt) {
  $selected = ($_SESSION['refresh'] == $ms ? ' selected="selected"' : '');
  echo <<<HTML
        <option value="$ms"$selected>$txt</option>

HTML;
} ?>
      </select>
      </p>

      <p>&nbsp;</p>
      <p><label for="set-max-down">Download limit : </label>
      <select name="set_max_down" class="themed" class="download" id="set-max-down">
<?php
if(!in_array($download_cap/1024, $cap_speeds) && $download_cap > 0) {
  $bytes = format_bytes($download_cap);
  echo <<<HTML
        <option value="$download_cap" selected="selected">$bytes</option>

HTML;
}
$selected = ($download_cap == 0 ? ' selected="selected"' : '');
echo <<<HTML
        <option value="0"$selected>-Unlimited-</option>

HTML;
foreach($cap_speeds AS $i) {
  $x = $i * 1024;
  $bytes = format_bytes($x);
  $selected = ($x == $download_cap ? ' selected="selected"' : '');
  echo <<<HTML
        <option value="$x"$selected>$bytes</option>

HTML;
}
?>
      </select>
      </p>

      <p>&nbsp;</p>
      <p><label for="set-max-up">Upload limit : </label>
      <select name="set_max_up" class="themed" class="upload" id="set-max-up">
<?php
if(!in_array($upload_cap/1024, $cap_speeds) && $upload_cap > 0) {
  $bytes = format_bytes($upload_cap);
  echo <<<HTML
        <option value="$upload_cap" selected="selected">$bytes</option>

HTML;
}
$selected = ($upload_cap == 0 ? ' selected="selected"' : '');
echo <<<HTML
        <option value="0"$selected>-Unlimited-</option>

HTML;
foreach($cap_speeds AS $i) {
  $x = $i * 1024;
  $bytes = format_bytes($x);
  $selected = ($x == $upload_cap ? ' selected="selected"' : '');
  echo <<<HTML
        <option value="$x"$selected>$bytes</option>

HTML;
}
?>
      </select>
      </p>

      <div id="modalButtons">
        <input type="submit" class="themed" onclick="window.top.hideDialog(false);" value="Cancel" />
        <input type="submit" name="submit" class="themed" value="Save" />
      </div>

    </div>
  </form>
</body>
</html>
