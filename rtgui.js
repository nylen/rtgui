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
        if(formatHandlers[k]) {
          return formatHandlers[k].call(this, data[k]);
        } else {
          return formatHandlers[k];
        }
      });
    }
  }
}
