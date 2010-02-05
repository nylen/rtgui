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
if(!function_exists('xml_parser_create')) {
  include('xmlrpc.inc');
  include('xmlrpc_extension_api.inc');
}

function do_xmlrpc($request) {
  global $rpc_connect;
  $context = stream_context_create(array(
    'http' => array(
      'method'  => 'POST',
      'header'  => 'Content-Type: text/xml',
      'content' => $request
    )
  ));
  if($file = @file_get_contents($rpc_connect, false, $context)) {
    $file=str_replace('i8', 'double', $file);
    $file = utf8_encode($file); 
    return xmlrpc_decode($file);
  } else {
    die ('ERROR: Cannot connect to rtorrent');
  }
}

// Get full list - retrieve full list of torrents 
function get_all_torrents($torrents_only=false, $view='main') {
  global $downloaddir, $use_groups, $use_date_added;
  global $tracker_hilite, $tracker_hilite_default;
  
  // TODO: remove unnecessary items
  $torrents = rtorrent_multicall('d', $view, array(
    'get_base_filename',
    'get_base_path',
    'get_bytes_done',
    'get_chunk_size',
    'get_chunks_hashed',
    'get_complete',
    #'get_completed_bytes', # overflows 32-bit int (must calculate)
    'get_completed_chunks',
    'get_connection_current',
    'get_connection_leech',
    'get_connection_seed',
    'get_creation_date',
    'get_directory',
    'get_down_rate',
    'get_down_total',
    #'get_free_diskspace', # unnecessary, and clutters diffs
    'get_hash',
    'get_hashing',
    'get_ignore_commands',
    'get_left_bytes',
    'get_local_id',
    'get_local_id_html',
    'get_max_file_size',
    'get_message',
    'get_peers_min',
    'get_name',
    'get_peer_exchange',
    'get_peers_accounted',
    'get_peers_complete',
    'get_peers_connected',
    'get_peers_max',
    'get_peers_not_connected',
    'get_priority',
    'get_priority_str',
    'get_ratio',
    #'get_size_bytes', # overflows 32-bit int (must calculate)
    'get_size_chunks',
    'get_size_files',
    'get_skip_rate',
    'get_skip_total',
    'get_state',
    'get_state_changed',
    'get_tied_to_file',
    'get_tracker_focus',
    'get_tracker_numwant',
    'get_tracker_size',
    'get_up_rate',
    #'get_up_total', # overflows 32-bit int (must calculate)
    'get_uploads_max',
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

  if(!is_array($_SESSION['trackers'])) {
    $_SESSION['trackers'] = array();
  }
  if($use_date_added && !is_array($_SESSION['dates_added'])) {
    $_SESSION['dates_added'] = array();
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
    
    if(!is_array($_SESSION['trackers'][$hash])) {
      $tracker_info = array();
      $tracker_info['hostname'] = tracker_hostname($hash);
      $tracker_info['color'] = $tracker_hilite_default;
      if(is_array($tracker_hilite)) {
        foreach($tracker_hilite as $hilite) {
          foreach($hilite as $thisurl) {
            if(stristr($tracker_info['hostname'], $thisurl) !== false) {
              $tracker_info['color'] = $hilite[0];
            }
          }
        }
      }
      $_SESSION['trackers'][$hash] = $tracker_info;
    }
    $t['tracker_hostname'] = $_SESSION['trackers'][$hash]['hostname'];
    $t['tracker_color'] = $_SESSION['trackers'][$hash]['color'];
    
    if($use_groups) {
      $t['group'] = get_torrent_group($t);
    }
    
    if($use_date_added) {
      if(!array_key_exists($t['hash'], $_SESSION['dates_added'])) {
        $_SESSION['dates_added'][$t['hash']] = filemtime(get_local_torrent_path($t['tied_to_file']));
      }
      $t['date_added'] = $_SESSION['dates_added'][$t['hash']];
    }
    
    $total_down_rate += $t['down_rate'];
    $total_up_rate += $t['up_rate'];
    
    // TODO: unset items that are only needed for setting other items
    
    $torrents[$hash] = $t;

if(
$t['hash'] != 'BDD3B6E23FAD1162FBC8715091E9D271C16DCCB1' &&
$t['hash'] != '574BE305F929A43E8514D20F4AD3BBE25DA92B50' &&
$t['hash'] != 'C9AD76C84656F241E45E487BFF9CF261441111DF' &&
$t['hash'] != 'AC1E12B23F5BA8CD865B99B620ABCA1BEA9EAA5E' &&
$t['hash'] != 'A0CD6C476E7327FB027A24D3A1537B1BEBBC4A05' &&
$t['hash'] != '7089C8A0DEFC4E4BDFB982C4C7F6C95924D62F72' &&
$t['hash'] != '718841905D0ACE1F90BE6CF399F8CF01CF00993B' &&
$t['hash'] != '2AAB1BD3573A90E3300D627D0848DEA7E5CEDE53' &&
$t['hash'] != 'A9AD96B4D961B6A254D92C306589436AF6E9AD2B' &&
$t['hash'] != '0B1F55631824BA1112B7BFC49AD09C8EBC0EE7C7' &&
$t['hash'] != '63432A2E03901AB949D3A98A4A21B1FACF5969F0' &&
$t['hash'] != 'FA6DF293ADBC6B328D23CF5539244A4D7B4FB3F8' &&
$t['hash'] != 'DFCE850F7B9EF443B91AD5210BBDEBB6839FB9BD' &&
$t['hash'] != 'B7016B9FE7796FC4406F5958C3297B8A48F0D9D7' &&
$t['hash'] != 'B61FE3506D63651745DFBB32AAF86360CC94836C' &&
$t['hash'] != '46C1BE3F937CFD29DB118FA95649CB668059CD1B' &&
$t['hash'] != '9A3DD4909039325322CDF6F44057623E194B61D5' &&
$t['hash'] != '945B92F11633BC4E8CBADE5EF4154D80C10D816B' &&
$t['hash'] != 'B44E94BE485281996ECF2DA23ADC784121E4EAA9' &&
$t['hash'] != '800B03CAF68041CEEB37E068033F9F165A9BBD98' &&
$t['hash'] != 'B8B5887706054D2F68D9AEEA82DAC88F741294AE' &&
$t['hash'] != '00AB2EDFD9E95825A90589F9801376F737429877' &&
$t['hash'] != 'B940928271A21C829438CD762E40BA0F403BA5A4' &&
$t['hash'] != 'B3D96D98D6C98DB58BC97EEBEF231F18497E75F6' &&
$t['hash'] != 'DF5CEBD2CF4EDBCAF73787847AC6B3E08845BA1D' &&
$t['hash'] != '7544E67A9A870AAB81432FFC596276E793271BB7' &&
$t['hash'] != '0680F91EF65D018F63362A6BBFBA45661273F749' &&
$t['hash'] != '5CF4F168BF8AD1C47092145F89A0795C9D3F49D5' &&
$t['hash'] != '8069CD28D3394989FC47E0529284CA0697DFEB2A' &&
$t['hash'] != '6C7B6EA0CAC1FA15DE17347174F3CCADBECF8621' &&
$t['hash'] != 'B9F2C7BC86FB149B989B6F6DA19EC1A2315CC27F' &&
$t['hash'] != '1C44F5117B523B82EAB696BA743BF44A0A5D46E6' &&
$t['hash'] != '500649BBECB5BF7DE8DB503FC58FBDAAABB6EB12' &&
$t['hash'] != '020A921986B62306F36C1DC83AE0066D430FB525' &&
$t['hash'] != 'C457E3F4C3FF0AE665DD7A2CA5107B8E6C3FFBD1' &&
$t['hash'] != '3F9AADA584AD6F20B17D72448BCD06F7810EBD16' &&
$t['hash'] != 'F8A676F8E0A9A1F82DD6B340C7B1196FD9619CE9' &&
$t['hash'] != 'DC06B99C7C75A2298C7E9D51CD1EA31B34403300' &&
$t['hash'] != '44F0710F4F7B043FB7B438598DB6D53860A14D4A' &&
$t['hash'] != 'BFB5C6C773253DEC1294C5794423CA88BEFB8C55' &&
$t['hash'] != '06905E68CCF1EF9986101CF581C79F7311209CD4' &&
$t['hash'] != '11B530C9D9BE2B5F92D148850306EDD3E31CD217' &&
$t['hash'] != 'AEC60A058DA7913B6656990DEEAF82C6151243D4') {
unset($torrents[$hash]);
}

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
  return @parse_url($response[0][0], PHP_URL_HOST);
}

/** rtorrent_xmlrpc
 * 
 * Short function to execute and return an XML-RPC request.
 * TODO: rename?
 */
function rtorrent_xmlrpc($command, $params=array('')) {
  $response = do_xmlrpc(xmlrpc_encode_request($command, $params));
  return (xmlrpc_is_fault($response) ? false : $response);
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
  if(xmlrpc_is_fault($response)) {
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
   $retvar.="<img src='images/percentbar.gif' height=4 width=".round($percent)." /></td></tr>";   
   $retvar.="</table>";
   return $retvar;
}

?>
