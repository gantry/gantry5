"use strict";
var prime      = require('prime'),
    Atom       = require('./atom'),
    bind       = require('mout/function/bind'),
    precision  = require('mout/number/enforcePrecision'),
    getAjaxURL = require('../../utils/get-ajax-url');

var UID = 0;

var Particle = new prime({
    inherits: Atom,
    options: {
        type: 'particle'
    },

    constructor: function(options) {
        ++UID;
        Atom.call(this, options);
    },

    layout: function() {
        var settings_uri = getAjaxURL('layout/' + this.getPageId() +  '/' + this.getType() + '/' + this.getId()),
            subtype = this.getSubType() ? 'data-lm-blocksubtype="' + this.getSubType() + '"' : '';

        return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '" ' + subtype + '><span><span class="title">' + this.getTitle() + '</span><span class="font-small">' + (this.getKey() || this.getSubType() || this.getType()) + '</span></span><div class="float-right"><span class="particle-size"></span> <i class="fa fa-cog" data-lm-nodrag data-lm-settings="' + settings_uri + '"></i></div></div>';
    },

    setLabelSize: function(size) {
        var label = this.block.find('.particle-size');
        if (!label) { return false; }

        label.text(precision(size, 1) + '%');
    },

    onRendered: function(element, parent) {
        var size = parent.getSize() || 100;

        this.setLabelSize(size);
        parent.on('resized', this.bound('onParentResize'));
    },

    onParentResize: function(resize) {
        this.setLabelSize(resize);
    }
});

module.exports = Particle;
