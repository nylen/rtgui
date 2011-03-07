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

if(!function_exists('dirbrowser_scandir')) {
  function dirbrowser_scandir($dir) {
    return @scandir($dir);
  }
}
if(!function_exists('dirbrowser_isdir')) {
  function dirbrowser_isdir($dir) {
    return is_dir($dir);
  }
}
if(!function_exists('dirbrowser_isrootdir')) {
  function dirbrowser_isrootdir($dir) {
    return ($dir == '/');
  }
}

import_request_variables('gp','r_');
if($r_dir == '' || !isset($r_dir)) $r_dir='/';
if(!isset($r_hilitedir)) $r_hilitedir = '';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
include_stylesheet('style.css', true);
include_stylesheet('dialog.css', true);
?>
</head>
<?php
$dir_encode = htmlentities($r_dir, ENT_QUOTES, 'UTF-8');
echo <<<HTML
<body class="dirbrowse" onLoad="window.parent.onDirBrowserLoaded('$dir_encode');">
  <div align="left">

HTML;

if(!dirbrowser_isrootdir($r_dir)) {
  $parent_dir_encode = urlencode(substr($r_dir,0,strrpos($r_dir,"/")));
  $highlight_dir_encode = urlencode($r_hilitedir);
  echo <<<HTML
    <a href="dirbrowser.php?dir=$parent_dir_encode&amp;hilitedir=$highlight_dir_encode">[..]</a><br />

HTML;
}

$files = array();
if($dir_array = dirbrowser_scandir($r_dir)) {
  foreach($dir_array as $file) {
    if($r_dir == '/') {
      $true_dir = $r_dir . $file;
    } else {
      $true_dir = "$r_dir/$file";
    }
    if($file != '.' && $file != '..') {
      if(dirbrowser_isdir($true_dir)) {
        $class = (substr($r_hilitedir, 1) == $file ? 'highlight folder' : 'folder');
        $true_dir_encode = urlencode($true_dir);
        $highlight_dir_encode = urlencode($r_hilitedir);
        $file_encode = htmlentities($file, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
    <img src="images/folder.gif">
    <a href="dirbrowser.php?dir=$true_dir_encode&amp;hilitedir=$highlight_dir_encode" class="$class">
      $file_encode
    </a>
    <br />

HTML;
      } else {
        $files[] = $file;
      }
    }
  }
  foreach($files as $file) {
    $file_encode = htmlentities($file, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
    <img src="images/file.gif"><span class="file">$file_encode</span><br />

HTML;
  }
} else {
  echo '<h3>Invalid directory!</h3>';
}
?>
</div>
</body>
</html>
