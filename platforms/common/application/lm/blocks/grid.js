"use strict";
var prime      = require('prime'),
    Base       = require('./base'),
    zen        = require('elements/zen'),
    $          = require('elements'),
    getAjaxURL = require('../../utils/get-ajax-url').config,
    translate  = require('../../utils/translate');

var Grid = new prime({
    inherits: Base,
    options: {
        type: 'grid'
    },

    constructor: function(options) {
        Base.call(this, options);

        this.on('changed', this.hasChanged);
    },

    layout: function() {
        return '<div class="g-grid nowrap" data-lm-id="' + this.getId() + '" ' + this.dropzone() + '  data-lm-samewidth data-lm-blocktype="grid"></div>';
    },

    onRendered: function(element) {
        var parent = this.block.parent();
        if (parent && parent.data('lm-blocktype') == 'atoms') {
            this.block.removeClass('nowrap');
        }

        if (parent && parent.data('lm-root') || (parent.data('lm-blocktype') == 'container' && parent.parent().data('lm-root'))) {
            this.removeDropzone();
        }

        this.addSettings(element);
    },

    hasChanged: function(state) {
        // Grids don't have room for an indicator so we forward it to the parent Section
        var parent = this.block.parent('[data-lm-blocktype="section"]'),
            id = parent ? parent.data('lm-id') : false;

        this.changeState = state;

        if (!parent || !id) { return; }

        if (this.options.builder) { this.options.builder.get(id).emit('changed', state, this); }
    },

    addSettings: function() {
        var parent = this.block.parent();

        if (parent.hasClass('g-lm-container')) {
            return;
        }

        var settings_uri = getAjaxURL(this.getPageId() + '/layout/' + this.getType() + '/' + this.getId()),
            actions = zen('div.g-grid-settings').top(this.block);

        actions.html('<span data-tip="' + translate('GANTRY5_PLATFORM_JS_LM_SETTINGS', 'Grid') + '" data-tip-place="top-right"><i aria-label="' + translate('GANTRY5_PLATFORM_JS_LM_CONFIGURE_SETTINGS', 'Grid') + '" class="fa fa-cog" aria-hidden="true" data-lm-settings="' + settings_uri + '"></i></span>');
    }
});

module.exports = Grid;
