<?php
require_once 'config.php';
require_once 'PersistentObject.php';

function rtgui_session_start() {
  global $tmp_add_dir, $private_storage_dir;
  if(!$_SESSION) {
    if(@file_put_contents("$private_storage_dir/test.txt", 'testing')) {
      unlink("$private_storage_dir/test.txt");
    } else {
      die('<h1>ERROR: could not write to private storage directory (defined by the $private_storage_dir setting).</h1>');
    }
    if(@file_put_contents("$tmp_add_dir/test.txt", 'testing')) {
      unlink("$tmp_add_dir/test.txt");
    } else {
      die('<h1>ERROR: could not write to temporary directory (defined by the $tmp_add_dir setting).</h1>');
    }
    session_start();
  }
  $tags_filename = "$private_storage_dir/tags.txt";
  if(!is_array($_SESSION['tags']) || @filemtime($tags_filename) > $_SESSION['tags_modified']) {
    $tags = new PersistentObject($tags_filename);
    $used_tags = array();
    if(is_array($tags->data)) {
      foreach($tags->data as $hash => $this_tags) {
        foreach($this_tags as $this_tag) {
          $used_tags[$this_tag] = true;
        }
      }
      $used_tags = array_keys($used_tags);
      if(is_array($always_show_tags)) {
        $used_tags = array_unique(array_merge($used_tags, $always_show_tags));
      }
      if(count($used_tags)) {
        sort($used_tags);
      }
      $_SESSION['tags'] = $tags->data;
    } else {
      $_SESSION['tags'] = array();
    }
    $_SESSION['used_tags'] = $used_tags;
    $_SESSION['tags_modified'] = @filemtime($tags_filename);
  }
}
?>
