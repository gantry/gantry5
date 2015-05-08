"use strict";
var getAjaxSuffix = function() {
    return typeof GANTRY_AJAX_SUFFIX == 'undefined' ? '' : GANTRY_AJAX_SUFFIX;
};

module.exports = getAjaxSuffix;