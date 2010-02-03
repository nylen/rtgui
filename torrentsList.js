// Functions to update torrents list

function updateTorrentsData() {
  $.getJSON('json.php', function(data, s) {
    if(!data) {
      debug('(No changes)');
      return;
    }
    debug(JSON.stringify(data, null, 2));
    
    $.extend(true, window.torrentsData, data);
    updateTorrentsHTML(window.torrentsData, data, false);
  });
}

function updateTorrentsHTML(full, changes, isFirstUpdate) {
  var dirty = {
    hashesToSort: [],
    hashesToFilter: [],
    stripes: !!isFirstUpdate,
  };
  
  if(changes.torrents) {
    // One or more torrents changed
    for(hash in changes.torrents) {
      if(changes.torrents[hash] === null) {
        // A torrent was removed
        delete full.torrents[hash];
        $('#t-' + hash).remove();
        dirty.stripes = true;
      } else {
        var mustRewriteHTML = false;
        if(isFirstUpdate || !full.torrents[hash]) {
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
          var html = applyTemplate(full.torrents[hash], templates.torrent, hash, 't');
          var container = $('#t-' + hash + '-container');
          if(container.length) {
            dirty.stripes = true;
            container.html(html);
            checkChangedVars = true;
          } else {
            $('#torrents').append(
              '<div class="torrent-container" id="t-' + hash + '-container">\n'
              + html + '\n</div>\n\n');
            dirty.hashesToSort.push(hash);
            dirty.hashesToFilter.push(hash);
          }
        } else {
          for(varName in changes.torrents[hash]) {
            var el = $('#t-' + hash + '-' + varName)[0];
            var val = getFormattedValue(varName, full.torrents[hash][varName], el);
            $(el).html(val);
            checkChangedVars = true;
          }
        }
        if(checkChangedVars) {
          for(varName in changes.torrents[hash]) {
            // TODO: check if varName is part of filters or sort keys, and add to dirty.hashesTo*
          }
        }
      }
    }
    if(isFirstUpdate) {
      // TODO: sort and filter all torrents
    } else {
      for(var i = 0; i < dirty.hashesToSort.length; i++) {
        // TODO: sort these torrents, and set dirty.stripes if needed
      }
      for(var i = 0; i < dirty.hashesToFilter.length; i++) {
        // TODO: filter these torrents, and set dirty.stripes if needed
      }
    }
    
    var torrentDivs = $('#torrents div.torrent:visible');
    $('#t-none').css('display', (torrentDivs.length ? 'none' : ''));
    
    // set row classes
    if(dirty.stripes) {
      var row1 = true;
      torrentDivs.each(function() {
        $(this)
        .addClass(row1 ? 'row1' : 'row2')
        .removeClass(row1 ? 'row2' : 'row1');
        row1 = !row1;
      });
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
