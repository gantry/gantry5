"use strict";

var ready   = require('domready'),
    prime   = require('prime'),
    $       = require('../utils/dollar-extras'),
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
            topLevel: '.g-toplevel',
            rootItems: '> ul > li',
            parent: '.g-parent',
            item: '.g-menu-item',
            dropdown: '.g-dropdown',
            touchIndicator: '.g-menu-parent-indicator',
            canHover: '.g-main-nav'
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
            main = $(selectors.mainContainer + ' ' + selectors.item),
            mobileContainer = $(selectors.mobileContainer);

        main.on('mouseenter', this.bound('mouseenter'));
        main.on('mouseleave', this.bound('mouseleave'));

        if (hasTouchEvents) {
            var parents = $(selectors.mainContainer + '.' + this.states.touchEvents + ' ' + selectors.parent);
            parents.on('touchend', this.bound('touchend'));
        }

        if (mobileContainer) {
            var query = 'only all and (max-width: ' + (mobileContainer.data('g-menu-breakpoint') || '30rem') + ')',
                match = matchMedia(query);
            match.addListener(this.bound('_checkQuery'));
            this._checkQuery(match);
        }
    },

    detach: function() {
        var selectors = this.selectors,
            main = $(selectors.mainContainer + ' ' + selectors.item),
            mobileContainer = $(selectors.mobileContainer);

        main.off('mouseenter', this.bound('mouseenter'));
        main.off('mouseleave', this.bound('mouseleave'));

        if (hasTouchEvents) {
            var parents = $(selectors.mainContainer + '.' + this.states.touchEvents + ' ' + selectors.parent);
            parents.off('touchend', this.bound('touchend'));
        }

        if (mobileContainer) {
            var query = 'only all and (max-width: ' + (mobileContainer.data('g-menu-breakpoint') || '30rem') + ')',
                match = matchMedia(query);
            match.removeListener(this.bound('_checkQuery'));
        }
    },

    mouseenter: function(event) {
        var element = $(event.target);
        if (!element.parent(this.options.selectors.canHover)) { return; }
        if (element.parent(this.options.selectors.item) && !element.parent('.g-standard')) { return; }

        this.openDropdown(element);
    },

    mouseleave: function(event) {
        var element = $(event.target);
        if (!element.parent(this.options.selectors.canHover)) { return; }
        if (element.parent(this.options.selectors.item) && !element.parent('.g-standard')) { return; }

        this.closeDropdown(element);
    },

    touchend: function(event) {
        event.stopPropagation();

        var selectors = this.selectors,
            states = this.states;

        var target = $(event.target),
            indicator = target.find('> div > ' + selectors.touchIndicator),
            parent, isSelected;

        if (indicator) {
            target = indicator;
        }

        if (!indicator || target.matches(selectors.touchIndicator)) {
            event.preventDefault();
        }

        parent = target.matches(selectors.item) ? target : target.parent(selectors.item);
        isSelected = parent.hasClass(states.selected);

        if (!isSelected) {
            var currentlyOpen = parent.siblings().search(selectors.touchIndicator + ' !> * !> ' + selectors.item + '.' + states.selected);
            (currentlyOpen || []).forEach(bind(function(open) {
                this.closeDropdown(open);
            }, this));
        }

        this[!isSelected ? 'openDropdown' : 'closeDropdown'](parent);
    },

    openDropdown: function(element) {
        element = $(element.target || element);
        var dropdown = element.find(this.selectors.dropdown);

        element.addClass(this.states.selected);

        if (dropdown) {
            dropdown.removeClass(this.states.inactive).addClass(this.states.active);
        }
    },

    closeDropdown: function(element) {
        element = $(element.target || element);
        var dropdown = element.find(this.selectors.dropdown);

        element.removeClass(this.states.selected);

        if (dropdown) {
            dropdown.removeClass(this.states.active).addClass(this.states.inactive);
        }
    },

    _checkQuery: function(mq) {

        var selectors = this.options.selectors,
            mobileContainer = $(selectors.mobileContainer),
            mainContainer = $(selectors.mainContainer),
            find;

        if (mq.matches) {
            find = mainContainer.find(selectors.topLevel);
            if (find) { find.top(mobileContainer); }
        } else {
            find = mobileContainer.find(selectors.topLevel);
            if (find) { find.top(mainContainer); }
        }
    },

    _debug: function() {}
});

module.exports = Menu;