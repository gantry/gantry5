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
            linkedParent: '[data-g-menuparent]',
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
            mobileContainer = $(selectors.mobileContainer),
            body = $('body');

        main.on('mouseenter', this.bound('mouseenter'));
        main.on('mouseleave', this.bound('mouseleave'));
        body.delegate('click', '.g-fullwidth .g-sublevel ' + selectors.linkedParent, this.bound('click'));

        if (hasTouchEvents) {
            $(selectors.linkedParent).on('touchend', this.bound('touchend'));
        }

        if (mobileContainer) {
            var query = 'only all and (max-width: ' + (mobileContainer.data('g-menu-breakpoint') || '48rem') + ')',
                match = matchMedia(query);
            match.addListener(this.bound('_checkQuery'));
            this._checkQuery(match);
        }
    },

    detach: function() {},

    click: function(event) {
        this.touchend(event);
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
        var selectors = this.selectors,
            states = this.states;

        var target = $(event.target),
            indicator = target.parent(selectors.item).find(selectors.touchIndicator),
            menuType = target.parent('.g-standard') ? 'standard' : 'megamenu',
            parent, isSelected;

        if (indicator) {
            target = indicator;
        }

        parent = target.matches(selectors.item) ? target : target.parent(selectors.item);
        isSelected = parent.hasClass(states.selected);

        if (!parent.find(selectors.dropdown) && !indicator) { console.log(event); return true; }

        event.stopPropagation();
        if (!indicator || target.matches(selectors.touchIndicator)) {
            event.preventDefault();
        }

        if (!isSelected) {
            var currentlyOpen = parent.siblings().search(selectors.touchIndicator + ' !> * !> ' + selectors.item + '.' + states.selected);
            (currentlyOpen || []).forEach(bind(function(open) {
                this.closeDropdown(open);
            }, this));
        }

        /*if (target.parent('.g-go-back')) {
            target.parent('.g-active').removeClass('g-active');
            target.parent('.g-slide-out').removeClass('g-slide-out');
            return;
        }*/
console.log(parent);
        if (menuType == 'megamenu' && (parent.find(' > ' + selectors.dropdown) || target.parent('.g-go-back'))) {
            var sublevel = target.parent('.g-sublevel');
            if (sublevel) sublevel[!isSelected ? 'addClass' : 'removeClass']('g-slide-out');
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