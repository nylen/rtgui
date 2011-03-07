$(function() {
  $('a.tab-multi').click(function() {
    var tabName = $(this).attr('rel');
    $('div.tab').addClass('hidden');
    $('#tab-' + tabName).removeClass('hidden');
    $('a.tab').removeClass('current');
    $(this).addClass('current');
    document.location.hash = tabName;
    return false;
  });

  var hash = document.location.hash.replace(/^#/, '');
  if(/^[a-z]+$/.test(hash)) {
    $('a.tab[rel=' + hash + ']').trigger('click');
  }

  $('.command-button').click(function() {
    var cmd = $(this).attr('rel');
    if(!$(this).hasClass('confirm') || confirmWithMessage(cmd)) {
      document.location.href = 'control.php?hash=' + window.currentHash + '&cmd=' + cmd;
    }
  });

  $('#btn-refresh').click(function() {
    document.location.reload();
  });
});

function onDirBrowserLoaded(dir) {
  $('#new-dir').val(dir);
  $('#sel-dir').html(dir);
}
