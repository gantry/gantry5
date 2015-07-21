"use strict";

var ready = require('domready'),
    $     = require('../utils/dollar-extras');

var timeOut,
    scrollToTop = function() {
        if (document.body.scrollTop != 0 || document.documentElement.scrollTop != 0) {
            window.scrollBy(0, -50);
            timeOut = setTimeout(scrollToTop, 10);
        } else {
            clearTimeout(timeOut);
        }
    };

ready(function() {
    var totop = $('#g-totop');
    if (!totop) { return; }

    totop.on('click', function(e) {
        e.preventDefault();
        scrollToTop();
    });
});

module.exports = {};
