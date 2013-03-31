<?php
require_once 'config.php';
require_once 'functions.php';
rtgui_session_start();

if ($_GET['urls']) {
  $urls = base64_decode($_GET['urls']);
} else {
  $urls = '';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<title>rtGui - Add torrents</title>
<?php
include_stylesheet('common.css', true);
include_stylesheet('form-controls.css', true);
include_stylesheet('dialogs.css', true);
include_stylesheet('add-torrents.css', true);
include_script('jquery.js');
include_script('jquery.hsjn.js');
include_script('jquery.form.js');
include_script('jquery.MultiFile.js');
include_script('jquery.mousewheel.js');
include_script('json2.min.js');
include_script('php.min.js');
include_script('add-torrents.js');
if (!$_GET['dialog']) {
  // Need to get the hashes of all torrents that have already been downloaded
  $torrents = rtorrent_multicall('d', 'main', array('get_hash'), 'hash', true);
  $torrents = array_map('is_numeric', array_flip(array_keys($torrents)));
  $data = array('torrents' => $torrents);
  $data_str = json_encode($data);
  echo <<<HTML
<script type="text/javascript">
var data = $data_str;
</script>

HTML;
}
?>
</head>

<body class="modal">
<?php if (!$_GET['dialog']) { ?>
<h3>Add torrent(s)</h3>
<?php } ?>

<div id="options">
<form id="form1" method="post" enctype="multipart/form-data" action="add-torrents.php">
<input type="hidden" name="action" value="get_list" />
<table id="upload-form">
  <tr class="controls">
    <td class="left">Paste URL(s):</td>
    <td class="right input"><textarea name="add_urls" rows="5"><?php echo $urls; ?></textarea></td>
  </tr>
  <tr class="controls">
    <td class="left">Upload file(s):</td>
    <td class="right input"><input name="add_files[]" type="file" class="multi themed" /></td>
  </tr>
  <tr class="controls">
    <td class="left">Torrent tags:</td>
    <td class="right">
<?php foreach ($_SESSION['used_tags'] as $tag) {
  $checked = (strpos('|' . get_user_setting('new_torrent_tags') . '|', "|$tag|") !== false ? ' checked="checked"' : '');
  echo <<<HTML
      <div class="tag-checkbox">
        <input type="checkbox" name="tags[]" value="$tag" id="tag-$tag"$checked>
        <label for="tag-$tag">$tag</label>
      </div>

HTML;
} ?>
    </td>
  </tr>
  <tr id="row-next">
    <td class="left"></td>
    <td class="right"><input type="submit" id="next" class="themed" value="Next &gt;&gt;" /></td>
  </tr>
  <tr id="row-back" class="hidden">
    <td class="left"></td>
    <td class="right"><input type="button" id="back" class="themed" value="&lt;&lt; Back" /></td>
  </tr>
</table>
</form>

<form id="form2" class="hidden" method="post" action="add-torrents.php">
  <hr />
  <h4>Select the torrents you want to add below.</h4>

  <div id="to-add"></div>

  <input type="hidden" name="action" value="add" />
  <input type="hidden" name="tags" value="" />
  <input type="submit" id="add" class="themed" value="Add selected" disabled="disabled" />
</form>

</div>
</body>
</html>
