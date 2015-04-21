"use strict";
var $          = require('elements'),
    map        = require('mout/array/map'),
    slick      = require('slick');

var walk = function(combinator, method) {

    return function(expression) {
        var parts = slick.parse(expression || "*");

        expression = map(parts, function(part) {
            return combinator + " " + part;
        }).join(', ');

        return this[method](expression);
    };

};


$.implement({
    sibling: walk('++', 'find'),
    siblings: walk('~~', 'search')
});


module.exports = $;
