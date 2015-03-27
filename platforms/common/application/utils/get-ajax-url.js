"use strict";
var unescapeHtml = require('mout/string/unescapeHtml');

var getAjaxURL = function(view, search) {
    if (!search) { search = '%ajax%'; }
    var re = new RegExp(search, 'g');

    return unescapeHtml(GANTRY_AJAX_URL.replace(re, view));
};

var getConfAjaxURL = function(view, search) {
    if (!search) { search = '%ajax%'; }
    var re = new RegExp(search, 'g');

    return unescapeHtml(GANTRY_AJAX_CONF_URL.replace(re, view));
};

module.exports = {
    global: getAjaxURL,
    config: getConfAjaxURL
};