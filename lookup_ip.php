<?php
require_once 'functions.php';

$ip = $_REQUEST['ip'];

if(!ip2long($ip)) {
  die(json_encode(array(
    'ip' => $ip,
    'error' => 'Invalid IP'
  )));
}

// What hostip.info returns happens to look a lot like HTTP headers
$url = "http://api.hostip.info/get_html.php?ip=$ip";
$info = parse_http_response(file_get_contents($url));
$info = $info[0];

$info['hostname'] = gethostbyaddr($ip);
$info['country_short'] =
  preg_replace('@^[^(]+\((.*)\)[^)]*$@', '\1', $info['country']);
if(stristr($info['city'], 'unknown') !== false) {
  $info['city'] = 'unknown';
}
$info['location'] = $info['city'] . ', ' . $info['country_short'];

print json_encode($info);

?>
