"use strict";
var prime      = require('prime'),
    $          = require('elements'),
    Atom       = require('./atom'),
    bind       = require('mout/function/bind'),
    precision  = require('mout/number/enforcePrecision'),
    getAjaxURL = require('../../utils/get-ajax-url').config;

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
        var settings_uri = getAjaxURL(this.getPageId() + '/layout/' + this.getType() + '/' + this.getId()),
            subtype      = this.getSubType() ? 'data-lm-blocksubtype="' + this.getSubType() + '"' : '';

        return '<div class="' + this.getType()  + (this.hasInheritance() ? ' g-inheriting' : '') + '" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '" ' + subtype + '><span><span class="icon" data-tip="Inheriting from -!- TODO -!-" data-tip-offset="-10" data-tip-place="top-right"><i class="fa ' + this.getIcon() + '"></i></span><span class="title">' + this.getTitle() + '</span><span class="font-small">' + (this.getKey() || this.getSubType() || this.getType()) + '</span></span><div class="float-right"><span class="particle-size"></span> <i aria-label="Configure Particle Settings" class="fa fa-cog" data-lm-nodrag data-lm-settings="' + settings_uri + '"></i></div></div>';
    },
    
    enableInheritance: function() {
        if (this.hasInheritance()) {
            this.block.addClass('g-inheriting');
            this.block.find('.icon .fa').attribute('class', 'fa ' + this.getIcon());
        }
    },

    disableInheritance: function() {
        this.block.removeClass('g-inheriting');
        this.block.find('.icon .fa').attribute('class', 'fa ' + this.getIcon());

    },

    refreshInheritance: function() {
        this.block[this.hasInheritance() ? 'removeClass' : 'addClass']('g-inheritance');
        console.log('refreshing inheritance');
    },

    setLabelSize: function(size) {
        var label = this.block.find('.particle-size');
        if (!label) { return false; }

        label.text(precision(size, 1) + '%');
    },

    onRendered: function(element, parent) {
        var size              = parent.getSize() || 100,
            globally_disabled = $('[data-lm-disabled][data-lm-subtype="' + this.getSubType() + '"]');

        if (globally_disabled || this.getAttribute('enabled') === 0) { this.disable(); }

        this.setLabelSize(size);
        parent.on('resized', this.bound('onParentResize'));
    },

    getParent: function() {
        var parent = this.block.parent('[data-lm-id]');

        return this.options.builder.get(parent.data('lm-id'));
    },

    onParentResize: function(resize) {
        this.setLabelSize(resize);
    },

    getIcon: function() {
        if (this.hasInheritance()) {
            return 'fa-lock';
        }

        var type = this.getType(),
            subtype = this.getSubType(),
            template = $('.particles-container [data-lm-blocktype="' + type + '"][data-lm-subtype="' + subtype + '"]');

        return template ? template.data('lm-icon') : 'fa-cube';
    },

    getLimits: function(parent) {
        if (!parent) { return false; }

        var sibling = parent.block.nextSibling() || parent.block.previousSibling() || false;

        if (!sibling) { return [100, 100]; }

        var siblingBlock = this.options.builder.get(sibling.data('lm-id')),
            sizes = {
                current: this.getParent().getSize(),
                sibling: siblingBlock.getSize()
            };

        return [5, (sizes.current + sizes.sibling) - 5];
    }
});

module.exports = Particle;
