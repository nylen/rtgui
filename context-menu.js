$(function() {
  var setTagState = function(el, state) {
    el = $(el);
    el.removeClass('state-true').removeClass('state-false').removeClass('state-undefined');
    setTimeout(function() {
      if(state === undefined) {
        el.addClass('state-undefined').find(':checkbox').attr('checked', false);
      } else if(state) {
        el.addClass('state-true').find(':checkbox').attr('checked', true);
      } else {
        el.addClass('state-false').find(':checkbox').attr('checked', false);
      }
    }, 10);
  };

  var getTagState = function(el) {
    return $(el).hasClass('state-true');
  };

  var selectedByMenuClick = null;
  var selectedTorrentHashes = [];
  var onMenuHide = function(clearCheckbox) {
    setTimeout(function() {
      window.contextMenuShowing = false;
    }, 10);
    if(clearCheckbox && selectedByMenuClick !== null) {
      $(selectedByMenuClick).find(':checkbox').attr('checked', false);
      selectedByMenuClick = null;
    }
  };
  $('#context-menu').jeegoocontext('.torrent-container', {
    onShow: function(e, context) {
      window.contextMenuShowing = true;
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
        $torrents.find(':checkbox').attr('checked', false);
        $torrents = $(context);
        $torrents.find(':checkbox').attr('checked', true);
        selectedByMenuClick = context;
      }
      selectedTorrentHashes = $torrents.map(function() {
        return this.id;
      }).toArray();

      $(this).find('.selected-torrents')
      .text(selectedTorrentHashes.length > 1
        ? selectedTorrentHashes.length + ' torrents selected'
        : window.data.torrents[selectedTorrentHashes[0]].name);

      $(this).find('.leave-checked :checkbox')
      .attr('checked', $('#leave-checked').attr('checked'));

      $('#context-menu li.tag').each(function() {
        var tag = $(this).data('tag');
        var tagSet = false;
        var tagUnset = false;
        for(var i in selectedTorrentHashes) {
          var hash = selectedTorrentHashes[i];
          var tags = '|' + window.data.torrents[hash].tags + '|';
          if(tags.indexOf('|' + tag + '|') != -1) {
            tagSet = true;
          } else {
            tagUnset = true;
          }
        }
        if(tagSet && tagUnset) {
          setTagState(this, undefined);
        } else {
          setTagState(this, tagSet);
        }
      });
    },

    onSelect: function(e, context) {
      if(!$(this).hasClass('no-hide')) {
        onMenuHide(false);
      }
      if($(this).data('command')) {
        // Just piggyback off of the control-form logic
        // HACK: this should probably be changed
        $('#bulk-action').val($(this).data('command'));
        $('#control-form').submit();
      }
      if($(this).hasClass('tag')) {
        setTagState(this, !getTagState(this));
      } else if($(this).hasClass('toggle')) {
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
    },

    onHide: function(e, context) {
      onMenuHide(true);
    }
  });

  var $tagName = $('#context-menu input.new-tag-name');

  var addTags = function() {
    var tags = $tagName.val();
    tags = tags.replace(/[^a-z0-9 (),_=|'-]/gi, '').replace(/\|+/, '|').split('|');

    for(var i in tags) {
      var tag = tags[i];
      if(!tag) continue;
      if(tag == '_hidden' && !config.canHideUnhide) continue;
      var foundTagListItem = false;
      $('#context-menu li.tag').each(function() {
        if($(this).data('tag') == tag) {
          foundTagListItem = true;
          return false;
        }
      });
      if(!foundTagListItem) {
        var $addAfter = $('#context-menu li.new-tag');
        while($addAfter.next('li').is('.tag') && $addAfter.next('li').data('tag') < tag) {
          $addAfter = $addAfter.next('li');
        }
        $addAfter.after(
            '<li class="tag no-hide toggle state-true" data-tag="' + tag + '">'
            + '<input type="checkbox" checked="checked" />' + tag
          + '</li>');
      }
    }
    $tagName.val('');
  };

  $tagName.keyup(function(e) {
    if(e.keyCode == 13) {
      if($(this).val()) {
        addTags();
      } else {
        $('#context-menu li.tag-controls .save').trigger('click');
      }
    }
  });

  $('#context-menu a.add-new-tag').click(function() {
    addTags();
    return false;
  });

  $('#context-menu li.tag-controls .save').click(function() {
    addTags();
    var returnTag = function() {
      return $(this).data('tag');
    };
    $.post('control.php', {
      ajax: true,
      bulkaction: 'set_tags',
      hashes: selectedTorrentHashes,
      add_tags: $('#context-menu li.tag.state-true').map(returnTag).toArray(),
      remove_tags: $('#context-menu li.tag.state-false').map(returnTag).toArray()
    }, onAjaxRequestDone);
    $.hidejeegoocontext();
  });

  $('#context-menu li.tag-controls .cancel').click(function() {
    $.hidejeegoocontext();
  });
});
