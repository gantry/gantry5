"use strict";
var prime     = require('prime'),
    Base      = require('./base'),
    precision = require('mout/number/enforcePrecision'),
    $         = require('../../utils/elements.moofx');

var Block = new prime({
    inherits: Base,
    options: {
        type: 'block',
        attributes: {
            size: 50
        }
    },

    constructor: function(options) {
        Base.call(this, options);
    },

    getSize: function() {
        return this.getAttribute('size');
    },

    setSize: function(size, store) {
        size = typeof size === 'undefined' ? this.getSize() : Math.max(0, Math.min(100, parseFloat(size)));
        if (store) {
            this.setAttribute('size', size);
        }

        $(this.block).style({ 'flex': '0 1 ' + size + '%' });
    },

    setAnimatedSize: function(size, store) {
        size = typeof size === 'undefined' ? this.getSize() : size;
        if (store) {
            this.setAttribute('size', size);
        }
        $(this.block).animate({ flex: '0 1 ' + precision(size, 1) + '%' });
    },

    layout: function() {
        return '<div class="block" data-lm-id="' + this.getId() + '" data-lm-blocktype="block"></div>';
    }
});

module.exports = Block;
