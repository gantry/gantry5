"use strict";

var zen = require('elements/zen');

var cached            = null,
    getScrollbarWidth = function() {
        if (cached !== null) { return cached; }

        var size, dummy = zen('div').bottom('#g5-container');
        dummy.style({
            width: 100,
            height: 100,
            overflow: 'scroll',
            position: 'absolute',
            zIndex: -9999
        });

        size = dummy[0].offsetWidth - dummy[0].clientWidth;
        dummy.remove();

        cached = size;
        return size;
    };

module.exports = getScrollbarWidth;
