'use strict';

var keys     = require('mout/object/keys'),
    contains = require('mout/array/contains'),
    rand     = require('mout/random/randInt');

var ID = function(options) {
    var map     = options.builder ? keys(options.builder.map) : {},
        type    = options.type,
        subtype = options.subtype,

        result  = [], key, id;

    if (type != 'particle') result.push(type);
    if (subtype)  result.push(subtype);

    key = result.join('-');

    while (id = rand(1000, 9999)) {
        if (!contains(map, key + '-' + id)) {
            break;
        }
    }

    return key + '-' + id;
};

module.exports = ID;