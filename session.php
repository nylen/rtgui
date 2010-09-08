<?php
function rtgui_session_start() {
  if(!$_SESSION) {
    session_set_cookie_params(
      60 * 60 * 6, # 6 hours
      str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname(__FILE__))
    );
    session_start();
  }
}
?>
