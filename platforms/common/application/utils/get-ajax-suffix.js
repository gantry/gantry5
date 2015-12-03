"use strict";

var GANTRY_AJAX_SUFFIX = window.GANTRY_AJAX_SUFFIX || undefined;
var getAjaxSuffix = function() {
    return typeof GANTRY_AJAX_SUFFIX == 'undefined' ? '' : GANTRY_AJAX_SUFFIX;
};

module.exports = getAjaxSuffix;