var prime   = require('prime'),
    Options = require('prime-util/prime/options'),
    guid    = require('mout/random/guid'),
    zen     = require('elements/zen'),
    $       = require('elements'),

    get     = require('mout/object/get'),
    has     = require('mout/object/has'),
    set     = require('mout/object/set');

require('elements/traversal');

var Base = new prime({
    mixin: Options,
    options: {
        attributes: {}
    },
    constructor: function(options){
        this.setOptions(options);
        this.fresh      = !this.options.id;
        this.id         = this.options.id || this.guid();
        this.attributes = this.options.attributes || {};

        this.block = zen('div').html(this.layout()).firstChild();

        return this;
    },

    guid: function(){
        return guid();
    },

    getId: function(){
        return this.id || (this.id = this.guid());
    },

    getType: function(){
        return this.options.type || '';
    },

    getTitle: function(){
        return '';
    },

    getAttribute: function(key){
        return get(this.attributes, key);
    },

    getAttributes: function(){
        return this.attributes || {};
    },

    setAttribute: function(key, value){
        set(this.attributes, key, value);
        return this;
    },

    hasAttribute: function(key){
        return has(this.attributes, key);
    },

    insert: function(target, location){
        this.block[location || 'after'](target);
        return this;
    },

    adopt: function(element){
        element.insert(this.block);
        return this;
    },

    isNew: function(fresh){
        if (typeof fresh !== 'undefined') this.fresh = !!fresh;
        return this.fresh;
    },

    dropZone: function(){
        var root = $('[data-lm-root]'),
            mode = root.data('lm-root'),
            type = this.getType();

        if (mode == 'page' && type != 'section' && type != 'grid' && type != 'block') return '';
        return 'data-lm-dropzone';
    },

    layout: function(){},

    setLayout: function(layout){
        this.block = layout;
        return this;
    }
});

module.exports = Base;
