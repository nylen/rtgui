function makeTemplate() {
  var str = $(arguments).toArray().join('\n');
  var arr = str.split(/([@\$])([a-z0-9_-]*)/i);
  var markers = {
    id: '@',
    value: '$',
  };
  
  var template = {
    before: arr[0],
    substitutions: [],
    mustRewriteHTML: {},
  };
  
  var i = 1;
  for(k in markers) {
    if(str[0] == markers[k]) {
      i = 0;
      template.before = '';
    }
  }
  
  var lastVarName = '';
  while(i < arr.length) {
    var thisSubstitution = {};
    for(k in markers) {
      if(arr[i] == markers[k]) {
        thisSubstitution.type = k;
        i++;
        break;
      }
    }
    if(!thisSubstitution.type) {
      throw 'Invalid template (Type marker not found)';
    }
    thisSubstitution.varName = arr[i++];
    if(thisSubstitution.varName) {
      if(thisSubstitution.type == 'value') {
        template.mustRewriteHTML[thisSubstitution.varName] = true;
      }
    } else {
      thisSubstitution.varName = lastVarName;
      if(!template.mustRewriteHTML[thisSubstitution.varName]) {
        template.mustRewriteHTML[thisSubstitution.varName] = false;
      }
      if(!lastVarName) {
        // Set a Firebug breakpoint here if this error occurs
        throw 'Invalid template (Variable name not found)';
      }
    }
    lastVarName = thisSubstitution.varName;
    thisSubstitution.after = (i >= arr.length ? '' : arr[i++]);
    template.substitutions.push(thisSubstitution);
  }
  
  return template;
}

function applyTemplate(data, template, key, group) {
  if(group === undefined) {
    group = 't';
  }
  
  var html = template.before;
  
  for(var i = 0; i < template.substitutions.length; i++) {
    var s = template.substitutions[i];
    switch(s.type) {
      case 'id':
        html += group + '-';
        if(key) {
          html += key + '-';
        }
        html += s.varName;
        break;
      case 'value':
        html += getFormattedValue(s.varName, data[s.varName]);
        break;
    }
    html += s.after;
  }
  
  return html;
}


var templates = {
  torrent: makeTemplate(
    '<div class="torrent" id="t-$hash">',
      '<div class="namecol" id="@name">',
        '<div class="tracker" id="@tracker_hostname">',
          '<a class="filter" href="#" rel="tracker:$tracker_hostname" style="color: $tracker_color">$tracker_hostname</a>&nbsp;',
        '</div>',
        '<input type="checkbox" name="select[]" value="$hash" />',
        '<a class="dialog $status_class" rel="600:520" href="view.php?hash=$hash">$name</a>',
        (config.useGroups ? '<span class="group">(<a class="filter" href="#" id="@group" rel="group:$group">$group</a>)</span>' : ''),
        (config.useDateAdded ? '<span class="date-added" id="@date_added">$</span>' : ''),
      '</div>',
      '<div class="errorcol" id="@message">$eta $message</div>',
      '<div class="datacol" style="width: 89px;">',
        '<a class="ajax" href="control.php?hash=$hash&amp;cmd=$start_stop_cmd">',
          '<img alt="$start_stop_cmd torrent" border="0" src="images/$start_stop_cmd.gif" width="16" height="16" />',
        '</a> ',
        '<a class="ajax confirm" rel="delete" href="control.php?hash=$hash&amp;cmd=delete">',
          '<img alt="delete torrent" border="0" src="images/delete.gif" width="16" height="16" align="bottom" />',
        '</a> ',
        '<a class="dialog" rel="600:520" href="view.php?hash=$hash">',
          '<img alt="view torrent info" border="0" src="images/view.gif" width="16" height="16" />',
        '</a><br />',
      '</div>',
      '<div class="datacol" style="width: 89px;" id="@status">',
        '<img alt="torrent status" src="images/$status_class.gif" width="10" height="9" />$status',
      '</div>',
      '<div class="datacol" style="width: 89px;" id="@percent_complete">$</div>',
      '<div class="datacol" style="width: 89px;" id="@bytes_remaining">$</div>',
      '<div class="datacol" style="width: 89px;" id="@size_bytes">$</div>',
      '<div class="datacol" style="width: 89px;" id="@down_rate">$</div>',
      '<div class="datacol" style="width: 89px;" id="@up_rate">$</div>',
      '<div class="datacol" style="width: 89px;" id="@up_total">$</div>',
      '<div class="datacol" style="width: 70px;" id="@ratio">$</div>',
      '<div class="datacol" style="width: 105px;" id="@peers_summary">$</div>',
      '<div class="datacollast" style="width: 70px;" id="@priority_str">$</div>',
      '<div class="spacer"> </div>',
    '</div>'),
  
  
};

// these functions define how to format numeric items
var formatHandlers = {
  total_down_rate: function(n) {
    return formatBytes(n, '0 B/s', '/s');
  },
  total_up_rate: function(n) {
    return formatBytes(n, '0 B/s', '/s');
  },
  total_down_limit: function(n) {
    return '[' + formatBytes(n, 'unlim', '/s') + ']';
  },
  total_up_limit: function(n) {
    return '[' + formatBytes(n, 'unlim', '/s') + ']';
  },
  
  disk_free: formatBytes,
  disk_total: formatBytes,
  disk_percent: function(n) {
    if(n <= config.diskAlertThreshold) {
      if(!$(this).parent().hasClass('diskalert')) {
        var msg = 'Disk free space in your torrents directory is running low!';
        window.setTimeout(function() { alert(msg); }, 200);
        $(this).parent().addClass('diskalert');
      }
    } else {
      $(this).parent().removeClass('diskalert');
    }
    return Math.round(n*100)/100 + '%';
  },
  
  date_added: function(ts) {
    var d = new Date(ts * 1000);
    return 'added on ' + (d.getMonth()+1) + '/' + d.getDate() + '/' + d.getFullYear();
  },
  eta: function(n) {
    if(!n) {
      return false;
    }
    var eta = '';
    var units = {
      d: 86400,
      h: 3600,
      m: 60,
      s: 1,
    };
    var unitsFound = 0;
    for(u in units) {
      if(unitsFound > 0 || n >= units[u]) {
        var thisUnit = n / units[u];
        switch(++unitsFound) {
          case 1:
            eta += Math.floor(n / units[u]);
            break;
          case 2:
            eta += Math.round(n / units[u]);
            break;
          default:
            return $.trim(eta);
        }
        eta += u + ' ';
        n %= units[u];
      }
    }
    return $.trim(eta);
  },
  message: function(m) {
    return (m ? m : false);
  },
  
  percent_complete: function(n) {
    return [
      n + '%<br />',
      '<table align="center" border="0" cellspacing="0" cellpadding="1" bgcolor="#666666" width="50"><tr>',
        '<td align="left"><img src="images/percentbar.gif" height="4" width="' + Math.round(n/2) + '"/></td>',
      '</tr></table>'
    ].join('\n');
  },
  bytes_remaining: formatBytes,
  size_bytes: formatBytes,
  down_rate: function(n) {
    return formatBytes(n, '', '/s');
  },
  up_rate: function(n) {
    return formatBytes(n, '', '/s');
  },
  up_total: formatBytes,
  ratio: function(n) {
    return Math.round(n/10)/100;
  },
  peers_summary: function(s) {
    var arr = s.split(',');
    return parseInt(arr[0]) + '/' + parseInt(arr[1]) + ' (' + parseInt(arr[2]) + ')';
  },
  
};

function getFormattedValue(varName, varValue, el) {
  var val = varValue;
  if(formatHandlers[varName]) {
    val = formatHandlers[varName].call(el, varValue);
  }
  if(val === false) {
    val = '';
  } else if(!$.trim(String(val))) {
    val = '&nbsp;';
  }
  return String(val);
}
