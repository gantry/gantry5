"use strict";

var ready    = require('elements/domready'),
    tooltips = require('ext/tooltips');

tooltips.defaults = {
    baseClass: 'g-tips',
    typeClass: null,
    effectClass: 'g-fade',
    inClass: 'g-tip-in',
    place: 'top',
    spacing: 10,
    offset: -3,
    auto: 1
};

var Instance = null;

ready(function() {
    Instance = new tooltips(document, {
        tooltip: tooltips.defaults,
        key: 'tip',
        showOn: 'mouseenter',
        hideOn: 'mouseleave',
        observe: 1
    });

    window.G5.tips = Instance;
});

module.exports = Instance;
