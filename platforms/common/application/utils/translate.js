"use strict";

var replace = require('mout/string/replace');

module.exports = function(key, replacement) {
    return replace(global.G5T(key), '%s', replacement || '');
};