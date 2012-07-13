(function() {
  // A few helper functions

  function log() {
    var msg = Array.prototype.slice.call(arguments).join(' ');
    document.write('<div>' + msg + '</div>\n');
  }

  function assert(cond) {
    if (!cond) {
      throw new Error('Assert failed (debug me!)');
    }
  }

  function randomArray(n, p) {
    if (!p) p = 1000;
    var arr = new Array(n);
    for (var i=0; i<n; i++) {
      arr[i] = Math.round(Math.random()*p);
    }
    return arr;
  }

  function mostlySortedArray(n, r) {
    var arr = new Array(n);
    for (var i=0; i<n; i++) {
      arr[i] = i;
    }
    for (var i=0; i<r; i++) {
      var x = Math.floor(Math.random()*n);
      var y = Math.floor(Math.random()*n);
      var tmp = arr[x];
      arr[x] = arr[y];
      arr[y] = tmp;
    }
    return arr;
  }


  // Test binary search
  (function() {
    var a = [0,5,10];
    assert(binarySearch(a, 0) == 0);
    assert(binarySearch(a, 5) == 1);
    assert(binarySearch(a, 10) == 2);
    assert(binarySearch(a, -1) == -1);
    assert(binarySearch(a, 11) == -4);
    a = [0,5,10,15];
    assert(binarySearch(a, 0) == 0);
    assert(binarySearch(a, 5) == 1);
    assert(binarySearch(a, 10) == 2);
    assert(binarySearch(a, 15) == 3);
    assert(binarySearch(a, -1) == -1);
    assert(binarySearch(a, 16) == -5);
  })();

  // Test patience sorting
  (function() {
    var trials = 30, len = 100;
    for (var n=0; n<trials; n++) {
      a = mostlySortedArray(len, Math.floor(Math.random()*len));
      var p = patienceSort(a);
      for (var i=1; i<a.length; i++) {
        if (i < p.subseq.length) {
          assert(p.subseq[i] >= p.subseq[i-1]);
        }
        assert(p.sorted[i] >= p.sorted[i-1]);
      }
    }
  })();

  // Benchmark vs. Array.sort()
  (function() {
    var cs = {
      'default': undefined,
      slower: function(a, b) {
        return getDefaultComparer()(a, b);
      },
      slowest: function(a, b) {
        var slowdown = randomArray(3);
        return getDefaultComparer()(a, b);
      }
    };

    var mob = (navigator.userAgent.indexOf('Mobile') >= 0);

    var trials = mob?50:500, len = 50, swaps = 10;
    log('trials=',trials, 'len=',len, 'swaps=',swaps);

    for (var c in cs) {
      var data = new Array(trials);
      for (var i=0; i<trials; i++) {
        data[i] = mostlySortedArray(len, swaps);
      }

      var d1 = new Date();
      for (var i=0; i<trials; i++) {
        patienceSort(data[i], cs[c]);
      }
      var d2 = new Date();
      for (var i=0; i<trials; i++) {
        patienceSort(data[i], cs[c], true);
      }
      var d3 = new Date();
      if (c == 'default') {
        for (var i=0; i<trials; i++) {
          data[i].sort();
        }
      } else {
        for (var i=0; i<trials; i++) {
          data[i].sort(cs[c]);
        }
      }
      var d4 = new Date();
      log('cmp=',c, 'patience=',d2-d1, 'subseq=',d3-d2, 'stock=',d4-d3, 'diff=',(d2-d1)-(d4-d2));
    }
  })();

  document.close();

})();
