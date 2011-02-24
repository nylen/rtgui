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

// Optionally use alternative XMLRPC library from http://sourceforge.net/projects/phpxmlrpc/
// See http://code.google.com/p/rtgui/issues/detail?id=19
if(!function_exists('xml_parser_create') || !function_exists('xmlrpc_encode_request')) {
  require_once 'xmlrpc.inc';
  require_once 'xmlrpc_extension_api.inc';
}

if(function_exists('on_page_requested')) {
  on_page_requested();
}

if($scgi_local) {
  $scgi_host = "unix://$scgi_local";
  $scgi_port = null;
}

function file_path_mtime($filename) {
  return $filename . '?_=' . filemtime($filename);
}

function include_script($script_filename) {
  echo '<script type="text/javascript" src="'
    . file_path_mtime($script_filename) . "\"></script>\n";
}

function include_stylesheet($stylesheet_filename, $use_theme=false) {
  global $theme;
  if($use_theme) {
    foreach(array($theme, 'base') as $test) {
      $new_filename = "themes/$test/$stylesheet_filename";
      if(file_exists($new_filename)) {
        $stylesheet_filename = $new_filename;
        break;
      }
    }
  }
  echo '<link rel="stylesheet" type="text/css" href="'
    . file_path_mtime($stylesheet_filename) . "\" />\n";
}

require_once 'session.php';

function do_xmlrpc($request) {
  global $scgi_host, $scgi_port, $scgi_timeout;
  if($response = scgi_send($scgi_host, $scgi_port, $request, $scgi_timeout)) {
    $response = parse_http_response($response);
    $content = str_replace('i8', 'double', $response[1]);
    return xmlrpc_decode(utf8_encode($content));
  } else {
    die('<h1>ERROR: Cannot connect to rtorrent</h1>');
  }
}

// Get full list - retrieve full list of torrents 
function get_all_torrents($torrents_only=false, $view='main') {
  global $downloaddir, $use_groups;
  global $tracker_hilite, $tracker_hilite_default;
  
  $torrents = rtorrent_multicall('d', $view, array(
    #'get_base_filename',
    #'get_base_path',
    #'get_bytes_done',
    'get_chunk_size', # only needed to set other items
    'get_chunks_hashed', # only needed to set other items
    'get_complete',
    #'get_completed_bytes', # overflows 32-bit int (must calculate)
    'get_completed_chunks',
    'get_connection_current', # only needed to set other items
    #'get_connection_leech',
    #'get_connection_seed',
    #'get_creation_date',
    'get_directory',
    'get_down_rate',
    'get_down_total',
    #'get_free_diskspace',
    'get_hash',
    'get_hashing', # only needed to set other items
    #'get_ignore_commands',
    #'get_left_bytes',
    #'get_local_id',
    #'get_local_id_html',
    #'get_max_file_size',
    'get_message',
    #'get_peers_min',
    'get_name',
    #'get_peer_exchange',
    #'get_peers_accounted',
    'get_peers_complete',
    'get_peers_connected',
    #'get_peers_max',
    'get_peers_not_connected',
    'get_priority',
    'get_priority_str',
    'get_ratio',
    #'get_size_bytes', # overflows 32-bit int (must calculate)
    'get_size_chunks', # only needed to set other items
    #'get_size_files',
    #'get_skip_rate',
    #'get_skip_total',
    'get_state',
    'get_state_changed',
    'get_tied_to_file',
    #'get_tracker_focus',
    #'get_tracker_numwant',
    #'get_tracker_size',
    'get_up_rate',
    #'get_up_total', # overflows 32-bit int (must calculate)
    #'get_uploads_max',
    'is_active',
    'is_hash_checked',
    'is_hash_checking',
    'is_multi_file',
    'is_open',
    'is_private'
  ), 'hash', true);
  if($response === false) {
    return false;
  }

  if(!is_array($_SESSION['persistent'])) {
    $_SESSION['persistent'] = array();
  }
  $total_down_rate = 0;
  $total_up_rate = 0;
  foreach($torrents as $hash => $t) {
    $t['completed_bytes'] = $t['completed_chunks'] * $t['chunk_size'];
    $t['size_bytes'] = $t['size_chunks'] * $t['chunk_size'];
    $t['up_total'] = $t['size_bytes'] * $t['ratio'] / 1000;
    
    $t['percent_complete'] = $t['completed_bytes'] / $t['size_bytes'] * 100;
    $t['bytes_remaining'] = $t['size_bytes'] - $t['completed_bytes'];
    
    if($t['message'] == 'Tracker: [Tried all trackers.]') {
      $t['message'] = '';
    }

    if($t['is_active'] == 0) {
      $t['status'] = 'Stopped';
    }
    if($t['complete'] == 1) {
      $t['status'] = 'Complete';
    }
    if($t['is_active'] == 1 && $t['connection_current'] == 'leech') {
      $t['status'] = 'Leeching';
    }
    if($t['is_active'] == 1 && $t['complete'] == 1) {
      $t['status'] = 'Seeding';
    }
    if($t['hashing'] > 0) {
      $t['status'] = 'Hashing';
      $t['percent_complete'] = $t['chunks_hashed'] / $t['size_chunks'] * 100;
    }
    
    if($t['complete'] == 1) {
      $t['status_class'] = 'complete';
    } else {
      $t['status_class'] = 'incomplete';
    }
    if($t['is_active'] == 1) {
      $t['status_class'] .= 'active';
    } else {
      $t['status_class'] .= 'inactive';
    }
    if($t['down_rate'] > 0) {
      $t['eta'] = ($t['size_bytes'] - $t['completed_bytes']) / $t['down_rate'];
    } else {
      $t['eta'] = 0;
    }
    
    $t['start_stop_cmd'] = ($t['is_active'] == 1 ? 'stop' : 'start');
    # Format peers_summary to keep the client side sorting routine as simple as possible
    $t['peers_summary'] = sprintf('%03d,%03d,%03d',
      $t['peers_connected'], $t['peers_not_connected'], $t['peers_complete']
    );
    
    $t['is_transferring'] = (($t['down_rate'] + $t['up_rate']) ? 1 : 0);
    
    if(is_array($_SESSION['persistent'][$hash])) {
      $s = $_SESSION['persistent'][$hash];
    } else {
      $s = array();
      $s['tracker_hostname'] = tracker_hostname($hash);
      $s['tracker_color'] = $tracker_hilite_default;
      if(is_array($tracker_hilite)) {
        foreach($tracker_hilite as $hilite) {
          foreach($hilite as $thisurl) {
            if(stristr($s['tracker_hostname'], $thisurl) !== false) {
              $s['tracker_color'] = $hilite[0];
            }
          }
        }
      }
      if($use_groups) {
        $s['group'] = get_torrent_group($t);
      }
      $fn = $t['tied_to_file'];
      if(function_exists('get_local_torrent_path')) {
        $fn = get_local_torrent_path($fn);
      }
      $s['date_added'] = filemtime($fn);
      $_SESSION['persistent'][$hash] = $s;
    }
    
    $t['tracker_hostname'] = $s['tracker_hostname'];
    $t['tracker_color'] = $s['tracker_color'];
    if($use_groups) {
      $t['group'] = $s['group'];
    }
    $t['date_added'] = $s['date_added'];
    
    $total_down_rate += $t['down_rate'];
    $total_up_rate += $t['up_rate'];
    
    // unset items that are only needed for setting other items
    unset($t['chunk_size']);
    unset($t['chunks_hashed']);
    unset($t['connection_current']);
    unset($t['hashing']);
    unset($t['size_chunks']);
    
    $torrents[$hash] = $t;
  }
  
  if($torrents_only) {
    return $torrents;
  }
  
  $data = array(
    'torrents'         => $torrents,
    'total_down_rate'  => $total_down_rate,
    'total_up_rate'    => $total_up_rate,
    'total_down_limit' => rtorrent_xmlrpc('get_download_rate'),
    'total_up_limit'   => rtorrent_xmlrpc('get_upload_rate'),
    'disk_free'        => @disk_free_space($downloaddir),
    'disk_total'       => @disk_total_space($downloaddir),
  );
  if($data['disk_total'] > 0) {
    $data['disk_percent'] = $data['disk_free'] / $data['disk_total'] * 100;
  } else {
    // avoid divide by zero error if disk_total_space() fails
    $data['disk_percent'] = 0;
  }
  
  return $data;
}

// Get list of files associated with a torrent...
function get_file_list($hash) {
  $use_old_api = (rtorrent_xmlrpc('system.client_version') == '0.7.9');
  $results = rtorrent_multicall('f', array($hash, ''), array(
    'get_completed_chunks',
    'get_frozen_path',
    ($use_old_api ? 'get_is_created' : 'is_created'),
    ($use_old_api ? 'get_is_open' : 'is_open'),
    'get_last_touched',
    'get_match_depth_next',
    'get_match_depth_prev',
    'get_offset',
    'get_path',
    'get_path_components',
    'get_path_depth',
    'get_priority',
    'get_range_first',
    'get_range_second',
    'get_size_bytes',
    'get_size_chunks'
  ));
  
  if(!$use_old_api) {
    for($i=0; $i<count($results); $i++) {
      $results[$i]['get_is_created'] = $results[$i]['is_created'];
      $results[$i]['get_is_open'] = $results[$i]['is_open'];
    }
  }
  
  return $results;
}

// Get list of trackers associated with torrent...
function get_tracker_list($hash) {
  return rtorrent_multicall('t', array($hash, ''), array(
    'get_group',
    'get_id',
    'get_min_interval',
    'get_normal_interval',
    'get_scrape_complete',
    'get_scrape_downloaded',
    'get_scrape_time_last',
    'get_type',
    'get_url',
    'is_enabled',
    'is_open'
  ));
}

// Get list of peers associated with torrent...
function get_peer_list($hash) {
  if(rtorrent_xmlrpc('system.client_version') == '0.7.9') {
    return array();
  }
  return rtorrent_multicall('p', array($hash, ''), array(
    'get_address',
    'get_client_version',
    'get_completed_percent',
    'get_down_rate',
    'get_down_total',
    'get_id',
    'get_id_html',
    'get_options_str',
    'get_peer_rate',
    'get_peer_total',
    'get_port',
    'get_up_rate',
    'get_up_total',
    'is_encrypted',
    'is_incoming',
    'is_obfuscated',
    'is_snubbed'
  ));
}

function tracker_hostname($hash) {
  $response = do_xmlrpc(xmlrpc_encode_request('t.multicall', array($hash, '', 't.get_url=')));
  return preg_replace('@^tracker\.@', '', @parse_url($response[0][0], PHP_URL_HOST));
}

/** rtorrent_xmlrpc
 * 
 * Short function to execute and return an XML-RPC request.
 * TODO: rename?
 */
function rtorrent_xmlrpc($command, $params=array('')) {
  $response = do_xmlrpc(xmlrpc_encode_request($command, $params));
  return (@xmlrpc_is_fault($response) ? false : $response);
}

/** rtorrent_multicall
 * 
 * Does a "multicall" request to rtorrent and returns an array of data
 * items that can be either sequential or associative.  Each data item
 * is an associative array with the requested variables as keys.
 */
function rtorrent_multicall($group, $params, $data_names, $key=null, $remove_get=false) {
  if(!is_array($params)) {
    $params = array($params);
  }
  $index = -1;
  foreach($data_names as $name) {
    $index++;
    $params[] = "$group.$name=";
    if($remove_get) {
      $name = preg_replace("@^get_@", "", $name);
      $data_names[$index] = $name;
    }
    if($key !== null && $key == $name) {
      $key_index = $index;
    }
  }
  
  $request = xmlrpc_encode_request("$group.multicall", $params);
  $response = do_xmlrpc($request);
  if(@xmlrpc_is_fault($response)) {
    trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
    return false;
  } else {
    $results = array();
    $result_index = 0;
    foreach($response as $data_array) {
      $this_key = ($key === null ? $result_index++ : $data_array[$key_index]);
      $name_index = 0;
      foreach($data_array as $data_item) {
        $results[$this_key][$data_names[$name_index++]] = $data_item;
      }
    }
    return $results;
  }
}

/** array_compare
 * 
 * Modified from: 
 * http://www.php.net/manual/en/function.array-diff-assoc.php#89635
 * 
 * Finds only the differences between two arbitrarily nested data
 * arrays.  For the output of this function to be suitable for use
 * with json_encode and jQuery.extend, $before and $after should
 * not have sequential keys or contain elements that have sequential
 * keys.  Or, you can pass json_encode the JSON_FORCE_OBJECT flag.
 */
function array_compare($before, $after) {
  $diff = false;
  // check all keys in $before (to find changed and deleted items)
  foreach($before as $key => $value) {
    if(!array_key_exists($key, $after)) {
      // $key is no longer a key
      $diff[$key] = null;
    } else if(is_array($value)) {
      // found an array
      if(is_array($after[$key])) {
        // still an array; compare recursively
        $new = array_compare($value, $after[$key]);
        if($new !== false) {
          $diff[$key] = $new;
        }
      } else {
        // the value for $key is no longer an array
        $diff[$key] = $after[$key];
      }
    } else if($after[$key] !== $value) {
      // the value for $key changed
      $diff[$key] = $after[$key];
    }
  }
  // check all keys in $after (to find new items)
  foreach($after as $key => $value) {
    if(!array_key_exists($key, $before)) {
      // $key is a new key
      $diff[$key] = $value;
    }
  }
  return $diff;
}

function parse_http_response($string) {
  $headers = array();
  $arr = explode("\n", $string);
  for($i = 0; $i < count($arr); $i++) {
    $str = trim($arr[$i]);
    if($str === '') {
      return array($headers, implode("\n", array_slice($arr, $i + 1)));
    } else {
      list($name, $val) = explode(':', $str, 2);
      $name = strtolower($name);
      $val = ltrim($val);
      if(is_array($headers[$name])) {
        $headers[$name][] = $val;
      } else if(isset($headers[$name])) {
        $headers[$name] = array($headers[$name], $val);
      } else {
        $headers[$name] = $val;
      }
    }
  }
  return array($headers, '');
}

// from source code of rutorrent
function scgi_send($host, $port, $data, $timeout=5) {
  $result = '';
  $contentlength = strlen($data);
  if($contentlength > 0) {
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if($socket) {
      $reqheader = "CONTENT_LENGTH\x00$contentlength\x00SCGI\x001\x00";
      $tosend = strlen($reqheader) . ":$reqheader,$data";
      @fputs($socket, $tosend);
      while(!feof($socket)) {
        $result .= @fread($socket, 4096);
      }
      fclose($socket);
    }
  }
  return $result;
}


// ---------- Old functions that should probably go away one day

// multibyte-safe replacement for wordwrap.
// (See http://code.google.com/p/rtgui/issues/detail?id=71 - Thanks llamaX)
function mb_wordwrap($string, $width=75, $break="\n", $cut=false) {
  if(!$cut) {
    $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){' . $width . ',}\b#U';
  } else {
    $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){' . $width . '}#';
  }
  $string_length = mb_strlen($string, 'UTF-8');
  $cut_length = ceil($string_length / $width);
  $i = 1;
  $return = '';
  while($i < $cut_length) {
    preg_match($regexp, $string, $matches);
    $new_string = $matches[0];
    $return .= $new_string . $break;
    $string = substr($string, strlen($new_string));
    $i++;
  }
  return $return . $string;
}

// Format no.bytes nicely...
function format_bytes($bytes) {
    if ($bytes==0) return "";
    $unim = array("B","KB","MB","GB","TB","PB");
    $c = 0;
    while ($bytes>=1024) {
        $c++;
        $bytes = $bytes/1024;
    }
    return number_format($bytes,($c ? 1 : 0),".",",")." ".$unim[$c];
}

// Draw the percent bar using a table...
function percentbar($percent) {
   $retvar="<table align=center border=0 cellspacing=0 cellpadding=1 bgcolor=#666666 width=50><tr><td align=left>";
   $retvar.="<img src='images/percentbar.gif' height=4 width=".round($percent/2)." /></td></tr>";   
   $retvar.="</table>";
   return $retvar;
}

?>
