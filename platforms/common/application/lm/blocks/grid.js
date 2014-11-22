"use strict";
var prime = require('prime'),
    Base  = require('./base');

var Grid = new prime({
    inherits: Base,
    options: {
        type: 'grid'
    },

    constructor: function(options) {
        Base.call(this, options);
    },

    layout: function() {
        return '<div class="grid" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="grid"></div>';
    }
});

module.exports = Grid;
