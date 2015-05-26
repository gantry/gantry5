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

var parseAjaxURI = function(uri) {
    var platform = typeof GANTRY_PLATFORM == 'undefined' ? '' : GANTRY_PLATFORM
    switch(platform){
        case 'wordpress':
            uri = uri.replace(/themes\.php/ig, 'admin-ajax.php');
            break;
        default:
    }

    return uri;
};

module.exports = {
    global: getAjaxURL,
    config: getConfAjaxURL,
    parse: parseAjaxURI
};