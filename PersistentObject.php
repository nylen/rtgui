<?php
class PersistentObject {
  private $filename = false;
  public $data = array();
  public $mode = 0644;

  public function __construct($filename=null) {
    $this->load($filename);
  }

  public function save($filename=null) {
    if($filename !== null) {
      $this->filename = $filename;
    }
    $tmp = tempnam(sys_get_temp_dir(), 'PO');
    if(file_put_contents($tmp, json_encode($this->data))) {
      return (rename($tmp, $this->filename) && chmod($this->filename, $this->mode));
    } else {
      return false;
    }
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
