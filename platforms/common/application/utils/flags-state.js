"use strict";

var prime         = require('prime'),
    map           = require('prime/map'),
    Emitter       = require('prime/emitter'),
    modal         = require('../ui').modal,

    getAjaxURL    = require('./get-ajax-url').global,
    getAjaxSuffix = require('./get-ajax-suffix');

var FlagsState = new prime({

    inherits: Emitter,

    constructor: function() {
        this.flags = map();
    },

    set: function(key, value) {
        return this.flags.set(key, value).get(key);
    },

    get: function(key, def) {
        var value = this.flags.get(key);
        return value ? value : this.set(key, def);
    },

    keys: function() {
        return this.flags.keys();
    },

    values: function() {
        return this.flags.values();
    },

    warning: function(callback) {
        modal.open({
            content: 'Loading...',
            remote: getAjaxURL('unsaved') + getAjaxSuffix(),
            remoteLoaded: function(response, modal){
                var content = modal.elements.content
                if (!callback) { return; }

                callback.call(this, response, content, modal);
            }
        });
    }

});

module.exports = new FlagsState();