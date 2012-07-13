<?php
require_once 'functions.php';
require_once 'session.php';
rtgui_session_start();

$ip = $_REQUEST['ip'];

if (!ip2long($ip)) {
  die(json_encode(array(
    'ip' => $ip,
    'error' => 'Invalid IP'
  )));
}

if (is_array($_SESSION["ip-$ip"])) {
  die(json_encode($_SESSION["ip-$ip"]));
}

// What hostip.info returns happens to look a lot like HTTP headers
$url = "http://api.hostip.info/get_html.php?ip=$ip";
$info = parse_http_response(file_get_contents($url));
$info = $info[0];

$info['hostname'] = gethostbyaddr($ip);
$info['country_short'] =
  preg_replace('@^[^(]+\((.*)\)[^)]*$@', '\1', $info['country']);
foreach (array('city', 'country', 'country_short') as $key) {
  if (stristr($info[$key], 'unknown') !== false) {
    $info[$key] = 'unknown';
  }
}
$info['location'] = $info['city'] . ', ' . $info['country_short'];

$_SESSION["ip-$ip"] = $info;

print json_encode($info);

?>
