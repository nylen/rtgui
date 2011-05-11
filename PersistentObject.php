<?php
class PersistentObject {
  private $filename = false;
  public $data = array();

  public function __construct($filename=null) {
    $this->load($filename);
  }

  public function save($filename=null) {
    if($filename !== null) {
      $this->filename = $filename;
    }
    return file_put_contents($this->filename, json_encode($this->data));
  }

  public function load($filename=null) {
    if($filename !== null) {
      $this->filename = $filename;
    }
    if(($contents = @file_get_contents($filename)) !== false) {
      $this->data = json_decode($contents, true);
    }
    return !!$contents;
  }
}
