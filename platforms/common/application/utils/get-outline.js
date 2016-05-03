"use strict";
var $    = require('elements'),
    trim = require('mout/string/trim');

var getOutlineNameById = function(outline) {
    return trim($('#configuration-selector').selectizeInstance.Options[outline].text);
};

var getCurrentOutline = function() {
    return trim($('#configuration-selector').selectizeInstance.getValue());
};

module.exports = { getOutlineNameById: getOutlineNameById, getCurrentOutline: getCurrentOutline };
