"use strict";
// deprecated (5.2.0)
var prime      = require('prime'),
    $          = require('elements'),
    Base       = require('./base'),
    zen        = require('elements/zen'),
    getAjaxURL = require('../../utils/get-ajax-url').config;

var Atom = new prime({
    inherits: Base,
    options: {
        type: 'atom'
    },

    constructor: function(options) {
        Base.call(this, options);

        this.on('changed', this.hasChanged);
    },

    updateTitle: function(title) {
        this.block.find('.title').text(title);
        this.setTitle(title);
        return this;
    },

    layout: function() {
        var settings_uri = getAjaxURL(this.getPageId() + '/layout/' + this.getType() + '/' + this.getId()),
            subtype      = this.getSubType() ? 'data-lm-blocksubtype="' + this.getSubType() + '"' : '';
        return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '" ' + subtype + '><span><span class="title">' + this.getTitle() + '</span><span class="font-small">' + (this.getSubType() || this.getKey() || this.getType()) + '</span></span><div class="float-right"><i aria-label="Configure Atom Settings" class="fa fa-cog" aria-hidden="true" data-lm-nodrag data-lm-nodrag data-lm-settings="' + settings_uri + '"></i></div></div>';
    },

    hasChanged: function(state, parent) {
        var icon = this.block.find('span > i.changes-indicator:first-child');

        // if the particle has changes but the parent block doesn't, we want to keep the indicator showing
        if (icon && parent && !parent.changeState) { return; }

        this.block[state ? 'addClass' : 'removeClass']('block-has-changes');

        if (!state && icon) { icon.remove(); }
        if (state && !icon) { zen('i.fa.fa-circle-o.changes-indicator').before(this.block.find('.icon')); }
    },

    onRendered: function(element, parent) {
        var globally_disabled = $('[data-lm-disabled][data-lm-subtype="' + this.getSubType() + '"]');

        if (globally_disabled || this.getAttribute('enabled') === 0) { this.disable(); }
    }
});

module.exports = Atom;
