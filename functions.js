function debug(msg) {
  $('#debug:visible').html('<b>' + new Date() + ':</b>\n' + htmlspecialchars(msg));
}

function hideDialog(doUpdate) {
  hidePopWin(false);
  if(doUpdate) {
    updateTorrentsNow();
  }
}

function updateTorrentsNow() {
  window.clearInterval(current.refreshIntervalID);
  updateTorrentsData();
  current.refreshIntervalID = window.setInterval(updateTorrentsData, config.refreshInterval);
}

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
  while(bytes >= 1000) {
      i++;
      bytes /= 1024;
  }
  return number_format(bytes, (i ? 1 : 0), '.', ',') + ' ' + units[i] + after;
}




// Functions to update torrents list

function updateTorrentsData() {
  $.getJSON('json.php', function(changes) {
    if(!changes) {
      debug('(No changes)');
      return;
    }
    debug(JSON.stringify(changes, null, 2));
    
    $.extend(true, window.data, changes);
    updateTorrentsHTML(changes, false);
  });
}

function updateTorrentsHTML(changes, isFirstUpdate) {
  var dirty = {
    toSort: [],
    toFilter: [],
    toCheckView: [],
    stripes: !!isFirstUpdate,
  };
  
  if(changes.torrents) {
    // One or more torrents changed
    for(hash in changes.torrents) {
      if(changes.torrents[hash] === null) {
        // A torrent was removed
        delete window.data.torrents[hash];
        delete current.visible[hash];
        $('#' + hash).remove();
        dirty.stripes = true;
      } else {
        var mustRewriteHTML = false;
        if(isFirstUpdate || !window.data.torrents[hash]) {
          mustRewriteHTML = true;
        }
        if(!mustRewriteHTML) {
          for(varName in changes.torrents[hash]) {
            if(templates.torrent.mustRewriteHTML[varName]) {
              mustRewriteHTML = true;
              break;
            }
          }
        }
        var checkChangedVars = false;
        if(mustRewriteHTML) {
          var html = applyTemplate(window.data.torrents[hash], templates.torrent, hash, 't');
          var container = $('#' + hash);
          if(container.length) {
            dirty.stripes = true;
            container.html(html);
            checkChangedVars = true;
          } else {
            $('#torrents').append(
              '<div class="torrent-container" id="' + hash + '">\n'
              + html + '\n</div>\n\n');
            if(!isFirstUpdate) {
              dirty.toCheckView.push(hash);
              dirty.toSort.push(hash);
              dirty.toFilter.push(hash);
            }
          }
        } else {
          for(varName in changes.torrents[hash]) {
            var el = $('#t-' + hash + '-' + varName)[0];
            var val = getFormattedValue(varName, window.data.torrents[hash][varName], el);
            $(el).html(val);
            checkChangedVars = true;
          }
        }
        if(checkChangedVars) {
          for(varName in changes.torrents[hash]) {
            if(viewHandlers.varsToCheck[varName]) {
              dirty.toCheckView.push(hash);
            }
            if(current.filters[varName] !== undefined) {
              dirty.toFilter.push(hash);
            }
            if(current.sortVar == varName) {
              dirty.toSort.push(hash);
            }
          }
        }
      }
    }
    if(isFirstUpdate) {
      sortAll();
    } else {
      for(var h in dirty.toCheckView) {
        
      }
      for(var h in dirty.toFilter) {
        // TODO: filter these torrents, and set dirty.stripes if needed
      }
      for(var h in dirty.toSort) {
        // TODO: sort these torrents, and set dirty.stripes if needed
      }
    }
    
    var torrentDivsAll = $('#torrents>div.torrent-container');
    var torrentDivsVisible = torrentDivsAll.filter(':visible');
    $('#t-none').css('display', (torrentDivsVisible.length ? 'none' : ''));
    $('#t-count-visible').html(torrentDivsVisible.length);
    $('#t-count-all').html(torrentDivsAll.length);
    
    // set row classes
    if(dirty.stripes) {
      resetStripes(torrentDivsVisible);
    }
  }
  
  // update global items (total speeds, caps, disk space, etc.)
  for(k in changes) {
    if(k != 'torrents') {
      var el = document.getElementById(k);
      $(el).html(getFormattedValue(k, changes[k], el));
    }
  }
}



var viewHandlers = {
  varsToCheck: {
    state: 1,
    is_active: 1,
    complete: 1,
  },
  
  'main': function(t) {
    return true;
  },
  'started': function(t) {
    return t.state;
  },
  'stopped': function(t) {
    return !t.state;
  },
  'active': function(t) {
    return t.is_active;
  },
  'inactive': function(t) {
    return !t.is_active;
  },
  'complete': function(t) {
    return t.complete;
  },
  'incomplete': function(t) {
    return !t.complete;
  },
  'seeding': function(t) {
    return t.complete && t.state;
  },
}


function sortAll() {
  if(!current.sortVar) {
    return;
  }
  var els = $('#torrents>div.torrent-container').toArray();
  sortElements(els);
  $(els).each(function() {
    $('#torrents').append(this);
  });
  resetStripes();
}

function sortSome(hashes) {
  if(!hashes.length) {
    return;
  }
  var els = [];
  for(var h in hashes) {
    els.push(document.getElementById(h));
  }
  sortElements(els);
  
}

function sortElements(arr) {
  var cmp = (current.sortDesc ? -1 : 1);
  arr.sort(function(a, b) {
    var va = window.data.torrents[a.id][current.sortVar];
    var vb = window.data.torrents[b.id][current.sortVar];
    if(va.toLowerCase) va = va.toLowerCase();
    if(vb.toLowerCase) vb = vb.toLowerCase();
    return (va < vb ? -cmp : (va > vb ? cmp : 0));
  });
}

function resetStripes(torrentDivsVisible) {
  if(!torrentDivsVisible) {
    torrentDivsVisible = $('#torrents>div.torrent-container:visible');
  }
  var row1 = true;
  torrentDivsVisible.each(function() {
    $(this)
    .addClass(row1 ? 'row1' : 'row2')
    .removeClass(row1 ? 'row2' : 'row1');
    row1 = !row1;
  });
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
