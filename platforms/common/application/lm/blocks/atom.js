"use strict";
var prime = require('prime'),
    Base  = require('./base'),
    zen   = require('elements/zen'),
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
            subtype = this.getSubType() ? 'data-lm-blocksubtype="' + this.getSubType() + '"' : '';
        return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '" ' + subtype + '><span><span class="title">' + this.getTitle() + '</span><span class="font-small">' + (this.getSubType() || this.getKey() || this.getType()) + '</span></span><div class="float-right"><i class="fa fa-cog" data-lm-nodrag data-lm-nodrag data-lm-settings="' + settings_uri + '"></i></div></div>';
    },

    hasChanged: function(state) {
        var icon = this.block.find('.title > i:first-child');

        this.block[state ? 'addClass' : 'removeClass']('block-has-changes');

        if (!state && icon) { icon.remove(); }
        if (state && !icon) { zen('i.fa.fa-circle-o.changes-indicator').top(this.block.find('.title')); }
    }
});

module.exports = Atom;
