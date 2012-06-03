/*!
 * HTML Snippet Javascript Notation - DomBuilder for jQuery
 * 
 * Copyright (c) 2009 Eric Garside (http://eric.garside.name)
 * Dual licensed under:
 * 	MIT: http://www.opensource.org/licenses/mit-license.php
 *	GPLv3: http://www.opensource.org/licenses/gpl-3.0.html
 */
(function($){
	
		// Regex classes for pulling out style info, much thanks to Karl Swedberg
	var rXclasses = /\.[a-zA-Z_-]+/g,
		rXid = /#[a-zA-Z_-]+/,
		rXelement = /^[a-zA-Z]+/,
		rXstrip = /\./g;
	
	/** jQuery Entry Point **/
	
	$.hsjn = function(hsjn){ return parseHSJN( hsjn ) }
	$.fn.hsjn = function( hsjn ){ 
		var el = parseHSJN( hsjn );
		return this.each(function(){ $(this).append(el) });
	}
	
	/** Internals **/
	
	// Parse a JSON object as HSJN
	function parseHSJN( hsjn ){
		var selector = hsjn.shift(), // Selector must always be the first object
			el, attrs, css, jQ, text, children,
			tag = selector.match(rXelement),
			id = selector.match(rXid),
			classes = selector.match(rXclasses);

		$.each(hsjn, function(){
			if (!attrs && isTypeOf(this, 'Object')) attrs = this;
			if (!text && isTypeOf(this, 'String')) text = this+'';	// Force reset to a literal string
			if (!children && isTypeOf(this, 'Array')) children = this;
		})

		if (attrs) css = extractor( attrs, '_' );	// If the attributes contains styling info, splice it out
		if (attrs) jQ = extractor( attrs, '$' );	// If the attributes contains chaining info, splice it out
		
		el = $(document.createElement(tag));
		if (attrs) el.attr( attrs );
		if (css) el.css( css );
		if (text) el.html( text );
		if (id) el.attr('id', id);
		if (classes) el.addClass( classes.join(' ').replace(rXstrip, '') );
		if (children && isTypeOf(children[0], 'String') ) el.append( parseHSJN( children ) )
		else if (children) $.each(children, function(){ el.append( parseHSJN( this ) ) })
		if (jQ) parseJQ( jQ, el );
		
		return el;
	}
	
	// Parses and binds jQuery chains
	function parseJQ( jQ, el ){
		$.each(jQ, function(k){
			var func, call = k.match(/^[a-zA-Z_]+/)[0];
			// If what we're calling isn't a valid function, continue.			
			if ( !isTypeOf( $.fn[call], 'Function' ) ) return;

			switch (true){
				case isTypeOf(this, 'String'):	// Parse-out Function
					func = evalFunction( this+'' );
				case isTypeOf(this, 'Function'):
					func = func || this;
					el[ call ]( func );
					break;
				case isTypeOf(this, 'Array'):	// Call with params
					func = this;
					// Iterate over each element, checking for function declarations
					$.each(func, function(k){ func[k] = evalFunction( this, true ) || this });
					// Call the function
					el[ call ].apply(el, func);
					break;
				case !this:	// Call with no params
					el[ call ]();
					break;
				default: return;
			}			
		})
	}
	
	// Parses declarations out of the flat attribute array
	function extractor( attrs, symbol ){
		if (!attrs || !attrs[symbol] ) return undefined;
		var extract = attrs[symbol];
		delete attrs[symbol];
		return extract;
	}
	
	// Test an object for it's constructor type. Sort of a reverse, discriminatory instanceof
	function isTypeOf(t, c){ 
    if(t === undefined) {
      return (c.toLowerCase() === 'undefined');
    } else {
      return (t.constructor.toString().match(new RegExp(c, 'i')) != null);
    }
  }
	
	// Path to a function, given a string containing the name and optional scoping
	function evalFunction( value, strict ){
		strict = strict || false;
		
		var func;
			
		switch (true){
			case /::/.test(value): // Pre-defined with scope
				var chain = value.split('::').reverse();
				if (chain.length == 2) func = window[ chain[0] ][ chain[1] ];
				else {
					func = window;
					$.each(chain, function(){ func = func[ this ] })
				}
				break;
			// Pre-defined
			default: func = window[ value ]; break;
		}

		return isTypeOf(func, 'Function') ? func : strict ? false : function(){};
	}
	
})(jQuery);
