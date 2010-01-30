//
//  This file is part of rtGui.  http://rtgui.googlecode.com/
//  Copyright (C) 2007-2008 Simon Hall.
//  Modifications (C) 2010 James Nylen.
//
//  rtGui is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, either version 3 of the License, or
//  (at your option) any later version.
//
//  rtGui is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with rtGui.  If not, see <http://www.gnu.org/licenses/>.


function updateData() {
  $.getJSON("json.php", function(data) {
    $('#debug').html(htmlspecialchars(new Date() + ":\n" + JSON.stringify(data, null, 2)));
    if(data === false) {
      // No changes
      $('#debug').append('\n(No changes)');
      return;
    }
    
    updateHTML(data, false);
  });
}

function updateHTML(data, isFirstUpdate) {
  if(data.torrents) {
    for(hash in data.torrents) {
      t = data.torrents[hash];
    }
  }
  for(k in data) {
    if(k != 'torrents') {
      // update global items
      $('#' + k).html(function() {
        if(updateHandlers[k]) {
          return updateHandlers[k].call(this, data[k]);
        } else {
          return data[k];
        }
      });
    }
  }
}

// these functions define how to format numeric items
var updateHandlers = {
  total_down_rate: function(b) {
    return formatBytes(b, '0 B/s', '/s');
  },
  total_down_limit: function(b) {
    return '[' + formatBytes(b, 'unlim', '/s') + ']';
  },
  disk_free: formatBytes,
  disk_total: formatBytes,
  disk_percent: function(n) {
    if(n <= diskAlertThreshold) {
      if(!$(this).parent().hasClass('diskalert')) {
        var msg = 'Disk free space in your torrents directory is running low!';
        window.setTimeout(function() { alert(msg); }, 200);
        $(this).parent().addClass('diskalert');
      }
    } else {
      $(this).parent().removeClass('diskalert');
    }
    return Math.round(n*100)/100 + '%';
  }
};
updateHandlers.total_up_rate  = updateHandlers.total_down_rate;
updateHandlers.total_up_limit = updateHandlers.total_down_limit;

// format a number of bytes nicely
function formatBytes(bytes, zero, after) {
  if(zero === undefined) {
    zero = '';
  }
  if(after === undefined) {
    after = '';
  }
  if(!bytes) {
    return zero;
  }
  var units = ['B','KB','MB','GB','TB','PB'];
  var i = 0;
  while(bytes >= 1024) {
      i++;
      bytes /= 1024;
  }
  return number_format(bytes, (i ? 1 : 0), '.', ',') + ' ' + units[i] + after;
}


// ----------- Original rtGui functions

function checkAll(field) {
   for (i = 0; i < field.length; i++)
	   field[i].checked = true ;
}

function uncheckAll(field) {
   for (i = 0; i < field.length; i++)
	   field[i].checked = false ;
}

function toggleLayer( whichLayer ) {
  var elem, vis;
  if( document.getElementById ) 
    elem = document.getElementById( whichLayer );
  else if( document.all ) 
      elem = document.all[whichLayer];
  else if( document.layers )
    elem = document.layers[whichLayer];
  vis = elem.style;
  if(vis.display==''&&elem.offsetWidth!=undefined&&elem.offsetHeight!=undefined)
    vis.display = (elem.offsetWidth!=0&&elem.offsetHeight!=0)?'block':'none';
  vis.display = (vis.display==''||vis.display=='block')?'none':'block';
}
