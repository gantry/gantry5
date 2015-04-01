"use strict";

var ready = require('domready'),
    prime = require('prime'),
    $     = require('elements'),
    zen   = require('elements/zen');

var Menu = new prime({
    options: {
        submenu: {
            dir: 'right',
            selector: '*'
        },
        tolerance: 75,
        mode: null
    },

    constructor: function(mode) {
        this.options.mode = mode;
        console.log(this.options.mode);
    }
});

// Trick to detect if the user is able to move the cursor or is just touch
// will be used to initialize the menu with click/touch only events vs hovers
var DetectMouse = function(callback) {
    var body = $('body'), type;
    var detectMouse = function(e) {
        type = (e.type === 'mousemove') ? 'mouse' : (e.type === 'touchstart' ? 'touch' : false);

        body.off('mousemove', detectMouse);
        body.off('touchstart', detectMouse);

        callback.call(undefined, type);
    };

    body.on('mousemove', detectMouse);
    body.on('touchstart', detectMouse);
};

// Initialize the menu only when we know what's the mode (mouse/touch)
ready(function() {
    DetectMouse(function(mode) {
        module.exports.menu = new Menu(mode);
    });
});

module.exports = {};