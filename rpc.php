<?php
error_reporting(E_ALL & ~E_NOTICE);

require_once 'config.php';
require_once 'functions.php';

$call = $argv[1];
$args = array();
for($i = 2; $i < count($argv); $i++) {
  $args[] = $argv[$i];
}

$response = do_xmlrpc(xmlrpc_encode_request($call, $args));
$readable = print_r($response, true);
print trim($readable) . "\n";
?>
