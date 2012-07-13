<?php
require_once 'config.php';
require_once 'PersistentObject.php';

function get_rtgui_path() {
  return substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
}

function get_rtgui_url() {
  // adapted from http://stackoverflow.com/questions/189113/1229827#1229827
  $protocol = 'http';
  if ($_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
      $protocol .= 's';
      $protocol_port = $_SERVER['SERVER_PORT'];
  } else {
      $protocol_port = 80;
  }
  $host = $_SERVER['HTTP_HOST'];
  $port = $_SERVER['SERVER_PORT'];
  return "$protocol://$host" . ($port == $protocol_port ? '' : ':' . $port) . get_rtgui_path();
}

function rtgui_session_start() {
  global $tmp_add_dir, $private_storage_dir, $always_show_tags;
  if (!$_SESSION) {
    if (@file_put_contents("$private_storage_dir/test.txt", 'testing')) {
      @unlink("$private_storage_dir/test.txt");
    } else {
      die('<h1>ERROR: could not write to private storage directory (defined by the $private_storage_dir setting).</h1>');
    }
    if (@file_put_contents("$tmp_add_dir/test.txt", 'testing')) {
      @unlink("$tmp_add_dir/test.txt");
    } else {
      die('<h1>ERROR: could not write to temporary directory (defined by the $tmp_add_dir setting).</h1>');
    }
    session_name(get_rtgui_path());
    session_start();
  }
  $tags_filename = "$private_storage_dir/tags.txt";
  if (!is_array($_SESSION['tags']) || @filemtime($tags_filename) > $_SESSION['tags_modified']) {
    $tags = new PersistentObject($tags_filename);
    $used_tags = array();
    if (is_array($tags->data)) {
      foreach ($tags->data as $hash => $this_tags) {
        foreach ($this_tags as $this_tag) {
          $used_tags[$this_tag] = true;
        }
      }
      $used_tags = array_keys($used_tags);
      if (count($used_tags)) {
        sort($used_tags);
      }
      $_SESSION['tags'] = $tags->data;
    } else {
      $_SESSION['tags'] = array();
    }
    $_SESSION['used_tags'] = $used_tags;
    $_SESSION['tags_modified'] = @filemtime($tags_filename);
  }
  if (is_array($always_show_tags)) {
    $_SESSION['used_tags'] = array_unique(array_merge($_SESSION['used_tags'], $always_show_tags));
  }
}
?>
