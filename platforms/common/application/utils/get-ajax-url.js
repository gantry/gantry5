"use strict";
var getAjaxURL = function(view, search) {
    if (!search) { search = '%ajax%'; }
    var re = new RegExp(search, 'g');

    return GANTRY_AJAX_URL.replace(re, view);
};

module.exports = getAjaxURL;