<?php
function rtgui_session_start() {
  if(!$_SESSION) {
    session_start();
  }
}
?>
