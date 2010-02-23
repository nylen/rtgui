<?php
if(!$_SESSION) session_start();
include 'config.php';
include 'functions.php';
import_request_variables('gp', 'r_');

if($r_add) {
  
  die();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<title>rtGui</title>
<link href="style.css" rel="stylesheet" type="text/css" />
</head>

<body>
<div class="modal">
<h3>Add torrent(s)</h3>

<form method="post" action="add-torrent.php">
<input type="hidden" name="add" value="true" />
<table id="upload-form">
  <tr>
    <td class="left">Paste URL(s):</td>
    <td class="right input"><textarea name="addurl" rows="5"></textarea></td>
  </tr>
  <tr>
    <td class="left">Upload file:</td>
    <td class="right input"><input name="uploadtorrent" type="file" /></td>
  </tr>
<?php if($use_groups) { ?>
  <tr>
    <td class="left">Torrent type:</td>
    <td class="right">
<?php for($i = 0; $i < count($all_groups); $i++) {
  $value = $all_groups[$i];
  $checked = ($value == $default_group ? ' checked="checked"' : '');
  echo "  <input type=\"radio\" name=\"group\" value=\"$value\" id=\"group-$value\"$checked>";
  echo "<label for=\"group-$value\">$value</label>\n";
} ?>
    </td>
  </tr>
<?php } ?>
  <tr>
    <td class="left"></td>
    <td class="right"><input type="submit" value="Add torrent(s)" /></td>
  </tr>
</table>
</form>

</div>
</body>
</html>
