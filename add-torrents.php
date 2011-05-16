<?php

require_once 'config.php';
require_once 'functions.php';
rtgui_session_start();

require_once 'Torrent.php';

// convert warnings into catchable exceptions
function handleError($errno, $errstr, $errfile, $errline, array $errcontext) {
  if(0 === error_reporting()) {
    // error was suppressed with the @-operator
    return false;
  }
  if($errno & E_NOTICE) {
    return false;
  }
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function upload_error_message($code) {
  switch($code) {
    case UPLOAD_ERR_INI_SIZE:
      $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
      break;
    case UPLOAD_ERR_FORM_SIZE:
      $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
      break;
    case UPLOAD_ERR_PARTIAL:
      $message = "The uploaded file was only partially uploaded";
      break;
    case UPLOAD_ERR_NO_FILE:
      $message = "No file was uploaded";
      break;
    case UPLOAD_ERR_NO_TMP_DIR:
      $message = "Missing a temporary folder";
      break;
    case UPLOAD_ERR_CANT_WRITE:
      $message = "Failed to write file to disk";
      break;
    case UPLOAD_ERR_EXTENSION:
      $message = "File upload stopped by extension";
      break;

    default:
      $message = "Unknown upload error";
      break;
  }
  return $message;
}

function json_error($msg) {
  die(json_encode(array('error' => $msg)));
}

function get_filename_no_clobber($filename) {
  $basename = substr($filename, 0, strrpos($filename, '.'));
  $ext = substr($filename, strrpos($filename, '.') + 1);
  $i = 0;
  while(file_exists($filename)) {
    $i++;
    $filename = "$basename.$i.$ext";
  }
  return $filename;
}

function process_torrent_data($content, $filename, $create_file=true) {
  global $tmp_add_dir;

  if(!is_array($_SESSION['to_add_data'])) {
    $_SESSION['to_add_data'] = array();
  }

  set_error_handler('handleError');
  try {
    $torrent = new Torrent($content);
    if($error = $torrent->error()) {
      return array('error' => $error);
    }
    $hash = $torrent->hash_info();

    $filename = "$tmp_add_dir/$filename";
    if($create_file) {
      $filename = get_filename_no_clobber($filename);
      file_put_contents($filename, $content);
    }
  } catch(Exception $e) {
    restore_error_handler();
    return array('error' => $e->getMessage());
  }
  restore_error_handler();

  return $_SESSION['to_add_data'][$hash] = array(
    'hash' => $hash,
    'name' => $torrent->name(),
    'files' => $torrent->content(),
    //'scrape' => $torrent->scrape(null, null, 3),
    'filename' => str_replace("$tmp_add_dir/", '', $filename)
  );
}

import_request_variables('gp', 'r_');
if(!is_array($r_tags)) {
  $r_tags = explode(',', $r_tags);
}

$this_watch_dir = $watch_dir;
switch($r_action) {
  case 'process_url':
  case 'process_file':
  case 'add':
    if(function_exists('get_watchdir_from_tags')) {
      try {
        $this_watch_dir = "$watch_dir/" . get_watchdir_from_tags($r_tags);
      } catch(Exception $e) {
        json_error($e->getMessage());
      }
    }
    break;
}

switch($r_action) {

  case 'get_list':
    $max_urls = 100;
    $to_add = array();

    if($r_add_urls) {
      $maybe_urls = preg_split('@[,;\s]+@', $r_add_urls);
      for($i = 0; $i < count($maybe_urls); $i++) {
        if($i >= $max_urls) {
          $to_add[] = array(
            'error' => "Can only add $max_urls URLs at a time"
          );
          break;
        }

        $url = $maybe_urls[$i];

        if(!preg_match('@^(ht|f)tps?:@', $url)) {
          if(preg_match('@^[^/]+\.[a-z]{1,6}/@', $url)) {
            $url = "http://$url";
          } else {
            continue;
          }
        }
        $url = preg_replace('@[/?]+$@', '', $url);

        if(filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
          $to_add[] = array(
            'type' => 'url',
            'value' => $url
          );
        }
      }
    }

    $err_level = error_reporting(E_NONE);
    foreach($_FILES['add_files']['error'] as $i => $err) {
      $filename = $_FILES['add_files']['name'][$i];

      if($err == UPLOAD_ERR_OK) {
        // In case move_uploaded_file fails without a useful error
        trigger_error('Unknown error', E_USER_WARNING);
        $filename = get_filename_no_clobber("$tmp_add_dir/$filename");
        $to_add_this = array(
          'type' => 'file',
          'value' => str_replace("$tmp_add_dir/", '', $filename)
        );

        if(!move_uploaded_file($_FILES['add_files']['tmp_name'][$i], $filename)) {
          $err = error_get_last();
          $to_add_this['error'] = $err['message'];
        }
        $to_add[] = $to_add_this;

      } else if($err != UPLOAD_ERR_NO_FILE) {
        $to_add_this['error'] = upload_error_message($err);
        $to_add[] = $to_add_this;
      }
    }
    error_reporting($err_level);

    print base64_encode(json_encode($to_add));

    break;


  case 'process_url':
    if(!function_exists('curl_init')) {
      json_error('The required PHP CURL extension is not present.');
    }
    $c = curl_init($r_url);
    curl_setopt_array($c, array(
      CURLOPT_HEADER => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FAILONERROR => true
    ));
    if($cookies_file) {
      curl_setopt($c, CURLOPT_COOKIEFILE, $cookies_file);
    }

    $r = curl_exec($c);
    if($r !== false) {
      curl_close($c);
      $response = parse_http_response($r);
      $content = $response[1];
      $filename = basename(parse_url($r_url, PHP_URL_PATH));
      if(!preg_match('@\.torrent$@i', $filename)) {
        $filename .= '.torrent';
      }
      $mime_type = '';
      foreach($response[0] as $header => $value) {
        switch(strtolower($header)) {
          case 'content-disposition':
            preg_match('@filename="?([^"]+)"?$@', $value, $matches);
            if(count($matches) > 1) {
              $filename = $matches[1];
            }
            break;
          case 'content-type':
            $arr = explode(';', $value);
            $mime_type = trim(strtolower($arr[0]));
            break;
        }
      }

      if($mime_type == 'application/x-bittorrent') {
        // Torrent OK
        print json_encode(process_torrent_data($content, $filename));
      } else {
        json_error("Not a torrent ($mime_type)");
      }
    } else {
      $err = curl_error($c);
      curl_close($c);
      json_error($err);
    }

    break;


  case 'process_file':
    $r_file = "$tmp_add_dir/$r_file";
    if(file_exists($r_file) && dirname(realpath($r_file)) === realpath($tmp_add_dir)) {
      print json_encode(process_torrent_data(file_get_contents($r_file), basename($r_file), false));
    } else {
      json_error('Bad path or filename');
    }

    break;


  case 'add':
    print '<style>body { background: white; color: black; }</style>';
    $errors = array();

    foreach($r_add_torrent as $hash) {
      if($data = $_SESSION['to_add_data'][$hash]) {
        $filename = $data['filename'];
        $name = $data['name'];
        if(copy("$tmp_add_dir/$filename", "$this_watch_dir/$filename")) {
          if(function_exists('on_add_torrent')) {
            on_add_torrent($name, $hash, $r_tags, "$this_watch_dir/$filename");
          }
        } else {
          $errors[] = "Failed to copy torrent \"$name\"";
        }
      }
    }

    if(count($errors)) {
      array_unshift($errors, "One or more errors occurred:\n");
      $script = 'alert(' . json_encode($errors) . '.join("\n"))';
    } else {
      if(is_array($r_tags)) {
        set_error_handler('handleError');
        try {
          set_torrent_tags($r_add_torrent, $r_tags, array());
          set_user_setting('new_torrent_tags', implode(',', $r_tags));
        } catch(Exception $e) {
          restore_error_handler();
          die('Error setting tags: ' . $e->getMessage());
        }
        restore_error_handler();
      }
      $script = 'top.hideDialog(true);';
    }

    print "<script>$script</script>\n";

    break;


  case 'delete_files':
    if(is_array($_SESSION['to_add_data'])) {
      foreach($_SESSION['to_add_data'] as $data) {
        @unlink("$tmp_add_dir/" . $data['filename']);
      }
      unset($_SESSION['to_add_data']);
    }

    break;
}
?>
