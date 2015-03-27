"use strict";

var $        = require('elements'),
    domready = require('elements/domready');

// Localise Globals
var History = {};

// Check Existence
if (typeof History.Adapter !== 'undefined') {
    throw new Error('History.js Adapter has already been loaded...');
}

// Add the Adapter
History.Adapter = {
    /**
     * History.Adapter.bind(el,event,callback)
     * @param {Element|string} el
     * @param {string} event - custom and standard events
     * @param {function} callback
     * @return {void}
     */
    bind: function(el, event, callback) {
        $(el).on(event, callback);
    },

    /**
     * History.Adapter.trigger(el,event)
     * @param {Element|string} el
     * @param {string} event - custom and standard events
     * @param {Object=} extra - a object of extra event data (optional)
     * @return void
     */
    trigger: function(el, event, extra) {
        $(el).emit(event, extra);
    },

    /**
     * History.Adapter.extractEventData(key,event,extra)
     * @param {string} key - key for the event data to extract
     * @param {string} event - custom and standard events
     */
    extractEventData: function(key, event) {
        // MooTools Native then MooTools Custom
        return (event && event.event && event.event[key]) || (event && event[key]) || undefined;
    },

    /**
     * History.Adapter.onDomLoad(callback)
     * @param {function} callback
     * @return {void}
     */
    onDomLoad: function(callback) {
        domready(callback);
    }
};

// Try and Initialise History
if (typeof History.init !== 'undefined') {
    History.init();
}

module.exports = History;