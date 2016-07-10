"use strict";
var prime              = require('prime'),
    Section            = require('./section'),

    getAjaxURL         = require('../../utils/get-ajax-url').config,
    getOutlineNameById = require('../../utils/get-outline').getOutlineNameById,
    translate          = require('../../utils/translate');

var Offcanvas = new prime({
    inherits: Section,
    options: {
        type: 'offcanvas',
        attributes: {
            name: "Offcanvas Section"
        }
    },

    layout: function() {
        var settings_uri = getAjaxURL(this.getPageId() + '/layout/' + this.getType() + '/' + this.getId()),
            inheritance  = '',
            klass        = '';

        if (this.hasInheritance()) {
            var outline = getOutlineNameById(this.inherit.outline);
            inheritance = '<div class="g-inherit g-section-inherit"><div class="g-inherit-content">' + translate('GANTRY5_PLATFORM_INHERITING_FROM_X', '<strong>' + outline + '</strong>') + '</div></div>';
            klass = ' g-inheriting g-inheriting-' + this.inherit.include.join(' g-inheriting-');
        }

        return '<div class="offcanvas-section' + klass + '" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '"><div class="section-header clearfix"><h4 class="float-left" title="' + this.getAttribute('name') + '">' + this.getAttribute('name') + '</h4><div class="section-actions float-right"><span data-tip="' + translate('GANTRY5_PLATFORM_JS_LM_ADD_ROW', 'Offcanvas') + '" data-tip-place="top-right"><i aria-label="' + translate('GANTRY5_PLATFORM_JS_LM_ADD_ROW', 'Offcanvas') + '" class="fa fa-plus"></i></span> <span class="section-settings" data-tip="' + translate('GANTRY5_PLATFORM_JS_LM_SETTINGS', 'Offcanvas') + '" data-tip-place="top-right"><i aria-label="' + translate('GANTRY5_PLATFORM_JS_LM_CONFIGURE_SETTINGS', 'Offcanvas') + '" class="fa fa-cog" data-lm-settings="' + settings_uri + '"></i></span></div></div>' + inheritance + '</div>';
    },

    getId: function() {
        return this.id || (this.id = this.options.type);
    }
});

module.exports = Offcanvas;
