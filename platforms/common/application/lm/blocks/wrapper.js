"use strict";
var prime      = require('prime'),
    Section    = require('./section'),

    getAjaxURL = require('../../utils/get-ajax-url').config;

var Wrapper = new prime({
    inherits: Section,
    options: {
        type: 'wrapper',
        attributes: {
            name: "Wrapper"
        }
    },

    layout: function() {
        var settings_uri = getAjaxURL(this.getPageId() + '/layout/' + this.getType() + '/' + this.getId());
        return '<div class="wrapper-section" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '" data-lm-blocksubtype="' + this.getSubType() + '"></div>';
    },

    hasChanged: function() {},

    getSize: function() {
        return false;
    },

    getId: function() {
        return this.id || (this.id = this.options.type);
    }
});

module.exports = Wrapper;
