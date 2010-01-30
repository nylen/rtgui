<?php
function get_torrent_headers() {
  global $displaytrackerurl;
  ob_start();
  
  // The headings, with sort links...
  $uparr = '<img src="images/uparrow.gif" height=8 width=5 alt="Ascending" />';
  $downarr = '<img src="images/downarrow.gif" height=8 width=5 alt="Descending" />';

  $cols = array(
    'name'             => 'Name',
    'status_string'    => 'Status',
    'percent_complete' => 'Done',
    'bytes_diff'       => 'Remain',
    'size_bytes'       => 'Size',
    'down_rate'        => 'Down',
    'up_rate'          => 'Up',
    'up_total'         => 'Seeded',
    'ratio'            => 'Ratio',
    'peers_connected'  => 'Peers',
    'priority_str'     => 'Pri',
    'tracker_url'      => 'Trk',
  );

  foreach($cols as $k => $v) {
    $sort_order = (($_SESSION['sortkey'] == $k xor $_SESSION['sortord'] == 'asc') ? 'asc' : 'desc');
    $width = ($k == 'priority_str' ? 84 : 89);
    if($k == 'tracker_url' && !$displaytrackerurl) {
      break;
    }
    if($k != 'tracker_url') {
      echo "<div class=\"headcol\" style=\"width: ${width}px;\">";
    }
    echo "<a href=\"?setsortkey=$k&amp;setsortord=$sort_order\">$v</a> ";
    if($_SESSION['sortkey'] == $k) {
      echo ($_SESSION['sortord'] == 'asc' ? $uparr : $downarr);
    }
    if($k == 'priority_str' && $displaytrackerurl) {
      echo "/ ";
    } else {
      echo "</div>\n";
    }
  }
  return ob_get_clean();
}

function get_torrent_html($item, $class="torrent") {
  global $displaytrackerurl, $tracker_hilite_default, $tracker_hilite;
  
  ob_start();
  echo "<div class=\"$class\">\n";
  
  $statusstyle = ($item['complete'] ? 'complete' : 'incomplete');
  $statusstyle .= ($item['is_active'] ? 'active' : 'inactive');

  $eta = "";
  if($item['down_rate'] > 0) {
     $eta = format_eta(($item['size_bytes']-$item['completed_bytes'])/$item['down_rate']);
  }

  echo "<div class=\"namecol\" id=\"t-".$item['hash']."-name\">\n";
  // Tracker URL
  if($displaytrackerurl) {
     $urlstyle = $tracker_hilite_default;
     foreach($tracker_hilite as $hilite) {
        foreach($hilite as $thisurl) { 
           if(stristr($item['tracker_url'], $thisurl) !== false) {
             $urlstyle = $hilite[0];
           }
        }
     }
     echo "<div class=\"trackerurl\" id=\"t-".$item['hash']."-tracker\"><a style=\"color: $urlstyle ;\" href=\"?settrackerfilter=".$item['tracker_url'].'">'.$item['tracker_url'].'</a>&nbsp;</div>';
  }
  
  // Torrent name
  echo "<input type='checkbox' name='select[]' value='" . $item['hash'] . "'  /> ";
  echo "<a class=\"submodal-600-520 $statusstyle\" href=\"view.php?hash=" . $item['hash'] . "\">";
  echo htmlspecialchars($item['name'], ENT_QUOTES) . "</a>\n";
  echo "</div>\n";

  // message...
  echo "<div class='errorcol' id='t-" . $item['hash'] . "-message'>\n";
  if($eta != "") {
    echo "$eta remaining... ";
  }
  if($item['message'] != '' && $item['message'] != 'Tracker: [Tried all trackers.]') {
    echo $item['message'] . "\n";
  }
  echo "</div>\n";

  // Stop/start controls...
  echo "<div class='datacol'  style='width:89px;'>\n";
  echo "<a href='control.php?hash=".$item['hash']."&amp;cmd=".($item['is_active']==1 ? "stop" : "start")."'>".($item['is_active']==1 ? "<img alt='Stop torrent' border=0 src='images/stop.gif' width=16 height=16 />" : "<img alt='Start torrent' border=0 src='images/start.gif' width=16 height=16 />")."</a> \n";
  echo "<a href='control.php?hash=".$item['hash']."&amp;cmd=delete' onClick='return confirm(\"Delete torrent - are you sure? (This will not delete data from disk)\");'><img align='bottom' alt='Delete torrent' border=0 src='images/delete.gif' width=16 height=16 /></a> \n";
  echo "<a class='submodal-600-520' href='view.php?hash=".$item['hash']."'><img alt='Torrent info' src='images/view.gif' width=16 height=16 /></a><br/>\n";
  echo "</div>\n";
  
  // Stats row...
  echo "<div class='datacol' style='width:89px;' id='t-".$item['hash']."-status_string'><img src='images/".$statusstyle.".gif' width=10 height=9 alt='Status' />".$item['status_string']."</div>\n";
  echo "<div class='datacol' style='width:89px;' id='t-".$item['hash']."-percent_complete'>".$item['percent_complete']." %<br/>".percentbar($item['percent_complete'])."</div>\n";
  echo "<div class='datacol' style='width:89px;' id='t-".$item['hash']."-bytes_diff'>&nbsp;".completed_bytes_diff($item['size_bytes'],$item['completed_bytes'])."</div>\n";
  echo "<div class='datacol' style='width:89px;' id='t-".$item['hash']."-size_bytes'>&nbsp;".format_bytes($item['size_bytes'])."</div>\n";
  echo "<div class='datacol download' style='width:89px;' id='t-".$item['hash']."-down_rate'>&nbsp;".format_bytes($item['down_rate'])."</div>\n";
  echo "<div class='datacol upload' style='width:89px;' id='t-".$item['hash']."-up_rate'>&nbsp;".format_bytes($item['up_rate'])."</div>\n";
  echo "<div class='datacol' style='width:89px;' id='t-".$item['hash']."-up_total'>&nbsp;".format_bytes($item['up_total'])."</div>\n";
  echo "<div class='datacol' style='width:70px;' id='t-".$item['hash']."-ratio'>&nbsp;".@round(($item['ratio']/1000),2)."</div>\n";
  echo "<div class='datacol' style='width:105px;' id='t-".$item['hash']."-peers'>".$item['peers_connected']."/".$item['peers_not_connected']." (".$item['peers_complete'].")"."</div>\n";
  echo "<div class='datacollast' style='width:70px;' id='t-".$item['hash']."-priority_str'>".$item['priority_str']."</div>\n";
  echo "<div class=spacer> </div>\n";

  echo "</div>\n"; // end of thisrow div
  
  return ob_get_clean();
}
?>
