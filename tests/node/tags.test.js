var require = require('./testutils').require,
  expect = require('expect.js'),
  util = require('util'),
  swig = require('../../lib/swig');

describe('Custom Tags', function () {
  var tags = {
    foo: function (indent) {
      return '_output += "hi!";';
    }
  };
  tags.foo.ends = true;

  it('can be included on init', function () {
    swig.init({ tags: tags });

    expect(swig.compile('{% foo %}{% endfoo %}')({}))
      .to.equal('hi!');
  });
});
