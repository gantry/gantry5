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
            overlay: '.g-menu-overlay',
            touchIndicator: '.g-menu-parent-indicator',
            linkedParent: '[data-g-menuparent]'
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
        this.overlay = zen('div' + this.selectors.overlay).top('#g-page-surround');
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
        body.delegate('click', ':not(' + selectors.mainContainer + ') ' + selectors.linkedParent + ', .g-fullwidth .g-sublevel ' + selectors.linkedParent, this.bound('click'));

        if (hasTouchEvents) {
            $(selectors.linkedParent).on('touchend', this.bound('touchend'));
            this.overlay.on('touchend', this.bound('closeAllDropdowns'));
        }

        if (mobileContainer) {
            var query = 'only all and (max-width: ' + this._calculateBreakpoint((mobileContainer.data('g-menu-breakpoint') || '48rem')) + ')',
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
        if (!element.parent(this.options.selectors.mainContainer)) { return; }
        if (element.parent(this.options.selectors.item) && !element.parent('.g-standard')) { return; }

        this.openDropdown(element);
    },

    mouseleave: function(event) {
        var element = $(event.target);
        if (!element.parent(this.options.selectors.mainContainer)) { return; }
        if (element.parent(this.options.selectors.item) && !element.parent('.g-standard')) { return; }

        this.closeDropdown(element);
    },

    touchend: function(event) {
        var selectors = this.selectors,
            states = this.states;

        var target = $(event.target),
            indicator = target.parent(selectors.item).find(selectors.touchIndicator),
            menuType = target.parent('.g-standard') ? 'standard' : 'megamenu',
            isGoingBack = target.parent('.g-go-back'),
            parent, isSelected;

        if (indicator) {
            target = indicator;
        }

        parent = target.matches(selectors.item) ? target : target.parent(selectors.item);
        isSelected = parent.hasClass(states.selected);

        if (!parent.find(selectors.dropdown) && !indicator) { return true; }

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

        if ((menuType == 'megamenu' || !parent.parent(selectors.mainContainer)) && (parent.find(' > ' + selectors.dropdown) || isGoingBack)) {
            var sublevel = target.parent('.g-sublevel') || target.parent('.g-toplevel'),
                slideout = parent.find('.g-sublevel'),
                columns = parent.parent('.g-dropdown-column'),
                blocks;

            if (sublevel) {
                var isNavMenu = target.parent(selectors.mainContainer);
                if (isNavMenu && !sublevel.hasClass('g-toplevel')) { this._fixHeights(sublevel, slideout, isGoingBack); }
                if (!isNavMenu && columns && (blocks = columns.search('> .g-grid > .g-block'))) {
                    if (blocks.length > 1) { sublevel = blocks.search('> .g-sublevel'); }
                }

                sublevel[!isSelected ? 'addClass' : 'removeClass']('g-slide-out');
            }
        }

        this[!isSelected ? 'openDropdown' : 'closeDropdown'](parent);
        if (event.type !== 'click') { this.toggleOverlay(target.parent(selectors.mainContainer)); }
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
            var sublevels = dropdown.search('.g-sublevel'),
                slideouts = dropdown.search('.g-slide-out, .' + this.states.selected),
                actives = dropdown.search('.' + this.states.active);

            if (sublevels) { sublevels.attribute('style', null); }
            if (slideouts) { slideouts.removeClass('g-slide-out').removeClass(this.states.selected); }
            if (actives) { actives.removeClass(this.states.active).addClass(this.states.inactive); }

            dropdown.removeClass(this.states.active).addClass(this.states.inactive);
        }
    },

    closeAllDropdowns: function() {
        var selectors = this.selectors,
            states = this.states,
            topLevel = $(selectors.mainContainer + ' > .g-toplevel'),
            roots = topLevel.search(' >' + selectors.item);

        if (roots) { roots.removeClass(states.selected); }
        if (topLevel) { this.closeDropdown(topLevel); }

        this.toggleOverlay(topLevel);
    },

    toggleOverlay: function(menu) {
        if (!menu) { return; }
        var shouldOpen = !!menu.find('.g-active, .g-selected');

        this.overlay[shouldOpen ? 'addClass' : 'removeClass']('g-menu-overlay-open');
        this.overlay[0].style.opacity = shouldOpen ? 1 : 0;
    },

    _fixHeights: function(parent, sublevel, isGoingBack) {
        if (parent == sublevel) { return; }
        if (isGoingBack) { parent.attribute('style', null); }

        var heights = {
            from: parent[0].getBoundingClientRect(),
            to: sublevel[0].getBoundingClientRect()
        };

        if (!isGoingBack) {
            // if from height is < than to height set the parent height else, set the target
            if (heights.from.height < heights.to.height) { parent[0].style.height = Math.max(heights.from.height, heights.to.height) + 'px'; }
            else { sublevel[0].style.height = Math.max(heights.from.height, heights.to.height) + 'px'; }
        }
    },

    _calculateBreakpoint: function(value) {
        var digit = parseFloat(value.match(/^\d{1,}/).shift()),
            unit = value.match(/[a-z]{1,}$/i).shift(),
            tolerance = unit.match(/r?em/) ? -0.062 : -1;

        return (digit + tolerance) + unit;
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