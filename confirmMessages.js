(function() {
  var stdMessage = 'Are you sure you want to remove this torrent';
  var messages = {
    'delete': stdMessage + '?  Its data will not be deleted.',
    'purge': stdMessage + ' AND delete its data?'
  };
  window.confirmWithMessage = function(cmd) {
    return confirm(messages[cmd]);
  }
})();

