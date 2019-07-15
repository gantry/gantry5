"use strict";

var getAjaxSuffix = function() {
    var GANTRY_AJAX_SUFFIX = window.GANTRY_AJAX_SUFFIX || undefined;
    return typeof GANTRY_AJAX_SUFFIX == 'undefined' ? '' : GANTRY_AJAX_SUFFIX;
};

module.exports = getAjaxSuffix;
