"use strict";
var prime   = require('prime'),
    Section = require('./section'),

    getAjaxURL = require('../../utils/get-ajax-url').config,
    getOutlineNameById = require('../../utils/get-outline').getOutlineNameById;

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
            inheritance = '';

        if (this.hasInheritance()) {
            var outline = getOutlineNameById(this.inherit.outline);
            inheritance = '<div class="g-inherit g-section-inherit"><div class="g-inherit-content">Inheriting from <strong>' + outline + '</strong></div></div>';
        }
        
        return '<div class="offcanvas-section' + (this.hasInheritance() ? ' g-inheriting' : '') + '" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '"><div class="section-header clearfix"><h4 class="float-left">' + (this.getAttribute('name')) + '</h4><div class="section-actions float-right"><span data-tip="Adds a new row in the offcanvas" data-tip-place="top-right"><i aria-label="Add a new row" class="fa fa-plus"></i></span> <span class="section-settings" data-tip="Offcanvas settings" data-tip-place="top-right"><i aria-label="Configure Offcanvas Settings" class="fa fa-cog" data-lm-settings="' + settings_uri + '"></i></span></div></div>' + inheritance + '</div>';
    },

    getId: function() {
        return this.id || (this.id = this.options.type);
    }
});

module.exports = Offcanvas;
