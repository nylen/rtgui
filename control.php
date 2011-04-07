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

// Bulk stop/start/delete torrents...
if(isset($r_bulkaction) && is_array($r_select)) {
  foreach($r_select as $hash) {
    if($hash) {
      switch($r_bulkaction) {
        case 'stop':
          $response = do_xmlrpc(xmlrpc_encode_request('d.stop', array($hash)));
          break;

        case 'start':
          $response = do_xmlrpc(xmlrpc_encode_request('d.start', array($hash)));
          break;

        case 'delete':
          $response = do_xmlrpc(xmlrpc_encode_request('d.erase', array($hash)));
          break;

        case 'hashcheck':
          $response = do_xmlrpc(xmlrpc_encode_request('d.check_hash', array($hash)));
          break;

        case 'pri_high':
          $response = do_xmlrpc(xmlrpc_encode_request('d.set_priority', array($hash, 3)));
          break;

        case 'pri_normal':
          $response = do_xmlrpc(xmlrpc_encode_request('d.set_priority', array($hash, 2)));
          break;

        case 'pri_low':
          $response = do_xmlrpc(xmlrpc_encode_request('d.set_priority', array($hash, 1)));
          break;

        case 'pri_off':
          $response = do_xmlrpc(xmlrpc_encode_request('d.set_priority', array($hash, 0)));
          break;
      }
    }
  }
  $r_cmd = '';
}



// Set file priorities...
if(isset($r_set_fpriority)) {
   $index = 0;
   foreach($r_set_fpriority as $item) {
      $response = do_xmlrpc(xmlrpc_encode_request('f.set_priority', array($r_hash,$index,$item)));
      $index++;
   }
   $response=do_xmlrpc(xmlrpc_encode_request('d.update_priorities', $r_hash));
   $r_cmd = '';
}

// Set torrent priorities...
if(isset($r_set_tpriority)) {
   $response = do_xmlrpc(xmlrpc_encode_request('d.set_priority', array($r_hash, $r_set_tpriority)));
   $r_cmd = '';
}


// Move torrent dir
if(isset($r_newdir)) {
  $old_path = rtorrent_xmlrpc('d.get_base_path', array($r_hash));
  if(!$old_path) {
    // work around rTorrent bug? sometimes get_base_path returns ''
    $old_path = rtorrent_xmlrpc('d.get_directory', array($r_hash));
    if(!rtorrent_xmlrpc('d.is_multi_file', array($r_hash))) {
      $old_path .= '/' . rtorrent_xmlrpc('d.get_name', array($r_hash));
    }
  }
  if(rtrim(dirname($old_path), '/') !== rtrim($r_newdir, '/')) {
    if(rtorrent_xmlrpc('execute', array('mv', '-u', $old_path, "$r_newdir/")) === false) {
      die("Failed to move '$old_path' to '$r_newdir'.  Check rTorrent's execute_log.");
    }
    rtorrent_xmlrpc('d.set_directory', array($r_hash, $r_newdir));
    rtorrent_xmlrpc('d.check_hash', array($r_hash));
  }
}

switch($r_cmd) {
   case 'stop':
      $response = do_xmlrpc(xmlrpc_encode_request('d.stop', array($r_hash)));
      break;
   case 'start':
      $response = do_xmlrpc(xmlrpc_encode_request('d.start', array($r_hash)));
      break;
   case 'delete':
      $response = do_xmlrpc(xmlrpc_encode_request('d.erase', array($r_hash)));
      break;
   case 'hashcheck':
      $response = do_xmlrpc(xmlrpc_encode_request('d.check_hash', array($r_hash)));
      break;
}

if(!$r_ajax) {
  $hash = ($r_tab ? "#$r_tab" : '');
  @header('Location: ' . $_SERVER['HTTP_REFERER'] . $hash);
}
?>
