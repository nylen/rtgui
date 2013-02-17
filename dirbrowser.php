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

function do_list($dir, $type) {
  $output = rtorrent_xmlrpc('execute_capture',
    array('find', $dir, '-mindepth', '1', '-maxdepth', '1', '-type', $type));
  if ($output === false) {
    return false;
  }
  $output = trim($output, "\n");
  if ($output === '') {
    return array();
  }
  $to_return = explode("\n", $output);

  // The find command prefixes its output as follows:
  // find /dir   -> /dir/
  // find /dir/  -> /dir/
  // find /dir// -> /dir//
  $to_remove = (substr($dir, strlen($dir) - 1, 1) == '/' ? $dir : $dir . '/');
  for ($i = 0; $i < count($to_return); $i++) {
    $to_return[$i] = rtrim(substr($to_return[$i], strlen($to_remove)), '/');
  }

  natcasesort($to_return);
  return $to_return;
}
function check_dir($dir) {
  global $dir_browser_root;
  if (!$dir_browser_root) {
    $dir_browser_root = '/';
  }
  $root2 = rtrim($dir_browser_root, '/') . '/';
  $dir2 = rtrim($dir, '/') . '/';
  return (substr($dir2, 0, strlen($root2)) == $root2);
}
function is_root_dir($dir) {
  global $dir_browser_root;
  if (!$dir_browser_root) {
    $dir_browser_root = '/';
  }
  return (rtrim($dir, '/') == rtrim($dir_browser_root, '/'));
}
function get_url($dir) {
  global $r_highlight_dir, $r_highlight_filename;
  return 'dirbrowser.php?dir=' . urlencode($dir)
    . '&amp;highlight_dir=' . urlencode($r_highlight_dir)
    . '&amp;highlight_filename=' . urlencode($r_highlight_filename);
}

extract($_REQUEST, EXTR_PREFIX_ALL, 'r');
if (!$r_dir) {
  $r_dir = '';
}
$r_dir = rtrim($r_dir, '/') . '/';

if (!$r_highlight_dir) {
  $r_highlight_dir = '';
}
$r_highlight_dir = rtrim($r_highlight_dir, '/') . '/';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
include_stylesheet('common.css', true);
include_stylesheet('dialogs.css', true);
include_script('jquery.js');
include_script('jquery.mousewheel.js');
?>
<script type="text/javascript">
$(function() {
  $(window).bind('mousewheel', function(e, d) {
    window.top.onMouseWheelFromChildFrame();
  });
});
</script>
</head>
<?php
$dir_encode = htmlentities($r_dir, ENT_QUOTES, 'UTF-8');
echo <<<HTML
<body class="dir-browser modal" onLoad="window.parent.onDirBrowserLoaded('$dir_encode');">

HTML;

if (check_dir($r_dir)) {
  $dirs  = do_list($r_dir, 'd');
  $files = do_list($r_dir, 'f');
} else {
  $dirs  = false;
  $files = false;
}

if ($dirs !== false && $files !== false) {
  echo <<<HTML
  <div id="dir-list">

HTML;

  if (!is_root_dir($r_dir)) {
    $parent_dir = substr($r_dir, 0, strrpos($r_dir, '/', -2));
    $parent_url = get_url($parent_dir);
    echo <<<HTML
    <a href="$parent_url">[..]</a><br />

HTML;
  }

  foreach ($dirs as $dir_name) {
    $dir_full = $r_dir . $dir_name;
    $class = ($r_highlight_dir == $r_dir && $r_highlight_filename == $dir_name
      ? 'highlight folder'
      : 'folder');
    $dir_url = get_url($dir_full);
    $dir_name_encode = htmlentities($dir_name, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
    <img src="images/folder.gif">
    <a href="$dir_url" class="$class">$dir_name_encode</a>
    <br />

HTML;
  }
  foreach ($files as $file_name) {
    $class = ($r_highlight_dir == $r_dir && $r_highlight_filename == $file_name
      ? 'highlight file'
      : 'file');
    $file_name_encode = htmlentities($file_name, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
    <img src="images/file.gif"><span class="$class">$file_name_encode</span><br />

HTML;
  }
} else {
  echo '<h3>Invalid directory!</h3>';
}
?>
</div>
</body>
</html>
