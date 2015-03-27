"use strict";
var prime      = require('prime'),
    Base       = require('./base'),
    $          = require('elements');

var Container = new prime({
    inherits: Base,
    options: {
        type: 'container'
    },

    constructor: function(options) {
        Base.call(this, options);
    },

    layout: function() {
        return '<div class="g-lm-container" data-lm-id="' + this.getId() + '" data-lm-blocktype="container"></div>';
    }
});

module.exports = Container;
