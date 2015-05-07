"use strict";
var unescapeHtml = require('mout/string/unescapeHtml');

var getAjaxURL = function(view, search) {
    if (!search) { search = '%ajax%'; }
    var re = new RegExp(search, 'g'),
        url = typeof GANTRY_AJAX_URL == 'undefined' ? '' : GANTRY_AJAX_URL;

    return unescapeHtml(url.replace(re, view));
};

var getConfAjaxURL = function(view, search) {
    if (!search) { search = '%ajax%'; }
    var re = new RegExp(search, 'g'),
        url = typeof GANTRY_AJAX_CONF_URL == 'undefined' ? '' : GANTRY_AJAX_CONF_URL;

    return unescapeHtml(url.replace(re, view));
};

module.exports = {
    global: getAjaxURL,
    config: getConfAjaxURL
};