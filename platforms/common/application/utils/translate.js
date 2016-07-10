"use strict";

var replace = require('mout/string/replace'),
    G5T     = global.G5T;

module.exports = function(key, replacement) {
    return replace(G5T(key), '%s', replacement || '');
};