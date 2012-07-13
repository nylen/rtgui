<?php
require_once 'config.php';
require_once 'functions.php';

$format_str = $_REQUEST['format'];
if (!$format_str) $format_str = $argv[1];
if (!$format_str) $format_str = '%hash:%base_path';
if (isset($_REQUEST['header']) || $argv[3]) print "$format_str\n";

function is_format($fmt) {
  return preg_match('@^%[a-z_]+$@', $fmt);
}

$format_items = preg_split('@(%[a-z_]+)@', $format_str, -1, PREG_SPLIT_DELIM_CAPTURE);
$format_items = array_filter($format_items, 'is_format');

$torrents = get_all_torrents(array('torrents_only' => true));

$sort_key = 'date_added';
if (isset($_REQUEST['key'])) $sort_key = $_REQUEST['key'];
if ($argv[2]) $sort_key = $argv[2];
usort($torrents, 'custom_sort');

function custom_sort($a, $b) {
  global $sort_key;
  $a = $a[$sort_key];
  $b = $b[$sort_key];
  return ($a < $b ? -1 : ($a > $b ? 1 : 0));
}

foreach ($torrents as $t) {
  $t['dir_name'] = dirname($t['directory']);
  $output = $format_str;
  foreach ($format_items as $format_item) {
    $key = substr($format_item, 1);
    if (isset($t[$key])) {
      $output = preg_replace("@%$key(?![a-z_])@", $t[$key], $output);
    }
  }
  print "$output\n";
}
?>
