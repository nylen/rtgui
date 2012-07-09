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

$data = get_all_torrents(array('for_html' => true));

if (@is_array($_SESSION['last_data'])) {
  $last_data = $_SESSION['last_data'];
  $diff_torrents = array_compare_special($last_data['torrents'], $data['torrents'], 2);
  $diff_global   = array_compare_special($last_data['global'],   $data['global']);
  $return = array();
  if ($diff_torrents !== false) {
    $return['torrents'] = $diff_torrents;
  }
  if ($diff_global !== false) {
    $return['global'] = $diff_global;
  }
  echo json_encode($return);
} else {
  echo json_encode($data);
}

$_SESSION['last_data'] = $data;
?>
