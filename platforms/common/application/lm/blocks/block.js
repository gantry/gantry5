"use strict";
var prime = require('prime'),
    Base  = require('./base'),
    $     = require('../../utils/elements.moofx'),
    zen   = require('elements/zen');

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

    layout: function() {
        return '<div class="block" data-lm-id="' + this.getId() + '"' + this.dropzone() + ' data-lm-blocktype="block"></div>';
    },

    onRendered: function(element, parent){
        if (element.block.find('> [data-lm-blocktype="section"]')){
            this.removeDropzone();
        }

        if (parent.block.parent().data('lm-root')) {
            zen('span.particle-size').text(this.getSize() + '%').top(element.block);
        }
    }
});

module.exports = Block;
