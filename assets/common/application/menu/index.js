"use strict";

var ready   = require('domready'),
    prime   = require('prime'),
    $       = require('elements'),
    zen     = require('elements/zen'),
    bind    = require('mout/function/bind'),
    timeout = require('mout/function/timeout'),
    Bound   = require('prime-util/prime/bound'),
    Options = require('prime-util/prime/options');


var hasTouchEvents = ('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch;

var Menu = new prime({

    mixin: [Bound, Options],

    options: {
        selectors: {
            mainContainer: '.g-main-nav',
            mobileContainer: '#g-mobilemenu-container',
            rootItems: '> ul > li',
            parent: '.g-parent',
            item: '.g-menu-item',
            dropdown: '.g-dropdown',
            touchIndicator: '.g-menu-parent-indicator'
        },

        states: {
            active: 'g-active',
            inactive: 'g-inactive',
            selected: 'g-selected',
            touchEvents: 'g-menu-hastouch'
        }
    },

    constructor: function(options) {
        this.setOptions(options);

        this.selectors = this.options.selectors;
        this.states = this.options.states;
        this.active = null;
        this.location = [];

        if (hasTouchEvents) {
            $(this.selectors.mainContainer).addClass(this.states.touchEvents);
        }

        this.attach();
    },

    attach: function() {
        var selectors = this.selectors,
            main = $(selectors.mainContainer + ' ' + selectors.item);

        main.on('mouseenter', this.bound('mouseenter'));
        main.on('mouseleave', this.bound('mouseleave'));
    },

    detach: function() {
        var selectors = this.selectors,
            main = $(selectors.mainContainer + ' ' + selectors.item);

        main.off('mouseenter', this.bound('mouseenter'));
        main.off('mouseleave', this.bound('mouseleave'));
    },

    mouseenter: function(event) {
        this.openDropdown(event.target);
    },

    mouseleave: function(event) {
        this.closeDropdown(event.target);
    },

    openDropdown: function(element) {
        var dropdown = $(element.target || element).find(this.selectors.dropdown);

        if (dropdown) {
            dropdown.removeClass(this.states.inactive).addClass(this.states.active);
        }
    },

    closeDropdown: function(element) {
        var dropdown = $(element.target || element).find(this.selectors.dropdown);

        if (dropdown) {
            dropdown.removeClass(this.states.active).addClass(this.states.inactive);
        }
    },

    _debug: function() {}
});

module.exports = Menu;