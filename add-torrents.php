<?php

if(!$_SESSION) session_start();
include 'config.php';
include 'functions.php';

include 'Torrent.php';

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
  
  $torrent = new Torrent($content);
  $hash = $torrent->hash_info();
  
  $filename = "$tmp_add_dir/$filename";
  if($create_file) {
    $filename = get_filename_no_clobber($filename);
    file_put_contents($content, $filename);
  }
    
  return $_SESSION['to_add_data'][$hash] = array(
    'hash' => $hash,
    'name' => $torrent->name(),
    'files' => $torrent->content(),
    //'scrape' => $torrent->scrape(null, null, 3),
    'filename' => str_replace("$tmp_add_dir/", '', $filename)
  );
}

import_request_variables('gp', 'r_');

switch($r_action) {
  
  case 'get_list':
    $to_add = array();
    
    if($r_add_urls) {
      $maybe_urls = preg_split('@[,;\s]+@', $r_add_urls);
      for($i = 0; $i < count($maybe_urls); $i++) {
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
    
    print json_encode($to_add);
    
    break;
  
  
  case 'process_url':
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
    curl_close($c);
    if($r !== false) {
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
        json_error("Bad MIME type: $mime_type");
      }
    } else {
      json_error(curl_error($c));
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
  
  
  
}
?>
