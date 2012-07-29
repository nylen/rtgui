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

require_once 'session.php';
require_once 'Mobile_Detect.php';

// Optionally use alternative XMLRPC library from http://sourceforge.net/projects/phpxmlrpc/
// See http://code.google.com/p/rtgui/issues/detail?id=19
if (!function_exists('xml_parser_create') || !function_exists('xmlrpc_encode_request')) {
  require_once 'xmlrpc.inc';
  require_once 'xmlrpc_extension_api.inc';
}

error_reporting(E_ALL & ~E_NOTICE);

if (function_exists('on_page_requested')) {
  on_page_requested();
}

if ($scgi_local) {
  $scgi_host = "unix://$scgi_local";
  $scgi_port = null;
}

function get_current_theme() {
  global $default_user_settings;
  if (!$_COOKIE['theme']) {
    // TODO: enumerate themes and look for user-agent match
    if (!$_COOKIE['theme']) {
      set_user_setting('theme', $default_user_settings['theme']);
    }
  }
  if (!is_dir('themes/' . $_COOKIE['theme'])) {
    if (!is_dir('themes/default')) {
      die('Error: default theme not found');
    }
    set_user_setting('theme', 'default');
  }
  return $_COOKIE['theme'];
}

function is_mobile_browser() {
  global $mobile_detect;
  if (!$mobile_detect) {
    $mobile_detect = new Mobile_Detect();
  }
  return $mobile_detect->isMobile();
}

function get_user_setting($key, $should_set=true) {
  global $default_user_settings, $mobile_detect;
  if (array_key_exists($key, $_COOKIE)) {
    return $_COOKIE[$key];
  } else {
    switch ($key) {
      case 'use_dialogs':
        $default_value = (is_mobile_browser() ? 'no' : 'yes');
        break;
      default:
        $default_value = $default_user_settings[$key];
        break;
    }
    if ($should_set) {
      set_user_setting($key, $default_value);
    }
    return $default_value;
  }
}

function set_user_setting($key, $value) {
  @setcookie($key, $value, time()+60*60*24*365, get_rtgui_path());
  // HACK: make the desired value available before the next page load
  $_COOKIE[$key] = $value;
}

function file_path_mtime($filename) {
  return $filename . '?_=' . filemtime($filename);
}

function set_torrent_tags($hashes, $add_tags, $remove_tags) {
  global $private_storage_dir;
  if (is_array($hashes)) {
    if (!is_array($add_tags)) {
      $add_tags = array();
    }
    if (!is_array($remove_tags)) {
      $remove_tags = array();
    }
    $tags = new PersistentObject("$private_storage_dir/tags.txt");
    foreach ($hashes as $hash) {
      if (!is_array($tags->data[$hash])) {
        $tags->data[$hash] = array();
      }
      $tags->data[$hash] = array_unique(array_merge(
        array_diff($tags->data[$hash], $remove_tags), $add_tags));
      sort($tags->data[$hash]);
    }
    $tags->save();
  }
}

function include_script($script_name) {
  $script_filename = (strpos($script_name, '/') === false ? 'js/' : '') . $script_name;
  echo '<script type="text/javascript" src="'
    . file_path_mtime($script_filename) . "\"></script>\n";
}

function get_theme_filename($filename) {
  foreach (array(get_current_theme(), 'default') as $test) {
    $theme_filename = "themes/$test/$filename";
    if (file_exists($theme_filename)) {
      return $theme_filename;
    }
  }
  trigger_error("Cannot find theme filename '$filename'.");
}


function include_stylesheet($stylesheet_filename, $use_theme=false) {
  if ($use_theme) {
    $stylesheet_filename = get_theme_filename($stylesheet_filename);
  }
  echo '<link rel="stylesheet" type="text/css" href="'
    . file_path_mtime($stylesheet_filename) . "\" />\n";
}

function get_template_filename($template_name) {
  return get_theme_filename("templates/$template_name.html");
}

function include_template($template_name) {
  $template_contents = file_get_contents(get_template_filename($template_name));
  echo <<<HTML
    <script type="text/html" id="template-$template_name">
$template_contents
    </script>
HTML;
}

$twig_env = null;
$twig_templates = array();

function twig_init_if_needed() {
  global $twig_env;
  if ($twig_env === null) {
    require_once 'Twig/lib/Twig/Autoloader.php';
    Twig_Autoloader::register();
    $loader = new Twig_Loader_Filesystem('.');
    $twig_env = new Twig_Environment($loader, array(
      cache => 'tmp/twig-cache',
      auto_reload => true
    ));
  }
}

function render_template($template_name, $data) {
  global $twig_env, $twig_templates;
  twig_init_if_needed();
  if (!$twig_templates[$template_name]) {
    $twig_templates[$template_name] = $twig_env->loadTemplate(get_template_filename($template_name));
  }
  return $twig_templates[$template_name]->render($data);
}


function do_xmlrpc($request) {
  global $scgi_host, $scgi_port, $scgi_timeout;
  if ($response = scgi_send($scgi_host, $scgi_port, $request, $scgi_timeout)) {
    $response = parse_http_response($response);
    $content = str_replace('i8', 'double', $response[1]);
    return xmlrpc_decode(utf8_encode($content));
  } else {
    die('<h1>ERROR: Cannot connect to rtorrent</h1>');
  }
}

function get_param($params, $name, $default=null) {
  return (isset($params[$name]) ? $params[$name] : $default);
}

// Get full list - retrieve full list of torrents
// TODO: Make this function take a named-params array
function get_all_torrents($params) {
  $torrents_only = get_param($params, 'torrents_only', false);
  $for_html      = get_param($params, 'for_html', false);
  $view          = get_param($params, 'view', 'main');

  global $disk_usage_dir;
  global $tracker_highlight, $tracker_highlight_default;
  global $can_hide_unhide, $date_added_format;

  $show_hidden = ($can_hide_unhide && get_user_setting('show_hidden') == 'yes');

  $torrents = rtorrent_multicall('d', 'main', array(
    #'get_base_filename',
    'get_base_path',
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
  if ($response === false) {
    return false;
  }

  if (!is_array($_SESSION['persistent'])) {
    $_SESSION['persistent'] = array();
  }
  $total_down_rate = 0;
  $total_up_rate = 0;
  $torrents_count_all = count($torrents);
  $torrents_count_superhidden = 0;
  $torrents_count_visible = 0;

  $index = 0;

  foreach ($torrents as $hash => $t) {
    $total_down_rate += $t['down_rate'];
    $total_up_rate += $t['up_rate'];

    if (is_array($_SESSION['tags'][$hash])) {
      $t['tags'] = $_SESSION['tags'][$hash];
      if (in_array('_hidden', $_SESSION['tags'][$hash])) {
        $torrents_count_superhidden++;
        if (!$show_hidden) {
          unset($torrents[$hash]);
          continue;
        }
      }
    } else {
      $t['tags'] = array();
    }

    $t['completed_bytes'] = $t['completed_chunks'] * $t['chunk_size'];
    $t['size_bytes'] = $t['size_chunks'] * $t['chunk_size'];
    $t['up_total'] = $t['size_bytes'] * $t['ratio'] / 1000;

    $t['percent_complete'] = $t['completed_bytes'] / $t['size_bytes'] * 100;
    $t['bytes_remaining'] = $t['size_bytes'] - $t['completed_bytes'];

    if ($t['message'] == 'Tracker: [Tried all trackers.]') {
      $t['message'] = '';
    }

    if ($t['is_active'] == 0) {
      $t['status'] = 'Stopped';
    }
    if ($t['complete'] == 1) {
      $t['status'] = 'Complete';
    }
    if ($t['is_active'] == 1 && $t['connection_current'] == 'leech') {
      $t['status'] = 'Leeching';
    }
    if ($t['is_active'] == 1 && $t['complete'] == 1) {
      $t['status'] = 'Seeding';
    }
    if ($t['hashing'] > 0) {
      $t['status'] = 'Hashing';
      $t['percent_complete'] = $t['chunks_hashed'] / $t['size_chunks'] * 100;
    }

    if ($t['complete'] == 1) {
      $t['status_class'] = 'complete';
    } else {
      $t['status_class'] = 'incomplete';
    }
    if ($t['is_active'] == 1) {
      $t['status_class'] .= 'active';
    } else {
      $t['status_class'] .= 'inactive';
    }

    if ($t['down_rate'] > 0) {
      $t['eta'] = ($t['size_bytes'] - $t['completed_bytes']) / $t['down_rate'];
    } else {
      $t['eta'] = 0;
    }

    $t['start_stop_cmd'] = ($t['is_active'] == 1 ? 'stop' : 'start');
    # Format peers_summary to keep the sorting routine as simple as possible
    $t['peers_summary'] = sprintf('%03d,%03d,%03d',
      $t['peers_connected'], $t['peers_not_connected'], $t['peers_complete']
    );

    $t['is_transferring'] = (($t['down_rate'] + $t['up_rate']) ? 1 : 0);

    if (is_array($_SESSION['persistent'][$hash])) {
      $s = $_SESSION['persistent'][$hash];
    } else {
      $s = array();
      $s['tracker_hostname'] = tracker_hostname($hash);
      $s['tracker_color'] = $tracker_highlight_default;
      if (is_array($tracker_highlight)) {
        foreach ($tracker_highlight as $highlight) {
          foreach ($highlight as $this_url) {
            if (stristr($s['tracker_hostname'], $this_url) !== false) {
              $s['tracker_color'] = $highlight[0];
            }
          }
        }
      }
      $fn = $t['tied_to_file'];
      if (function_exists('get_local_torrent_path')) {
        $fn = get_local_torrent_path($fn);
      }
      if (file_exists($fn)) {
        // Yes, this even works for magnet links.  rTorrent creates a ".meta"
        // file and sets it as "tied_to_file".
        $s['date_added'] = filemtime($fn);
      } else {
        $s['date_added'] = 0;
      }
      $_SESSION['persistent'][$hash] = $s;
    }

    $t['tracker_hostname'] = $s['tracker_hostname'];
    $t['tracker_color']    = $s['tracker_color'];
    $t['date_added']       = $s['date_added'];

    // unset items that are only needed for setting other items
    unset($t['chunk_size']);
    unset($t['chunks_hashed']);
    unset($t['connection_current']);
    unset($t['hashing']);
    unset($t['size_chunks']);

    if ($for_html) {
      $t['server_index'] = $index++;
      $t['server_visible'] = true;

      switch ($view) {
        case 'main':
          // (Always visible)
          break;
        case 'started':
          $t['server_visible'] = !!$t['state'];
          break;
        case 'stopped':
          $t['server_visible'] = !$t['state'];
          break;
        case 'active':
          $t['server_visible'] = !!$t['is_transferring'];
          break;
        case 'inactive':
          $t['server_visible'] = !$t['is_transferring'];
          break;
        case 'complete':
          $t['server_visible'] = !!$t['complete'];
          break;
        case 'incomplete':
          $t['server_visible'] = !$t['complete'];
          break;
        case 'seeding':
          $t['server_visible'] = (!!$t['complete'] && !!$t['state']);
          break;
      }
      if ($t['server_visible']) {
        $torrents_count_visible++;
      }

      // unset items that aren't needed by the HTML templates

      unset($t['base_path']);
      unset($t['directory']);
      unset($t['is_active']);
      unset($t['is_hash_checked']);
      unset($t['is_hash_checking']);
      unset($t['is_multi_file']);
      unset($t['is_open']);
      unset($t['is_private']);
      unset($t['priority']);
      unset($t['state_changed']);
      unset($t['tied_to_file']);

      // set some string values for the HTML templates

      $t['date_added_str']         = ($t['date_added'] ? date($date_added_format, $t['date_added']) : '');
      $t['eta_str']                = format_duration($t['eta']);
      $t['percent_complete_str']   = round($t['percent_complete'], 1) . '%';
      $t['percent_complete_width'] = round($t['percent_complete'] / 2);
      $t['tags_str']               = implode('|', $t['tags']);

      $t['bytes_remaining_str']    = format_bytes($t['bytes_remaining'], '&nbsp;', '');
      $t['size_bytes_str']         = format_bytes($t['size_bytes']     , '&nbsp;', '');
      $t['down_rate_str']          = format_bytes($t['down_rate']      , '&nbsp;', '/s');
      $t['up_rate_str']            = format_bytes($t['up_rate']        , '&nbsp;', '/s');
      $t['up_total_str']           = format_bytes($t['up_total']       , '&nbsp;', '');
      $t['ratio_str']              = number_format($t['ratio'] / 1000, 2);
    }

    $torrents[$hash] = $t;
  }

  if ($torrents_only) {
    return $torrents;
  }

  if (isset($disk_usage_dir)) {
    $df_output = rtorrent_xmlrpc('execute_capture',
      array('sh', '-c', "BLOCKSIZE=1 df \"$disk_usage_dir\""));
    $df_output = explode("\n", $df_output);
    $df_output = $df_output[1];
    // Filesystem [1B-blocks Used Available] Use% Mounted on
    if (preg_match('@\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+@', $df_output, $matches)) {
      $disk_total = $matches[1];
      $disk_free  = $matches[3];
    }
  }
  if ($disk_total > 0) {
    $disk_percent = $disk_free / $disk_total * 100;
  } else {
    // avoid divide by zero error if getting total space fails
    $disk_percent = 0;
  }

  $data = array(
    'torrents' => $torrents,
    'global'   => array(
      'torrents_count_visible'     => $torrents_count_visible,
      'torrents_count_all'         => $torrents_count_all,
      'torrents_count_superhidden' => $torrents_count_superhidden,
      'total_down_rate'            => format_bytes($total_down_rate, '0 B/s', '/s'),
      'total_up_rate'              => format_bytes($total_up_rate  , '0 B/s', '/s'),
      'total_down_limit'           => format_bytes(rtorrent_xmlrpc('get_download_rate'), 'unlim', '/s'),
      'total_up_limit'             => format_bytes(rtorrent_xmlrpc('get_upload_rate'),   'unlim', '/s'),
      'show_disk_free'             => (isset($disk_usage_dir) && $disk_total),
      'disk_free'                  => format_bytes($disk_free),
      'disk_total'                 => format_bytes($disk_total),
      'disk_percent'               => round($disk_percent, 2),
    ),
  );

  return $data;
}

// Get list of files associated with a torrent...
function get_file_list($hash) {
  $use_old_api = (rtorrent_xmlrpc_cached('system.client_version') == '0.7.9');
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

  if (!$use_old_api) {
    for ($i=0; $i<count($results); $i++) {
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
  if (rtorrent_xmlrpc_cached('system.client_version') == '0.7.9') {
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
function rtorrent_xmlrpc($command, $params=array(''), $return_fault=false) {
  $response = do_xmlrpc(xmlrpc_encode_request($command, $params));
  if (@xmlrpc_is_fault($response)) {
    if ($return_fault) {
      return $response;
    } else {
      return false;
    }
  } else {
    return $response;
  }
}

/** rtorrent_xmlrpc_cached
 *
 * rtorrent_xmlrpc() with caching.
 * TODO: rename?
 */
function rtorrent_xmlrpc_cached($command) {
  if (!is_array($_SESSION['rpc_cache'])) {
    $_SESSION['rpc_cache'] = array();
  }
  if (!$_SESSION['rpc_cache'][$command]) {
    $_SESSION['rpc_cache'][$command] = rtorrent_xmlrpc($command);
  }
  return $_SESSION['rpc_cache'][$command];
}

/** rtorrent_multicall
 *
 * Does a "multicall" request to rtorrent and returns an array of data
 * items that can be either sequential or associative.  Each data item
 * is an associative array with the requested variables as keys.
 */
function rtorrent_multicall($group, $params, $data_names, $key=null, $remove_get=false) {
  if (!is_array($params)) {
    $params = array($params);
  }
  $index = -1;
  foreach ($data_names as $name) {
    $index++;
    $params[] = "$group.$name=";
    if ($remove_get) {
      $name = preg_replace("@^get_@", "", $name);
      $data_names[$index] = $name;
    }
    if ($key !== null && $key == $name) {
      $key_index = $index;
    }
  }

  $request = xmlrpc_encode_request("$group.multicall", $params);
  $response = do_xmlrpc($request);
  if (@xmlrpc_is_fault($response)) {
    trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
    return false;
  } else {
    $results = array();
    $result_index = 0;
    foreach ($response as $data_array) {
      $this_key = ($key === null ? $result_index++ : $data_array[$key_index]);
      $name_index = 0;
      foreach ($data_array as $data_item) {
        $results[$this_key][$data_names[$name_index++]] = $data_item;
      }
    }
    return $results;
  }
}

/** array_compare_special
 *
 * If any elements of $before and $after are different, return the
 * element from $after, or null if the element is only in $before.
 * Compares arbitrarily nested arrays, but if the $levels parameter
 * is given, only returns whole elements (or null) from $after after
 * comparing that many levels of keys.
 */
function array_compare_special($before, $after, $levels=null) {
  $new_levels = $levels;
  if ($new_levels !== null) {
    $new_levels--;
  }
  $diff = false;
  // check all keys in $before (to find changed and deleted items)
  foreach ($before as $key => $value) {
    if (!array_key_exists($key, $after)) {
      // $key is no longer a key
      $diff[$key] = null;
    } else if (is_array($value)) {
      // found an array
      if (is_array($after[$key])) {
        // still an array; compare recursively
        if ($new_levels === null || $new_levels > 0) {
          // still need to compare more keys
          $new = array_compare_special($value, $after[$key], $new_levels);
          if ($new !== false) {
            $diff[$key] = $new;
          }
        } else if (!compare_recursive($value, $after[$key])) {
          // return the whole array from $after
          $diff[$key] = $after[$key];
        }
      } else {
        // the value for $key in $after is no longer an array
        $diff[$key] = $after[$key];
      }
    } else if ($after[$key] !== $value) {
      // the value for $key changed
      $diff[$key] = $after[$key];
    }
  }
  // check all keys in $after (to find new items)
  foreach ($after as $key => $value) {
    if (!array_key_exists($key, $before)) {
      // $key is a new key
      $diff[$key] = $value;
    }
  }
  return $diff;
}

function compare_recursive($before, $after) {
  // check all keys in $before (to find changed and deleted items)
  foreach ($before as $key => $value) {
    if (!array_key_exists($key, $after)) {
      // $key is no longer a key
      return false;
    } else if (is_array($value)) {
      // found an array
      if (is_array($after[$key])) {
        // still an array; compare recursively
        return compare_recursive($value, $after[$key]);
      } else {
        // the value for $key is no longer an array
        return false;
      }
    } else if ($after[$key] !== $value) {
      // the value for $key changed
      return false;
    }
  }
  // check all keys in $after (to find new items)
  foreach ($after as $key => $value) {
    if (!array_key_exists($key, $before)) {
      // $key is a new key
      return false;
    }
  }
  return true;
}


function parse_http_response($string) {
  $headers = array();
  $arr = explode("\n", $string);
  for ($i = 0; $i < count($arr); $i++) {
    $str = trim($arr[$i]);
    if ($str === '') {
      return array($headers, implode("\n", array_slice($arr, $i + 1)));
    } else {
      list($name, $val) = explode(':', $str, 2);
      $name = strtolower($name);
      $val = ltrim($val);
      if (is_array($headers[$name])) {
        $headers[$name][] = $val;
      } else if (isset($headers[$name])) {
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
  if ($contentlength > 0) {
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($socket) {
      $reqheader = "CONTENT_LENGTH\x00$contentlength\x00SCGI\x001\x00";
      $tosend = strlen($reqheader) . ":$reqheader,$data";
      @fputs($socket, $tosend);
      while (!feof($socket)) {
        $result .= @fread($socket, 4096);
      }
      fclose($socket);
    }
  }
  return $result;
}

// This function formats a number of bytes nicely.
function format_bytes($bytes, $zero='', $after='') {
  if (!$bytes) {
    return $zero;
  }
  $units = array('B','KB','MB','GB','TB','PB');
  $i = 0;
  while ($bytes >= 1000) {
    $i++;
    $bytes /= 1024;
  }
  return number_format($bytes, ($i ? 1 : 0), '.', ',') . ' ' . $units[$i] . $after;
}

// This function takes a number of seconds and changes it into a readable
// string that has two units' worth of precision.
function format_duration($seconds) {
  if (!($seconds = round($seconds))) {
    return '';
  }
  $dur = '';
  $units = array(
    array('d', 86400),
    array('h', 3600),
    array('m', 60),
    array('s', 1)
  );
  // Loop over all units
  for ($i = 0; $i < count($units); $i++) {
    $u = $units[$i];
    // If the number of seconds is at least one of the current unit
    if ($seconds >= $u[1]) {
      // Round to the nearest unit that is one smaller
      // e.g. for hours, round to the nearest minute
      $round_to = $units[min($i + 1, count($units) - 1)][1];
      $seconds = round($seconds / $round_to) * $round_to;
      // Append the larger unit
      $dur .= floor($seconds / $u[1]) . $u[0];
      // Remove the number of seconds represented by the larger unit
      $seconds %= $u[1];
      // Append the smaller unit, if there is one
      if (++$i < count($units)) {
        $u = $units[$i];
        $dur .= ' ' . round($seconds / $u[1]) . $u[0];
      }
      return $dur;
    }
  }
  return '';
}


// ---------- Old functions


// multibyte-safe replacement for wordwrap.
// (See http://code.google.com/p/rtgui/issues/detail?id=71 - Thanks llamaX)
function mb_wordwrap($string, $width=75, $break="\n", $cut=false) {
  if (!$cut) {
    $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){' . $width . ',}\b#U';
  } else {
    $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){' . $width . '}#';
  }
  $string_length = mb_strlen($string, 'UTF-8');
  $cut_length = ceil($string_length / $width);
  $i = 1;
  $return = '';
  while ($i < $cut_length) {
    preg_match($regexp, $string, $matches);
    $new_string = $matches[0];
    $return .= $new_string . $break;
    $string = substr($string, strlen($new_string));
    $i++;
  }
  return $return . $string;
}

// Draw the percent bar using a table...
function percentbar($percent) {
   $retvar="<table align=center border=0 cellspacing=0 cellpadding=1 bgcolor=#666666 width=50><tr><td align=left>";
   $retvar.="<img src='images/percentbar.gif' height=4 width=".round($percent/2)." /></td></tr>";
   $retvar.="</table>";
   return $retvar;
}

?>
