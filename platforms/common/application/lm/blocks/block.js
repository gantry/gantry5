"use strict";
var prime     = require('prime'),
    Base      = require('./base'),
    $         = require('../../utils/elements.moofx'),
    zen       = require('elements/zen'),
    precision = require('mout/number/enforcePrecision');

var Block = new prime({
    inherits: Base,
    options: {
        type: 'block',
        attributes: {
            size: 100
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

        this.emit('resized', size, this);
    },

    setAnimatedSize: function(size, store) {
        size = typeof size === 'undefined' ? this.getSize() : Math.max(0, Math.min(100, parseFloat(size)));
        if (store) {
            this.setAttribute('size', size);
        }
        $(this.block).animate({ flex: '0 1 ' + size + '%' });
        this.emit('resized', size, this);
    },

    setLabelSize: function(size) {
        var label = this.block.find('> .particle-size');
        if (!label) { return false; }

        label.text(precision(size, 1) + '%');
    },

    layout: function() {
        return '<div class="g-block" data-lm-id="' + this.getId() + '"' + this.dropzone() + ' data-lm-blocktype="block"></div>';
    },

    onRendered: function(element, parent) {
        if (element.block.find('> [data-lm-blocktype="section"]')) {
            this.removeDropzone();
        }

        if (!parent) { return; }

        if (parent.block.parent().data('lm-root')) {
            zen('span.particle-size').text(this.getSize() + '%').top(element.block);
            element.on('resized', this.bound('onResize'));
        }
    },

    onResize: function(resize) {
        this.setLabelSize(resize);
    }
});

module.exports = Block;
