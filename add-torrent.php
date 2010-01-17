<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<title>rtGui</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<style type="text/css">
iframe {
 width: 98%;
 border: 1px solid #ccc;
 border-width: 1px 0;
 margin-top: 10px;
 height: 380px;
}
</style>
</head>

<body>
<form method='post' action='control.php' enctype='multipart/form-data' target='submit'>
URL: <input type=text name='addurl' size=38 maxlength=500 /> <input type='submit' value='Go' /><br/>
File: <input name='uploadtorrent' type='file' size=25 /> <input type='submit' value='Go' />
<br>Type: 
<?php
//JCN { allow different torrent types
$types = array("music", "tv", "movies", "private1", "other");
for($i=0; $i<count($types); $i++) {
  $value = $types[$i];
  if($value == "other") $value = "";
  $checked = ($value == "tv" ? " checked='checked'" : "");
  echo "<input type=radio name='torrenttype' value='$value' id='torrenttype-$types[$i]'$checked><label for='torrenttype-$types[$i]'>$types[$i]</label>\n";
}
?>
</form>

<iframe name="submit"></iframe>

</body>
</html>
