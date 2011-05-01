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
    // For some reason dragging and resizing are VERY slow...
    draggable: false,
    resizable: false
  });

  updateTorrentsHTML(data, true);
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
    success: function() {
      if(!$('#leave-checked:checked').length) {
        $('div.torrent-container input[type=checkbox]')
        .attr('checked', false);
      }
      updateTorrentsNow();
    }
  });

  $('div.torrent-container').live('click', function(e) {
    var thisHash = this.id;
    var $thisCheckbox = $(this).find('input[type=checkbox]');
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

  // Set up context menu

  $('.torrent-container').jeegoocontext('context-menu', {
    onShow: function(e, context) {
      // There's a problem with this logic: the number of visible checked
      // torrents could change in between refreshes due to filters or views.
      // These changes won't be reflected.
      //
      // Speed may also be an issue.  Maybe :checked should be cached like
      // :visible is?
      var $torrents = $('.torrent-container:has(:checked)').filter(function() {
        return window.data.torrents[this.id].visible;
      });
      if($.inArray(context, $torrents) == -1) {
        $torrents.find(':checked').attr('checked', false);
        $torrents = $(context);
        $torrents.find(':checkbox').attr('checked', true);
      }
      $(this).find('.selected-torrents')
      .text($torrents.length > 1
        ? $torrents.length + ' torrents selected'
        : window.data.torrents[$torrents[0].id].name);
      $(this).find('.leave-checked :checkbox')
      .attr('checked', $('#leave-checked').attr('checked'));
    },
    onSelect: function(e, context) {
      if($(this).data('command')) {
        // Just piggyback off of the control-form logic
        // HACK: this should probably be changed
        $('#bulk-action').val($(this).data('command'));
        $('#control-form').submit();
      }
      if($(this).data('tag')) {
        // TODO
      }
      if($(this).hasClass('toggle')) {
        var $ch = $(this).find(':checkbox');
        if($ch.filter(e.target).length) {
          // Need to do this because otherwise something "resets" the checked state
          // of the checkbox to what it was before this event handler was called.
          var checked = $ch.attr('checked');
          window.setTimeout(function() {
            $ch.attr('checked', checked);
          }, 10);
        } else {
          $ch.attr('checked', !$ch.attr('checked'));
        }
      }
      if($(this).hasClass('leave-checked')) {
        $('#leave-checked').attr('checked', $(this).find(':checkbox').attr('checked'));
      }
    }
  });
});
