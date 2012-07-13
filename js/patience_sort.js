function getDefaultComparer() {
  return function(a, b) {
    return (a < b ? -1 : (a > b ? 1 : 0));
  };
}

function binarySearch(list, val, cmp, len) {
  if (typeof cmp != 'function') {
    cmp = getDefaultComparer();
  }
  if (!len && len !== 0) {
    len = list.length;
  }

  var left = 0, right = len - 1;
  while (right >= left) {
    var mid = (left + right) >> 1;
    var c = cmp(list[mid], val);
    if (c > 0) {
      right = mid - 1;
    } else if (c < 0) {
      left = mid + 1;
    } else {
      return mid;
    }
  }
  return ~left;
}

function patienceSort(list, cmp, subseqOnly) {
  if (typeof cmp != 'function') {
    cmp = getDefaultComparer();
  }

  // we'll use this whenever we binary search through the piles
  var pileComparer = function(pile, val) {
    // compare each pile's last element to the desired value
    return cmp(pile[pile.length-1].item, val);
  };

  /* we'll work with an array of piles, where each "pile" is a
   * stack of "cards"
   */
  var piles = [[{
    item: list[0],
    backPtr: null
  }]];
  var nItems = list.length;

  // build the piles
  for (var i = 1; i < nItems; i++) {
    /* each "card" contains the list item and a back-pointer to the
     * next highest element in what could be the longest increasing
     * subsequence
     */
    var card = {
      item: list[i],
      backPtr: null
    };

    /* optimize for a common case (often, the list is mostly sorted,
     * which means we will be starting a lot of new piles)
     */
    var p = piles.length;
    var c = cmp(card.item, piles[p-1][piles[p-1].length - 1].item);
    if (c > 0) {
      // start a new pile with this card
      piles.push([]);
    } else {
      /* we rely on binarySearch returning a negative index like .NET's
       * Array.BinarySearch does when no match is found
       */
      var b = binarySearch(piles, card.item, pileComparer);
      p = (b < 0 ? ~b : b);
    }
    if (p > 0) {
      card.backPtr = piles[p-1][piles[p-1].length - 1];
    }
    piles[p].push(card);
  }

  if (piles.length == nItems) {
    // the list was already sorted
    if (subseqOnly) {
      return list;
    } else {
      /* note that what we return here takes no time to build, but it
       * could cause some problems with references!
       */
      return {sorted: list, subseq: list};
    }
  }

  // build the longest increasing subsequence
  var subseq = new Array(piles.length);
  var p = piles[piles.length - 1];
  var card = p[p.length - 1];
  subseq[piles.length - 1] = card.item;
  for (var i = piles.length - 2; i >= 0; i--) {
    card = card.backPtr;
    subseq[i] = card.item;
  }

  if (subseqOnly) {
    return subseq;
  }

  var sorted = new Array(nItems);
  // try to traverse the piles efficiently
  for (var i = 0; i < nItems; i++) {
    p = piles.shift();
    sorted[i] = p.pop().item;
    if (p.length) {
      var b = binarySearch(piles, p[p.length-1].item, pileComparer);
      piles.splice((b < 0 ? ~b : b), 0, p);
    }
  }

  return {sorted: sorted, subseq: subseq};
}
