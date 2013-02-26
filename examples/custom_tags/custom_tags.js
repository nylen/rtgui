var parser  = require('../../lib/parser'),
  helpers = require('../../lib/helpers'),
  _ = require('underscore');

exports.input = function (indent) {
  var type = parser.parseVariable(this.args[0]),
    name = parser.parseVariable(this.args[1]),
    label = (this.args.length > 2) ? parser.parseVariable(this.args[2]) : name,
    value = (this.args.length > 3) ? parser.parseVariable(this.args[3]) : '',
    out = [];

  out = [
    '(function() {',
    helpers.setVar('__name', name),
    helpers.setVar('__type', type),
    helpers.setVar('__label', label),
    helpers.setVar('__value', value),
    '  _output += \'<div class="input \' + __type + \'">\';',
    '  _output += \'\\n\';',
    '  _output += \'<label for="\' + __name + \'">\' + __label + \'</label>\';',
    '  _output += \'<input type="\' + __type + \'" name="\' + __name + \'" id="\' + __name + \'" value="\' + __value + \'">\';',
    '  _output += \'\\n\';',
    '  _output += "</div>";',
    '  _output += \'\\n\';',
    '})();'
  ];

  return out.join('\n' + indent);
};
exports.input.ends = false;
