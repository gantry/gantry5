"use strict";

var ready     = require('domready'),
    menu      = require('./menu'),
    offcanvas = require('./offcanvas'),

    instances = {};

ready(function() {
    instances = {
        offcanvas: new offcanvas(),
        menu: menu
    };
});

module.exports = window.G5 = instances;