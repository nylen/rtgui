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
  $.getJSON('json.php', function(data, s) {
    if(!data) {
      debug('(No changes)');
      return;
    }
    debug(JSON.stringify(data, null, 2));
    
    $.extend(true, torrentsData, data);
    updateHTML(torrentsData, data, false);
  });
}

function updateHTML(full, changes, isFirstUpdate) {
  var dirty = {
    hashesToSort: [],
    hashesToFilter: [],
    stripes: false,
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
            var val = changes.torrents[hash][varName];
            var el = $('#t-' + hash + '-' + varName)[0];
            if(formatHandlers[varName]) {
              val = formatHandlers[varName].call(el, val);
            }
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
        // TODO: sort these torrents
      }
      for(var i = 0; i < dirty.hashesToFilter.length; i++) {
        // TODO: filter these torrents
      }
    }
    
    var torrentDivs = $('#torrents div.torrent:visible');
    $('#t-none').css('display', (torrentDivs.length ? 'none' : ''));
    
    // set row classes
    if(dirty.stripes || dirty.hashesToSort.length || dirty.hashesToFilter.length) {
      var row1 = true;
      $('#torrents div.torrent:visible').each(function() {
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
      $('#' + k).html(function() {
        if(formatHandlers[k]) {
          return formatHandlers[k].call(this, changes[k]);
        } else {
          return changes[k];
        }
      });
    }
  }
}
