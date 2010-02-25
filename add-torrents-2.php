<?php
if(!$_SESSION) session_start();
include 'config.php';
include 'functions.php';

import_request_variables('gp', 'r_');

$files = array();
if($r_add_urls) {
  $maybe_urls = preg_split('@\s+@', $r_add_urls);
  for($i = 0; $i < count($maybe_urls); $i++) {
    $url = $maybe_urls[$i];
    
    if(!preg_match('@^(ht|f)tps?:@', $url)) {
      if(preg_match('@^[^/]+\\.[a-z]{1,6}/@', $url)) {
        $url = "http://$url";
      } else {
        continue;
      }
    }
    $url = preg_replace('@[/?]+$@', '', $url);
    
    if(filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
      $c = curl_init($url);
      curl_setopt_array($c, array(
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEFILE => 'cookies.txt',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true
      ));
      
      $r = parse_http_response(curl_exec($c));
      $content = $r[1];
      $filename = "url-$i.torrent";
      $mime_type = '';
      foreach($r[0] as $header => $value) {
        echo "$header: $value<br>";
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
      
      echo "<br>Filename: $filename<br>Type: $mime_type<br>\n";
      
      if($mime_type == 'application/x-bittorrent') {
        echo "OK\n";
      }
      echo "<hr>\n";
    }
  }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<title>rtGui</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="jquery.js"></script>
</head>

<body>
<div class="modal">
<h3>Add torrent(s) - step 2</h3>

<form method="post" action="control.php">



</form>

</div>
</body>
</html>
