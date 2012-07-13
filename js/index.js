function onAjaxRequestDone(data) {
  if(!$('#leave-checked:checked').length) {
    $('div.torrent-container input[type=checkbox]')
    .attr('checked', false);
  }
  updateTorrentsNow();
  data = $.trim(data);
  if(data) {
    alert('Server error:\n\n' + data);
  }
}

$(function() {
  // Perform initialization functions

  var hideIframe = function() {
    $('#dialog-iframe').css('display', 'none');
  };
  var showIframe = function() {
    $('#dialog-iframe').css('display', '');
  };

  $('#dialog').dialog({
    modal: true,
    autoOpen: false,
    minWidth: 150,
    minHeight: 150,
    beforeClose: beforeCloseDialog,
    dragStart: hideIframe,
    dragStop: showIframe,
    resizeStart: hideIframe,
    resizeStop: showIframe,
    // For some reason dragging and resizing are VERY slow in Firefox...
    draggable: !$.browser.mozilla,
    resizable: !$.browser.mozilla
  });

  //updateTorrentsHTML(data, true);
  window.clearTimeout(current.refreshTimeoutID);
  if(userSettings.refreshInterval) {
    current.refreshTimeoutID = window.setTimeout(updateTorrentsData, userSettings.refreshInterval);
  }

  if(userSettings.sortVar) {
    $('a.sort').each(function() {
      if($(this).attr('rel').split(':')[0] == userSettings.sortVar) {
        $(this).addClass(userSettings.sortDesc ? 'sort-desc' : 'sort-asc');
      }
    });
  }


  // Set up event handlers

  $(window).bind('mousewheel', function(e, d) {
    if(window.dialogShowing) {
      e.preventDefault();
    }
  }).scroll(function() {
    if(window.scrollingFromFrame) {
      $(window).scrollTop(window.originalScrollTop);
      window.scrollingFromFrame = false;
    } else {
      window.originalScrollTop = $(window).scrollTop();
    }
  });

  $('#debug-tab').click(function() {
    var dbg = $('#debug:visible').length;
    $(this)[dbg ? 'removeClass' : 'addClass']('current');
    $('#debug').css('display', dbg ? 'none' : 'block');
    debug('Waiting for refresh...');
    return false;
  });

  $('#torrents-header a.sort').click(function() {
    setCurrentSort($(this).attr('rel'), $(this));
    return false;
  });

  $('#navlist a.view').click(function() {
    setCurrentView($(this).attr('rel'), $(this));
    return false;
  });

  $('#filter').blur(function() {
    if($(this).val() == '') {
      $(this).val(config.defaultFilterText);
    }
  }).focus(function() {
    if($(this).val() == config.defaultFilterText) {
      $(this).val('');
    }
  }).trigger('blur');

  $('#control-form').ajaxForm({
    beforeSubmit: function(formData, form, options) {
      for(var i = 0; i < formData.length; i++) {
        if(formData[i].name == 'bulkaction') {
          // confirm delete
          if(formData[i].value == 'delete'
          && !confirm('Are you sure you want to delete the selected '
            + 'torrents?  Their data will not be deleted.')) {

            return false;
          }
        } else {
          // don't submit invisible checked torrents
          var t = window.data.torrents[formData[i].value];
          if(t && !t.visible) {
            formData[i].value = '';
          }
        }
      }
      formData.push({
        name: 'ajax',
        value: true
      });
    },
    success: onAjaxRequestDone
  });

  $('div.torrent-container').live('click', function(e) {
    var thisHash = this.id;
    var $thisCheckbox = $(this).find('input[type=checkbox]');
    if(window.contextMenuShowing) {
      if($thisCheckbox.filter(e.target).length) {
        $thisCheckbox.attr('checked', function() {
          return !this.checked;
        });
      }
      return;
    }
    $thisCheckbox.not(e.target).attr('checked', function() {
      return !this.checked;
    });
    if(e.shiftKey && current.lastHash) {
      var thisPos = window.data.torrents[thisHash].pos;
      var lastPos = window.data.torrents[current.lastHash].pos;
      if(window.data.torrents[current.lastHash].visible) {
        var d = (thisPos > lastPos ? 1 : -1);
        var checked = $thisCheckbox.attr('checked');
        for(var p = lastPos; p != thisPos; p += d) {
          var hash = current.torrentHashes[p];
          if(window.data.torrents[hash].visible) {
            $('#t-' + hash + '-checkbox').attr('checked', checked);
          }
        }
      }
    }
    $thisCheckbox[0].focus();
    current.lastHash = thisHash;
  }).live('mousedown', function(e) {
    if(e.shiftKey) {
      return false;
    }
  });

  $('.select-all, .unselect-all').click(function() {
    $('div.torrent-container input[type=checkbox]')
    .attr('checked', $(this).hasClass('select-all'));
    return false;
  });

  $('a.dialog').live('click', function() {
    if(!userSettings.useDialogs) {
      return true;
    }
    var $this = $(this);
    var href = $this.attr('href');
    if(!/dialog=1/.test(href)) {
      // Dialog pages know not to display a title if $_GET['dialog'] is set
      href += (/\?/.test(href) ? '&' : '?') + 'dialog=1';
    }
    var dims = $this.attr('rel').split(':');
    showDialog(href, $this.text(), dims[0], dims[1]);
    return false;
  });

  $('a.ajax').live('click', function() {
    if($(this).hasClass('confirm')
    && !confirmWithMessage($(this).attr('rel'))) {
      return false;
    }
    $.post($(this).attr('href'), {ajax: true}, function(d) {
      updateTorrentsNow();
    });
    return false;
  });
});
