<?php
require_once 'config.php';
require_once 'functions.php';

$call = $argv[1];
$args = array();
for($i = 2; $i < count($argv); $i++) {
  $args[] = $argv[$i];
}
if(!count($args)) $args[] = '';

print(rtorrent_xmlrpc($call, $args));
?>
