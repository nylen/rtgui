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
      // Set a Firebug breakpoint here if this error occurs
      throw 'Invalid template (1)';
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
        throw 'Invalid template (2)';
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
        if(formatHandlers[s.varName]) {
          html += formatHandlers[s.varName].call(null, data[s.varName]);
        } else {
          html += data[s.varName];
        }
        break;
    }
    html += s.after;
  }
  
  return html;
}


var templates = {
  torrent: makeTemplate(
    '<div class="torrent">',
      '<div class="namecol" id="@name">',
        '<div class="trackerurl" id="@tracker_url">',
          '<a class="filter" href="#tracker:$tracker_url" style="color: $tracker_color">$tracker_url</a>&nbsp;', // $tracker_color
        '</div>',
        '<input type="checkbox" name="select[]" value="$hash" />',
        '<a class="submodal-600-520 $status_class" href="view.php?hash=$hash">$name</a>', // $status_class
      '</div>',
      '<div class="errorcol" id="@message">$eta $message</div>', // $eta
      '<div class="datacol" style="width: 89px;">',
        '<a class="ajax" href="control.php?hash=$hash&amp;cmd=$start_stop_cmd">', // $start_stop_cmd
          '<img alt="$start_stop_cmd torrent" border="0" src="images/$start_stop_cmd.gif" width="16" height="16" />',
        '</a> ',
        '<a class="ajax confirm" rel="delete" href="control.php?hash=$hash&amp;cmd=delete">',
          '<img alt="delete torrent" border="0" src="images/delete.gif" width="16" height="16" align="bottom" />',
        '</a> ',
        '<a class="submodal-600-520" href="view.php?hash=$hash">',
          '<img alt="view torrent info" border="0" src="images/view.gif" width="16" height="16" />',
        '</a><br />',
      '</div>',
      '<div class="datacol" style="width: 89px;" id="@status_string">',
        '<img alt="torrent status" src="images/$status_class.gif" width="10" height="9" />$status_string', // $status_string
      '</div>',
      '<div class="datacol" style="width: 89px;" id="@percent_complete">$</div>',
      '<div class="datacol" style="width: 89px;" id="@bytes_remaining">$</div>',
      '<div class="datacol" style="width: 89px;" id="@size_bytes">$</div>',
      '<div class="datacol" style="width: 89px;" id="@down_rate">$</div>',
      '<div class="datacol" style="width: 89px;" id="@up_rate">$</div>',
      '<div class="datacol" style="width: 89px;" id="@up_total">$</div>',
      '<div class="datacol" style="width: 70px;" id="@ratio">$</div>',
      '<div class="datacol" style="width: 105px;" id="@peers_summary">$</div>', // $peers_summary
      '<div class="datacollast" style="width: 70px;" id="@priority_string">$</div>', // $priority_string
      '<div class="spacer"> </div>',
    '</div>'),
  
  
};

// these functions define how to format numeric items
var formatHandlers = {
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
formatHandlers.total_up_rate  = formatHandlers.total_down_rate;
formatHandlers.total_up_limit = formatHandlers.total_down_limit;
