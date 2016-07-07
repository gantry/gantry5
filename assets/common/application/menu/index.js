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
            linkedParent: '[data-g-menuparent]',
            mobileTarget: '[data-g-mobile-target]'
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

        var mainContainer = $(this.selectors.mainContainer);
        if (!mainContainer) { return; }

        var gHoverExpand  = mainContainer.data('g-hover-expand');

        this.hoverExpand = gHoverExpand === null || gHoverExpand === 'true';
        if (hasTouchEvents || !this.hoverExpand) {
            mainContainer.addClass(this.states.touchEvents);
        }

        this.attach();
    },

    attach: function() {
        var selectors       = this.selectors,
            main            = $(selectors.mainContainer + ' ' + selectors.item),
            mobileContainer = $(selectors.mobileContainer),
            body            = $('body');

        if (!main) { return; }
        if (this.hoverExpand) {
            main.on('mouseenter', this.bound('mouseenter'));
            main.on('mouseleave', this.bound('mouseleave'));
        }

        body.delegate('click', ':not(' + selectors.mainContainer + ') ' + selectors.linkedParent + ', .g-fullwidth .g-sublevel ' + selectors.linkedParent, this.bound('click'));
        body.delegate(hasTouchEvents ? 'touchend' : 'click', ':not(' + selectors.mainContainer + ') a[href]', this.bound('resetAfterClick'));

        if (hasTouchEvents || !this.hoverExpand) {
            var linkedParent = $(selectors.linkedParent);
            if (linkedParent) { linkedParent.on('touchend', this.bound('touchend')); }
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

    resetAfterClick: function(event) {
        var target = $(event.target);

        if (target.data('g-menuparent') !== null) {
            return true;
        }

        this.closeDropdown(event);
        if (global.G5 && global.G5.offcanvas) {
            G5.offcanvas.close();
        }
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
            states    = this.states;

        var target      = $(event.target),
            indicator   = target.parent(selectors.item).find(selectors.touchIndicator),
            menuType    = target.parent('.g-standard') ? 'standard' : 'megamenu',
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
            var siblings = parent.siblings();
            if (siblings) {
                var currentlyOpen = siblings.search(selectors.touchIndicator + ' !> * !> ' + selectors.item + '.' + states.selected);
                (currentlyOpen || []).forEach(bind(function(open) {
                    this.closeDropdown(open);
                }, this));
            }
        }

        if ((menuType == 'megamenu' || !parent.parent(selectors.mainContainer)) && (parent.find(' > ' + selectors.dropdown + ', > * > ' + selectors.dropdown) || isGoingBack)) {
            var sublevel = target.parent('.g-sublevel') || target.parent('.g-toplevel'),
                slideout = parent.find('.g-sublevel'),
                columns  = parent.parent('.g-dropdown-column'),
                blocks;

            if (sublevel) {
                var isNavMenu = target.parent(selectors.mainContainer);
                if (!isNavMenu || (isNavMenu && !sublevel.matches('.g-toplevel'))) { this._fixHeights(sublevel, slideout, isGoingBack, isNavMenu); }
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
                actives   = dropdown.search('.' + this.states.active);

            if (sublevels) { sublevels.attribute('style', null); }
            if (slideouts) { slideouts.removeClass('g-slide-out').removeClass(this.states.selected); }
            if (actives) { actives.removeClass(this.states.active).addClass(this.states.inactive); }

            dropdown.removeClass(this.states.active).addClass(this.states.inactive);
        }
    },

    closeAllDropdowns: function() {
        var selectors = this.selectors,
            states    = this.states,
            topLevel  = $(selectors.mainContainer + ' > .g-toplevel'),
            roots     = topLevel.search(' >' + selectors.item);

        if (roots) { roots.removeClass(states.selected); }
        if (topLevel) {
            var allRoots = topLevel.search('> ' + this.options.selectors.item);
            if (allRoots) { allRoots.forEach(this.closeDropdown.bind(this)); }
            this.closeDropdown(topLevel);
        }

        this.toggleOverlay(topLevel);
    },

    resetStates: function(menu) {
        if (!menu) { return; }
        var items   = menu.search('.g-toplevel, .g-dropdown-column, .g-dropdown, .g-selected, .g-active, .g-slide-out'),
            actives = menu.search('.g-active');
        if (!items) { return; }

        menu.attribute('style', null).removeClass('g-selected').removeClass('g-slide-out');
        items.attribute('style', null).removeClass('g-selected').removeClass('g-slide-out');
        if (actives) { actives.removeClass('g-active').addClass('g-inactive'); }
    },

    toggleOverlay: function(menu) {
        if (!menu) { return; }
        var shouldOpen = !!menu.find('.g-active, .g-selected');

        this.overlay[shouldOpen ? 'addClass' : 'removeClass']('g-menu-overlay-open');
        this.overlay[0].style.opacity = shouldOpen ? 1 : 0;
    },

    _fixHeights: function(parent, sublevel, isGoingBack, isNavMenu) {
        if (parent == sublevel) { return; }
        if (isGoingBack) { parent.attribute('style', null); }

        var heights = {
                from: parent[0].getBoundingClientRect(),
                to: (!isNavMenu ? sublevel.parent('.g-dropdown')[0] : sublevel[0]).getBoundingClientRect()
            },
            height  = Math.max(heights.from.height, heights.to.height);

        if (!isGoingBack) {
            // if from height is < than to height set the parent height else, set the target
            if (heights.from.height < heights.to.height) { parent[0].style.height = height + 'px'; }
            else if (isNavMenu) { sublevel[0].style.height = height + 'px'; }

            // fix sublevels heights in side menu (offcanvas etc)
            if (!isNavMenu) {
                var maxHeight = height,
                    block     = $(sublevel).parent('.g-block:not(.size-100)'),
                    column    = block ? block.parent('.g-dropdown-column') : null;
                (sublevel.parents('.g-slide-out, .g-dropdown-column') || parent).forEach(function(slideout) {
                    maxHeight = Math.max(height, parseInt(slideout.style.height || 0, 10));
                });

                if (column) {
                    column[0].style.height = maxHeight + 'px';

                    var blocks = column.search('> .g-grid > .g-block'),
                        diff   = maxHeight;

                    blocks.forEach(function(block, i) {
                        if ((i + 1) != blocks.length) {
                            diff -= block.getBoundingClientRect().height;
                        } else {
                            $(block).find('.g-sublevel')[0].style.height = diff + 'px';
                        }
                    });


                } else {
                    sublevel[0].style.height = maxHeight + 'px';
                }
            }
        }
    },

    _calculateBreakpoint: function(value) {
        var digit     = parseFloat(value.match(/^\d{1,}/).shift()),
            unit      = value.match(/[a-z]{1,}$/i).shift(),
            tolerance = unit.match(/r?em/) ? -0.062 : -1;

        return (digit + tolerance) + unit;
    },

    _checkQuery: function(mq) {
        var selectors       = this.options.selectors,
            mobileContainer = $(selectors.mobileContainer),
            mainContainer   = $(selectors.mainContainer + selectors.mobileTarget) || $(selectors.mainContainer),
            find, dropdowns;

        if (mq.matches) {
            // move to Mobile Container
            find = mainContainer.find(selectors.topLevel);
            if (find) {
                mainContainer.parent('.g-block').addClass('hidden');
                mobileContainer.parent('.g-block').removeClass('hidden');
                find.top(mobileContainer);
            }
        } else {
            // move back to Original Location
            find = mobileContainer.find(selectors.topLevel);
            if (find) {
                mobileContainer.parent('.g-block').addClass('hidden');
                mainContainer.parent('.g-block').removeClass('hidden');
                find.top(mainContainer);
            }
        }

        this.resetStates(find);

        // we need to reintroduce fixed widths for those dropdowns that come with it
        if (!mq.matches && (find && (dropdowns = find.search('[data-g-item-width]')))) {
            dropdowns.forEach(function(dropdown) {
                dropdown = $(dropdown);
                dropdown[0].style.width = dropdown.data('g-item-width');
            });
        }
    },

    _debug: function() {}
});

module.exports = Menu;
