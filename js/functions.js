function debug(msg) {
  $('#debug:visible').html('<b>' + new Date() + ':</b>\n' + htmlspecialchars(msg));
}

function error(msg) {
  if ($.browser.msie) {
    alert('Error: ' + msg);
  }
  throw new Error(msg);
}


// Functions for dialogs

function showDialog(url, title, width, height) {
  var w = Math.min(parseInt(width),  $(window).width()  - 40);
  var h = Math.min(parseInt(height), $(window).height() - 40);
  var px = function(n) {
    return Math.round(n) + 'px';
  };
  window.dialogShowing = true;
  window.originalScrollTop = $(window).scrollTop();
  $('#dialog')
  .html('<iframe id="dialog-iframe" src="' + htmlspecialchars(url) + '" />')
  .dialog('option', 'title', title)
  .dialog('option', 'width', w)
  .dialog('option', 'height', h)
  .dialog('option', 'position', 'center')
  .dialog('open');
}

function onMouseWheelFromChildFrame() {
  window.scrollingFromFrame = true;
}

function beforeCloseDialog() {
  if (typeof window.hideDialogCallback == 'function') {
    var result = window.hideDialogCallback();
    window.hideDialogCallback = null;
  }
  window.dialogShowing = false;
  return true;
}

function hideDialog(doUpdate) {
  $('#dialog').dialog('close');
  if (doUpdate) {
    updateTorrentsNow();
  }
}


// Functions for user settings

function reloadUserSettings() {
  userSettings.refreshInterval = parseInt($.cookie('refresh_interval'));
  userSettings.useDialogs = ($.cookie('use_dialogs') == 'yes');
  if (userSettings.theme != $.cookie('theme')
    || userSettings.showHidden != ($.cookie('show_hidden' == 'yes'))) {

    document.location.reload();
  }
}

function saveUserSettings() {
  var cookieOptions = {
    expires: 365,
    path: config.rtGuiPath
  };
  $.cookie('sort_var', userSettings.sortVar, cookieOptions);
  $.cookie('sort_desc', (userSettings.sortDesc ? 'yes' : 'no'), cookieOptions);
}


// Format a number of bytes nicely

function formatBytes(bytes, zero, after) {
  if (zero === undefined) {
    zero = '';
  }
  if (after === undefined) {
    after = '';
  }
  if (!bytes) {
    return zero;
  }
  var units = ['B','KB','MB','GB','TB','PB'];
  var i = 0;
  while (bytes >= 1000) {
      i++;
      bytes /= 1024;
  }
  return number_format(bytes, (i ? 1 : 0), '.', ',') + ' ' + units[i] + after;
}


// Functions to update torrents list

function updateTorrentsNow() {
  window.clearTimeout(current.refreshTimeoutID);
  updateTorrentsData();
}

function updateTorrentsData() {
  $.ajax({
    url: 'json.php',
    cache: false,
    type: 'GET',
    success: function(d) {
      var changes = false;
      try {
        changes = JSON.parse(d);
      } catch (_) {
        $('#error').html(current.error = d.replace(/<[^>]+>/g, '')).show();
        return false;
      }

      if (current.error) {
        current.error = false;
        $('#error').hide();
      }

      if (config.debugTab) {
        debug(changes ? JSON.stringify(changes, null, 2) : '(No changes)');
      }

      if (!changes) {
        return;
      }

      $.extend(true, window.data, changes);
      updateTorrentsHTML(changes, false);
    },
    error: function(xhr, status, e) {
      current.error = 'Error updating: ' + status;
      $('#error').html(current.error).show();
    },
    complete: function(xhr, status) {
      window.clearTimeout(current.refreshTimeoutID);
      if (userSettings.refreshInterval) {
        current.refreshTimeoutID = window.setTimeout(updateTorrentsData, userSettings.refreshInterval);
      }
    }
  });
}

function updateTorrentsHTML(changes) {
  var dirty = {
    mustSort: false,
    toFilter: [],
    toCheckView: [],
    positions: false,
    addedTorrents: false,
    removedTorrents: false
  };

  if (changes.torrents) {
    // One or more torrents changed
    for (var hash in changes.torrents) {
      if (changes.torrents[hash] === null) {
        // A torrent was removed
        $('#' + hash).remove();
        dirty.positions = true;
        dirty.removedTorrents = true;
      } else {
        // A torrent was added or modified
        // Render the template to produce the new HTML
        var html = window.templates.torrent.render(
          window.data.torrents[hash]);
        // Get the element that contains the rendered HTML
        var $container = $('#' + hash);
        if ($container.length) {
          // This is an existing torrent: the container element already exists
          // Save the checkbox state
          var checked = $container.find('input.checkbox').attr('checked');
          // Set the HTML
          $container.html(html);
          // Restore the checkbox state
          if (checked) {
            $container.find('input.checkbox').attr('checked', true);
          }
          // Determine if we need to hide/show or sort torrents
          for (var varName in changes.torrents[hash]) {
            if (viewHandlers.varsToCheck[varName]) {
              dirty.toCheckView.push(hash);
            }
            if (current.filters[varName]) {
              dirty.toFilter.push(hash);
            }
            if (userSettings.sortVar == varName) {
              dirty.mustSort = true;
            }
          }
        } else {
          // This is a new torrent: the container element did not exist
          window.data.torrents[hash].visible = true;
          $('#torrents').append(
            '<div class="torrent-container row" id="' + hash + '">\n'
            + html + '\n</div>\n\n');
          dirty.toCheckView.push(hash);
          dirty.toFilter.push(hash);
          dirty.mustSort = true;
          dirty.positions = true;
          dirty.addedTorrents = true;
        }
      }
    }

    if (dirty.toFilter.length || dirty.toCheckView.length
      || dirty.addedTorrents || dirty.removedTorrents) {

      var $torrentDivsAll = $('#torrents>div.torrent-container');
      var opts = {
        filter: dirty.toFilter,
        checkView: dirty.toCheckView,
        addedTorrents: dirty.addedTorrents,
        removedTorrents: dirty.removedTorrents
      };
      updateVisibleTorrents($torrentDivsAll, opts);
    }

    if (dirty.mustSort && sortTorrents($torrentDivsAll)) {
      dirty.positions = true;
    }

    // update current positions
    if (dirty.positions) {
      updateTorrentPositions();
    }
  }

  // update global items (total speeds, caps, disk space, etc.)
  if (changes.global) {
    for (var k in changes.global) {
      $('#global-' + k).html(changes.global[k]);
    }

    if (changes.global.disk_percent) {
      checkDiskPercent();
    }
  }
}


function checkDiskPercent() {
  var $diskData = $('#disk-data');
  if (window.data.global.disk_percent < config.diskAlertThreshold) {
    if (!$diskData.hasClass('disk-alert')) {
      $diskData.addClass('disk-alert');
      var msg = 'Disk free space in your torrents directory is running low!';
      window.setTimeout(function() { alert(msg); }, 200);
    }
  } else if ($diskData.hasClass('disk-alert')) {
    $diskData.removeClass('disk-alert');
  }
}

var viewHandlers = {
  varsToCheck: {
    state: true,
    is_transferring: true,
    complete: true
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
    return t.is_transferring;
  },
  'inactive': function(t) {
    return !t.is_transferring;
  },
  'complete': function(t) {
    return t.complete;
  },
  'incomplete': function(t) {
    return !t.complete;
  },
  'seeding': function(t) {
    return t.complete && t.state;
  }
}


function sortTorrents($torrentDivsAll, reorderAll) {
  if (!userSettings.sortVar) {
    // no sort order is defined
    return false;
  }
  if (!$torrentDivsAll) {
    $torrentDivsAll = $('#torrents>div.torrent-container');
  }
  var runs = [];
  var els = $torrentDivsAll.toArray();
  var len = els.length;

  for (var i = 0; i < len; i++) {
    // set the before-sort position to ensure a stable sort
    window.data.torrents[els[i].id].sortPos = i;
  }

  var toMove = [];
  var anyVisibleMoved = false;
  var elsSorted = null;

  if (reorderAll) {

    els.sort(getTorrentsComparer());
    elsSorted = els;

  } else {

    var result = patienceSort(els, getTorrentsComparer());
    elsSorted = result.sorted;

    if (result.subseq.length == len) {
      // the list was already sorted
      return false;
    }

    // figure out which divs to move, and where
    toMove = new Array(len - result.subseq.length);
    if (toMove.length >= len - 5) {

      /* if we can avoid 5 or more moves, do it; otherwise, just
       * reorder everything
       */
      reorderAll = true;

    } else {

      var iSubseq = 0, subseqLen = result.subseq.length;
      var iToMove = 0, after = 't-none';
      for (var i = 0; i < len; i++) {
        var item = result.sorted[i];
        if (iSubseq < subseqLen && item.id == result.subseq[iSubseq].id) {
          iSubseq++;
        } else {
          if (!anyVisibleMoved && data.torrents[item.id].visible) {
            anyVisibleMoved = true;
          }
          toMove[iToMove++] = {
            after: after,
            item: item
          };
        }
        after = item.id;
      }
    }
  }

  if (reorderAll) {
    // [almost] everything was reordered, so just reorder everything
    var t = $('#torrents');
    $(elsSorted).each(function() {
      t.append(this);
    });
  } else {
    for (var i = 0; i < toMove.length; i++) {
      var move = toMove[i];
      $('#' + move.after).after(move.item);
    }
  }

  return reorderAll || anyVisibleMoved;
}

function updateVisibleTorrents($torrentDivsAll, ids) {
  if (!$torrentDivsAll) {
    $torrentDivsAll = $('#torrents>div.torrent-container');
  }
  var anyChanged = false;

  var actions = {
    checkView: function(id) {
      return viewHandlers[current.view](data.torrents[id]);
    },
    filter: function(id) {
      for (var f in current.filters) {
        // TODO: fill in filtering logic (return false if no match)
      }
      return true;
    }
  };

  var canStop = (!ids || (!ids.addedTorrents && !ids.removedTorrents));
  var checkAll = {}, indices = {};
  for (var a in actions) {
    checkAll[a] = !ids;
    if (checkAll[a] || (ids && ids[a] && ids[a].length)) {
      canStop = false;
    }
    indices[a] = 0;
  }

  if (canStop) {
    return false;
  }

  $torrentDivsAll.each(function() {
    var id = $(this).attr('id');
    var checkState = false, shouldShow = true;

    for (var a in actions) {
      if (checkAll[a] || (ids[a] && ids[a][indices[a]] == id)) {
        checkState = true;
        indices[a]++;
        if (shouldShow && !actions[a](id)) {
          shouldShow = false;
          break;
        }
      }
    }

    if (checkState && shouldShow != data.torrents[id].visible) {
      anyChanged = true;
      $(this).css('display', shouldShow ? '' : 'none');
      data.torrents[id].visible = shouldShow;
    }
  });

  if (anyChanged || (ids && (ids.addedTorrents || ids.removedTorrents))) {
    var $torrentDivsVisible = $torrentDivsAll.filter(function() {
      return data.torrents[$(this).attr('id')].visible;
    });
    $('#t-none').css('display', ($torrentDivsVisible.length ? 'none' : 'block'));
    $('#t-count-visible').html($torrentDivsVisible.length);
    $('#t-count-all').html(data.torrents_count_all);
    $('#t-count-hidden').html(data.torrents_count_hidden);
  }
  return anyChanged;
}

function updateTorrentPositions() {
  var i = 0;
  current.torrentHashes = [];
  $('#torrents>div.torrent-container').each(function() {
    var h = this.id;
    if (window.data.torrents[h].visible) {
      current.torrentHashes[i] = h;
      window.data.torrents[h].pos = i;
      i++;
    } else {
      window.data.torrents[h].pos = -1;
    }
  });
}

function getTorrentsComparer() {
  var cmp = (userSettings.sortDesc ? -1 : 1);
  return function(a, b) {
    var ta = window.data.torrents[a.id];
    var tb = window.data.torrents[b.id];
    var va = ta[userSettings.sortVar];
    var vb = tb[userSettings.sortVar];
    if (va && va.toLowerCase) va = va.toLowerCase();
    if (vb && vb.toLowerCase) vb = vb.toLowerCase();
    return (va < vb ? -cmp : (va > vb ? cmp : ta.sortPos - tb.sortPos));
  };
}

function setCurrentSort(sortInfo, $obj) {
  if (!$obj) {
    $obj = $('#torrents-header a.sort[rel=' + sortInfo + ']');
  }
  var arr = sortInfo.split(':');
  var reversing = false;
  if (arr[0] == userSettings.sortVar) {
    reversing = true;
    userSettings.sortDesc = !userSettings.sortDesc;
  } else {
    userSettings.sortVar = arr[0];
    userSettings.sortDesc = (arr[1] == 'desc');
  }
  $('#torrents-header a.sort').attr('class', 'sort');
  $obj.addClass(userSettings.sortDesc ? 'sort-desc' : 'sort-asc');
  if (sortTorrents(null, arr.length > 2 && reversing)) {
    updateTorrentPositions();
  }
  saveUserSettings();
}

function setCurrentView(viewName, $obj) {
  if (current.view == viewName) {
    return;
  }
  if (!$obj) {
    $obj = $('#navlist a.view[rel=' + viewName + ']');
  }
  current.view = viewName;
  $('#navlist a.view').attr('class', 'view');
  $obj.addClass('current');
  updateVisibleTorrents();
}
