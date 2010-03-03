<?php
include 'config.php';
include 'functions.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<title>rtGui</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="jquery.form.js"></script>
<script type="text/javascript" src="jquery.MultiFile.js"></script>
</head>

<body>
<div class="modal">
<h3>Add torrent(s) - step 1 of 2</h3>

<form method="post" enctype="multipart/form-data" action="add-torrents.php">
<input type="hidden" name="action" value="get_list" />
<table id="upload-form">
  <tr>
    <td class="left">Paste URL(s):</td>
    <td class="right input"><textarea name="add_urls" rows="5"></textarea></td>
  </tr>
  <tr>
    <td class="left">Upload file(s):</td>
    <td class="right input"><input name="add_files[]" type="file" class="multi" accept="torrent" /></td>
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
    <td class="right"><input type="submit" value="Next step" /></td>
  </tr>
</table>
</form>

</div>
</body>
</html>
