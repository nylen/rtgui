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

include 'functions.php';
include 'config.php';
import_request_variables('gp', 'r_');

// Bulk stop/start/delete torrents...
if(isset($r_bulkaction) && is_array($r_select)) {
   foreach($r_select as $hash) {
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
   $r_cmd='';
}

// Set torrent priorities...
if(isset($r_set_tpriority)) {
   $response = do_xmlrpc(xmlrpc_encode_request('d.set_priority', array($r_hash, $r_set_tpriority)));
   $r_cmd = "";
}

//JCN { enable different watch directories
$this_watchdir = $watchdir;
if(!empty($r_torrenttype)) $this_watchdir .= "$r_torrenttype/";
//} JCN


// Add torrent URL...
// JCN - just make this do a wget / curl to the right place (with authentication)
if(!empty($r_addurl)) { // JCN - use empty() instead of isset()
   echo "<pre>";
   system("wget --content-disposition --no-check-certificate --load-cookies=cookies.txt -P '$this_watchdir' '$r_addurl' 2>&1");
   //echo "</pre>\n<script>window.setTimeout(\"location.href='index.php';\", 2000);</script>";
   #die('Adding torrent by URL is not implemented yet.  <a href="index.php">Continue</a>'); // JCN
   //global $load_start;
   /*if ($load_start) // JCN - no don't
     $response = do_xmlrpc(xmlrpc_encode_request("load_start",array("$r_addurl")));
   else
     $response = do_xmlrpc(xmlrpc_encode_request("load",array("$r_addurl")));//*/
}

// Upload torrent file...
if(isset($r_uploadtorrent)) {
   if($_FILES['uploadtorrent']['name'] != "") {
      $tmpfile = $_FILES['uploadtorrent']['name'];
      if (move_uploaded_file($_FILES['uploadtorrent']['tmp_name'], $this_watchdir.basename($_FILES['uploadtorrent']['name']))) {
         //JCN - don't send start request to rtorrent - let the folder watch pick it up
         //$response = do_xmlrpc(xmlrpc_encode_request('load_start',array($watchdir.basename($_FILES['uploadtorrent']['name']))));
         header('Location: index.php');
      } else {
         echo 'Error moving file - check permissions etc!  <a href=index.php>Continue</a>.\n';
      }
      die();
   }
}

// Move torrent dir
if(isset($r_newdir)) {
   $response=do_xmlrpc(xmlrpc_encode_request('d.set_directory', array($r_hash,$r_newdir)));
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
$referer = parse_url($_SERVER['HTTP_REFERER']);
$script = basename($referer['path']);

if(empty($r_uploadtorrent) && empty($r_addurl)) { // JCN (wtf is this for anyway?)
  if($r_cmd == "delete" || !preg_match('@^(index|view|feedread|settings)\\.php$@', $script)) {
    $script = 'index.php';
  }
  @header("Location: $script?".$referer['query']);
}
?>
