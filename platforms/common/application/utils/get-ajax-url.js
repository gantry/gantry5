"use strict";
var unescapeHtml  = require('mout/string/unescapeHtml'),
    getAjaxSuffix = require('./get-ajax-suffix'),
    endsWith      = require('mout/string/endsWith'),
    getQuery      = require('mout/queryString/getQuery'),
    getParam      = require('mout/queryString/getParam'),
    setParam      = require('mout/queryString/setParam');

var getAjaxURL = function(view, search) {
    var GANTRY_AJAX_URL = window.GANTRY_AJAX_URL || '';
    if (!search) { search = '%ajax%'; }
    var re  = new RegExp(search, 'g'),
        url = typeof GANTRY_AJAX_URL == 'undefined' ? '' : GANTRY_AJAX_URL;

    return unescapeHtml(url.replace(re, view));
};

var getConfAjaxURL = function(view, search) {
    var GANTRY_AJAX_CONF_URL = window.GANTRY_AJAX_CONF_URL || '';
    if (!search) { search = '%ajax%'; }
    var re  = new RegExp(search, 'g'),
        url = typeof GANTRY_AJAX_CONF_URL == 'undefined' ? '' : GANTRY_AJAX_CONF_URL;

    return unescapeHtml(url.replace(re, view));
};

var parseAjaxURI = function(uri) {
    var GANTRY_PLATFORM = window.GANTRY_PLATFORM || '',
        platform        = typeof GANTRY_PLATFORM == 'undefined' ? '' : GANTRY_PLATFORM;

    switch (platform) {
        case 'wordpress':
            uri = uri.replace(/themes\.php/ig, 'admin-ajax.php');
            break;
        case 'grav':
            // converts foo/bar?nonce=1234.json to foo/bar.json?nonce=1234
            var suffix = getAjaxSuffix();
            if (endsWith(uri, suffix)) {
                var query  = '' + getQuery(uri),
                    nonce  = '' + getParam(uri, 'nonce');

                uri = uri.replace(query, suffix) + query.replace(nonce, (nonce.replace(suffix, '')));
            }
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
