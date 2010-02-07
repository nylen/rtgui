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
            if(current.filters[varName]) {
              dirty.toFilter.push(hash);
            }
            if(current.sortVar == varName) {
              dirty.toSort.push(hash);
            }
          }
        }
      }
    }
    
    var torrentDivsAll = $('#torrents>div.torrent-container');
    var torrentDivsVisible = torrentDivsAll.filter(':visible');
    $('#t-none').css('display', (torrentDivsVisible.length ? 'none' : ''));
    $('#t-count-visible').html(torrentDivsVisible.length);
    $('#t-count-all').html(torrentDivsAll.length);
    
    if(isFirstUpdate) {
      // dirty.stripes is already true
      sortTorrents(torrentDivsAll, true);
    } else {
      for(var h in dirty.toCheckView) {
        
      }
      for(var h in dirty.toFilter) {
        // TODO: filter these torrents, and set dirty.stripes if needed
      }
      for(var h in dirty.toSort) {
        // TODO: need to pass a list of torrent divs to sort?
        // If not then use "dirty.mustSort = true"
        if(sortTorrents(torrentDivsAll)) {
          dirty.stripes = true;
        }
        break;
      }
    }
    
    // set row classes
    if(dirty.stripes) {
      resetStripes(dirty.toSort.length ? null : torrentDivsVisible);
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


function sortTorrents(torrentDivsAll, reorderAll) {
  if(!current.sortVar) {
    // no sort order is defined
    return false;
  }
  if(!torrentDivsAll) {
    torrentDivsAll = $('#torrents>div.torrent-container');
  }
  var runs = [];
  var els = torrentDivsAll.toArray();
  var len = els.length;
  
  if(reorderAll) {
    els.sort(getSortComparator());
    
  } else {
    var before = [];
    var after = {};
    
    for(var i = 0; i < len; i++) {
      before.push(els[i].id);
    }
    els.sort(getSortComparator());
    for(var i = 0; i < len; i++) {
      after[els[i].id] = i;
    }
    
    // identify sorted runs in the original list
    var lastPos = -1;
    var run = {
      pos: 0,
      entries: [],
    };
    for(var i = 0; i < len; i++) {
      var thisHash = before[i];
      var thisPos = after[thisHash];
      var entry = {
        hash: thisHash,
        pos: thisPos,
        after: (thisPos ? els[thisPos - 1].id : 't-none'),
      };
      if(thisPos != lastPos + 1 && lastPos != -1) {
        // end the current run and start a new one
        runs.push(run);
        run = {
          pos: i,
          entries: [],
        };
      }
      lastPos = thisPos;
      run.entries.push(entry);
    }
    runs.push(run);
    
    if(runs.length <= 1) {
      // the list was already sorted
      return false;
    }
  }
    
  if(reorderAll || runs.length >= els.length / 1.5) {
    // almost everything was reordered, so just reorder everything
    var t = $('#torrents');
    $(els).each(function() {
      t.append(this);
    });
    
  } else {
    // choose which divs to move
    var runsByPos = {};
    for(var i = 0; i < runs.length; i++) {
      runsByPos[runs[i].pos] = runs[i];
    }
    var runsByLength = runs;
    runsByLength.sort(function(a, b) {
      var c = a.entries.length - b.entries.length;
      return (c ? c : a.pos - b.pos);
    });
    
    var toMove = [];
    for(var i in runsByLength) {
      toMove = toMove.concat(runsByLength[i].entries);
      delete runsByPos[runsByLength[i].pos];
      
      var isSorted = true;
      var lastPos = -1;
      for(var thisPos in runsByPos) {
        if(lastPos != -1) {
          var lastEntries = runsByPos[lastPos].entries;
          if(lastEntries[lastEntries.length - 1].pos > runsByPos[thisPos].entries[0].pos - 1) {
            isSorted = false;
            break;
          }
        }
        lastPos = thisPos;
      }
      if(isSorted) {
        break;
      }
    }
    
    toMove.sort(function(a, b) {
      return a.pos - b.pos;
    });    
    for(var i = 0; i < toMove.length; i++) {
      $('#' + toMove[i].hash).insertAfter('#' + toMove[i].after);
    }
  }
  
  return true;
}

function sortSome(hashes) {
  if(!hashes.length) {
    return;
  }
  var els = [];
  for(var h in hashes) {
    els.push(document.getElementById(h));
  }
  els.sort(sortComparator());
  
}

function getSortComparator() {
  var cmp = (current.sortDesc ? -1 : 1);
  return function(a, b) {
    var va = window.data.torrents[a.id][current.sortVar];
    var vb = window.data.torrents[b.id][current.sortVar];
    if(va.toLowerCase) va = va.toLowerCase();
    if(vb.toLowerCase) vb = vb.toLowerCase();
    return (va < vb ? -cmp : (va > vb ? cmp : 0));
  };
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
