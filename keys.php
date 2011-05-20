<?php
require_once 'config.php';
require_once 'functions.php';

$torrents = get_all_torrents(true);
foreach($torrents as $t) {
  foreach(array_keys($t) as $key) {
    print("$key\n");
  }
  die();
}
?>
