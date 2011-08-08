$(function() {
  $(window).bind('mousewheel', function(e, d) {
    window.top.onMouseWheelFromChildFrame();
  });

  $('a.tab-multi').click(function() {
    var tabName = $(this).attr('rel');
    $('div.tab').addClass('hidden');
    $('#tab-' + tabName).removeClass('hidden');
    $('a.tab').removeClass('current');
    $(this).addClass('current');
    document.location.hash = tabName;
    return false;
  });

  var getHash = function() {
    return document.location.hash.replace(/^#/, '');
  };

  var hash = getHash();
  if(/^[a-z]+$/.test(hash)) {
    $('a.tab[rel=' + hash + ']').trigger('click');
  }

  $('.command-button').click(function() {
    var cmd = $(this).attr('rel');
    var currentTab = getHash();
    if(currentTab) currentTab = '&tab=' + currentTab;
    if(!$(this).hasClass('confirm') || confirmWithMessage(cmd)) {
      document.location.href = 'control.php?hash=' + window.currentHash + '&cmd=' + cmd + currentTab;
    }
  });

  $('#btn-refresh').click(function() {
    document.location.reload();
  });

  $('#directory-form').submit(function() {
    $(this).attr('action', 'control.php?tab=' + getHash());
  });

  $('a.ip-address').each(function() {
    var $a = $(this);
    $.get('lookup-ip.php', {
      ip: $(this).data('ip')
    }, function(d) {
      d = JSON.parse(d);
      $a
      .text(d.hostname)
      .attr('title', d.ip + '<br />' + d.location + '<br />Click for whois')
      .tipTip({
        delay: 100,
        fadeOut: 0
      });
    });
  });
});

function onDirBrowserLoaded(dir) {
  $('#new-dir').val(dir);
  $('#sel-dir').html(dir);
}
