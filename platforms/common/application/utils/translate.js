"use strict";

var replace = require('mout/string/replace');

module.exports = function(key, replacement) {
    var G5T = global.G5T || function(key) { return key; };
    return replace(G5T(key), '%s', replacement || '');
};