"use strict";
var prime     = require('prime'),
    Options   = require('prime-util/prime/options'),
    Bound     = require('prime-util/prime/bound'),
    Emitter   = require('prime/emitter'),
    zen       = require('elements/zen'),
    trim      = require('mout/string/trim'),
    $         = require('elements'),
    ID        = require('../id'),

    size      = require('mout/object/size'),
    get       = require('mout/object/get'),
    has       = require('mout/object/has'),
    set       = require('mout/object/set'),
    translate = require('../../utils/translate'),
    getCurrentOutline  = require('../../utils/get-outline').getCurrentOutline;

require('elements/traversal');

var Base = new prime({
    mixin: [Bound, Options],
    inherits: Emitter,
    options: {
        subtype: false,
        attributes: {},
        inherit: {}
    },
    constructor: function(options) {
        this.setOptions(options);

        this.fresh = !this.options.id;
        this.id = this.options.id || ID(this.options);
        this.attributes = this.options.attributes || {};
        this.inherit = this.options.inherit || {};

        this.block = zen('div').html(this.layout()).firstChild();

        this.on('rendered', this.bound('onRendered'));

        return this;
    },

    guid: function() {
        return guid();
    },

    getId: function() {
        return this.id || (this.id = ID(this.options));
    },

    getType: function() {
        return this.options.type || '';
    },

    getSubType: function() {
        return this.options.subtype || '';
    },

    getTitle: function() {
        return trim(this.options.title || 'Untitled');
    },

    setTitle: function(title) {
        this.options.title = trim(title || 'Untitled');
        return this;
    },

    getKey: function() {
        return '';
    },

    getPageId: function() {
        var root = $('[data-lm-root]');
        if (!root) return 'data-root-not-found';

        return root.data('lm-page');
    },

    getAttribute: function(key) {
        return get(this.attributes, key);
    },

    getAttributes: function() {
        return this.attributes || {};
    },

    getInheritance: function() {
        return this.inherit || {};
    },

    updateTitle: function() {
        return this;
    },

    setAttribute: function(key, value) {
        set(this.attributes, key, value);
        return this;
    },

    setAttributes: function(attributes) {
        this.attributes = attributes;

        return this;
    },

    setInheritance: function(inheritance) {
        this.inherit = inheritance;

        return this;
    },

    hasAttribute: function(key) {
        return has(this.attributes, key);
    },

    enableInheritance: function() {},

    disableInheritance: function() {},

    refreshInheritance: function() {},

    hasInheritance: function() {
        return size(this.inherit) && this.inherit.outline != getCurrentOutline();
    },

    disable: function() {
        this.block.title(translate('GANTRY5_PLATFORM_JS_LM_DISABLED_PARTICLE', 'particle'));
        this.block.addClass('particle-disabled');
    },

    enable: function() {
        this.block.removeAttribute('title');
        this.block.removeClass('particle-disabled');
    },

    insert: function(target, location) {
        this.block[location || 'after'](target);
        return this;
    },

    adopt: function(element) {
        element.insert(this.block);
        return this;
    },

    isNew: function(fresh) {
        if (typeof fresh !== 'undefined') {
            this.fresh = !!fresh;
        }
        return this.fresh;
    },

    dropzone: function() {
        return 'data-lm-dropzone';
    },

    addDropzone: function() {
        this.block.data('lm-dropzone', true);
    },

    removeDropzone: function() {
        this.block.data('lm-dropzone', null);
    },

    layout: function() {},

    onRendered: function() {},

    setLayout: function(layout) {
        this.block = layout;
        return this;
    },

    getLimits: function() {
        return false;
    }
});

module.exports = Base;
