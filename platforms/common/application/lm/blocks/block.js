"use strict";
var prime     = require('prime'),
    Base      = require('./base'),
    $         = require('../../utils/elements.utils'),
    zen       = require('elements/zen'),
    precision = require('mout/number/enforcePrecision'),
    bind      = require('mout/function/bind');

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
        if (options.attributes && options.attributes.size) this.setAttribute('size', precision(options.attributes.size, 1));

        this.on('changed', this.hasChanged);
    },

    getSize: function() {
        return precision(this.getAttribute('size'), 1);
    },

    setSize: function(size, store) {
        size = typeof size === 'undefined' ? this.getSize() : Math.max(0, Math.min(100, parseFloat(size)));
        size = precision(size, 1);
        if (store) {
            this.setAttribute('size', size);
        }

        $(this.block).style({
            flex: '0 1 ' + size + '%',
            '-webkit-flex': '0 1 ' + size + '%',
            '-ms-flex': '0 1 ' + size + '%'
        });

        this.emit('resized', size, this);
    },

    setAnimatedSize: function(size, store) {
        size = typeof size === 'undefined' ? this.getSize() : Math.max(0, Math.min(100, parseFloat(size)));
        size = precision(size, 1);
        if (store) {
            this.setAttribute('size', size);
        }
        $(this.block).animate({
            flex: '0 1 ' + size + '%',
            '-webkit-flex': '0 1 ' + size + '%',
            '-ms-flex': '0 1 ' + size + '%'
        }, bind(function() {
            this.block.attribute('style', null);
            this.setSize(size);
        }, this));

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

        var grandpa = parent.block.parent();
        if (grandpa.data('lm-root') || (grandpa.data('lm-blocktype') == 'container' && (grandpa.parent().data('lm-root') || grandpa.parent().data('lm-blocktype') == 'wrapper'))) {
            zen('span.particle-size').text(this.getSize() + '%').top(element.block);
            element.on('resized', this.bound('onResize'));
        }
    },

    onResize: function(resize) {
        this.setLabelSize(resize);
    },

    hasChanged: function(state) {
        var icon,
            child = this.block.find('> [data-lm-id]:not([data-lm-blocktype="section"]):not([data-lm-blocktype="container"])');

        this.changeState = state;

        if (!child) {
            child = this.block.find('> .particle-size') || this.block.parent('[data-lm-blocktype="block"]').find('> .particle-size');
            icon = child.find('i:first-child');

            if (!state && icon) { icon.remove(); }
            if (state && !icon) { zen('i.far.fa-circle.changes-indicator').top(child); }

            return;
        }

        var mapped = this.options.builder.get(child.data('lm-id'));
        if (mapped) { mapped.emit('changed', state, this); }
    }
});

module.exports = Block;
