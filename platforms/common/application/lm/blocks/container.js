"use strict";
var prime      = require('prime'),
    Base       = require('./base'),
    zen        = require('elements/zen'),
    $          = require('elements'),
    getAjaxURL = require('../../utils/get-ajax-url').config,
    translate  = require('../../utils/translate');

var Container = new prime({
    inherits: Base,
    options: {
        type: 'container'
    },

    constructor: function(options) {
        Base.call(this, options);
        this.on('changed', this.hasChanged);
    },

    layout: function() {
        return '<div class="g-lm-container" data-lm-id="' + this.getId() + '" data-lm-blocktype="container"></div>';
    },

    onRendered: function(element, parent) {
        if (!parent) {
            this.addSettings(element);
        }
    },

    hasChanged: function(state, child) {
        var icon = this.block.find('span.title > i:first-child');

        // if the the event is triggered from a grid we need to be cautious not to override the proper state
        if (icon && child && !child.changeState) { return; }

        this.block[state ? 'addClass' : 'removeClass']('block-has-changes');

        if (!state && icon) { icon.remove(); }
        if (state && !icon) {
            var title = this.block.find('span.title');
            if (title) { zen('i.far.fa-circle.changes-indicator').top(title); }
        }
    },

    addSettings: function(container) {
        var settings_uri = getAjaxURL(this.getPageId() + '/layout/' + this.getType() + '/' + this.getId()),
            wrapper      = zen('div.container-wrapper.clearfix').top(container.block),
            title        = zen('div.container-title').bottom(wrapper),
            actions      = zen('div.container-actions').bottom(wrapper);

        title.html('<span class="title">' + this.getType() + '</span>');
        actions.html('<span data-tip="' + translate('GANTRY5_PLATFORM_JS_LM_SETTINGS', 'Container') + '" data-tip-place="top-left"><i aria-label="' + translate('GANTRY5_PLATFORM_JS_LM_CONFIGURE_SETTINGS', 'Container') + '" class="fa fa-cog" aria-hidden="true" data-lm-settings="' + settings_uri + '"></i></span>');
    }
});

module.exports = Container;
