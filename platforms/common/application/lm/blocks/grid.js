"use strict";
var prime      = require('prime'),
    Base       = require('./base'),
    $          = require('elements'),
    getAjaxURL = require('../../utils/get-ajax-url').config;

var Grid = new prime({
    inherits: Base,
    options: {
        type: 'grid'
    },

    constructor: function(options) {
        Base.call(this, options);
    },

    layout: function() {
        return '<div class="g-grid nowrap" data-lm-id="' + this.getId() + '" ' + this.dropzone() + '  data-lm-samewidth data-lm-blocktype="grid"></div>';
    },

    onRendered: function() {
        var parent = this.block.parent();
        if (parent && parent.data('lm-root')) {
            this.removeDropzone();
        }
    }
});

module.exports = Grid;
