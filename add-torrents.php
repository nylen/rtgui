<?php
if(!$_SESSION) session_start();
include 'config.php';
include 'functions.php';

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

import_request_variables('gp', 'r_');

switch($r_action) {
  
  case 'list':
    $_SESSION['to_add'] = array();
    
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
          $_SESSION['to_add'][] = array('url' => $url);
        }
      }
    }
    
    $n_files = count($_FILES['add_files']['name']);
    $err_level = error_reporting(E_NONE);
    foreach($_FILES['add_files']['error'] as $i => $err) {
      $name = $_FILES['add_files']['name'][$i];
      $to_add = array('file' => $name);
      if($err == UPLOAD_ERR_OK) {
        trigger_error('Unknown error', E_USER_WARNING);
        if(!move_uploaded_file($_FILES['add_files']['tmp_name'][$i], "$tmp_add_dir/$name")) {
          $err = error_get_last();
          $to_add['error'] = $err['message'];
        }
      } else if($err != UPLOAD_ERR_NO_FILE) {
        $to_add['error'] = upload_error_message($err);
      }
      $_SESSION['to_add'][] = $to_add;
    }
    error_reporting($err_level);
    
    print json_encode($_SESSION['to_add']);
    
    break;
  
  case 'process_url':
    $c = curl_init($url);
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
      $filename = basename(parse_url($url, PHP_URL_PATH));
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
            $mime_type = strtolower($arr[0]);
            break;
        }
      }
      
      if($mime_type == 'application/x-bittorrent') {
        // Torrent OK
      } else {
        json_error("Bad MIME type: $mime_type");
      }
    } else {
      json_error(curl_error($c));
    }
    
    
    
    break;
}
?>
