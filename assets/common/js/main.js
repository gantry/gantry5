(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
"use strict";

var ready     = require('domready'),
    menu      = require('./menu'),
    offcanvas = require('./offcanvas'),
    totop     = require('./totop'),
    $         = require('./utils/dollar-extras'),

    instances = {};

ready(function() {
    instances = {
        offcanvas: new offcanvas(),
        menu: new menu(),
        $: $,
        ready: ready
    };

    module.exports = window.G5 = instances;
});

module.exports = window.G5 = instances;

},{"./menu":2,"./offcanvas":3,"./totop":4,"./utils/dollar-extras":6,"domready":7}],2:[function(require,module,exports){
(function (global){
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
        body.delegate('click', ':not(' + selectors.mainContainer + ') a[href]', this.bound('resetAfterClick'));

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

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"../utils/dollar-extras":6,"domready":7,"elements/zen":36,"mout/function/bind":40,"mout/function/timeout":44,"prime":85,"prime-util/prime/bound":81,"prime-util/prime/options":82}],3:[function(require,module,exports){
// Offcanvas slide with desktop, touch and all-in-one touch devices support that supports both left and right placement.
// Fast and optimized using CSS3 transitions
// Based on the awesome Slideout.js <https://mango.github.io/slideout/>

"use strict";

var ready     = require('domready'),
    prime     = require('prime'),
    bind      = require('mout/function/bind'),
    forEach   = require('mout/array/forEach'),
    mapNumber = require('mout/math/map'),
    clamp     = require('mout/math/clamp'),
    timeout   = require('mout/function/timeout'),
    trim      = require('mout/string/trim'),
    decouple  = require('../utils/decouple'),
    Bound     = require('prime-util/prime/bound'),
    Options   = require('prime-util/prime/options'),
    $         = require('elements'),
    zen       = require('elements/zen');

// thanks David Walsh
var prefix = (function() {
    var styles = window.getComputedStyle(document.documentElement, ''),
        pre    = (Array.prototype.slice.call(styles).join('')
                .match(/-(moz|webkit|ms)-/) || (styles.OLink === '' && ['', 'o'])
        )[1],
        dom    = ('WebKit|Moz|MS|O').match(new RegExp('(' + pre + ')', 'i'))[1];
    return {
        dom: dom,
        lowercase: pre,
        css: '-' + pre + '-',
        js: pre[0].toUpperCase() + pre.substr(1)
    };
})();

var hasTouchEvents = ('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch,
    isScrolling    = false, scrollTimeout;

var Offcanvas = new prime({

    mixin: [Bound, Options],

    options: {
        effect: 'ease',
        duration: 300,
        tolerance: function(padding) { // tolerance can also be just an integer value
            return padding / 3;
        },
        padding: 0,
        touch: true,
        css3: true,

        openClass: 'g-offcanvas-open',
        openingClass: 'g-offcanvas-opening',
        closingClass: 'g-offcanvas-closing',
        overlayClass: 'g-nav-overlay'
    },

    constructor: function(options) {
        this.setOptions(options);

        this.attached = false;
        this.opening = false;
        this.moved = false;
        this.dragging = false;
        this.opened = false;
        this.preventOpen = false;
        this.offset = {
            x: {
                start: 0,
                current: 0
            },
            y: {
                start: 0,
                current: 0
            }
        };

        this.bodyEl = $('body');
        this.htmlEl = $('html');

        this.panel = $('#g-page-surround');
        this.offcanvas = $('#g-offcanvas');

        if (!this.panel || !this.offcanvas) { return false; }

        var swipe = this.offcanvas.data('g-offcanvas-swipe'),
            css3 = this.offcanvas.data('g-offcanvas-css3');
        this.setOptions({ touch: !!(swipe !== null ? parseInt(swipe) : 1), css3: !!(css3 !== null ? parseInt(css3) : 1) });

        if (!this.options.padding) {
            this.offcanvas[0].style.display = 'block';
            var width = this.offcanvas[0].getBoundingClientRect().width;
            this.offcanvas[0].style.removeProperty('display');

            this.setOptions({ padding: width });
        }

        this.tolerance = typeof this.options.tolerance == 'function' ? this.options.tolerance.call(this, this.options.padding) : this.options.tolerance;

        this.htmlEl.addClass('g-offcanvas-' + (this.options.css3 ? 'css3' : 'css2'));

        this.attach();
        this._checkTogglers();

        return this;
    },

    attach: function() {
        this.attached = true;

        if (this.options.touch && hasTouchEvents) {
            this.attachTouchEvents();
        }

        forEach(['toggle', 'open', 'close'], bind(function(mode) {
            this.bodyEl.delegate('click', '[data-offcanvas-' + mode + ']', this.bound(mode));
            if (hasTouchEvents) { this.bodyEl.delegate('touchend', '[data-offcanvas-' + mode + ']', this.bound(mode)); }
        }, this));

        this.attachMutationEvent();

        this.overlay = zen('div[data-offcanvas-close].' + this.options.overlayClass).top(this.panel);

        return this;
    },

    attachMutationEvent: function() {
        this.offcanvas.on('DOMSubtreeModified', this.bound('_checkTogglers')); // IE8 < has propertychange
    },

    attachTouchEvents: function() {
        var msPointerSupported = window.navigator.msPointerEnabled,
            touch              = {
                start: msPointerSupported ? 'MSPointerDown' : 'touchstart',
                move: msPointerSupported ? 'MSPointerMove' : 'touchmove',
                end: msPointerSupported ? 'MSPointerUp' : 'touchend'
            };

        this._scrollBound = decouple(window, 'scroll', this.bound('_bodyScroll'));
        this.bodyEl.on(touch.move, this.bound('_bodyMove'));
        this.panel.on(touch.start, this.bound('_touchStart'));
        this.panel.on('touchcancel', this.bound('_touchCancel'));
        this.panel.on(touch.end, this.bound('_touchEnd'));
        this.panel.on(touch.move, this.bound('_touchMove'));
    },

    detach: function() {
        this.attached = false;

        if (this.options.touch && hasTouchEvents) {
            this.detachTouchEvents();
        }

        forEach(['toggle', 'open', 'close'], bind(function(mode) {
            this.bodyEl.undelegate('click', '[data-offcanvas-' + mode + ']', this.bound(mode));
            if (hasTouchEvents) { this.bodyEl.undelegate('touchend', '[data-offcanvas-' + mode + ']', this.bound(mode)); }
        }, this));

        this.detachMutationEvent();
        this.overlay.remove();

        return this;
    },

    detachMutationEvent: function() {
        this.offcanvas.off('DOMSubtreeModified', this.bound('_checkTogglers'));
    },

    detachTouchEvents: function() {
        var msPointerSupported = window.navigator.msPointerEnabled,
            touch              = {
                start: msPointerSupported ? 'MSPointerDown' : 'touchstart',
                move: msPointerSupported ? 'MSPointerMove' : 'touchmove',
                end: msPointerSupported ? 'MSPointerUp' : 'touchend'
            };

        window.removeEventListener('scroll', this._scrollBound);
        this.bodyEl.off(touch.move, this.bound('_bodyMove'));
        this.panel.off(touch.start, this.bound('_touchStart'));
        this.panel.off('touchcancel', this.bound('_touchCancel'));
        this.panel.off(touch.end, this.bound('_touchEnd'));
        this.panel.off(touch.move, this.bound('_touchMove'));
    },


    open: function(event) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        else { this.dragging = false; }

        if (this.opened) { return this; }

        this.htmlEl.addClass(this.options.openClass);
        this.htmlEl.addClass(this.options.openingClass);

        this.overlay[0].style.opacity = 1;

        if (this.options.css3) {
            // for translate3d
            this.panel[0].style[this.getOffcanvasPosition()] = 'inherit';
        }

        this._setTransition();
        this._translateXTo((this.bodyEl.hasClass('g-offcanvas-right') ? -1 : 1) * this.options.padding);
        this.opened = true;

        setTimeout(bind(function() {
            var panel = this.panel[0];

            this.htmlEl.removeClass(this.options.openingClass);
            panel.style.transition = panel.style[prefix.css + 'transition'] = '';
        }, this), this.options.duration);

        return this;
    },

    close: function(event, element) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        else { this.dragging = false; }

        element = element || window;

        if (!this.opened && !this.opening) { return this; }
        if (this.panel !== element && this.dragging) { return false; }

        this.htmlEl.addClass(this.options.closingClass);

        this.overlay[0].style.opacity = 0;

        this._setTransition();
        this._translateXTo(0);
        this.opened = false;

        setTimeout(bind(function() {
            var panel = this.panel[0];

            this.htmlEl.removeClass(this.options.openClass);
            this.htmlEl.removeClass(this.options.closingClass);
            panel.style.transition = panel.style[prefix.css + 'transition'] = '';
            panel.style.transform = panel.style[prefix.css + 'transform'] = '';
            panel.style[this.getOffcanvasPosition()] = '';
        }, this), this.options.duration);


        return this;
    },

    toggle: function(event, element) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        else { this.dragging = false; }

        return this[this.opened ? 'close' : 'open'](event, element);
    },

    getOffcanvasPosition: function() {
        return this.bodyEl.hasClass('g-offcanvas-right') ? 'right' : 'left';
    },

    _setTransition: function() {
        var panel = this.panel[0];

        if (this.options.css3) {
            // for translate3d
            panel.style[prefix.css + 'transition'] = panel.style.transition = prefix.css + 'transform ' + this.options.duration + 'ms ' + this.options.effect;
        } else {
            // left/right transition
            panel.style[prefix.css + 'transition'] = panel.style.transition = 'left ' + this.options.duration + 'ms ' + this.options.effect + ', right ' + this.options.duration + 'ms ' + this.options.effect;
        }
    },

    _translateXTo: function(x) {
        var panel     = this.panel[0],
            placement = this.getOffcanvasPosition();

        this.offset.x.current = x;

        if (this.options.css3) {
            // for translate3d
            panel.style[prefix.css + 'transform'] = panel.style.transform = 'translate3d(' + x + 'px, 0, 0)';
        } else {
            // left/right transition
            panel.style[placement] = Math.abs(x) + 'px';
        }
    },

    _bodyScroll: function() {
        if (!this.moved) {
            clearTimeout(scrollTimeout);
            isScrolling = true;
            scrollTimeout = setTimeout(function() {
                isScrolling = false;
            }, 250);
        }
    },

    _bodyMove: function() {
        if (this.moved) { event.preventDefault(); }
        this.dragging = true;

        return false;
    },

    _touchStart: function(event) {
        if (!event.touches) { return; }

        this.moved = false;
        this.opening = false;
        this.dragging = false;
        this.offset.x.start = event.touches[0].pageX;
        this.offset.y.start = event.touches[0].pageY;
        this.preventOpen = (!this.opened && this.offcanvas[0].clientWidth !== 0);
    },

    _touchCancel: function() {
        this.moved = false;
        this.opening = false;
    },

    _touchMove: function(event) {
        if (isScrolling || this.preventOpen || !event.touches) { return; }
        if (this.options.css3) {
            this.panel[0].style[this.getOffcanvasPosition()] = 'inherit';
        }

        var placement  = this.getOffcanvasPosition(),
            diffX      = clamp(event.touches[0].clientX - this.offset.x.start, -this.options.padding, this.options.padding),
            translateX = this.offset.x.current = diffX,
            diffY  = Math.abs(event.touches[0].pageY - this.offset.y.start),
            offset = placement == 'right' ? -1 : 1,
            overlayOpacity;

        if (Math.abs(translateX) > this.options.padding) { return; }
        if (diffY > 5 && !this.moved) { return; }

        if (Math.abs(diffX) > 0) {
            this.opening = true;

            // offcanvas on left
            if (placement == 'left' && (this.opened && diffX > 0 || !this.opened && diffX < 0)) { return; }

            // offcanvas on right
            if (placement == 'right' && (this.opened && diffX < 0 || !this.opened && diffX > 0)) { return; }

            if (!this.moved && !this.htmlEl.hasClass(this.options.openClass)) {
                this.htmlEl.addClass(this.options.openClass);
            }

            if ((placement == 'left' && diffX <= 0) || (placement == 'right' && diffX >= 0)) {
                translateX = diffX + (offset * this.options.padding);
                this.opening = false;
            }

            overlayOpacity = mapNumber(Math.abs(translateX), 0, this.options.padding, 0, 1);
            this.overlay[0].style.opacity = overlayOpacity;

            if (this.options.css3) {
                // for translate3d
                this.panel[0].style[prefix.css + 'transform'] = this.panel[0].style.transform = 'translate3d(' + translateX + 'px, 0, 0)';
            } else {
                // left/right transition
                this.panel[0].style[placement] = Math.abs(translateX) + 'px';
            }

            this.moved = true;
        }
    },

    _touchEnd: function(event) {
        if (this.moved) {
            var tolerance = Math.abs(this.offset.x.current) > this.tolerance,
                placement = this.bodyEl.hasClass('g-offcanvas-right') ? true : false,
                direction = !placement ? (this.offset.x.current < 0) : (this.offset.x.current > 0);

            this.opening = tolerance ? !direction : direction;
            this.opened = !this.opening;
            this[this.opening ? 'open' : 'close'](event, this.panel);
        }

        this.moved = false;

        return true;
    },

    _checkTogglers: function(mutator) {
        var togglers        = $('[data-offcanvas-toggle], [data-offcanvas-open], [data-offcanvas-close]'),
            mobileContainer = $('#g-mobilemenu-container'),
            blocks, mCtext;

        if (!togglers || (mutator && ((mutator.target || mutator.srcElement) !== mobileContainer[0]))) { return; }
        if (this.opened) { this.close(); }

        timeout(function() {
            blocks = this.offcanvas.search('.g-block');
            mCtext = mobileContainer ? mobileContainer.text().length : 0;
            var shouldCollapse = (blocks && blocks.length == 1) && mobileContainer && !trim(this.offcanvas.text()).length;

            togglers[shouldCollapse ? 'addClass' : 'removeClass']('g-offcanvas-hide');
            if (mobileContainer) {
                mobileContainer.parent('.g-block')[!mCtext ? 'addClass' : 'removeClass']('hidden');
            }

            if (!shouldCollapse && !this.attached) { this.attach(); }
            else if (shouldCollapse && this.attached) {
                this.detach();
                this.attachMutationEvent();
            }
        }, 0, this);
    }
});

module.exports = Offcanvas;

},{"../utils/decouple":5,"domready":7,"elements":12,"elements/zen":36,"mout/array/forEach":37,"mout/function/bind":40,"mout/function/timeout":44,"mout/math/clamp":49,"mout/math/map":51,"mout/string/trim":60,"prime":85,"prime-util/prime/bound":81,"prime-util/prime/options":82}],4:[function(require,module,exports){
"use strict";

var ready = require('domready'),
    $     = require('../utils/dollar-extras');

var timeOut,
    scrollToTop = function() {
        if (document.body.scrollTop != 0 || document.documentElement.scrollTop != 0) {
            window.scrollBy(0, -50);
            timeOut = setTimeout(scrollToTop, 10);
        } else {
            clearTimeout(timeOut);
        }
    };

ready(function() {
    var totop = $('#g-totop');
    if (!totop) { return; }

    totop.on('click', function(e) {
        e.preventDefault();
        scrollToTop();
    });
});

module.exports = {};

},{"../utils/dollar-extras":6,"domready":7}],5:[function(require,module,exports){
'use strict';

var rAF = (function() {
    return window.requestAnimationFrame ||
        window.webkitRequestAnimationFrame ||
        function(callback) { window.setTimeout(callback, 1000 / 60); };
}());

var decouple = function(element, event, callback) {
    var evt, tracking = false;
    element = element[0] || element;

    var capture = function(e) {
        evt = e;
        track();
    };

    var track = function() {
        if (!tracking) {
            rAF(update);
            tracking = true;
        }
    };

    var update = function() {
        callback.call(element, evt);
        tracking = false;
    };

    try {
        element.addEventListener(event, capture, false);
    } catch (e) {}

    return capture;
};

module.exports = decouple;
},{}],6:[function(require,module,exports){
"use strict";
var $          = require('elements'),
    map        = require('mout/array/map'),
    slick      = require('slick');

var walk = function(combinator, method) {

    return function(expression) {
        var parts = slick.parse(expression || "*");

        expression = map(parts, function(part) {
            return combinator + " " + part;
        }).join(', ');

        return this[method](expression);
    };

};


$.implement({
    sibling: walk('++', 'find'),
    siblings: walk('~~', 'search')
});


module.exports = $;

},{"elements":12,"mout/array/map":38,"slick":97}],7:[function(require,module,exports){
/*!
  * domready (c) Dustin Diaz 2014 - License MIT
  */
!function (name, definition) {

  if (typeof module != 'undefined') module.exports = definition()
  else if (typeof define == 'function' && typeof define.amd == 'object') define(definition)
  else this[name] = definition()

}('domready', function () {

  var fns = [], listener
    , doc = document
    , hack = doc.documentElement.doScroll
    , domContentLoaded = 'DOMContentLoaded'
    , loaded = (hack ? /^loaded|^c/ : /^loaded|^i|^c/).test(doc.readyState)


  if (!loaded)
  doc.addEventListener(domContentLoaded, listener = function () {
    doc.removeEventListener(domContentLoaded, listener)
    loaded = 1
    while (listener = fns.shift()) listener()
  })

  return function (fn) {
    loaded ? setTimeout(fn, 0) : fns.push(fn)
  }

});

},{}],8:[function(require,module,exports){
/*
attributes
*/"use strict"

var $       = require("./base")

var trim    = require("mout/string/trim"),
    forEach = require("mout/array/forEach"),
    filter  = require("mout/array/filter"),
    indexOf = require("mout/array/indexOf")

// attributes

$.implement({

    setAttribute: function(name, value){
        return this.forEach(function(node){
            node.setAttribute(name, value)
        })
    },

    getAttribute: function(name){
        var attr = this[0].getAttributeNode(name)
        return (attr && attr.specified) ? attr.value : null
    },

    hasAttribute: function(name){
        var node = this[0]
        if (node.hasAttribute) return node.hasAttribute(name)
        var attr = node.getAttributeNode(name)
        return !!(attr && attr.specified)
    },

    removeAttribute: function(name){
        return this.forEach(function(node){
            var attr = node.getAttributeNode(name)
            if (attr) node.removeAttributeNode(attr)
        })
    }

})

var accessors = {}

forEach(["type", "value", "name", "href", "title", "id"], function(name){

    accessors[name] = function(value){
        return (value !== undefined) ? this.forEach(function(node){
            node[name] = value
        }) : this[0][name]
    }

})

// booleans

forEach(["checked", "disabled", "selected"], function(name){

    accessors[name] = function(value){
        return (value !== undefined) ? this.forEach(function(node){
            node[name] = !!value
        }) : !!this[0][name]
    }

})

// className

var classes = function(className){
    var classNames = trim(className).replace(/\s+/g, " ").split(" "),
        uniques    = {}

    return filter(classNames, function(className){
        if (className !== "" && !uniques[className]) return uniques[className] = className
    }).sort()
}

accessors.className = function(className){
    return (className !== undefined) ? this.forEach(function(node){
        node.className = classes(className).join(" ")
    }) : classes(this[0].className).join(" ")
}

// attribute

$.implement({

    attribute: function(name, value){
        var accessor = accessors[name]
        if (accessor) return accessor.call(this, value)
        if (value != null) return this.setAttribute(name, value)
        if (value === null) return this.removeAttribute(name)
        if (value === undefined) return this.getAttribute(name)
    }

})

$.implement(accessors)

// shortcuts

$.implement({

    check: function(){
        return this.checked(true)
    },

    uncheck: function(){
        return this.checked(false)
    },

    disable: function(){
        return this.disabled(true)
    },

    enable: function(){
        return this.disabled(false)
    },

    select: function(){
        return this.selected(true)
    },

    deselect: function(){
        return this.selected(false)
    }

})

// classNames, has / add / remove Class

$.implement({

    classNames: function(){
        return classes(this[0].className)
    },

    hasClass: function(className){
        return indexOf(this.classNames(), className) > -1
    },

    addClass: function(className){
        return this.forEach(function(node){
            var nodeClassName = node.className
            var classNames = classes(nodeClassName + " " + className).join(" ")
            if (nodeClassName !== classNames) node.className = classNames
        })
    },

    removeClass: function(className){
        return this.forEach(function(node){
            var classNames = classes(node.className)
            forEach(classes(className), function(className){
                var index = indexOf(classNames, className)
                if (index > -1) classNames.splice(index, 1)
            })
            node.className = classNames.join(" ")
        })
    },

    toggleClass: function(className, force){
        var add = force !== undefined ? force : !this.hasClass(className)
        if (add)
            this.addClass(className)
        else
            this.removeClass(className)
        return !!add
    }

})

// toString

$.prototype.toString = function(){
    var tag     = this.tag(),
        id      = this.id(),
        classes = this.classNames()

    var str = tag
    if (id) str += '#' + id
    if (classes.length) str += '.' + classes.join(".")
    return str
}

var textProperty = (document.createElement('div').textContent == null) ? 'innerText' : 'textContent'

// tag, html, text, data

$.implement({

    tag: function(){
        return this[0].tagName.toLowerCase()
    },

    html: function(html){
        return (html !== undefined) ? this.forEach(function(node){
            node.innerHTML = html
        }) : this[0].innerHTML
    },

    text: function(text){
        return (text !== undefined) ? this.forEach(function(node){
            node[textProperty] = text
        }) : this[0][textProperty]
    },

    data: function(key, value){
        switch(value) {
            case undefined: return this.getAttribute("data-" + key)
            case null: return this.removeAttribute("data-" + key)
            default: return this.setAttribute("data-" + key, value)
        }
    }

})

module.exports = $

},{"./base":9,"mout/array/filter":15,"mout/array/forEach":16,"mout/array/indexOf":17,"mout/string/trim":34}],9:[function(require,module,exports){
/*
elements
*/"use strict"

var prime   = require("prime")

var forEach = require("mout/array/forEach"),
    map     = require("mout/array/map"),
    filter  = require("mout/array/filter"),
    every   = require("mout/array/every"),
    some    = require("mout/array/some")

// uniqueID

var index = 0,
    __dc = document.__counter,
    counter = document.__counter = (__dc ? parseInt(__dc, 36) + 1 : 0).toString(36),
    key = "uid:" + counter

var uniqueID = function(n){
    if (n === window) return "window"
    if (n === document) return "document"
    if (n === document.documentElement) return "html"
    return n[key] || (n[key] = (index++).toString(36))
}

var instances = {}

// elements prime

var $ = prime({constructor: function $(n, context){

    if (n == null) return (this && this.constructor === $) ? new Elements : null

    var self, uid

    if (n.constructor !== Elements){

        self = new Elements

        if (typeof n === "string"){
            if (!self.search) return null
            self[self.length++] = context || document
            return self.search(n)
        }

        if (n.nodeType || n === window){

            self[self.length++] = n

        } else if (n.length){

            // this could be an array, or any object with a length attribute,
            // including another instance of elements from another interface.

            var uniques = {}

            for (var i = 0, l = n.length; i < l; i++){ // perform elements flattening
                var nodes = $(n[i], context)
                if (nodes && nodes.length) for (var j = 0, k = nodes.length; j < k; j++){
                    var node = nodes[j]
                    uid = uniqueID(node)
                    if (!uniques[uid]){
                        self[self.length++] = node
                        uniques[uid] = true
                    }
                }
            }

        }

    } else {
      self = n
    }

    if (!self.length) return null

    // when length is 1 always use the same elements instance

    if (self.length === 1){
        uid = uniqueID(self[0])
        return instances[uid] || (instances[uid] = self)
    }

    return self

}})

var Elements = prime({

    inherits: $,

    constructor: function Elements(){
        this.length = 0
    },

    unlink: function(){
        return this.map(function(node){
            delete instances[uniqueID(node)]
            return node
        })
    },

    // methods

    forEach: function(method, context){
        forEach(this, method, context)
        return this
    },

    map: function(method, context){
        return map(this, method, context)
    },

    filter: function(method, context){
        return filter(this, method, context)
    },

    every: function(method, context){
        return every(this, method, context)
    },

    some: function(method, context){
        return some(this, method, context)
    }

})

module.exports = $

},{"mout/array/every":14,"mout/array/filter":15,"mout/array/forEach":16,"mout/array/map":18,"mout/array/some":19,"prime":85}],10:[function(require,module,exports){
/*
delegation
*/"use strict"

var Map = require("prime/map")

var $ = require("./events")
        require('./traversal')

$.implement({

    delegate: function(event, selector, handle, useCapture){

        return this.forEach(function(node){

            var self = $(node)

            var delegation = self._delegation || (self._delegation = {}),
                events     = delegation[event] || (delegation[event] = {}),
                map        = (events[selector] || (events[selector] = new Map))

            if (map.get(handle)) return

            var action = function(e){
                var target = $(e.target || e.srcElement),
                    match  = target.matches(selector) ? target : target.parent(selector)

                var res

                if (match) res = handle.call(self, e, match)

                return res
            }

            map.set(handle, action)

            self.on(event, action, useCapture)

        })

    },

    undelegate: function(event, selector, handle, useCapture){

        return this.forEach(function(node){

            var self = $(node), delegation, events, map

            if (!(delegation = self._delegation) || !(events = delegation[event]) || !(map = events[selector])) return;

            var action = map.get(handle)

            if (action){
                self.off(event, action, useCapture)
                map.remove(action)

                // if there are no more handles in a given selector, delete it
                if (!map.count()) delete events[selector]
                // var evc = evd = 0, x
                var e1 = true, e2 = true, x
                for (x in events){
                    e1 = false
                    break
                }
                // if no more selectors in a given event type, delete it
                if (e1) delete delegation[event]
                for (x in delegation){
                    e2 = false
                    break
                }
                // if there are no more delegation events in the element, delete the _delegation object
                if (e2) delete self._delegation
            }

        })

    }

})

module.exports = $

},{"./events":11,"./traversal":35,"prime/map":86}],11:[function(require,module,exports){
/*
events
*/"use strict"

var Emitter = require("prime/emitter")

var $ = require("./base")

var html = document.documentElement

var addEventListener = html.addEventListener ? function(node, event, handle, useCapture){
    node.addEventListener(event, handle, useCapture || false)
    return handle
} : function(node, event, handle){
    node.attachEvent('on' + event, handle)
    return handle
}

var removeEventListener = html.removeEventListener ? function(node, event, handle, useCapture){
    node.removeEventListener(event, handle, useCapture || false)
} : function(node, event, handle){
    node.detachEvent("on" + event, handle)
}

$.implement({

    on: function(event, handle, useCapture){

        return this.forEach(function(node){
            var self = $(node)

            var internalEvent = event + (useCapture ? ":capture" : "")

            Emitter.prototype.on.call(self, internalEvent, handle)

            var domListeners = self._domListeners || (self._domListeners = {})
            if (!domListeners[internalEvent]) domListeners[internalEvent] = addEventListener(node, event, function(e){
                Emitter.prototype.emit.call(self, internalEvent, e || window.event, Emitter.EMIT_SYNC)
            }, useCapture)
        })
    },

    off: function(event, handle, useCapture){

        return this.forEach(function(node){

            var self = $(node)

            var internalEvent = event + (useCapture ? ":capture" : "")

            var domListeners = self._domListeners, domEvent, listeners = self._listeners, events

            if (domListeners && (domEvent = domListeners[internalEvent]) && listeners && (events = listeners[internalEvent])){

                Emitter.prototype.off.call(self, internalEvent, handle)

                if (!self._listeners || !self._listeners[event]){
                    removeEventListener(node, event, domEvent)
                    delete domListeners[event]

                    for (var l in domListeners) return
                    delete self._domListeners
                }

            }
        })
    },

    emit: function(){
        var args = arguments
        return this.forEach(function(node){
            Emitter.prototype.emit.apply($(node), args)
        })
    }

})

module.exports = $

},{"./base":9,"prime/emitter":84}],12:[function(require,module,exports){
/*
elements
*/"use strict"

var $ = require("./base")
        require("./attributes")
        require("./events")
        require("./insertion")
        require("./traversal")
        require("./delegation")

module.exports = $

},{"./attributes":8,"./base":9,"./delegation":10,"./events":11,"./insertion":13,"./traversal":35}],13:[function(require,module,exports){
/*
insertion
*/"use strict"

var $ = require("./base")

// base insertion

$.implement({

    appendChild: function(child){
        this[0].appendChild($(child)[0])
        return this
    },

    insertBefore: function(child, ref){
        this[0].insertBefore($(child)[0], $(ref)[0])
        return this
    },

    removeChild: function(child){
        this[0].removeChild($(child)[0])
        return this
    },

    replaceChild: function(child, ref){
        this[0].replaceChild($(child)[0], $(ref)[0])
        return this
    }

})

// before, after, bottom, top

$.implement({

    before: function(element){
        element = $(element)[0]
        var parent = element.parentNode
        if (parent) this.forEach(function(node){
            parent.insertBefore(node, element)
        })
        return this
    },

    after: function(element){
        element = $(element)[0]
        var parent = element.parentNode
        if (parent) this.forEach(function(node){
            parent.insertBefore(node, element.nextSibling)
        })
        return this
    },

    bottom: function(element){
        element = $(element)[0]
        return this.forEach(function(node){
            element.appendChild(node)
        })
    },

    top: function(element){
        element = $(element)[0]
        return this.forEach(function(node){
            element.insertBefore(node, element.firstChild)
        })
    }

})

// insert, replace

$.implement({

    insert: $.prototype.bottom,

    remove: function(){
        return this.forEach(function(node){
            var parent = node.parentNode
            if (parent) parent.removeChild(node)
        })
    },

    replace: function(element){
        element = $(element)[0]
        element.parentNode.replaceChild(this[0], element)
        return this
    }

})

module.exports = $

},{"./base":9}],14:[function(require,module,exports){
var makeIterator = require('../function/makeIterator_');

    /**
     * Array every
     */
    function every(arr, callback, thisObj) {
        callback = makeIterator(callback, thisObj);
        var result = true;
        if (arr == null) {
            return result;
        }

        var i = -1, len = arr.length;
        while (++i < len) {
            // we iterate over sparse items since there is no way to make it
            // work properly on IE 7-8. see #64
            if (!callback(arr[i], i, arr) ) {
                result = false;
                break;
            }
        }

        return result;
    }

    module.exports = every;


},{"../function/makeIterator_":21}],15:[function(require,module,exports){
var makeIterator = require('../function/makeIterator_');

    /**
     * Array filter
     */
    function filter(arr, callback, thisObj) {
        callback = makeIterator(callback, thisObj);
        var results = [];
        if (arr == null) {
            return results;
        }

        var i = -1, len = arr.length, value;
        while (++i < len) {
            value = arr[i];
            if (callback(value, i, arr)) {
                results.push(value);
            }
        }

        return results;
    }

    module.exports = filter;



},{"../function/makeIterator_":21}],16:[function(require,module,exports){


    /**
     * Array forEach
     */
    function forEach(arr, callback, thisObj) {
        if (arr == null) {
            return;
        }
        var i = -1,
            len = arr.length;
        while (++i < len) {
            // we iterate over sparse items since there is no way to make it
            // work properly on IE 7-8. see #64
            if ( callback.call(thisObj, arr[i], i, arr) === false ) {
                break;
            }
        }
    }

    module.exports = forEach;



},{}],17:[function(require,module,exports){


    /**
     * Array.indexOf
     */
    function indexOf(arr, item, fromIndex) {
        fromIndex = fromIndex || 0;
        if (arr == null) {
            return -1;
        }

        var len = arr.length,
            i = fromIndex < 0 ? len + fromIndex : fromIndex;
        while (i < len) {
            // we iterate over sparse items since there is no way to make it
            // work properly on IE 7-8. see #64
            if (arr[i] === item) {
                return i;
            }

            i++;
        }

        return -1;
    }

    module.exports = indexOf;


},{}],18:[function(require,module,exports){
var makeIterator = require('../function/makeIterator_');

    /**
     * Array map
     */
    function map(arr, callback, thisObj) {
        callback = makeIterator(callback, thisObj);
        var results = [];
        if (arr == null){
            return results;
        }

        var i = -1, len = arr.length;
        while (++i < len) {
            results[i] = callback(arr[i], i, arr);
        }

        return results;
    }

     module.exports = map;


},{"../function/makeIterator_":21}],19:[function(require,module,exports){
var makeIterator = require('../function/makeIterator_');

    /**
     * Array some
     */
    function some(arr, callback, thisObj) {
        callback = makeIterator(callback, thisObj);
        var result = false;
        if (arr == null) {
            return result;
        }

        var i = -1, len = arr.length;
        while (++i < len) {
            // we iterate over sparse items since there is no way to make it
            // work properly on IE 7-8. see #64
            if ( callback(arr[i], i, arr) ) {
                result = true;
                break;
            }
        }

        return result;
    }

    module.exports = some;


},{"../function/makeIterator_":21}],20:[function(require,module,exports){


    /**
     * Returns the first argument provided to it.
     */
    function identity(val){
        return val;
    }

    module.exports = identity;



},{}],21:[function(require,module,exports){
var identity = require('./identity');
var prop = require('./prop');
var deepMatches = require('../object/deepMatches');

    /**
     * Converts argument into a valid iterator.
     * Used internally on most array/object/collection methods that receives a
     * callback/iterator providing a shortcut syntax.
     */
    function makeIterator(src, thisObj){
        if (src == null) {
            return identity;
        }
        switch(typeof src) {
            case 'function':
                // function is the first to improve perf (most common case)
                // also avoid using `Function#call` if not needed, which boosts
                // perf a lot in some cases
                return (typeof thisObj !== 'undefined')? function(val, i, arr){
                    return src.call(thisObj, val, i, arr);
                } : src;
            case 'object':
                return function(val){
                    return deepMatches(val, src);
                };
            case 'string':
            case 'number':
                return prop(src);
        }
    }

    module.exports = makeIterator;



},{"../object/deepMatches":27,"./identity":20,"./prop":22}],22:[function(require,module,exports){


    /**
     * Returns a function that gets a property of the passed object
     */
    function prop(name){
        return function(obj){
            return obj[name];
        };
    }

    module.exports = prop;



},{}],23:[function(require,module,exports){
var isKind = require('./isKind');
    /**
     */
    var isArray = Array.isArray || function (val) {
        return isKind(val, 'Array');
    };
    module.exports = isArray;


},{"./isKind":24}],24:[function(require,module,exports){
var kindOf = require('./kindOf');
    /**
     * Check if value is from a specific "kind".
     */
    function isKind(val, kind){
        return kindOf(val) === kind;
    }
    module.exports = isKind;


},{"./kindOf":25}],25:[function(require,module,exports){


    var _rKind = /^\[object (.*)\]$/,
        _toString = Object.prototype.toString,
        UNDEF;

    /**
     * Gets the "kind" of value. (e.g. "String", "Number", etc)
     */
    function kindOf(val) {
        if (val === null) {
            return 'Null';
        } else if (val === UNDEF) {
            return 'Undefined';
        } else {
            return _rKind.exec( _toString.call(val) )[1];
        }
    }
    module.exports = kindOf;


},{}],26:[function(require,module,exports){


    /**
     * Typecast a value to a String, using an empty string value for null or
     * undefined.
     */
    function toString(val){
        return val == null ? '' : val.toString();
    }

    module.exports = toString;



},{}],27:[function(require,module,exports){
var forOwn = require('./forOwn');
var isArray = require('../lang/isArray');

    function containsMatch(array, pattern) {
        var i = -1, length = array.length;
        while (++i < length) {
            if (deepMatches(array[i], pattern)) {
                return true;
            }
        }

        return false;
    }

    function matchArray(target, pattern) {
        var i = -1, patternLength = pattern.length;
        while (++i < patternLength) {
            if (!containsMatch(target, pattern[i])) {
                return false;
            }
        }

        return true;
    }

    function matchObject(target, pattern) {
        var result = true;
        forOwn(pattern, function(val, key) {
            if (!deepMatches(target[key], val)) {
                // Return false to break out of forOwn early
                return (result = false);
            }
        });

        return result;
    }

    /**
     * Recursively check if the objects match.
     */
    function deepMatches(target, pattern){
        if (target && typeof target === 'object') {
            if (isArray(target) && isArray(pattern)) {
                return matchArray(target, pattern);
            } else {
                return matchObject(target, pattern);
            }
        } else {
            return target === pattern;
        }
    }

    module.exports = deepMatches;



},{"../lang/isArray":23,"./forOwn":29}],28:[function(require,module,exports){
var hasOwn = require('./hasOwn');

    var _hasDontEnumBug,
        _dontEnums;

    function checkDontEnum(){
        _dontEnums = [
                'toString',
                'toLocaleString',
                'valueOf',
                'hasOwnProperty',
                'isPrototypeOf',
                'propertyIsEnumerable',
                'constructor'
            ];

        _hasDontEnumBug = true;

        for (var key in {'toString': null}) {
            _hasDontEnumBug = false;
        }
    }

    /**
     * Similar to Array/forEach but works over object properties and fixes Don't
     * Enum bug on IE.
     * based on: http://whattheheadsaid.com/2010/10/a-safer-object-keys-compatibility-implementation
     */
    function forIn(obj, fn, thisObj){
        var key, i = 0;
        // no need to check if argument is a real object that way we can use
        // it for arrays, functions, date, etc.

        //post-pone check till needed
        if (_hasDontEnumBug == null) checkDontEnum();

        for (key in obj) {
            if (exec(fn, obj, key, thisObj) === false) {
                break;
            }
        }


        if (_hasDontEnumBug) {
            var ctor = obj.constructor,
                isProto = !!ctor && obj === ctor.prototype;

            while (key = _dontEnums[i++]) {
                // For constructor, if it is a prototype object the constructor
                // is always non-enumerable unless defined otherwise (and
                // enumerated above).  For non-prototype objects, it will have
                // to be defined on this object, since it cannot be defined on
                // any prototype objects.
                //
                // For other [[DontEnum]] properties, check if the value is
                // different than Object prototype value.
                if (
                    (key !== 'constructor' ||
                        (!isProto && hasOwn(obj, key))) &&
                    obj[key] !== Object.prototype[key]
                ) {
                    if (exec(fn, obj, key, thisObj) === false) {
                        break;
                    }
                }
            }
        }
    }

    function exec(fn, obj, key, thisObj){
        return fn.call(thisObj, obj[key], key, obj);
    }

    module.exports = forIn;



},{"./hasOwn":30}],29:[function(require,module,exports){
var hasOwn = require('./hasOwn');
var forIn = require('./forIn');

    /**
     * Similar to Array/forEach but works over object properties and fixes Don't
     * Enum bug on IE.
     * based on: http://whattheheadsaid.com/2010/10/a-safer-object-keys-compatibility-implementation
     */
    function forOwn(obj, fn, thisObj){
        forIn(obj, function(val, key){
            if (hasOwn(obj, key)) {
                return fn.call(thisObj, obj[key], key, obj);
            }
        });
    }

    module.exports = forOwn;



},{"./forIn":28,"./hasOwn":30}],30:[function(require,module,exports){


    /**
     * Safer Object.hasOwnProperty
     */
     function hasOwn(obj, prop){
         return Object.prototype.hasOwnProperty.call(obj, prop);
     }

     module.exports = hasOwn;



},{}],31:[function(require,module,exports){

    /**
     * Contains all Unicode white-spaces. Taken from
     * http://en.wikipedia.org/wiki/Whitespace_character.
     */
    module.exports = [
        ' ', '\n', '\r', '\t', '\f', '\v', '\u00A0', '\u1680', '\u180E',
        '\u2000', '\u2001', '\u2002', '\u2003', '\u2004', '\u2005', '\u2006',
        '\u2007', '\u2008', '\u2009', '\u200A', '\u2028', '\u2029', '\u202F',
        '\u205F', '\u3000'
    ];


},{}],32:[function(require,module,exports){
var toString = require('../lang/toString');
var WHITE_SPACES = require('./WHITE_SPACES');
    /**
     * Remove chars from beginning of string.
     */
    function ltrim(str, chars) {
        str = toString(str);
        chars = chars || WHITE_SPACES;

        var start = 0,
            len = str.length,
            charLen = chars.length,
            found = true,
            i, c;

        while (found && start < len) {
            found = false;
            i = -1;
            c = str.charAt(start);

            while (++i < charLen) {
                if (c === chars[i]) {
                    found = true;
                    start++;
                    break;
                }
            }
        }

        return (start >= len) ? '' : str.substr(start, len);
    }

    module.exports = ltrim;


},{"../lang/toString":26,"./WHITE_SPACES":31}],33:[function(require,module,exports){
var toString = require('../lang/toString');
var WHITE_SPACES = require('./WHITE_SPACES');
    /**
     * Remove chars from end of string.
     */
    function rtrim(str, chars) {
        str = toString(str);
        chars = chars || WHITE_SPACES;

        var end = str.length - 1,
            charLen = chars.length,
            found = true,
            i, c;

        while (found && end >= 0) {
            found = false;
            i = -1;
            c = str.charAt(end);

            while (++i < charLen) {
                if (c === chars[i]) {
                    found = true;
                    end--;
                    break;
                }
            }
        }

        return (end >= 0) ? str.substring(0, end + 1) : '';
    }

    module.exports = rtrim;


},{"../lang/toString":26,"./WHITE_SPACES":31}],34:[function(require,module,exports){
var toString = require('../lang/toString');
var WHITE_SPACES = require('./WHITE_SPACES');
var ltrim = require('./ltrim');
var rtrim = require('./rtrim');
    /**
     * Remove white-spaces from beginning and end of string.
     */
    function trim(str, chars) {
        str = toString(str);
        chars = chars || WHITE_SPACES;
        return ltrim(rtrim(str, chars), chars);
    }

    module.exports = trim;


},{"../lang/toString":26,"./WHITE_SPACES":31,"./ltrim":32,"./rtrim":33}],35:[function(require,module,exports){
/*
traversal
*/"use strict"

var map = require("mout/array/map")

var slick = require("slick")

var $ = require("./base")

var gen = function(combinator, expression){
    return map(slick.parse(expression || "*"), function(part){
        return combinator + " " + part
    }).join(", ")
}

var push_ = Array.prototype.push

$.implement({

    search: function(expression){
        if (this.length === 1) return $(slick.search(expression, this[0], new $))

        var buffer = []
        for (var i = 0, node; node = this[i]; i++) push_.apply(buffer, slick.search(expression, node))
        buffer = $(buffer)
        return buffer && buffer.sort()
    },

    find: function(expression){
        if (this.length === 1) return $(slick.find(expression, this[0]))

        for (var i = 0, node; node = this[i]; i++) {
            var found = slick.find(expression, node)
            if (found) return $(found)
        }

        return null
    },

    sort: function(){
        return slick.sort(this)
    },

    matches: function(expression){
        return slick.matches(this[0], expression)
    },

    contains: function(node){
        return slick.contains(this[0], node)
    },

    nextSiblings: function(expression){
        return this.search(gen('~', expression))
    },

    nextSibling: function(expression){
        return this.find(gen('+', expression))
    },

    previousSiblings: function(expression){
        return this.search(gen('!~', expression))
    },

    previousSibling: function(expression){
        return this.find(gen('!+', expression))
    },

    children: function(expression){
        return this.search(gen('>', expression))
    },

    firstChild: function(expression){
        return this.find(gen('^', expression))
    },

    lastChild: function(expression){
        return this.find(gen('!^', expression))
    },

    parent: function(expression){
        var buffer = []
        loop: for (var i = 0, node; node = this[i]; i++) while ((node = node.parentNode) && (node !== document)){
            if (!expression || slick.matches(node, expression)){
                buffer.push(node)
                break loop
                break
            }
        }
        return $(buffer)
    },

    parents: function(expression){
        var buffer = []
        for (var i = 0, node; node = this[i]; i++) while ((node = node.parentNode) && (node !== document)){
            if (!expression || slick.matches(node, expression)) buffer.push(node)
        }
        return $(buffer)
    }

})

module.exports = $

},{"./base":9,"mout/array/map":18,"slick":97}],36:[function(require,module,exports){
/*
zen
*/"use strict"

var forEach = require("mout/array/forEach"),
    map     = require("mout/array/map")

var parse = require("slick/parser")

var $ = require("./base")

module.exports = function(expression, doc){

    return $(map(parse(expression), function(expression){

        var previous, result

        forEach(expression, function(part, i){

            var node = (doc || document).createElement(part.tag)

            if (part.id) node.id = part.id

            if (part.classList) node.className = part.classList.join(" ")

            if (part.attributes) forEach(part.attributes, function(attribute){
                node.setAttribute(attribute.name, attribute.value || "")
            })

            if (part.pseudos) forEach(part.pseudos, function(pseudo){
                var n = $(node), method = n[pseudo.name]
                if (method) method.call(n, pseudo.value)
            })

            if (i === 0){

                result = node

            } else if (part.combinator === " "){

                previous.appendChild(node)

            } else if (part.combinator === "+"){
                var parentNode = previous.parentNode
                if (parentNode) parentNode.appendChild(node)
            }

            previous = node

        })

        return result

    }))

}

},{"./base":9,"mout/array/forEach":16,"mout/array/map":18,"slick/parser":98}],37:[function(require,module,exports){
arguments[4][16][0].apply(exports,arguments)
},{"dup":16}],38:[function(require,module,exports){
arguments[4][18][0].apply(exports,arguments)
},{"../function/makeIterator_":42,"dup":18}],39:[function(require,module,exports){


    /**
     * Create slice of source array or array-like object
     */
    function slice(arr, start, end){
        var len = arr.length;

        if (start == null) {
            start = 0;
        } else if (start < 0) {
            start = Math.max(len + start, 0);
        } else {
            start = Math.min(start, len);
        }

        if (end == null) {
            end = len;
        } else if (end < 0) {
            end = Math.max(len + end, 0);
        } else {
            end = Math.min(end, len);
        }

        var result = [];
        while (start < end) {
            result.push(arr[start++]);
        }

        return result;
    }

    module.exports = slice;



},{}],40:[function(require,module,exports){
var slice = require('../array/slice');

    /**
     * Return a function that will execute in the given context, optionally adding any additional supplied parameters to the beginning of the arguments collection.
     * @param {Function} fn  Function.
     * @param {object} context   Execution context.
     * @param {rest} args    Arguments (0...n arguments).
     * @return {Function} Wrapped Function.
     */
    function bind(fn, context, args){
        var argsArr = slice(arguments, 2); //curried args
        return function(){
            return fn.apply(context, argsArr.concat(slice(arguments)));
        };
    }

    module.exports = bind;



},{"../array/slice":39}],41:[function(require,module,exports){
arguments[4][20][0].apply(exports,arguments)
},{"dup":20}],42:[function(require,module,exports){
arguments[4][21][0].apply(exports,arguments)
},{"../object/deepMatches":53,"./identity":41,"./prop":43,"dup":21}],43:[function(require,module,exports){
arguments[4][22][0].apply(exports,arguments)
},{"dup":22}],44:[function(require,module,exports){
var slice = require('../array/slice');

    /**
     * Delays the call of a function within a given context.
     */
    function timeout(fn, millis, context){

        var args = slice(arguments, 3);

        return setTimeout(function() {
            fn.apply(context, args);
        }, millis);
    }

    module.exports = timeout;



},{"../array/slice":39}],45:[function(require,module,exports){
arguments[4][23][0].apply(exports,arguments)
},{"./isKind":46,"dup":23}],46:[function(require,module,exports){
arguments[4][24][0].apply(exports,arguments)
},{"./kindOf":47,"dup":24}],47:[function(require,module,exports){
arguments[4][25][0].apply(exports,arguments)
},{"dup":25}],48:[function(require,module,exports){
arguments[4][26][0].apply(exports,arguments)
},{"dup":26}],49:[function(require,module,exports){

    /**
     * Clamps value inside range.
     */
    function clamp(val, min, max){
        return val < min? min : (val > max? max : val);
    }
    module.exports = clamp;


},{}],50:[function(require,module,exports){

    /**
    * Linear interpolation.
    * IMPORTANT:will return `Infinity` if numbers overflow Number.MAX_VALUE
    */
    function lerp(ratio, start, end){
        return start + (end - start) * ratio;
    }

    module.exports = lerp;


},{}],51:[function(require,module,exports){
var lerp = require('./lerp');
var norm = require('./norm');
    /**
    * Maps a number from one scale to another.
    * @example map(3, 0, 4, -1, 1) -> 0.5
    */
    function map(val, min1, max1, min2, max2){
        return lerp( norm(val, min1, max1), min2, max2 );
    }
    module.exports = map;


},{"./lerp":50,"./norm":52}],52:[function(require,module,exports){

    /**
    * Gets normalized ratio of value inside range.
    */
    function norm(val, min, max){
        if (val < min || val > max) {
            throw new RangeError('value (' + val + ') must be between ' + min + ' and ' + max);
        }

        return val === max ? 1 : (val - min) / (max - min);
    }
    module.exports = norm;


},{}],53:[function(require,module,exports){
var forOwn = require('./forOwn');
var isArray = require('../lang/isArray');

    function containsMatch(array, pattern) {
        var i = -1, length = array.length;
        while (++i < length) {
            if (deepMatches(array[i], pattern)) {
                return true;
            }
        }

        return false;
    }

    function matchArray(target, pattern) {
        var i = -1, patternLength = pattern.length;
        while (++i < patternLength) {
            if (!containsMatch(target, pattern[i])) {
                return false;
            }
        }

        return true;
    }

    function matchObject(target, pattern) {
        var result = true;
        forOwn(pattern, function(val, key) {
            if (!deepMatches(target[key], val)) {
                // Return false to break out of forOwn early
                return (result = false);
            }
        });

        return result;
    }

    /**
     * Recursively check if the objects match.
     */
    function deepMatches(target, pattern){
        if (target && typeof target === 'object' &&
            pattern && typeof pattern === 'object') {
            if (isArray(target) && isArray(pattern)) {
                return matchArray(target, pattern);
            } else {
                return matchObject(target, pattern);
            }
        } else {
            return target === pattern;
        }
    }

    module.exports = deepMatches;



},{"../lang/isArray":45,"./forOwn":55}],54:[function(require,module,exports){
arguments[4][28][0].apply(exports,arguments)
},{"./hasOwn":56,"dup":28}],55:[function(require,module,exports){
arguments[4][29][0].apply(exports,arguments)
},{"./forIn":54,"./hasOwn":56,"dup":29}],56:[function(require,module,exports){
arguments[4][30][0].apply(exports,arguments)
},{"dup":30}],57:[function(require,module,exports){
arguments[4][31][0].apply(exports,arguments)
},{"dup":31}],58:[function(require,module,exports){
arguments[4][32][0].apply(exports,arguments)
},{"../lang/toString":48,"./WHITE_SPACES":57,"dup":32}],59:[function(require,module,exports){
arguments[4][33][0].apply(exports,arguments)
},{"../lang/toString":48,"./WHITE_SPACES":57,"dup":33}],60:[function(require,module,exports){
arguments[4][34][0].apply(exports,arguments)
},{"../lang/toString":48,"./WHITE_SPACES":57,"./ltrim":58,"./rtrim":59,"dup":34}],61:[function(require,module,exports){
arguments[4][39][0].apply(exports,arguments)
},{"dup":39}],62:[function(require,module,exports){
arguments[4][40][0].apply(exports,arguments)
},{"../array/slice":61,"dup":40}],63:[function(require,module,exports){
var kindOf = require('./kindOf');
var isPlainObject = require('./isPlainObject');
var mixIn = require('../object/mixIn');

    /**
     * Clone native types.
     */
    function clone(val){
        switch (kindOf(val)) {
            case 'Object':
                return cloneObject(val);
            case 'Array':
                return cloneArray(val);
            case 'RegExp':
                return cloneRegExp(val);
            case 'Date':
                return cloneDate(val);
            default:
                return val;
        }
    }

    function cloneObject(source) {
        if (isPlainObject(source)) {
            return mixIn({}, source);
        } else {
            return source;
        }
    }

    function cloneRegExp(r) {
        var flags = '';
        flags += r.multiline ? 'm' : '';
        flags += r.global ? 'g' : '';
        flags += r.ignorecase ? 'i' : '';
        return new RegExp(r.source, flags);
    }

    function cloneDate(date) {
        return new Date(+date);
    }

    function cloneArray(arr) {
        return arr.slice();
    }

    module.exports = clone;



},{"../object/mixIn":73,"./isPlainObject":67,"./kindOf":68}],64:[function(require,module,exports){
var clone = require('./clone');
var forOwn = require('../object/forOwn');
var kindOf = require('./kindOf');
var isPlainObject = require('./isPlainObject');

    /**
     * Recursively clone native types.
     */
    function deepClone(val, instanceClone) {
        switch ( kindOf(val) ) {
            case 'Object':
                return cloneObject(val, instanceClone);
            case 'Array':
                return cloneArray(val, instanceClone);
            default:
                return clone(val);
        }
    }

    function cloneObject(source, instanceClone) {
        if (isPlainObject(source)) {
            var out = {};
            forOwn(source, function(val, key) {
                this[key] = deepClone(val, instanceClone);
            }, out);
            return out;
        } else if (instanceClone) {
            return instanceClone(source);
        } else {
            return source;
        }
    }

    function cloneArray(arr, instanceClone) {
        var out = [],
            i = -1,
            n = arr.length,
            val;
        while (++i < n) {
            out[i] = deepClone(arr[i], instanceClone);
        }
        return out;
    }

    module.exports = deepClone;




},{"../object/forOwn":70,"./clone":63,"./isPlainObject":67,"./kindOf":68}],65:[function(require,module,exports){
arguments[4][24][0].apply(exports,arguments)
},{"./kindOf":68,"dup":24}],66:[function(require,module,exports){
var isKind = require('./isKind');
    /**
     */
    function isObject(val) {
        return isKind(val, 'Object');
    }
    module.exports = isObject;


},{"./isKind":65}],67:[function(require,module,exports){


    /**
     * Checks if the value is created by the `Object` constructor.
     */
    function isPlainObject(value) {
        return (!!value && typeof value === 'object' &&
            value.constructor === Object);
    }

    module.exports = isPlainObject;



},{}],68:[function(require,module,exports){
arguments[4][25][0].apply(exports,arguments)
},{"dup":25}],69:[function(require,module,exports){
arguments[4][28][0].apply(exports,arguments)
},{"./hasOwn":71,"dup":28}],70:[function(require,module,exports){
arguments[4][29][0].apply(exports,arguments)
},{"./forIn":69,"./hasOwn":71,"dup":29}],71:[function(require,module,exports){
arguments[4][30][0].apply(exports,arguments)
},{"dup":30}],72:[function(require,module,exports){
var hasOwn = require('./hasOwn');
var deepClone = require('../lang/deepClone');
var isObject = require('../lang/isObject');

    /**
     * Deep merge objects.
     */
    function merge() {
        var i = 1,
            key, val, obj, target;

        // make sure we don't modify source element and it's properties
        // objects are passed by reference
        target = deepClone( arguments[0] );

        while (obj = arguments[i++]) {
            for (key in obj) {
                if ( ! hasOwn(obj, key) ) {
                    continue;
                }

                val = obj[key];

                if ( isObject(val) && isObject(target[key]) ){
                    // inception, deep merge objects
                    target[key] = merge(target[key], val);
                } else {
                    // make sure arrays, regexp, date, objects are cloned
                    target[key] = deepClone(val);
                }

            }
        }

        return target;
    }

    module.exports = merge;



},{"../lang/deepClone":64,"../lang/isObject":66,"./hasOwn":71}],73:[function(require,module,exports){
var forOwn = require('./forOwn');

    /**
    * Combine properties from all the objects into first one.
    * - This method affects target object in place, if you want to create a new Object pass an empty object as first param.
    * @param {object} target    Target Object
    * @param {...object} objects    Objects to be combined (0...n objects).
    * @return {object} Target Object.
    */
    function mixIn(target, objects){
        var i = 0,
            n = arguments.length,
            obj;
        while(++i < n){
            obj = arguments[i];
            if (obj != null) {
                forOwn(obj, copyProp, target);
            }
        }
        return target;
    }

    function copyProp(val, key){
        this[key] = val;
    }

    module.exports = mixIn;


},{"./forOwn":70}],74:[function(require,module,exports){
/*
prime
 - prototypal inheritance
*/"use strict"

var hasOwn = require("mout/object/hasOwn"),
    mixIn  = require("mout/object/mixIn"),
    create = require("mout/lang/createObject"),
    kindOf = require("mout/lang/kindOf")

var hasDescriptors = true

try {
    Object.defineProperty({}, "~", {})
    Object.getOwnPropertyDescriptor({}, "~")
} catch (e){
    hasDescriptors = false
}

// we only need to be able to implement "toString" and "valueOf" in IE < 9
var hasEnumBug = !({valueOf: 0}).propertyIsEnumerable("valueOf"),
    buggy      = ["toString", "valueOf"]

var verbs = /^constructor|inherits|mixin$/

var implement = function(proto){
    var prototype = this.prototype

    for (var key in proto){
        if (key.match(verbs)) continue
        if (hasDescriptors){
            var descriptor = Object.getOwnPropertyDescriptor(proto, key)
            if (descriptor){
                Object.defineProperty(prototype, key, descriptor)
                continue
            }
        }
        prototype[key] = proto[key]
    }

    if (hasEnumBug) for (var i = 0; (key = buggy[i]); i++){
        var value = proto[key]
        if (value !== Object.prototype[key]) prototype[key] = value
    }

    return this
}

var prime = function(proto){

    if (kindOf(proto) === "Function") proto = {constructor: proto}

    var superprime = proto.inherits

    // if our nice proto object has no own constructor property
    // then we proceed using a ghosting constructor that all it does is
    // call the parent's constructor if it has a superprime, else an empty constructor
    // proto.constructor becomes the effective constructor
    var constructor = (hasOwn(proto, "constructor")) ? proto.constructor : (superprime) ? function(){
        return superprime.apply(this, arguments)
    } : function(){}

    if (superprime){

        mixIn(constructor, superprime)

        var superproto = superprime.prototype
        // inherit from superprime
        var cproto = constructor.prototype = create(superproto)

        // setting constructor.parent to superprime.prototype
        // because it's the shortest possible absolute reference
        constructor.parent = superproto
        cproto.constructor = constructor
    }

    if (!constructor.implement) constructor.implement = implement

    var mixins = proto.mixin
    if (mixins){
        if (kindOf(mixins) !== "Array") mixins = [mixins]
        for (var i = 0; i < mixins.length; i++) constructor.implement(create(mixins[i].prototype))
    }

    // implement proto and return constructor
    return constructor.implement(proto)

}

module.exports = prime

},{"mout/lang/createObject":75,"mout/lang/kindOf":76,"mout/object/hasOwn":79,"mout/object/mixIn":80}],75:[function(require,module,exports){
var mixIn = require('../object/mixIn');

    /**
     * Create Object using prototypal inheritance and setting custom properties.
     * - Mix between Douglas Crockford Prototypal Inheritance <http://javascript.crockford.com/prototypal.html> and the EcmaScript 5 `Object.create()` method.
     * @param {object} parent    Parent Object.
     * @param {object} [props] Object properties.
     * @return {object} Created object.
     */
    function createObject(parent, props){
        function F(){}
        F.prototype = parent;
        return mixIn(new F(), props);

    }
    module.exports = createObject;



},{"../object/mixIn":80}],76:[function(require,module,exports){
arguments[4][25][0].apply(exports,arguments)
},{"dup":25}],77:[function(require,module,exports){
arguments[4][28][0].apply(exports,arguments)
},{"./hasOwn":79,"dup":28}],78:[function(require,module,exports){
arguments[4][29][0].apply(exports,arguments)
},{"./forIn":77,"./hasOwn":79,"dup":29}],79:[function(require,module,exports){
arguments[4][30][0].apply(exports,arguments)
},{"dup":30}],80:[function(require,module,exports){
arguments[4][73][0].apply(exports,arguments)
},{"./forOwn":78,"dup":73}],81:[function(require,module,exports){
"use strict";

// credits to @cpojer's Class.Binds, released under the MIT license
// https://github.com/cpojer/mootools-class-extras/blob/master/Source/Class.Binds.js

var prime = require("prime")
var bind = require("mout/function/bind")

var bound = prime({

    bound: function(name){
        var bound = this._bound || (this._bound = {})
        return bound[name] || (bound[name] = bind(this[name], this))
    }

})

module.exports = bound

},{"mout/function/bind":62,"prime":74}],82:[function(require,module,exports){
"use strict";

var prime = require("prime")
var merge = require("mout/object/merge")

var Options = prime({

    setOptions: function(options){
        var args = [{}, this.options]
        args.push.apply(args, arguments)
        this.options = merge.apply(null, args)
        return this
    }

})

module.exports = Options

},{"mout/object/merge":72,"prime":74}],83:[function(require,module,exports){
(function (process,global){
/*
defer
*/"use strict"

var kindOf  = require("mout/lang/kindOf"),
    now     = require("mout/time/now"),
    forEach = require("mout/array/forEach"),
    indexOf = require("mout/array/indexOf")

var callbacks = {
    timeout: {},
    frame: [],
    immediate: []
}

var push = function(collection, callback, context, defer){

    var iterator = function(){
        iterate(collection)
    }

    if (!collection.length) defer(iterator)

    var entry = {
        callback: callback,
        context: context
    }

    collection.push(entry)

    return function(){
        var io = indexOf(collection, entry)
        if (io > -1) collection.splice(io, 1)
    }
}

var iterate = function(collection){
    var time = now()

    forEach(collection.splice(0), function(entry) {
        entry.callback.call(entry.context, time)
    })
}

var defer = function(callback, argument, context){
    return (kindOf(argument) === "Number") ? defer.timeout(callback, argument, context) : defer.immediate(callback, argument)
}

if (global.process && process.nextTick){

    defer.immediate = function(callback, context){
        return push(callbacks.immediate, callback, context, process.nextTick)
    }

} else if (global.setImmediate){

    defer.immediate = function(callback, context){
        return push(callbacks.immediate, callback, context, setImmediate)
    }

} else if (global.postMessage && global.addEventListener){

    addEventListener("message", function(event){
        if (event.source === global && event.data === "@deferred"){
            event.stopPropagation()
            iterate(callbacks.immediate)
        }
    }, true)

    defer.immediate = function(callback, context){
        return push(callbacks.immediate, callback, context, function(){
            postMessage("@deferred", "*")
        })
    }

} else {

    defer.immediate = function(callback, context){
        return push(callbacks.immediate, callback, context, function(iterator){
            setTimeout(iterator, 0)
        })
    }

}

var requestAnimationFrame = global.requestAnimationFrame ||
    global.webkitRequestAnimationFrame ||
    global.mozRequestAnimationFrame ||
    global.oRequestAnimationFrame ||
    global.msRequestAnimationFrame ||
    function(callback) {
        setTimeout(callback, 1e3 / 60)
    }

defer.frame = function(callback, context){
    return push(callbacks.frame, callback, context, requestAnimationFrame)
}

var clear

defer.timeout = function(callback, ms, context){
    var ct = callbacks.timeout

    if (!clear) clear = defer.immediate(function(){
        clear = null
        callbacks.timeout = {}
    })

    return push(ct[ms] || (ct[ms] = []), callback, context, function(iterator){
        setTimeout(iterator, ms)
    })
}

module.exports = defer

}).call(this,require('_process'),typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"_process":99,"mout/array/forEach":87,"mout/array/indexOf":88,"mout/lang/kindOf":90,"mout/time/now":95}],84:[function(require,module,exports){
/*
Emitter
*/"use strict"

var indexOf = require("mout/array/indexOf"),
    forEach = require("mout/array/forEach")

var prime = require("./index"),
    defer = require("./defer")

var slice = Array.prototype.slice;

var Emitter = prime({

    constructor: function(stoppable){
        this._stoppable = stoppable
    },

    on: function(event, fn){
        var listeners = this._listeners || (this._listeners = {}),
            events = listeners[event] || (listeners[event] = [])

        if (indexOf(events, fn) === -1) events.push(fn)

        return this
    },

    off: function(event, fn){
        var listeners = this._listeners, events
        if (listeners && (events = listeners[event])){

            var io = indexOf(events, fn)
            if (io > -1) events.splice(io, 1)
            if (!events.length) delete listeners[event];
            for (var l in listeners) return this
            delete this._listeners
        }
        return this
    },

    emit: function(event){
        var self = this,
            args = slice.call(arguments, 1)

        var emit = function(){
            var listeners = self._listeners, events
            if (listeners && (events = listeners[event])){
                forEach(events.slice(0), function(event){
                    var result = event.apply(self, args)
                    if (self._stoppable) return result
                })
            }
        }

        if (args[args.length - 1] === Emitter.EMIT_SYNC){
            args.pop()
            emit()
        } else {
            defer(emit)
        }

        return this
    }

})

Emitter.EMIT_SYNC = {}

module.exports = Emitter

},{"./defer":83,"./index":85,"mout/array/forEach":87,"mout/array/indexOf":88}],85:[function(require,module,exports){
/*
prime
 - prototypal inheritance
*/"use strict"

var hasOwn = require("mout/object/hasOwn"),
    mixIn  = require("mout/object/mixIn"),
    create = require("mout/lang/createObject"),
    kindOf = require("mout/lang/kindOf")

var hasDescriptors = true

try {
    Object.defineProperty({}, "~", {})
    Object.getOwnPropertyDescriptor({}, "~")
} catch (e){
    hasDescriptors = false
}

// we only need to be able to implement "toString" and "valueOf" in IE < 9
var hasEnumBug = !({valueOf: 0}).propertyIsEnumerable("valueOf"),
    buggy      = ["toString", "valueOf"]

var verbs = /^constructor|inherits|mixin$/

var implement = function(proto){
    var prototype = this.prototype

    for (var key in proto){
        if (key.match(verbs)) continue
        if (hasDescriptors){
            var descriptor = Object.getOwnPropertyDescriptor(proto, key)
            if (descriptor){
                Object.defineProperty(prototype, key, descriptor)
                continue
            }
        }
        prototype[key] = proto[key]
    }

    if (hasEnumBug) for (var i = 0; (key = buggy[i]); i++){
        var value = proto[key]
        if (value !== Object.prototype[key]) prototype[key] = value
    }

    return this
}

var prime = function(proto){

    if (kindOf(proto) === "Function") proto = {constructor: proto}

    var superprime = proto.inherits

    // if our nice proto object has no own constructor property
    // then we proceed using a ghosting constructor that all it does is
    // call the parent's constructor if it has a superprime, else an empty constructor
    // proto.constructor becomes the effective constructor
    var constructor = (hasOwn(proto, "constructor")) ? proto.constructor : (superprime) ? function(){
        return superprime.apply(this, arguments)
    } : function(){}

    if (superprime){

        mixIn(constructor, superprime)

        var superproto = superprime.prototype
        // inherit from superprime
        var cproto = constructor.prototype = create(superproto)

        // setting constructor.parent to superprime.prototype
        // because it's the shortest possible absolute reference
        constructor.parent = superproto
        cproto.constructor = constructor
    }

    if (!constructor.implement) constructor.implement = implement

    var mixins = proto.mixin
    if (mixins){
        if (kindOf(mixins) !== "Array") mixins = [mixins]
        for (var i = 0; i < mixins.length; i++) constructor.implement(create(mixins[i].prototype))
    }

    // implement proto and return constructor
    return constructor.implement(proto)

}

module.exports = prime

},{"mout/lang/createObject":89,"mout/lang/kindOf":90,"mout/object/hasOwn":93,"mout/object/mixIn":94}],86:[function(require,module,exports){
/*
Map
*/"use strict"

var indexOf = require("mout/array/indexOf")

var prime = require("./index")

var Map = prime({

    constructor: function Map(){
        this.length = 0
        this._values = []
        this._keys = []
    },

    set: function(key, value){
        var index = indexOf(this._keys, key)

        if (index === -1){
            this._keys.push(key)
            this._values.push(value)
            this.length++
        } else {
            this._values[index] = value
        }

        return this
    },

    get: function(key){
        var index = indexOf(this._keys, key)
        return (index === -1) ? null : this._values[index]
    },

    count: function(){
        return this.length
    },

    forEach: function(method, context){
        for (var i = 0, l = this.length; i < l; i++){
            if (method.call(context, this._values[i], this._keys[i], this) === false) break
        }
        return this
    },

    map: function(method, context){
        var results = new Map
        this.forEach(function(value, key){
            results.set(key, method.call(context, value, key, this))
        }, this)
        return results
    },

    filter: function(method, context){
        var results = new Map
        this.forEach(function(value, key){
            if (method.call(context, value, key, this)) results.set(key, value)
        }, this)
        return results
    },

    every: function(method, context){
        var every = true
        this.forEach(function(value, key){
            if (!method.call(context, value, key, this)) return (every = false)
        }, this)
        return every
    },

    some: function(method, context){
        var some = false
        this.forEach(function(value, key){
            if (method.call(context, value, key, this)) return !(some = true)
        }, this)
        return some
    },

    indexOf: function(value){
        var index = indexOf(this._values, value)
        return (index > -1) ? this._keys[index] : null
    },

    remove: function(value){
        var index = indexOf(this._values, value)

        if (index !== -1){
            this._values.splice(index, 1)
            this.length--
            return this._keys.splice(index, 1)[0]
        }

        return null
    },

    unset: function(key){
        var index = indexOf(this._keys, key)

        if (index !== -1){
            this._keys.splice(index, 1)
            this.length--
            return this._values.splice(index, 1)[0]
        }

        return null
    },

    keys: function(){
        return this._keys.slice()
    },

    values: function(){
        return this._values.slice()
    }

})

var map = function(){
    return new Map
}

map.prototype = Map.prototype

module.exports = map

},{"./index":85,"mout/array/indexOf":88}],87:[function(require,module,exports){
arguments[4][16][0].apply(exports,arguments)
},{"dup":16}],88:[function(require,module,exports){
arguments[4][17][0].apply(exports,arguments)
},{"dup":17}],89:[function(require,module,exports){
arguments[4][75][0].apply(exports,arguments)
},{"../object/mixIn":94,"dup":75}],90:[function(require,module,exports){
arguments[4][25][0].apply(exports,arguments)
},{"dup":25}],91:[function(require,module,exports){
arguments[4][28][0].apply(exports,arguments)
},{"./hasOwn":93,"dup":28}],92:[function(require,module,exports){
arguments[4][29][0].apply(exports,arguments)
},{"./forIn":91,"./hasOwn":93,"dup":29}],93:[function(require,module,exports){
arguments[4][30][0].apply(exports,arguments)
},{"dup":30}],94:[function(require,module,exports){
arguments[4][73][0].apply(exports,arguments)
},{"./forOwn":92,"dup":73}],95:[function(require,module,exports){


    /**
     * Get current time in miliseconds
     */
    function now(){
        // yes, we defer the work to another function to allow mocking it
        // during the tests
        return now.get();
    }

    now.get = (typeof Date.now === 'function')? Date.now : function(){
        return +(new Date());
    };

    module.exports = now;



},{}],96:[function(require,module,exports){
/*
Slick Finder
*/"use strict"

// Notable changes from Slick.Finder 1.0.x

// faster bottom -> up expression matching
// prefers mental sanity over *obsessive compulsive* milliseconds savings
// uses prototypes instead of objects
// tries to use matchesSelector smartly, whenever available
// can populate objects as well as arrays
// lots of stuff is broken or not implemented

var parse = require("./parser")

// utilities

var index = 0,
    counter = document.__counter = (parseInt(document.__counter || -1, 36) + 1).toString(36),
    key = "uid:" + counter

var uniqueID = function(n, xml){
    if (n === window) return "window"
    if (n === document) return "document"
    if (n === document.documentElement) return "html"

    if (xml) {
        var uid = n.getAttribute(key)
        if (!uid) {
            uid = (index++).toString(36)
            n.setAttribute(key, uid)
        }
        return uid
    } else {
        return n[key] || (n[key] = (index++).toString(36))
    }
}

var uniqueIDXML = function(n) {
    return uniqueID(n, true)
}

var isArray = Array.isArray || function(object){
    return Object.prototype.toString.call(object) === "[object Array]"
}

// tests

var uniqueIndex = 0;

var HAS = {

    GET_ELEMENT_BY_ID: function(test, id){
        id = "slick_" + (uniqueIndex++);
        // checks if the document has getElementById, and it works
        test.innerHTML = '<a id="' + id + '"></a>'
        return !!this.getElementById(id)
    },

    QUERY_SELECTOR: function(test){
        // this supposedly fixes a webkit bug with matchesSelector / querySelector & nth-child
        test.innerHTML = '_<style>:nth-child(2){}</style>'

        // checks if the document has querySelectorAll, and it works
        test.innerHTML = '<a class="MiX"></a>'

        return test.querySelectorAll('.MiX').length === 1
    },

    EXPANDOS: function(test, id){
        id = "slick_" + (uniqueIndex++);
        // checks if the document has elements that support expandos
        test._custom_property_ = id
        return test._custom_property_ === id
    },

    // TODO: use this ?

    // CHECKED_QUERY_SELECTOR: function(test){
    //
    //     // checks if the document supports the checked query selector
    //     test.innerHTML = '<select><option selected="selected">a</option></select>'
    //     return test.querySelectorAll(':checked').length === 1
    // },

    // TODO: use this ?

    // EMPTY_ATTRIBUTE_QUERY_SELECTOR: function(test){
    //
    //     // checks if the document supports the empty attribute query selector
    //     test.innerHTML = '<a class=""></a>'
    //     return test.querySelectorAll('[class*=""]').length === 1
    // },

    MATCHES_SELECTOR: function(test){

        test.className = "MiX"

        // checks if the document has matchesSelector, and we can use it.

        var matches = test.matchesSelector || test.mozMatchesSelector || test.webkitMatchesSelector

        // if matchesSelector trows errors on incorrect syntax we can use it
        if (matches) try {
            matches.call(test, ':slick')
        } catch(e){
            // just as a safety precaution, also test if it works on mixedcase (like querySelectorAll)
            return matches.call(test, ".MiX") ? matches : false
        }

        return false
    },

    GET_ELEMENTS_BY_CLASS_NAME: function(test){
        test.innerHTML = '<a class="f"></a><a class="b"></a>'
        if (test.getElementsByClassName('b').length !== 1) return false

        test.firstChild.className = 'b'
        if (test.getElementsByClassName('b').length !== 2) return false

        // Opera 9.6 getElementsByClassName doesnt detects the class if its not the first one
        test.innerHTML = '<a class="a"></a><a class="f b a"></a>'
        if (test.getElementsByClassName('a').length !== 2) return false

        // tests passed
        return true
    },

    // no need to know

    // GET_ELEMENT_BY_ID_NOT_NAME: function(test, id){
    //     test.innerHTML = '<a name="'+ id +'"></a><b id="'+ id +'"></b>'
    //     return this.getElementById(id) !== test.firstChild
    // },

    // this is always checked for and fixed

    // STAR_GET_ELEMENTS_BY_TAG_NAME: function(test){
    //
    //     // IE returns comment nodes for getElementsByTagName('*') for some documents
    //     test.appendChild(this.createComment(''))
    //     if (test.getElementsByTagName('*').length > 0) return false
    //
    //     // IE returns closed nodes (EG:"</foo>") for getElementsByTagName('*') for some documents
    //     test.innerHTML = 'foo</foo>'
    //     if (test.getElementsByTagName('*').length) return false
    //
    //     // tests passed
    //     return true
    // },

    // this is always checked for and fixed

    // STAR_QUERY_SELECTOR: function(test){
    //
    //     // returns closed nodes (EG:"</foo>") for querySelector('*') for some documents
    //     test.innerHTML = 'foo</foo>'
    //     return !!(test.querySelectorAll('*').length)
    // },

    GET_ATTRIBUTE: function(test){
        // tests for working getAttribute implementation
        var shout = "fus ro dah"
        test.innerHTML = '<a class="' + shout + '"></a>'
        return test.firstChild.getAttribute('class') === shout
    }

}

// Finder

var Finder = function Finder(document){

    this.document        = document
    var root = this.root = document.documentElement
    this.tested          = {}

    // uniqueID

    this.uniqueID = this.has("EXPANDOS") ? uniqueID : uniqueIDXML

    // getAttribute

    this.getAttribute = (this.has("GET_ATTRIBUTE")) ? function(node, name){

        return node.getAttribute(name)

    } : function(node, name){

        node = node.getAttributeNode(name)
        return (node && node.specified) ? node.value : null

    }

    // hasAttribute

    this.hasAttribute = (root.hasAttribute) ? function(node, attribute){

        return node.hasAttribute(attribute)

    } : function(node, attribute) {

        node = node.getAttributeNode(attribute)
        return !!(node && node.specified)

    }

    // contains

    this.contains = (document.contains && root.contains) ? function(context, node){

        return context.contains(node)

    } : (root.compareDocumentPosition) ? function(context, node){

        return context === node || !!(context.compareDocumentPosition(node) & 16)

    } : function(context, node){

        do {
            if (node === context) return true
        } while ((node = node.parentNode))

        return false
    }

    // sort
    // credits to Sizzle (http://sizzlejs.com/)

    this.sorter = (root.compareDocumentPosition) ? function(a, b){

        if (!a.compareDocumentPosition || !b.compareDocumentPosition) return 0
        return a.compareDocumentPosition(b) & 4 ? -1 : a === b ? 0 : 1

    } : ('sourceIndex' in root) ? function(a, b){

        if (!a.sourceIndex || !b.sourceIndex) return 0
        return a.sourceIndex - b.sourceIndex

    } : (document.createRange) ? function(a, b){

        if (!a.ownerDocument || !b.ownerDocument) return 0
        var aRange = a.ownerDocument.createRange(),
            bRange = b.ownerDocument.createRange()

        aRange.setStart(a, 0)
        aRange.setEnd(a, 0)
        bRange.setStart(b, 0)
        bRange.setEnd(b, 0)
        return aRange.compareBoundaryPoints(Range.START_TO_END, bRange)

    } : null

    this.failed = {}

    var nativeMatches = this.has("MATCHES_SELECTOR")

    if (nativeMatches) this.matchesSelector = function(node, expression){

        if (this.failed[expression]) return null

        try {
            return nativeMatches.call(node, expression)
        } catch(e){
            if (slick.debug) console.warn("matchesSelector failed on " + expression)
            this.failed[expression] = true
            return null
        }

    }

    if (this.has("QUERY_SELECTOR")){

        this.querySelectorAll = function(node, expression){

            if (this.failed[expression]) return true

            var result, _id, _expression, _combinator, _node


            // non-document rooted QSA
            // credits to Andrew Dupont

            if (node !== this.document){

                _combinator = expression[0].combinator

                _id         = node.getAttribute("id")
                _expression = expression

                if (!_id){
                    _node = node
                    _id = "__slick__"
                    _node.setAttribute("id", _id)
                }

                expression = "#" + _id + " " + _expression


                // these combinators need a parentNode due to how querySelectorAll works, which is:
                // finding all the elements that match the given selector
                // then filtering by the ones that have the specified element as an ancestor
                if (_combinator.indexOf("~") > -1 || _combinator.indexOf("+") > -1){

                    node = node.parentNode
                    if (!node) result = true
                    // if node has no parentNode, we return "true" as if it failed, without polluting the failed cache

                }

            }

            if (!result) try {
                result = node.querySelectorAll(expression.toString())
            } catch(e){
                if (slick.debug) console.warn("querySelectorAll failed on " + (_expression || expression))
                result = this.failed[_expression || expression] = true
            }

            if (_node) _node.removeAttribute("id")

            return result

        }

    }

}

Finder.prototype.has = function(FEATURE){

    var tested        = this.tested,
        testedFEATURE = tested[FEATURE]

    if (testedFEATURE != null) return testedFEATURE

    var root     = this.root,
        document = this.document,
        testNode = document.createElement("div")

    testNode.setAttribute("style", "display: none;")

    root.appendChild(testNode)

    var TEST = HAS[FEATURE], result = false

    if (TEST) try {
        result = TEST.call(document, testNode)
    } catch(e){}

    if (slick.debug && !result) console.warn("document has no " + FEATURE)

    root.removeChild(testNode)

    return tested[FEATURE] = result

}

var combinators = {

    " ": function(node, part, push){

        var item, items

        var noId = !part.id, noTag = !part.tag, noClass = !part.classes

        if (part.id && node.getElementById && this.has("GET_ELEMENT_BY_ID")){
            item = node.getElementById(part.id)

            // return only if id is found, else keep checking
            // might be a tad slower on non-existing ids, but less insane

            if (item && item.getAttribute('id') === part.id){
                items = [item]
                noId = true
                // if tag is star, no need to check it in match()
                if (part.tag === "*") noTag = true
            }
        }

        if (!items){

            if (part.classes && node.getElementsByClassName && this.has("GET_ELEMENTS_BY_CLASS_NAME")){
                items = node.getElementsByClassName(part.classList)
                noClass = true
                // if tag is star, no need to check it in match()
                if (part.tag === "*") noTag = true
            } else {
                items = node.getElementsByTagName(part.tag)
                // if tag is star, need to check it in match because it could select junk, boho
                if (part.tag !== "*") noTag = true
            }

            if (!items || !items.length) return false

        }

        for (var i = 0; item = items[i++];)
            if ((noTag && noId && noClass && !part.attributes && !part.pseudos) || this.match(item, part, noTag, noId, noClass))
                push(item)

        return true

    },

    ">": function(node, part, push){ // direct children
        if ((node = node.firstChild)) do {
            if (node.nodeType == 1 && this.match(node, part)) push(node)
        } while ((node = node.nextSibling))
    },

    "+": function(node, part, push){ // next sibling
        while ((node = node.nextSibling)) if (node.nodeType == 1){
            if (this.match(node, part)) push(node)
            break
        }
    },

    "^": function(node, part, push){ // first child
        node = node.firstChild
        if (node){
            if (node.nodeType === 1){
                if (this.match(node, part)) push(node)
            } else {
                combinators['+'].call(this, node, part, push)
            }
        }
    },

    "~": function(node, part, push){ // next siblings
        while ((node = node.nextSibling)){
            if (node.nodeType === 1 && this.match(node, part)) push(node)
        }
    },

    "++": function(node, part, push){ // next sibling and previous sibling
        combinators['+'].call(this, node, part, push)
        combinators['!+'].call(this, node, part, push)
    },

    "~~": function(node, part, push){ // next siblings and previous siblings
        combinators['~'].call(this, node, part, push)
        combinators['!~'].call(this, node, part, push)
    },

    "!": function(node, part, push){ // all parent nodes up to document
        while ((node = node.parentNode)) if (node !== this.document && this.match(node, part)) push(node)
    },

    "!>": function(node, part, push){ // direct parent (one level)
        node = node.parentNode
        if (node !== this.document && this.match(node, part)) push(node)
    },

    "!+": function(node, part, push){ // previous sibling
        while ((node = node.previousSibling)) if (node.nodeType == 1){
            if (this.match(node, part)) push(node)
            break
        }
    },

    "!^": function(node, part, push){ // last child
        node = node.lastChild
        if (node){
            if (node.nodeType == 1){
                if (this.match(node, part)) push(node)
            } else {
                combinators['!+'].call(this, node, part, push)
            }
        }
    },

    "!~": function(node, part, push){ // previous siblings
        while ((node = node.previousSibling)){
            if (node.nodeType === 1 && this.match(node, part)) push(node)
        }
    }

}

Finder.prototype.search = function(context, expression, found){

    if (!context) context = this.document
    else if (!context.nodeType && context.document) context = context.document

    var expressions = parse(expression)

    // no expressions were parsed. todo: is this really necessary?
    if (!expressions || !expressions.length) throw new Error("invalid expression")

    if (!found) found = []

    var uniques, push = isArray(found) ? function(node){
        found[found.length] = node
    } : function(node){
        found[found.length++] = node
    }

    // if there is more than one expression we need to check for duplicates when we push to found
    // this simply saves the old push and wraps it around an uid dupe check.
    if (expressions.length > 1){
        uniques = {}
        var plush = push
        push = function(node){
            var uid = uniqueID(node)
            if (!uniques[uid]){
                uniques[uid] = true
                plush(node)
            }
        }
    }

    // walker

    var node, nodes, part

    main: for (var i = 0; expression = expressions[i++];){

        // querySelector

        // TODO: more functional tests

        // if there is querySelectorAll (and the expression does not fail) use it.
        if (!slick.noQSA && this.querySelectorAll){

            nodes = this.querySelectorAll(context, expression)
            if (nodes !== true){
                if (nodes && nodes.length) for (var j = 0; node = nodes[j++];) if (node.nodeName > '@'){
                    push(node)
                }
                continue main
            }
        }

        // if there is only one part in the expression we don't need to check each part for duplicates.
        // todo: this might be too naive. while solid, there can be expression sequences that do not
        // produce duplicates. "body div" for instance, can never give you each div more than once.
        // "body div a" on the other hand might.
        if (expression.length === 1){

            part = expression[0]
            combinators[part.combinator].call(this, context, part, push)

        } else {

            var cs = [context], c, f, u, p = function(node){
                var uid = uniqueID(node)
                if (!u[uid]){
                    u[uid] = true
                    f[f.length] = node
                }
            }

            // loop the expression parts
            for (var j = 0; part = expression[j++];){
                f = []; u = {}
                // loop the contexts
                for (var k = 0; c = cs[k++];) combinators[part.combinator].call(this, c, part, p)
                // nothing was found, the expression failed, continue to the next expression.
                if (!f.length) continue main
                cs = f // set the contexts for future parts (if any)
            }

            if (i === 0) found = f // first expression. directly set found.
            else for (var l = 0; l < f.length; l++) push(f[l]) // any other expression needs to push to found.
        }

    }

    if (uniques && found && found.length > 1) this.sort(found)

    return found

}

Finder.prototype.sort = function(nodes){
    return this.sorter ? Array.prototype.sort.call(nodes, this.sorter) : nodes
}

// TODO: most of these pseudo selectors include <html> and qsa doesnt. fixme.

var pseudos = {


    // TODO: returns different results than qsa empty.

    'empty': function(){
        return !(this && this.nodeType === 1) && !(this.innerText || this.textContent || '').length
    },

    'not': function(expression){
        return !slick.matches(this, expression)
    },

    'contains': function(text){
        return (this.innerText || this.textContent || '').indexOf(text) > -1
    },

    'first-child': function(){
        var node = this
        while ((node = node.previousSibling)) if (node.nodeType == 1) return false
        return true
    },

    'last-child': function(){
        var node = this
        while ((node = node.nextSibling)) if (node.nodeType == 1) return false
        return true
    },

    'only-child': function(){
        var prev = this
        while ((prev = prev.previousSibling)) if (prev.nodeType == 1) return false

        var next = this
        while ((next = next.nextSibling)) if (next.nodeType == 1) return false

        return true
    },

    'first-of-type': function(){
        var node = this, nodeName = node.nodeName
        while ((node = node.previousSibling)) if (node.nodeName == nodeName) return false
        return true
    },

    'last-of-type': function(){
        var node = this, nodeName = node.nodeName
        while ((node = node.nextSibling)) if (node.nodeName == nodeName) return false
        return true
    },

    'only-of-type': function(){
        var prev = this, nodeName = this.nodeName
        while ((prev = prev.previousSibling)) if (prev.nodeName == nodeName) return false
        var next = this
        while ((next = next.nextSibling)) if (next.nodeName == nodeName) return false
        return true
    },

    'enabled': function(){
        return !this.disabled
    },

    'disabled': function(){
        return this.disabled
    },

    'checked': function(){
        return this.checked || this.selected
    },

    'selected': function(){
        return this.selected
    },

    'focus': function(){
        var doc = this.ownerDocument
        return doc.activeElement === this && (this.href || this.type || slick.hasAttribute(this, 'tabindex'))
    },

    'root': function(){
        return (this === this.ownerDocument.documentElement)
    }

}

Finder.prototype.match = function(node, bit, noTag, noId, noClass){

    // TODO: more functional tests ?

    if (!slick.noQSA && this.matchesSelector){
        var matches = this.matchesSelector(node, bit)
        if (matches !== null) return matches
    }

    // normal matching

    if (!noTag && bit.tag){

        var nodeName = node.nodeName.toLowerCase()
        if (bit.tag === "*"){
            if (nodeName < "@") return false
        } else if (nodeName != bit.tag){
            return false
        }

    }

    if (!noId && bit.id && node.getAttribute('id') !== bit.id) return false

    var i, part

    if (!noClass && bit.classes){

        var className = this.getAttribute(node, "class")
        if (!className) return false

        for (part in bit.classes) if (!RegExp('(^|\\s)' + bit.classes[part] + '(\\s|$)').test(className)) return false
    }

    var name, value

    if (bit.attributes) for (i = 0; part = bit.attributes[i++];){

        var operator  = part.operator,
            escaped   = part.escapedValue

        name  = part.name
        value = part.value

        if (!operator){

            if (!this.hasAttribute(node, name)) return false

        } else {

            var actual = this.getAttribute(node, name)
            if (actual == null) return false

            switch (operator){
                case '^=' : if (!RegExp(      '^' + escaped            ).test(actual)) return false; break
                case '$=' : if (!RegExp(            escaped + '$'      ).test(actual)) return false; break
                case '~=' : if (!RegExp('(^|\\s)' + escaped + '(\\s|$)').test(actual)) return false; break
                case '|=' : if (!RegExp(      '^' + escaped + '(-|$)'  ).test(actual)) return false; break

                case '='  : if (actual !== value) return false; break
                case '*=' : if (actual.indexOf(value) === -1) return false; break
                default   : return false
            }

        }
    }

    if (bit.pseudos) for (i = 0; part = bit.pseudos[i++];){

        name  = part.name
        value = part.value

        if (pseudos[name]) return pseudos[name].call(node, value)

        if (value != null){
            if (this.getAttribute(node, name) !== value) return false
        } else {
            if (!this.hasAttribute(node, name)) return false
        }

    }

    return true

}

Finder.prototype.matches = function(node, expression){

    var expressions = parse(expression)

    if (expressions.length === 1 && expressions[0].length === 1){ // simplest match
        return this.match(node, expressions[0][0])
    }

    // TODO: more functional tests ?

    if (!slick.noQSA && this.matchesSelector){
        var matches = this.matchesSelector(node, expressions)
        if (matches !== null) return matches
    }

    var nodes = this.search(this.document, expression, {length: 0})

    for (var i = 0, res; res = nodes[i++];) if (node === res) return true
    return false

}

var finders = {}

var finder = function(context){
    var doc = context || document
    if (doc.ownerDocument) doc = doc.ownerDocument
    else if (doc.document) doc = doc.document

    if (doc.nodeType !== 9) throw new TypeError("invalid document")

    var uid = uniqueID(doc)
    return finders[uid] || (finders[uid] = new Finder(doc))
}

// ... API ...

var slick = function(expression, context){
    return slick.search(expression, context)
}

slick.search = function(expression, context, found){
    return finder(context).search(context, expression, found)
}

slick.find = function(expression, context){
    return finder(context).search(context, expression)[0] || null
}

slick.getAttribute = function(node, name){
    return finder(node).getAttribute(node, name)
}

slick.hasAttribute = function(node, name){
    return finder(node).hasAttribute(node, name)
}

slick.contains = function(context, node){
    return finder(context).contains(context, node)
}

slick.matches = function(node, expression){
    return finder(node).matches(node, expression)
}

slick.sort = function(nodes){
    if (nodes && nodes.length > 1) finder(nodes[0]).sort(nodes)
    return nodes
}

slick.parse = parse;

// slick.debug = true
// slick.noQSA  = true

module.exports = slick

},{"./parser":98}],97:[function(require,module,exports){
(function (global){
/*
slick
*/"use strict"

module.exports = "document" in global ? require("./finder") : { parse: require("./parser") }

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"./finder":96,"./parser":98}],98:[function(require,module,exports){
/*
Slick Parser
 - originally created by the almighty Thomas Aylott <@subtlegradient> (http://subtlegradient.com)
*/"use strict"

// Notable changes from Slick.Parser 1.0.x

// The parser now uses 2 classes: Expressions and Expression
// `new Expressions` produces an array-like object containing a list of Expression objects
// - Expressions::toString() produces a cleaned up expressions string
// `new Expression` produces an array-like object
// - Expression::toString() produces a cleaned up expression string
// The only exposed method is parse, which produces a (cached) `new Expressions` instance
// parsed.raw is no longer present, use .toString()
// parsed.expression is now useless, just use the indices
// parsed.reverse() has been removed for now, due to its apparent uselessness
// Other changes in the Expressions object:
// - classNames are now unique, and save both escaped and unescaped values
// - attributes now save both escaped and unescaped values
// - pseudos now save both escaped and unescaped values

var escapeRe   = /([-.*+?^${}()|[\]\/\\])/g,
    unescapeRe = /\\/g

var escape = function(string){
    // XRegExp v2.0.0-beta-3
    // « https://github.com/slevithan/XRegExp/blob/master/src/xregexp.js
    return (string + "").replace(escapeRe, '\\$1')
}

var unescape = function(string){
    return (string + "").replace(unescapeRe, '')
}

var slickRe = RegExp(
/*
#!/usr/bin/env ruby
puts "\t\t" + DATA.read.gsub(/\(\?x\)|\s+#.*$|\s+|\\$|\\n/,'')
__END__
    "(?x)^(?:\
      \\s* ( , ) \\s*               # Separator          \n\
    | \\s* ( <combinator>+ ) \\s*   # Combinator         \n\
    |      ( \\s+ )                 # CombinatorChildren \n\
    |      ( <unicode>+ | \\* )     # Tag                \n\
    | \\#  ( <unicode>+       )     # ID                 \n\
    | \\.  ( <unicode>+       )     # ClassName          \n\
    |                               # Attribute          \n\
    \\[  \
        \\s* (<unicode1>+)  (?:  \
            \\s* ([*^$!~|]?=)  (?:  \
                \\s* (?:\
                    ([\"']?)(.*?)\\9 \
                )\
            )  \
        )?  \\s*  \
    \\](?!\\]) \n\
    |   :+ ( <unicode>+ )(?:\
    \\( (?:\
        (?:([\"'])([^\\12]*)\\12)|((?:\\([^)]+\\)|[^()]*)+)\
    ) \\)\
    )?\
    )"
*/
"^(?:\\s*(,)\\s*|\\s*(<combinator>+)\\s*|(\\s+)|(<unicode>+|\\*)|\\#(<unicode>+)|\\.(<unicode>+)|\\[\\s*(<unicode1>+)(?:\\s*([*^$!~|]?=)(?:\\s*(?:([\"']?)(.*?)\\9)))?\\s*\\](?!\\])|(:+)(<unicode>+)(?:\\((?:(?:([\"'])([^\\13]*)\\13)|((?:\\([^)]+\\)|[^()]*)+))\\))?)"
    .replace(/<combinator>/, '[' + escape(">+~`!@$%^&={}\\;</") + ']')
    .replace(/<unicode>/g, '(?:[\\w\\u00a1-\\uFFFF-]|\\\\[^\\s0-9a-f])')
    .replace(/<unicode1>/g, '(?:[:\\w\\u00a1-\\uFFFF-]|\\\\[^\\s0-9a-f])')
)

// Part

var Part = function Part(combinator){
    this.combinator = combinator || " "
    this.tag = "*"
}

Part.prototype.toString = function(){

    if (!this.raw){

        var xpr = "", k, part

        xpr += this.tag || "*"
        if (this.id) xpr += "#" + this.id
        if (this.classes) xpr += "." + this.classList.join(".")
        if (this.attributes) for (k = 0; part = this.attributes[k++];){
            xpr += "[" + part.name + (part.operator ? part.operator + '"' + part.value + '"' : '') + "]"
        }
        if (this.pseudos) for (k = 0; part = this.pseudos[k++];){
            xpr += ":" + part.name
            if (part.value) xpr += "(" + part.value + ")"
        }

        this.raw = xpr

    }

    return this.raw
}

// Expression

var Expression = function Expression(){
    this.length = 0
}

Expression.prototype.toString = function(){

    if (!this.raw){

        var xpr = ""

        for (var j = 0, bit; bit = this[j++];){
            if (j !== 1) xpr += " "
            if (bit.combinator !== " ") xpr += bit.combinator + " "
            xpr += bit
        }

        this.raw = xpr

    }

    return this.raw
}

var replacer = function(
    rawMatch,

    separator,
    combinator,
    combinatorChildren,

    tagName,
    id,
    className,

    attributeKey,
    attributeOperator,
    attributeQuote,
    attributeValue,

    pseudoMarker,
    pseudoClass,
    pseudoQuote,
    pseudoClassQuotedValue,
    pseudoClassValue
){

    var expression, current

    if (separator || !this.length){
        expression = this[this.length++] = new Expression
        if (separator) return ''
    }

    if (!expression) expression = this[this.length - 1]

    if (combinator || combinatorChildren || !expression.length){
        current = expression[expression.length++] = new Part(combinator)
    }

    if (!current) current = expression[expression.length - 1]

    if (tagName){

        current.tag = unescape(tagName)

    } else if (id){

        current.id = unescape(id)

    } else if (className){

        var unescaped = unescape(className)

        var classes = current.classes || (current.classes = {})
        if (!classes[unescaped]){
            classes[unescaped] = escape(className)
            var classList = current.classList || (current.classList = [])
            classList.push(unescaped)
            classList.sort()
        }

    } else if (pseudoClass){

        pseudoClassValue = pseudoClassValue || pseudoClassQuotedValue

        ;(current.pseudos || (current.pseudos = [])).push({
            type         : pseudoMarker.length == 1 ? 'class' : 'element',
            name         : unescape(pseudoClass),
            escapedName  : escape(pseudoClass),
            value        : pseudoClassValue ? unescape(pseudoClassValue) : null,
            escapedValue : pseudoClassValue ? escape(pseudoClassValue) : null
        })

    } else if (attributeKey){

        attributeValue = attributeValue ? escape(attributeValue) : null

        ;(current.attributes || (current.attributes = [])).push({
            operator     : attributeOperator,
            name         : unescape(attributeKey),
            escapedName  : escape(attributeKey),
            value        : attributeValue ? unescape(attributeValue) : null,
            escapedValue : attributeValue ? escape(attributeValue) : null
        })

    }

    return ''

}

// Expressions

var Expressions = function Expressions(expression){
    this.length = 0

    var self = this

    var original = expression, replaced

    while (expression){
        replaced = expression.replace(slickRe, function(){
            return replacer.apply(self, arguments)
        })
        if (replaced === expression) throw new Error(original + ' is an invalid expression')
        expression = replaced
    }
}

Expressions.prototype.toString = function(){
    if (!this.raw){
        var expressions = []
        for (var i = 0, expression; expression = this[i++];) expressions.push(expression)
        this.raw = expressions.join(", ")
    }

    return this.raw
}

var cache = {}

var parse = function(expression){
    if (expression == null) return null
    expression = ('' + expression).replace(/^\s+|\s+$/g, '')
    return cache[expression] || (cache[expression] = new Expressions(expression))
}

module.exports = parse

},{}],99:[function(require,module,exports){
// shim for using process in browser
var process = module.exports = {};

// cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
}
function defaultClearTimeout () {
    throw new Error('clearTimeout has not been defined');
}
(function () {
    try {
        if (typeof setTimeout === 'function') {
            cachedSetTimeout = setTimeout;
        } else {
            cachedSetTimeout = defaultSetTimout;
        }
    } catch (e) {
        cachedSetTimeout = defaultSetTimout;
    }
    try {
        if (typeof clearTimeout === 'function') {
            cachedClearTimeout = clearTimeout;
        } else {
            cachedClearTimeout = defaultClearTimeout;
        }
    } catch (e) {
        cachedClearTimeout = defaultClearTimeout;
    }
} ())
function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
        //normal enviroments in sane situations
        return setTimeout(fun, 0);
    }
    // if setTimeout wasn't available but was latter defined
    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
        cachedSetTimeout = setTimeout;
        return setTimeout(fun, 0);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedSetTimeout(fun, 0);
    } catch(e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
            return cachedSetTimeout.call(null, fun, 0);
        } catch(e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
            return cachedSetTimeout.call(this, fun, 0);
        }
    }


}
function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
        //normal enviroments in sane situations
        return clearTimeout(marker);
    }
    // if clearTimeout wasn't available but was latter defined
    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
        cachedClearTimeout = clearTimeout;
        return clearTimeout(marker);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedClearTimeout(marker);
    } catch (e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
            return cachedClearTimeout.call(null, marker);
        } catch (e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
            // Some versions of I.E. have different rules for clearTimeout vs setTimeout
            return cachedClearTimeout.call(this, marker);
        }
    }



}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;

function cleanUpNextTick() {
    if (!draining || !currentQueue) {
        return;
    }
    draining = false;
    if (currentQueue.length) {
        queue = currentQueue.concat(queue);
    } else {
        queueIndex = -1;
    }
    if (queue.length) {
        drainQueue();
    }
}

function drainQueue() {
    if (draining) {
        return;
    }
    var timeout = runTimeout(cleanUpNextTick);
    draining = true;

    var len = queue.length;
    while(len) {
        currentQueue = queue;
        queue = [];
        while (++queueIndex < len) {
            if (currentQueue) {
                currentQueue[queueIndex].run();
            }
        }
        queueIndex = -1;
        len = queue.length;
    }
    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
}

process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);
    if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
            args[i - 1] = arguments[i];
        }
    }
    queue.push(new Item(fun, args));
    if (queue.length === 1 && !draining) {
        runTimeout(drainQueue);
    }
};

// v8 likes predictible objects
function Item(fun, array) {
    this.fun = fun;
    this.array = array;
}
Item.prototype.run = function () {
    this.fun.apply(null, this.array);
};
process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];
process.version = ''; // empty string to avoid regexp issues
process.versions = {};

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;

process.binding = function (name) {
    throw new Error('process.binding is not supported');
};

process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};
process.umask = function() { return 0; };

},{}]},{},[1])

//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJhc3NldHMvY29tbW9uL2FwcGxpY2F0aW9uL21haW4uanMiLCJhc3NldHMvY29tbW9uL2FwcGxpY2F0aW9uL21lbnUvaW5kZXguanMiLCJhc3NldHMvY29tbW9uL2FwcGxpY2F0aW9uL29mZmNhbnZhcy9pbmRleC5qcyIsImFzc2V0cy9jb21tb24vYXBwbGljYXRpb24vdG90b3AvaW5kZXguanMiLCJhc3NldHMvY29tbW9uL2FwcGxpY2F0aW9uL3V0aWxzL2RlY291cGxlLmpzIiwiYXNzZXRzL2NvbW1vbi9hcHBsaWNhdGlvbi91dGlscy9kb2xsYXItZXh0cmFzLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZG9tcmVhZHkvcmVhZHkuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9lbGVtZW50cy9hdHRyaWJ1dGVzLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvYmFzZS5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL2VsZW1lbnRzL2RlbGVnYXRpb24uanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9lbGVtZW50cy9ldmVudHMuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9lbGVtZW50cy9pbmRleC5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL2VsZW1lbnRzL2luc2VydGlvbi5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL2VsZW1lbnRzL25vZGVfbW9kdWxlcy9tb3V0L2FycmF5L2V2ZXJ5LmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvYXJyYXkvZmlsdGVyLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvYXJyYXkvZm9yRWFjaC5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL2VsZW1lbnRzL25vZGVfbW9kdWxlcy9tb3V0L2FycmF5L2luZGV4T2YuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9lbGVtZW50cy9ub2RlX21vZHVsZXMvbW91dC9hcnJheS9tYXAuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9lbGVtZW50cy9ub2RlX21vZHVsZXMvbW91dC9hcnJheS9zb21lLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvZnVuY3Rpb24vaWRlbnRpdHkuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9lbGVtZW50cy9ub2RlX21vZHVsZXMvbW91dC9mdW5jdGlvbi9tYWtlSXRlcmF0b3JfLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvZnVuY3Rpb24vcHJvcC5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL2VsZW1lbnRzL25vZGVfbW9kdWxlcy9tb3V0L2xhbmcvaXNBcnJheS5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL2VsZW1lbnRzL25vZGVfbW9kdWxlcy9tb3V0L2xhbmcvaXNLaW5kLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvbGFuZy9raW5kT2YuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9lbGVtZW50cy9ub2RlX21vZHVsZXMvbW91dC9sYW5nL3RvU3RyaW5nLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvb2JqZWN0L2RlZXBNYXRjaGVzLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvb2JqZWN0L2ZvckluLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvb2JqZWN0L2Zvck93bi5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL2VsZW1lbnRzL25vZGVfbW9kdWxlcy9tb3V0L29iamVjdC9oYXNPd24uanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9lbGVtZW50cy9ub2RlX21vZHVsZXMvbW91dC9zdHJpbmcvV0hJVEVfU1BBQ0VTLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvc3RyaW5nL2x0cmltLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvc3RyaW5nL3J0cmltLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvZWxlbWVudHMvbm9kZV9tb2R1bGVzL21vdXQvc3RyaW5nL3RyaW0uanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9lbGVtZW50cy90cmF2ZXJzYWwuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9lbGVtZW50cy96ZW4uanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9tb3V0L2FycmF5L3NsaWNlLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvbW91dC9mdW5jdGlvbi9iaW5kLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvbW91dC9mdW5jdGlvbi90aW1lb3V0LmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvbW91dC9tYXRoL2NsYW1wLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvbW91dC9tYXRoL2xlcnAuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9tb3V0L21hdGgvbWFwLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvbW91dC9tYXRoL25vcm0uanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9tb3V0L29iamVjdC9kZWVwTWF0Y2hlcy5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL3ByaW1lLXV0aWwvbm9kZV9tb2R1bGVzL21vdXQvbGFuZy9jbG9uZS5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL3ByaW1lLXV0aWwvbm9kZV9tb2R1bGVzL21vdXQvbGFuZy9kZWVwQ2xvbmUuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9wcmltZS11dGlsL25vZGVfbW9kdWxlcy9tb3V0L2xhbmcvaXNPYmplY3QuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9wcmltZS11dGlsL25vZGVfbW9kdWxlcy9tb3V0L2xhbmcvaXNQbGFpbk9iamVjdC5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL3ByaW1lLXV0aWwvbm9kZV9tb2R1bGVzL21vdXQvb2JqZWN0L21lcmdlLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvcHJpbWUtdXRpbC9ub2RlX21vZHVsZXMvbW91dC9vYmplY3QvbWl4SW4uanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9wcmltZS11dGlsL25vZGVfbW9kdWxlcy9wcmltZS9pbmRleC5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL3ByaW1lLXV0aWwvbm9kZV9tb2R1bGVzL3ByaW1lL25vZGVfbW9kdWxlcy9tb3V0L2xhbmcvY3JlYXRlT2JqZWN0LmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvcHJpbWUtdXRpbC9wcmltZS9ib3VuZC5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL3ByaW1lLXV0aWwvcHJpbWUvb3B0aW9ucy5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL3ByaW1lL2RlZmVyLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvcHJpbWUvZW1pdHRlci5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL3ByaW1lL2luZGV4LmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvcHJpbWUvbWFwLmpzIiwiYXNzZXRzL2NvbW1vbi9ub2RlX21vZHVsZXMvcHJpbWUvbm9kZV9tb2R1bGVzL21vdXQvdGltZS9ub3cuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9zbGljay9maW5kZXIuanMiLCJhc3NldHMvY29tbW9uL25vZGVfbW9kdWxlcy9zbGljay9pbmRleC5qcyIsImFzc2V0cy9jb21tb24vbm9kZV9tb2R1bGVzL3NsaWNrL3BhcnNlci5qcyIsIm5vZGVfbW9kdWxlcy9wcm9jZXNzL2Jyb3dzZXIuanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7QUNBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOzs7QUN0QkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7OztBQ3BWQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUMzWkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQzFCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNwQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDM0JBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQzlCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ3pOQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNqSUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDakZBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQzlFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNaQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDNUZBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQzNCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDMUJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUN2QkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUM1QkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUN0QkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDM0JBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ1pBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDbENBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNkQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDUkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDVEE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ3BCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ2JBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDdkRBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDNUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDbkJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ1pBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ1pBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDbENBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ2pDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNmQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ3ZHQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7OztBQ3hEQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDbkNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7Ozs7O0FDbkJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7Ozs7Ozs7OztBQ2pCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNUQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDWEE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ1hBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDYkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOzs7Ozs7Ozs7Ozs7Ozs7Ozs7OztBQ3hEQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ2pEQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7OztBQ2hEQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDUkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7Ozs7Ozs7OztBQ2JBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDeENBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDNUJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQzFGQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7Ozs7Ozs7Ozs7O0FDbEJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ2xCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7OztBQ2pCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7OztBQ2xIQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNyRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDMUZBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7Ozs7Ozs7Ozs7Ozs7OztBQzVIQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNsQkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7O0FDN3pCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7QUNMQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQzFQQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSIsImZpbGUiOiJnZW5lcmF0ZWQuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlc0NvbnRlbnQiOlsiKGZ1bmN0aW9uIGUodCxuLHIpe2Z1bmN0aW9uIHMobyx1KXtpZighbltvXSl7aWYoIXRbb10pe3ZhciBhPXR5cGVvZiByZXF1aXJlPT1cImZ1bmN0aW9uXCImJnJlcXVpcmU7aWYoIXUmJmEpcmV0dXJuIGEobywhMCk7aWYoaSlyZXR1cm4gaShvLCEwKTt2YXIgZj1uZXcgRXJyb3IoXCJDYW5ub3QgZmluZCBtb2R1bGUgJ1wiK28rXCInXCIpO3Rocm93IGYuY29kZT1cIk1PRFVMRV9OT1RfRk9VTkRcIixmfXZhciBsPW5bb109e2V4cG9ydHM6e319O3Rbb11bMF0uY2FsbChsLmV4cG9ydHMsZnVuY3Rpb24oZSl7dmFyIG49dFtvXVsxXVtlXTtyZXR1cm4gcyhuP246ZSl9LGwsbC5leHBvcnRzLGUsdCxuLHIpfXJldHVybiBuW29dLmV4cG9ydHN9dmFyIGk9dHlwZW9mIHJlcXVpcmU9PVwiZnVuY3Rpb25cIiYmcmVxdWlyZTtmb3IodmFyIG89MDtvPHIubGVuZ3RoO28rKylzKHJbb10pO3JldHVybiBzfSkiLCJcInVzZSBzdHJpY3RcIjtcblxudmFyIHJlYWR5ICAgICA9IHJlcXVpcmUoJ2RvbXJlYWR5JyksXG4gICAgbWVudSAgICAgID0gcmVxdWlyZSgnLi9tZW51JyksXG4gICAgb2ZmY2FudmFzID0gcmVxdWlyZSgnLi9vZmZjYW52YXMnKSxcbiAgICB0b3RvcCAgICAgPSByZXF1aXJlKCcuL3RvdG9wJyksXG4gICAgJCAgICAgICAgID0gcmVxdWlyZSgnLi91dGlscy9kb2xsYXItZXh0cmFzJyksXG5cbiAgICBpbnN0YW5jZXMgPSB7fTtcblxucmVhZHkoZnVuY3Rpb24oKSB7XG4gICAgaW5zdGFuY2VzID0ge1xuICAgICAgICBvZmZjYW52YXM6IG5ldyBvZmZjYW52YXMoKSxcbiAgICAgICAgbWVudTogbmV3IG1lbnUoKSxcbiAgICAgICAgJDogJCxcbiAgICAgICAgcmVhZHk6IHJlYWR5XG4gICAgfTtcblxuICAgIG1vZHVsZS5leHBvcnRzID0gd2luZG93Lkc1ID0gaW5zdGFuY2VzO1xufSk7XG5cbm1vZHVsZS5leHBvcnRzID0gd2luZG93Lkc1ID0gaW5zdGFuY2VzO1xuIiwiXCJ1c2Ugc3RyaWN0XCI7XG5cbnZhciByZWFkeSAgID0gcmVxdWlyZSgnZG9tcmVhZHknKSxcbiAgICBwcmltZSAgID0gcmVxdWlyZSgncHJpbWUnKSxcbiAgICAkICAgICAgID0gcmVxdWlyZSgnLi4vdXRpbHMvZG9sbGFyLWV4dHJhcycpLFxuICAgIHplbiAgICAgPSByZXF1aXJlKCdlbGVtZW50cy96ZW4nKSxcbiAgICBiaW5kICAgID0gcmVxdWlyZSgnbW91dC9mdW5jdGlvbi9iaW5kJyksXG4gICAgdGltZW91dCA9IHJlcXVpcmUoJ21vdXQvZnVuY3Rpb24vdGltZW91dCcpLFxuICAgIEJvdW5kICAgPSByZXF1aXJlKCdwcmltZS11dGlsL3ByaW1lL2JvdW5kJyksXG4gICAgT3B0aW9ucyA9IHJlcXVpcmUoJ3ByaW1lLXV0aWwvcHJpbWUvb3B0aW9ucycpO1xuXG5cbnZhciBoYXNUb3VjaEV2ZW50cyA9ICgnb250b3VjaHN0YXJ0JyBpbiB3aW5kb3cpIHx8IHdpbmRvdy5Eb2N1bWVudFRvdWNoICYmIGRvY3VtZW50IGluc3RhbmNlb2YgRG9jdW1lbnRUb3VjaDtcblxudmFyIE1lbnUgPSBuZXcgcHJpbWUoe1xuXG4gICAgbWl4aW46IFtCb3VuZCwgT3B0aW9uc10sXG5cbiAgICBvcHRpb25zOiB7XG4gICAgICAgIHNlbGVjdG9yczoge1xuICAgICAgICAgICAgbWFpbkNvbnRhaW5lcjogJy5nLW1haW4tbmF2JyxcbiAgICAgICAgICAgIG1vYmlsZUNvbnRhaW5lcjogJyNnLW1vYmlsZW1lbnUtY29udGFpbmVyJyxcbiAgICAgICAgICAgIHRvcExldmVsOiAnLmctdG9wbGV2ZWwnLFxuICAgICAgICAgICAgcm9vdEl0ZW1zOiAnPiB1bCA+IGxpJyxcbiAgICAgICAgICAgIHBhcmVudDogJy5nLXBhcmVudCcsXG4gICAgICAgICAgICBpdGVtOiAnLmctbWVudS1pdGVtJyxcbiAgICAgICAgICAgIGRyb3Bkb3duOiAnLmctZHJvcGRvd24nLFxuICAgICAgICAgICAgb3ZlcmxheTogJy5nLW1lbnUtb3ZlcmxheScsXG4gICAgICAgICAgICB0b3VjaEluZGljYXRvcjogJy5nLW1lbnUtcGFyZW50LWluZGljYXRvcicsXG4gICAgICAgICAgICBsaW5rZWRQYXJlbnQ6ICdbZGF0YS1nLW1lbnVwYXJlbnRdJyxcbiAgICAgICAgICAgIG1vYmlsZVRhcmdldDogJ1tkYXRhLWctbW9iaWxlLXRhcmdldF0nXG4gICAgICAgIH0sXG5cbiAgICAgICAgc3RhdGVzOiB7XG4gICAgICAgICAgICBhY3RpdmU6ICdnLWFjdGl2ZScsXG4gICAgICAgICAgICBpbmFjdGl2ZTogJ2ctaW5hY3RpdmUnLFxuICAgICAgICAgICAgc2VsZWN0ZWQ6ICdnLXNlbGVjdGVkJyxcbiAgICAgICAgICAgIHRvdWNoRXZlbnRzOiAnZy1tZW51LWhhc3RvdWNoJ1xuICAgICAgICB9XG4gICAgfSxcblxuICAgIGNvbnN0cnVjdG9yOiBmdW5jdGlvbihvcHRpb25zKSB7XG4gICAgICAgIHRoaXMuc2V0T3B0aW9ucyhvcHRpb25zKTtcblxuICAgICAgICB0aGlzLnNlbGVjdG9ycyA9IHRoaXMub3B0aW9ucy5zZWxlY3RvcnM7XG4gICAgICAgIHRoaXMuc3RhdGVzID0gdGhpcy5vcHRpb25zLnN0YXRlcztcbiAgICAgICAgdGhpcy5vdmVybGF5ID0gemVuKCdkaXYnICsgdGhpcy5zZWxlY3RvcnMub3ZlcmxheSkudG9wKCcjZy1wYWdlLXN1cnJvdW5kJyk7XG4gICAgICAgIHRoaXMuYWN0aXZlID0gbnVsbDtcbiAgICAgICAgdGhpcy5sb2NhdGlvbiA9IFtdO1xuXG4gICAgICAgIHZhciBtYWluQ29udGFpbmVyID0gJCh0aGlzLnNlbGVjdG9ycy5tYWluQ29udGFpbmVyKTtcbiAgICAgICAgaWYgKCFtYWluQ29udGFpbmVyKSB7IHJldHVybjsgfVxuXG4gICAgICAgIHZhciBnSG92ZXJFeHBhbmQgID0gbWFpbkNvbnRhaW5lci5kYXRhKCdnLWhvdmVyLWV4cGFuZCcpO1xuXG4gICAgICAgIHRoaXMuaG92ZXJFeHBhbmQgPSBnSG92ZXJFeHBhbmQgPT09IG51bGwgfHwgZ0hvdmVyRXhwYW5kID09PSAndHJ1ZSc7XG4gICAgICAgIGlmIChoYXNUb3VjaEV2ZW50cyB8fCAhdGhpcy5ob3ZlckV4cGFuZCkge1xuICAgICAgICAgICAgbWFpbkNvbnRhaW5lci5hZGRDbGFzcyh0aGlzLnN0YXRlcy50b3VjaEV2ZW50cyk7XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLmF0dGFjaCgpO1xuICAgIH0sXG5cbiAgICBhdHRhY2g6IGZ1bmN0aW9uKCkge1xuICAgICAgICB2YXIgc2VsZWN0b3JzICAgICAgID0gdGhpcy5zZWxlY3RvcnMsXG4gICAgICAgICAgICBtYWluICAgICAgICAgICAgPSAkKHNlbGVjdG9ycy5tYWluQ29udGFpbmVyICsgJyAnICsgc2VsZWN0b3JzLml0ZW0pLFxuICAgICAgICAgICAgbW9iaWxlQ29udGFpbmVyID0gJChzZWxlY3RvcnMubW9iaWxlQ29udGFpbmVyKSxcbiAgICAgICAgICAgIGJvZHkgICAgICAgICAgICA9ICQoJ2JvZHknKTtcblxuICAgICAgICBpZiAoIW1haW4pIHsgcmV0dXJuOyB9XG4gICAgICAgIGlmICh0aGlzLmhvdmVyRXhwYW5kKSB7XG4gICAgICAgICAgICBtYWluLm9uKCdtb3VzZWVudGVyJywgdGhpcy5ib3VuZCgnbW91c2VlbnRlcicpKTtcbiAgICAgICAgICAgIG1haW4ub24oJ21vdXNlbGVhdmUnLCB0aGlzLmJvdW5kKCdtb3VzZWxlYXZlJykpO1xuICAgICAgICB9XG5cbiAgICAgICAgYm9keS5kZWxlZ2F0ZSgnY2xpY2snLCAnOm5vdCgnICsgc2VsZWN0b3JzLm1haW5Db250YWluZXIgKyAnKSAnICsgc2VsZWN0b3JzLmxpbmtlZFBhcmVudCArICcsIC5nLWZ1bGx3aWR0aCAuZy1zdWJsZXZlbCAnICsgc2VsZWN0b3JzLmxpbmtlZFBhcmVudCwgdGhpcy5ib3VuZCgnY2xpY2snKSk7XG4gICAgICAgIGJvZHkuZGVsZWdhdGUoJ2NsaWNrJywgJzpub3QoJyArIHNlbGVjdG9ycy5tYWluQ29udGFpbmVyICsgJykgYVtocmVmXScsIHRoaXMuYm91bmQoJ3Jlc2V0QWZ0ZXJDbGljaycpKTtcblxuICAgICAgICBpZiAoaGFzVG91Y2hFdmVudHMgfHwgIXRoaXMuaG92ZXJFeHBhbmQpIHtcbiAgICAgICAgICAgIHZhciBsaW5rZWRQYXJlbnQgPSAkKHNlbGVjdG9ycy5saW5rZWRQYXJlbnQpO1xuICAgICAgICAgICAgaWYgKGxpbmtlZFBhcmVudCkgeyBsaW5rZWRQYXJlbnQub24oJ3RvdWNoZW5kJywgdGhpcy5ib3VuZCgndG91Y2hlbmQnKSk7IH1cbiAgICAgICAgICAgIHRoaXMub3ZlcmxheS5vbigndG91Y2hlbmQnLCB0aGlzLmJvdW5kKCdjbG9zZUFsbERyb3Bkb3ducycpKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmIChtb2JpbGVDb250YWluZXIpIHtcbiAgICAgICAgICAgIHZhciBxdWVyeSA9ICdvbmx5IGFsbCBhbmQgKG1heC13aWR0aDogJyArIHRoaXMuX2NhbGN1bGF0ZUJyZWFrcG9pbnQoKG1vYmlsZUNvbnRhaW5lci5kYXRhKCdnLW1lbnUtYnJlYWtwb2ludCcpIHx8ICc0OHJlbScpKSArICcpJyxcbiAgICAgICAgICAgICAgICBtYXRjaCA9IG1hdGNoTWVkaWEocXVlcnkpO1xuICAgICAgICAgICAgbWF0Y2guYWRkTGlzdGVuZXIodGhpcy5ib3VuZCgnX2NoZWNrUXVlcnknKSk7XG4gICAgICAgICAgICB0aGlzLl9jaGVja1F1ZXJ5KG1hdGNoKTtcbiAgICAgICAgfVxuICAgIH0sXG5cbiAgICBkZXRhY2g6IGZ1bmN0aW9uKCkge30sXG5cbiAgICBjbGljazogZnVuY3Rpb24oZXZlbnQpIHtcbiAgICAgICAgdGhpcy50b3VjaGVuZChldmVudCk7XG4gICAgfSxcblxuICAgIHJlc2V0QWZ0ZXJDbGljazogZnVuY3Rpb24oZXZlbnQpIHtcbiAgICAgICAgdmFyIHRhcmdldCA9ICQoZXZlbnQudGFyZ2V0KTtcblxuICAgICAgICBpZiAodGFyZ2V0LmRhdGEoJ2ctbWVudXBhcmVudCcpICE9PSBudWxsKSB7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMuY2xvc2VEcm9wZG93bihldmVudCk7XG4gICAgICAgIGlmIChnbG9iYWwuRzUgJiYgZ2xvYmFsLkc1Lm9mZmNhbnZhcykge1xuICAgICAgICAgICAgRzUub2ZmY2FudmFzLmNsb3NlKCk7XG4gICAgICAgIH1cbiAgICB9LFxuXG4gICAgbW91c2VlbnRlcjogZnVuY3Rpb24oZXZlbnQpIHtcbiAgICAgICAgdmFyIGVsZW1lbnQgPSAkKGV2ZW50LnRhcmdldCk7XG4gICAgICAgIGlmICghZWxlbWVudC5wYXJlbnQodGhpcy5vcHRpb25zLnNlbGVjdG9ycy5tYWluQ29udGFpbmVyKSkgeyByZXR1cm47IH1cbiAgICAgICAgaWYgKGVsZW1lbnQucGFyZW50KHRoaXMub3B0aW9ucy5zZWxlY3RvcnMuaXRlbSkgJiYgIWVsZW1lbnQucGFyZW50KCcuZy1zdGFuZGFyZCcpKSB7IHJldHVybjsgfVxuXG4gICAgICAgIHRoaXMub3BlbkRyb3Bkb3duKGVsZW1lbnQpO1xuICAgIH0sXG5cbiAgICBtb3VzZWxlYXZlOiBmdW5jdGlvbihldmVudCkge1xuICAgICAgICB2YXIgZWxlbWVudCA9ICQoZXZlbnQudGFyZ2V0KTtcbiAgICAgICAgaWYgKCFlbGVtZW50LnBhcmVudCh0aGlzLm9wdGlvbnMuc2VsZWN0b3JzLm1haW5Db250YWluZXIpKSB7IHJldHVybjsgfVxuICAgICAgICBpZiAoZWxlbWVudC5wYXJlbnQodGhpcy5vcHRpb25zLnNlbGVjdG9ycy5pdGVtKSAmJiAhZWxlbWVudC5wYXJlbnQoJy5nLXN0YW5kYXJkJykpIHsgcmV0dXJuOyB9XG5cbiAgICAgICAgdGhpcy5jbG9zZURyb3Bkb3duKGVsZW1lbnQpO1xuICAgIH0sXG5cbiAgICB0b3VjaGVuZDogZnVuY3Rpb24oZXZlbnQpIHtcbiAgICAgICAgdmFyIHNlbGVjdG9ycyA9IHRoaXMuc2VsZWN0b3JzLFxuICAgICAgICAgICAgc3RhdGVzICAgID0gdGhpcy5zdGF0ZXM7XG5cbiAgICAgICAgdmFyIHRhcmdldCAgICAgID0gJChldmVudC50YXJnZXQpLFxuICAgICAgICAgICAgaW5kaWNhdG9yICAgPSB0YXJnZXQucGFyZW50KHNlbGVjdG9ycy5pdGVtKS5maW5kKHNlbGVjdG9ycy50b3VjaEluZGljYXRvciksXG4gICAgICAgICAgICBtZW51VHlwZSAgICA9IHRhcmdldC5wYXJlbnQoJy5nLXN0YW5kYXJkJykgPyAnc3RhbmRhcmQnIDogJ21lZ2FtZW51JyxcbiAgICAgICAgICAgIGlzR29pbmdCYWNrID0gdGFyZ2V0LnBhcmVudCgnLmctZ28tYmFjaycpLFxuICAgICAgICAgICAgcGFyZW50LCBpc1NlbGVjdGVkO1xuXG4gICAgICAgIGlmIChpbmRpY2F0b3IpIHtcbiAgICAgICAgICAgIHRhcmdldCA9IGluZGljYXRvcjtcbiAgICAgICAgfVxuXG4gICAgICAgIHBhcmVudCA9IHRhcmdldC5tYXRjaGVzKHNlbGVjdG9ycy5pdGVtKSA/IHRhcmdldCA6IHRhcmdldC5wYXJlbnQoc2VsZWN0b3JzLml0ZW0pO1xuICAgICAgICBpc1NlbGVjdGVkID0gcGFyZW50Lmhhc0NsYXNzKHN0YXRlcy5zZWxlY3RlZCk7XG5cbiAgICAgICAgaWYgKCFwYXJlbnQuZmluZChzZWxlY3RvcnMuZHJvcGRvd24pICYmICFpbmRpY2F0b3IpIHsgcmV0dXJuIHRydWU7IH1cblxuICAgICAgICBldmVudC5zdG9wUHJvcGFnYXRpb24oKTtcbiAgICAgICAgaWYgKCFpbmRpY2F0b3IgfHwgdGFyZ2V0Lm1hdGNoZXMoc2VsZWN0b3JzLnRvdWNoSW5kaWNhdG9yKSkge1xuICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICghaXNTZWxlY3RlZCkge1xuICAgICAgICAgICAgdmFyIHNpYmxpbmdzID0gcGFyZW50LnNpYmxpbmdzKCk7XG4gICAgICAgICAgICBpZiAoc2libGluZ3MpIHtcbiAgICAgICAgICAgICAgICB2YXIgY3VycmVudGx5T3BlbiA9IHNpYmxpbmdzLnNlYXJjaChzZWxlY3RvcnMudG91Y2hJbmRpY2F0b3IgKyAnICE+ICogIT4gJyArIHNlbGVjdG9ycy5pdGVtICsgJy4nICsgc3RhdGVzLnNlbGVjdGVkKTtcbiAgICAgICAgICAgICAgICAoY3VycmVudGx5T3BlbiB8fCBbXSkuZm9yRWFjaChiaW5kKGZ1bmN0aW9uKG9wZW4pIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5jbG9zZURyb3Bkb3duKG9wZW4pO1xuICAgICAgICAgICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIGlmICgobWVudVR5cGUgPT0gJ21lZ2FtZW51JyB8fCAhcGFyZW50LnBhcmVudChzZWxlY3RvcnMubWFpbkNvbnRhaW5lcikpICYmIChwYXJlbnQuZmluZCgnID4gJyArIHNlbGVjdG9ycy5kcm9wZG93biArICcsID4gKiA+ICcgKyBzZWxlY3RvcnMuZHJvcGRvd24pIHx8IGlzR29pbmdCYWNrKSkge1xuICAgICAgICAgICAgdmFyIHN1YmxldmVsID0gdGFyZ2V0LnBhcmVudCgnLmctc3VibGV2ZWwnKSB8fCB0YXJnZXQucGFyZW50KCcuZy10b3BsZXZlbCcpLFxuICAgICAgICAgICAgICAgIHNsaWRlb3V0ID0gcGFyZW50LmZpbmQoJy5nLXN1YmxldmVsJyksXG4gICAgICAgICAgICAgICAgY29sdW1ucyAgPSBwYXJlbnQucGFyZW50KCcuZy1kcm9wZG93bi1jb2x1bW4nKSxcbiAgICAgICAgICAgICAgICBibG9ja3M7XG5cbiAgICAgICAgICAgIGlmIChzdWJsZXZlbCkge1xuICAgICAgICAgICAgICAgIHZhciBpc05hdk1lbnUgPSB0YXJnZXQucGFyZW50KHNlbGVjdG9ycy5tYWluQ29udGFpbmVyKTtcbiAgICAgICAgICAgICAgICBpZiAoIWlzTmF2TWVudSB8fCAoaXNOYXZNZW51ICYmICFzdWJsZXZlbC5tYXRjaGVzKCcuZy10b3BsZXZlbCcpKSkgeyB0aGlzLl9maXhIZWlnaHRzKHN1YmxldmVsLCBzbGlkZW91dCwgaXNHb2luZ0JhY2ssIGlzTmF2TWVudSk7IH1cbiAgICAgICAgICAgICAgICBpZiAoIWlzTmF2TWVudSAmJiBjb2x1bW5zICYmIChibG9ja3MgPSBjb2x1bW5zLnNlYXJjaCgnPiAuZy1ncmlkID4gLmctYmxvY2snKSkpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGJsb2Nrcy5sZW5ndGggPiAxKSB7IHN1YmxldmVsID0gYmxvY2tzLnNlYXJjaCgnPiAuZy1zdWJsZXZlbCcpOyB9XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgc3VibGV2ZWxbIWlzU2VsZWN0ZWQgPyAnYWRkQ2xhc3MnIDogJ3JlbW92ZUNsYXNzJ10oJ2ctc2xpZGUtb3V0Jyk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzWyFpc1NlbGVjdGVkID8gJ29wZW5Ecm9wZG93bicgOiAnY2xvc2VEcm9wZG93biddKHBhcmVudCk7XG4gICAgICAgIGlmIChldmVudC50eXBlICE9PSAnY2xpY2snKSB7IHRoaXMudG9nZ2xlT3ZlcmxheSh0YXJnZXQucGFyZW50KHNlbGVjdG9ycy5tYWluQ29udGFpbmVyKSk7IH1cbiAgICB9LFxuXG4gICAgb3BlbkRyb3Bkb3duOiBmdW5jdGlvbihlbGVtZW50KSB7XG4gICAgICAgIGVsZW1lbnQgPSAkKGVsZW1lbnQudGFyZ2V0IHx8IGVsZW1lbnQpO1xuICAgICAgICB2YXIgZHJvcGRvd24gPSBlbGVtZW50LmZpbmQodGhpcy5zZWxlY3RvcnMuZHJvcGRvd24pO1xuXG4gICAgICAgIGVsZW1lbnQuYWRkQ2xhc3ModGhpcy5zdGF0ZXMuc2VsZWN0ZWQpO1xuXG4gICAgICAgIGlmIChkcm9wZG93bikge1xuICAgICAgICAgICAgZHJvcGRvd24ucmVtb3ZlQ2xhc3ModGhpcy5zdGF0ZXMuaW5hY3RpdmUpLmFkZENsYXNzKHRoaXMuc3RhdGVzLmFjdGl2ZSk7XG4gICAgICAgIH1cbiAgICB9LFxuXG4gICAgY2xvc2VEcm9wZG93bjogZnVuY3Rpb24oZWxlbWVudCkge1xuICAgICAgICBlbGVtZW50ID0gJChlbGVtZW50LnRhcmdldCB8fCBlbGVtZW50KTtcbiAgICAgICAgdmFyIGRyb3Bkb3duID0gZWxlbWVudC5maW5kKHRoaXMuc2VsZWN0b3JzLmRyb3Bkb3duKTtcblxuICAgICAgICBlbGVtZW50LnJlbW92ZUNsYXNzKHRoaXMuc3RhdGVzLnNlbGVjdGVkKTtcblxuICAgICAgICBpZiAoZHJvcGRvd24pIHtcbiAgICAgICAgICAgIHZhciBzdWJsZXZlbHMgPSBkcm9wZG93bi5zZWFyY2goJy5nLXN1YmxldmVsJyksXG4gICAgICAgICAgICAgICAgc2xpZGVvdXRzID0gZHJvcGRvd24uc2VhcmNoKCcuZy1zbGlkZS1vdXQsIC4nICsgdGhpcy5zdGF0ZXMuc2VsZWN0ZWQpLFxuICAgICAgICAgICAgICAgIGFjdGl2ZXMgICA9IGRyb3Bkb3duLnNlYXJjaCgnLicgKyB0aGlzLnN0YXRlcy5hY3RpdmUpO1xuXG4gICAgICAgICAgICBpZiAoc3VibGV2ZWxzKSB7IHN1YmxldmVscy5hdHRyaWJ1dGUoJ3N0eWxlJywgbnVsbCk7IH1cbiAgICAgICAgICAgIGlmIChzbGlkZW91dHMpIHsgc2xpZGVvdXRzLnJlbW92ZUNsYXNzKCdnLXNsaWRlLW91dCcpLnJlbW92ZUNsYXNzKHRoaXMuc3RhdGVzLnNlbGVjdGVkKTsgfVxuICAgICAgICAgICAgaWYgKGFjdGl2ZXMpIHsgYWN0aXZlcy5yZW1vdmVDbGFzcyh0aGlzLnN0YXRlcy5hY3RpdmUpLmFkZENsYXNzKHRoaXMuc3RhdGVzLmluYWN0aXZlKTsgfVxuXG4gICAgICAgICAgICBkcm9wZG93bi5yZW1vdmVDbGFzcyh0aGlzLnN0YXRlcy5hY3RpdmUpLmFkZENsYXNzKHRoaXMuc3RhdGVzLmluYWN0aXZlKTtcbiAgICAgICAgfVxuICAgIH0sXG5cbiAgICBjbG9zZUFsbERyb3Bkb3duczogZnVuY3Rpb24oKSB7XG4gICAgICAgIHZhciBzZWxlY3RvcnMgPSB0aGlzLnNlbGVjdG9ycyxcbiAgICAgICAgICAgIHN0YXRlcyAgICA9IHRoaXMuc3RhdGVzLFxuICAgICAgICAgICAgdG9wTGV2ZWwgID0gJChzZWxlY3RvcnMubWFpbkNvbnRhaW5lciArICcgPiAuZy10b3BsZXZlbCcpLFxuICAgICAgICAgICAgcm9vdHMgICAgID0gdG9wTGV2ZWwuc2VhcmNoKCcgPicgKyBzZWxlY3RvcnMuaXRlbSk7XG5cbiAgICAgICAgaWYgKHJvb3RzKSB7IHJvb3RzLnJlbW92ZUNsYXNzKHN0YXRlcy5zZWxlY3RlZCk7IH1cbiAgICAgICAgaWYgKHRvcExldmVsKSB7XG4gICAgICAgICAgICB2YXIgYWxsUm9vdHMgPSB0b3BMZXZlbC5zZWFyY2goJz4gJyArIHRoaXMub3B0aW9ucy5zZWxlY3RvcnMuaXRlbSk7XG4gICAgICAgICAgICBpZiAoYWxsUm9vdHMpIHsgYWxsUm9vdHMuZm9yRWFjaCh0aGlzLmNsb3NlRHJvcGRvd24uYmluZCh0aGlzKSk7IH1cbiAgICAgICAgICAgIHRoaXMuY2xvc2VEcm9wZG93bih0b3BMZXZlbCk7XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLnRvZ2dsZU92ZXJsYXkodG9wTGV2ZWwpO1xuICAgIH0sXG5cbiAgICByZXNldFN0YXRlczogZnVuY3Rpb24obWVudSkge1xuICAgICAgICBpZiAoIW1lbnUpIHsgcmV0dXJuOyB9XG4gICAgICAgIHZhciBpdGVtcyAgID0gbWVudS5zZWFyY2goJy5nLXRvcGxldmVsLCAuZy1kcm9wZG93bi1jb2x1bW4sIC5nLWRyb3Bkb3duLCAuZy1zZWxlY3RlZCwgLmctYWN0aXZlLCAuZy1zbGlkZS1vdXQnKSxcbiAgICAgICAgICAgIGFjdGl2ZXMgPSBtZW51LnNlYXJjaCgnLmctYWN0aXZlJyk7XG4gICAgICAgIGlmICghaXRlbXMpIHsgcmV0dXJuOyB9XG5cbiAgICAgICAgbWVudS5hdHRyaWJ1dGUoJ3N0eWxlJywgbnVsbCkucmVtb3ZlQ2xhc3MoJ2ctc2VsZWN0ZWQnKS5yZW1vdmVDbGFzcygnZy1zbGlkZS1vdXQnKTtcbiAgICAgICAgaXRlbXMuYXR0cmlidXRlKCdzdHlsZScsIG51bGwpLnJlbW92ZUNsYXNzKCdnLXNlbGVjdGVkJykucmVtb3ZlQ2xhc3MoJ2ctc2xpZGUtb3V0Jyk7XG4gICAgICAgIGlmIChhY3RpdmVzKSB7IGFjdGl2ZXMucmVtb3ZlQ2xhc3MoJ2ctYWN0aXZlJykuYWRkQ2xhc3MoJ2ctaW5hY3RpdmUnKTsgfVxuICAgIH0sXG5cbiAgICB0b2dnbGVPdmVybGF5OiBmdW5jdGlvbihtZW51KSB7XG4gICAgICAgIGlmICghbWVudSkgeyByZXR1cm47IH1cbiAgICAgICAgdmFyIHNob3VsZE9wZW4gPSAhIW1lbnUuZmluZCgnLmctYWN0aXZlLCAuZy1zZWxlY3RlZCcpO1xuXG4gICAgICAgIHRoaXMub3ZlcmxheVtzaG91bGRPcGVuID8gJ2FkZENsYXNzJyA6ICdyZW1vdmVDbGFzcyddKCdnLW1lbnUtb3ZlcmxheS1vcGVuJyk7XG4gICAgICAgIHRoaXMub3ZlcmxheVswXS5zdHlsZS5vcGFjaXR5ID0gc2hvdWxkT3BlbiA/IDEgOiAwO1xuICAgIH0sXG5cbiAgICBfZml4SGVpZ2h0czogZnVuY3Rpb24ocGFyZW50LCBzdWJsZXZlbCwgaXNHb2luZ0JhY2ssIGlzTmF2TWVudSkge1xuICAgICAgICBpZiAocGFyZW50ID09IHN1YmxldmVsKSB7IHJldHVybjsgfVxuICAgICAgICBpZiAoaXNHb2luZ0JhY2spIHsgcGFyZW50LmF0dHJpYnV0ZSgnc3R5bGUnLCBudWxsKTsgfVxuXG4gICAgICAgIHZhciBoZWlnaHRzID0ge1xuICAgICAgICAgICAgICAgIGZyb206IHBhcmVudFswXS5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKSxcbiAgICAgICAgICAgICAgICB0bzogKCFpc05hdk1lbnUgPyBzdWJsZXZlbC5wYXJlbnQoJy5nLWRyb3Bkb3duJylbMF0gOiBzdWJsZXZlbFswXSkuZ2V0Qm91bmRpbmdDbGllbnRSZWN0KClcbiAgICAgICAgICAgIH0sXG4gICAgICAgICAgICBoZWlnaHQgID0gTWF0aC5tYXgoaGVpZ2h0cy5mcm9tLmhlaWdodCwgaGVpZ2h0cy50by5oZWlnaHQpO1xuXG4gICAgICAgIGlmICghaXNHb2luZ0JhY2spIHtcbiAgICAgICAgICAgIC8vIGlmIGZyb20gaGVpZ2h0IGlzIDwgdGhhbiB0byBoZWlnaHQgc2V0IHRoZSBwYXJlbnQgaGVpZ2h0IGVsc2UsIHNldCB0aGUgdGFyZ2V0XG4gICAgICAgICAgICBpZiAoaGVpZ2h0cy5mcm9tLmhlaWdodCA8IGhlaWdodHMudG8uaGVpZ2h0KSB7IHBhcmVudFswXS5zdHlsZS5oZWlnaHQgPSBoZWlnaHQgKyAncHgnOyB9XG4gICAgICAgICAgICBlbHNlIGlmIChpc05hdk1lbnUpIHsgc3VibGV2ZWxbMF0uc3R5bGUuaGVpZ2h0ID0gaGVpZ2h0ICsgJ3B4JzsgfVxuXG4gICAgICAgICAgICAvLyBmaXggc3VibGV2ZWxzIGhlaWdodHMgaW4gc2lkZSBtZW51IChvZmZjYW52YXMgZXRjKVxuICAgICAgICAgICAgaWYgKCFpc05hdk1lbnUpIHtcbiAgICAgICAgICAgICAgICB2YXIgbWF4SGVpZ2h0ID0gaGVpZ2h0LFxuICAgICAgICAgICAgICAgICAgICBibG9jayAgICAgPSAkKHN1YmxldmVsKS5wYXJlbnQoJy5nLWJsb2NrOm5vdCguc2l6ZS0xMDApJyksXG4gICAgICAgICAgICAgICAgICAgIGNvbHVtbiAgICA9IGJsb2NrID8gYmxvY2sucGFyZW50KCcuZy1kcm9wZG93bi1jb2x1bW4nKSA6IG51bGw7XG4gICAgICAgICAgICAgICAgKHN1YmxldmVsLnBhcmVudHMoJy5nLXNsaWRlLW91dCwgLmctZHJvcGRvd24tY29sdW1uJykgfHwgcGFyZW50KS5mb3JFYWNoKGZ1bmN0aW9uKHNsaWRlb3V0KSB7XG4gICAgICAgICAgICAgICAgICAgIG1heEhlaWdodCA9IE1hdGgubWF4KGhlaWdodCwgcGFyc2VJbnQoc2xpZGVvdXQuc3R5bGUuaGVpZ2h0IHx8IDAsIDEwKSk7XG4gICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICBpZiAoY29sdW1uKSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbHVtblswXS5zdHlsZS5oZWlnaHQgPSBtYXhIZWlnaHQgKyAncHgnO1xuXG4gICAgICAgICAgICAgICAgICAgIHZhciBibG9ja3MgPSBjb2x1bW4uc2VhcmNoKCc+IC5nLWdyaWQgPiAuZy1ibG9jaycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgZGlmZiAgID0gbWF4SGVpZ2h0O1xuXG4gICAgICAgICAgICAgICAgICAgIGJsb2Nrcy5mb3JFYWNoKGZ1bmN0aW9uKGJsb2NrLCBpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoKGkgKyAxKSAhPSBibG9ja3MubGVuZ3RoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZGlmZiAtPSBibG9jay5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKS5oZWlnaHQ7XG4gICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoYmxvY2spLmZpbmQoJy5nLXN1YmxldmVsJylbMF0uc3R5bGUuaGVpZ2h0ID0gZGlmZiArICdweCc7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuXG5cbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBzdWJsZXZlbFswXS5zdHlsZS5oZWlnaHQgPSBtYXhIZWlnaHQgKyAncHgnO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH0sXG5cbiAgICBfY2FsY3VsYXRlQnJlYWtwb2ludDogZnVuY3Rpb24odmFsdWUpIHtcbiAgICAgICAgdmFyIGRpZ2l0ICAgICA9IHBhcnNlRmxvYXQodmFsdWUubWF0Y2goL15cXGR7MSx9Lykuc2hpZnQoKSksXG4gICAgICAgICAgICB1bml0ICAgICAgPSB2YWx1ZS5tYXRjaCgvW2Etel17MSx9JC9pKS5zaGlmdCgpLFxuICAgICAgICAgICAgdG9sZXJhbmNlID0gdW5pdC5tYXRjaCgvcj9lbS8pID8gLTAuMDYyIDogLTE7XG5cbiAgICAgICAgcmV0dXJuIChkaWdpdCArIHRvbGVyYW5jZSkgKyB1bml0O1xuICAgIH0sXG5cbiAgICBfY2hlY2tRdWVyeTogZnVuY3Rpb24obXEpIHtcbiAgICAgICAgdmFyIHNlbGVjdG9ycyAgICAgICA9IHRoaXMub3B0aW9ucy5zZWxlY3RvcnMsXG4gICAgICAgICAgICBtb2JpbGVDb250YWluZXIgPSAkKHNlbGVjdG9ycy5tb2JpbGVDb250YWluZXIpLFxuICAgICAgICAgICAgbWFpbkNvbnRhaW5lciAgID0gJChzZWxlY3RvcnMubWFpbkNvbnRhaW5lciArIHNlbGVjdG9ycy5tb2JpbGVUYXJnZXQpIHx8ICQoc2VsZWN0b3JzLm1haW5Db250YWluZXIpLFxuICAgICAgICAgICAgZmluZCwgZHJvcGRvd25zO1xuXG4gICAgICAgIGlmIChtcS5tYXRjaGVzKSB7XG4gICAgICAgICAgICAvLyBtb3ZlIHRvIE1vYmlsZSBDb250YWluZXJcbiAgICAgICAgICAgIGZpbmQgPSBtYWluQ29udGFpbmVyLmZpbmQoc2VsZWN0b3JzLnRvcExldmVsKTtcbiAgICAgICAgICAgIGlmIChmaW5kKSB7XG4gICAgICAgICAgICAgICAgbWFpbkNvbnRhaW5lci5wYXJlbnQoJy5nLWJsb2NrJykuYWRkQ2xhc3MoJ2hpZGRlbicpO1xuICAgICAgICAgICAgICAgIG1vYmlsZUNvbnRhaW5lci5wYXJlbnQoJy5nLWJsb2NrJykucmVtb3ZlQ2xhc3MoJ2hpZGRlbicpO1xuICAgICAgICAgICAgICAgIGZpbmQudG9wKG1vYmlsZUNvbnRhaW5lcik7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAvLyBtb3ZlIGJhY2sgdG8gT3JpZ2luYWwgTG9jYXRpb25cbiAgICAgICAgICAgIGZpbmQgPSBtb2JpbGVDb250YWluZXIuZmluZChzZWxlY3RvcnMudG9wTGV2ZWwpO1xuICAgICAgICAgICAgaWYgKGZpbmQpIHtcbiAgICAgICAgICAgICAgICBtb2JpbGVDb250YWluZXIucGFyZW50KCcuZy1ibG9jaycpLmFkZENsYXNzKCdoaWRkZW4nKTtcbiAgICAgICAgICAgICAgICBtYWluQ29udGFpbmVyLnBhcmVudCgnLmctYmxvY2snKS5yZW1vdmVDbGFzcygnaGlkZGVuJyk7XG4gICAgICAgICAgICAgICAgZmluZC50b3AobWFpbkNvbnRhaW5lcik7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLnJlc2V0U3RhdGVzKGZpbmQpO1xuXG4gICAgICAgIC8vIHdlIG5lZWQgdG8gcmVpbnRyb2R1Y2UgZml4ZWQgd2lkdGhzIGZvciB0aG9zZSBkcm9wZG93bnMgdGhhdCBjb21lIHdpdGggaXRcbiAgICAgICAgaWYgKCFtcS5tYXRjaGVzICYmIChmaW5kICYmIChkcm9wZG93bnMgPSBmaW5kLnNlYXJjaCgnW2RhdGEtZy1pdGVtLXdpZHRoXScpKSkpIHtcbiAgICAgICAgICAgIGRyb3Bkb3ducy5mb3JFYWNoKGZ1bmN0aW9uKGRyb3Bkb3duKSB7XG4gICAgICAgICAgICAgICAgZHJvcGRvd24gPSAkKGRyb3Bkb3duKTtcbiAgICAgICAgICAgICAgICBkcm9wZG93blswXS5zdHlsZS53aWR0aCA9IGRyb3Bkb3duLmRhdGEoJ2ctaXRlbS13aWR0aCcpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICB9LFxuXG4gICAgX2RlYnVnOiBmdW5jdGlvbigpIHt9XG59KTtcblxubW9kdWxlLmV4cG9ydHMgPSBNZW51O1xuIiwiLy8gT2ZmY2FudmFzIHNsaWRlIHdpdGggZGVza3RvcCwgdG91Y2ggYW5kIGFsbC1pbi1vbmUgdG91Y2ggZGV2aWNlcyBzdXBwb3J0IHRoYXQgc3VwcG9ydHMgYm90aCBsZWZ0IGFuZCByaWdodCBwbGFjZW1lbnQuXG4vLyBGYXN0IGFuZCBvcHRpbWl6ZWQgdXNpbmcgQ1NTMyB0cmFuc2l0aW9uc1xuLy8gQmFzZWQgb24gdGhlIGF3ZXNvbWUgU2xpZGVvdXQuanMgPGh0dHBzOi8vbWFuZ28uZ2l0aHViLmlvL3NsaWRlb3V0Lz5cblxuXCJ1c2Ugc3RyaWN0XCI7XG5cbnZhciByZWFkeSAgICAgPSByZXF1aXJlKCdkb21yZWFkeScpLFxuICAgIHByaW1lICAgICA9IHJlcXVpcmUoJ3ByaW1lJyksXG4gICAgYmluZCAgICAgID0gcmVxdWlyZSgnbW91dC9mdW5jdGlvbi9iaW5kJyksXG4gICAgZm9yRWFjaCAgID0gcmVxdWlyZSgnbW91dC9hcnJheS9mb3JFYWNoJyksXG4gICAgbWFwTnVtYmVyID0gcmVxdWlyZSgnbW91dC9tYXRoL21hcCcpLFxuICAgIGNsYW1wICAgICA9IHJlcXVpcmUoJ21vdXQvbWF0aC9jbGFtcCcpLFxuICAgIHRpbWVvdXQgICA9IHJlcXVpcmUoJ21vdXQvZnVuY3Rpb24vdGltZW91dCcpLFxuICAgIHRyaW0gICAgICA9IHJlcXVpcmUoJ21vdXQvc3RyaW5nL3RyaW0nKSxcbiAgICBkZWNvdXBsZSAgPSByZXF1aXJlKCcuLi91dGlscy9kZWNvdXBsZScpLFxuICAgIEJvdW5kICAgICA9IHJlcXVpcmUoJ3ByaW1lLXV0aWwvcHJpbWUvYm91bmQnKSxcbiAgICBPcHRpb25zICAgPSByZXF1aXJlKCdwcmltZS11dGlsL3ByaW1lL29wdGlvbnMnKSxcbiAgICAkICAgICAgICAgPSByZXF1aXJlKCdlbGVtZW50cycpLFxuICAgIHplbiAgICAgICA9IHJlcXVpcmUoJ2VsZW1lbnRzL3plbicpO1xuXG4vLyB0aGFua3MgRGF2aWQgV2Fsc2hcbnZhciBwcmVmaXggPSAoZnVuY3Rpb24oKSB7XG4gICAgdmFyIHN0eWxlcyA9IHdpbmRvdy5nZXRDb21wdXRlZFN0eWxlKGRvY3VtZW50LmRvY3VtZW50RWxlbWVudCwgJycpLFxuICAgICAgICBwcmUgICAgPSAoQXJyYXkucHJvdG90eXBlLnNsaWNlLmNhbGwoc3R5bGVzKS5qb2luKCcnKVxuICAgICAgICAgICAgICAgIC5tYXRjaCgvLShtb3p8d2Via2l0fG1zKS0vKSB8fCAoc3R5bGVzLk9MaW5rID09PSAnJyAmJiBbJycsICdvJ10pXG4gICAgICAgIClbMV0sXG4gICAgICAgIGRvbSAgICA9ICgnV2ViS2l0fE1venxNU3xPJykubWF0Y2gobmV3IFJlZ0V4cCgnKCcgKyBwcmUgKyAnKScsICdpJykpWzFdO1xuICAgIHJldHVybiB7XG4gICAgICAgIGRvbTogZG9tLFxuICAgICAgICBsb3dlcmNhc2U6IHByZSxcbiAgICAgICAgY3NzOiAnLScgKyBwcmUgKyAnLScsXG4gICAgICAgIGpzOiBwcmVbMF0udG9VcHBlckNhc2UoKSArIHByZS5zdWJzdHIoMSlcbiAgICB9O1xufSkoKTtcblxudmFyIGhhc1RvdWNoRXZlbnRzID0gKCdvbnRvdWNoc3RhcnQnIGluIHdpbmRvdykgfHwgd2luZG93LkRvY3VtZW50VG91Y2ggJiYgZG9jdW1lbnQgaW5zdGFuY2VvZiBEb2N1bWVudFRvdWNoLFxuICAgIGlzU2Nyb2xsaW5nICAgID0gZmFsc2UsIHNjcm9sbFRpbWVvdXQ7XG5cbnZhciBPZmZjYW52YXMgPSBuZXcgcHJpbWUoe1xuXG4gICAgbWl4aW46IFtCb3VuZCwgT3B0aW9uc10sXG5cbiAgICBvcHRpb25zOiB7XG4gICAgICAgIGVmZmVjdDogJ2Vhc2UnLFxuICAgICAgICBkdXJhdGlvbjogMzAwLFxuICAgICAgICB0b2xlcmFuY2U6IGZ1bmN0aW9uKHBhZGRpbmcpIHsgLy8gdG9sZXJhbmNlIGNhbiBhbHNvIGJlIGp1c3QgYW4gaW50ZWdlciB2YWx1ZVxuICAgICAgICAgICAgcmV0dXJuIHBhZGRpbmcgLyAzO1xuICAgICAgICB9LFxuICAgICAgICBwYWRkaW5nOiAwLFxuICAgICAgICB0b3VjaDogdHJ1ZSxcbiAgICAgICAgY3NzMzogdHJ1ZSxcblxuICAgICAgICBvcGVuQ2xhc3M6ICdnLW9mZmNhbnZhcy1vcGVuJyxcbiAgICAgICAgb3BlbmluZ0NsYXNzOiAnZy1vZmZjYW52YXMtb3BlbmluZycsXG4gICAgICAgIGNsb3NpbmdDbGFzczogJ2ctb2ZmY2FudmFzLWNsb3NpbmcnLFxuICAgICAgICBvdmVybGF5Q2xhc3M6ICdnLW5hdi1vdmVybGF5J1xuICAgIH0sXG5cbiAgICBjb25zdHJ1Y3RvcjogZnVuY3Rpb24ob3B0aW9ucykge1xuICAgICAgICB0aGlzLnNldE9wdGlvbnMob3B0aW9ucyk7XG5cbiAgICAgICAgdGhpcy5hdHRhY2hlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLm9wZW5pbmcgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5tb3ZlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLmRyYWdnaW5nID0gZmFsc2U7XG4gICAgICAgIHRoaXMub3BlbmVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMucHJldmVudE9wZW4gPSBmYWxzZTtcbiAgICAgICAgdGhpcy5vZmZzZXQgPSB7XG4gICAgICAgICAgICB4OiB7XG4gICAgICAgICAgICAgICAgc3RhcnQ6IDAsXG4gICAgICAgICAgICAgICAgY3VycmVudDogMFxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIHk6IHtcbiAgICAgICAgICAgICAgICBzdGFydDogMCxcbiAgICAgICAgICAgICAgICBjdXJyZW50OiAwXG4gICAgICAgICAgICB9XG4gICAgICAgIH07XG5cbiAgICAgICAgdGhpcy5ib2R5RWwgPSAkKCdib2R5Jyk7XG4gICAgICAgIHRoaXMuaHRtbEVsID0gJCgnaHRtbCcpO1xuXG4gICAgICAgIHRoaXMucGFuZWwgPSAkKCcjZy1wYWdlLXN1cnJvdW5kJyk7XG4gICAgICAgIHRoaXMub2ZmY2FudmFzID0gJCgnI2ctb2ZmY2FudmFzJyk7XG5cbiAgICAgICAgaWYgKCF0aGlzLnBhbmVsIHx8ICF0aGlzLm9mZmNhbnZhcykgeyByZXR1cm4gZmFsc2U7IH1cblxuICAgICAgICB2YXIgc3dpcGUgPSB0aGlzLm9mZmNhbnZhcy5kYXRhKCdnLW9mZmNhbnZhcy1zd2lwZScpLFxuICAgICAgICAgICAgY3NzMyA9IHRoaXMub2ZmY2FudmFzLmRhdGEoJ2ctb2ZmY2FudmFzLWNzczMnKTtcbiAgICAgICAgdGhpcy5zZXRPcHRpb25zKHsgdG91Y2g6ICEhKHN3aXBlICE9PSBudWxsID8gcGFyc2VJbnQoc3dpcGUpIDogMSksIGNzczM6ICEhKGNzczMgIT09IG51bGwgPyBwYXJzZUludChjc3MzKSA6IDEpIH0pO1xuXG4gICAgICAgIGlmICghdGhpcy5vcHRpb25zLnBhZGRpbmcpIHtcbiAgICAgICAgICAgIHRoaXMub2ZmY2FudmFzWzBdLnN0eWxlLmRpc3BsYXkgPSAnYmxvY2snO1xuICAgICAgICAgICAgdmFyIHdpZHRoID0gdGhpcy5vZmZjYW52YXNbMF0uZ2V0Qm91bmRpbmdDbGllbnRSZWN0KCkud2lkdGg7XG4gICAgICAgICAgICB0aGlzLm9mZmNhbnZhc1swXS5zdHlsZS5yZW1vdmVQcm9wZXJ0eSgnZGlzcGxheScpO1xuXG4gICAgICAgICAgICB0aGlzLnNldE9wdGlvbnMoeyBwYWRkaW5nOiB3aWR0aCB9KTtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMudG9sZXJhbmNlID0gdHlwZW9mIHRoaXMub3B0aW9ucy50b2xlcmFuY2UgPT0gJ2Z1bmN0aW9uJyA/IHRoaXMub3B0aW9ucy50b2xlcmFuY2UuY2FsbCh0aGlzLCB0aGlzLm9wdGlvbnMucGFkZGluZykgOiB0aGlzLm9wdGlvbnMudG9sZXJhbmNlO1xuXG4gICAgICAgIHRoaXMuaHRtbEVsLmFkZENsYXNzKCdnLW9mZmNhbnZhcy0nICsgKHRoaXMub3B0aW9ucy5jc3MzID8gJ2NzczMnIDogJ2NzczInKSk7XG5cbiAgICAgICAgdGhpcy5hdHRhY2goKTtcbiAgICAgICAgdGhpcy5fY2hlY2tUb2dnbGVycygpO1xuXG4gICAgICAgIHJldHVybiB0aGlzO1xuICAgIH0sXG5cbiAgICBhdHRhY2g6IGZ1bmN0aW9uKCkge1xuICAgICAgICB0aGlzLmF0dGFjaGVkID0gdHJ1ZTtcblxuICAgICAgICBpZiAodGhpcy5vcHRpb25zLnRvdWNoICYmIGhhc1RvdWNoRXZlbnRzKSB7XG4gICAgICAgICAgICB0aGlzLmF0dGFjaFRvdWNoRXZlbnRzKCk7XG4gICAgICAgIH1cblxuICAgICAgICBmb3JFYWNoKFsndG9nZ2xlJywgJ29wZW4nLCAnY2xvc2UnXSwgYmluZChmdW5jdGlvbihtb2RlKSB7XG4gICAgICAgICAgICB0aGlzLmJvZHlFbC5kZWxlZ2F0ZSgnY2xpY2snLCAnW2RhdGEtb2ZmY2FudmFzLScgKyBtb2RlICsgJ10nLCB0aGlzLmJvdW5kKG1vZGUpKTtcbiAgICAgICAgICAgIGlmIChoYXNUb3VjaEV2ZW50cykgeyB0aGlzLmJvZHlFbC5kZWxlZ2F0ZSgndG91Y2hlbmQnLCAnW2RhdGEtb2ZmY2FudmFzLScgKyBtb2RlICsgJ10nLCB0aGlzLmJvdW5kKG1vZGUpKTsgfVxuICAgICAgICB9LCB0aGlzKSk7XG5cbiAgICAgICAgdGhpcy5hdHRhY2hNdXRhdGlvbkV2ZW50KCk7XG5cbiAgICAgICAgdGhpcy5vdmVybGF5ID0gemVuKCdkaXZbZGF0YS1vZmZjYW52YXMtY2xvc2VdLicgKyB0aGlzLm9wdGlvbnMub3ZlcmxheUNsYXNzKS50b3AodGhpcy5wYW5lbCk7XG5cbiAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgfSxcblxuICAgIGF0dGFjaE11dGF0aW9uRXZlbnQ6IGZ1bmN0aW9uKCkge1xuICAgICAgICB0aGlzLm9mZmNhbnZhcy5vbignRE9NU3VidHJlZU1vZGlmaWVkJywgdGhpcy5ib3VuZCgnX2NoZWNrVG9nZ2xlcnMnKSk7IC8vIElFOCA8IGhhcyBwcm9wZXJ0eWNoYW5nZVxuICAgIH0sXG5cbiAgICBhdHRhY2hUb3VjaEV2ZW50czogZnVuY3Rpb24oKSB7XG4gICAgICAgIHZhciBtc1BvaW50ZXJTdXBwb3J0ZWQgPSB3aW5kb3cubmF2aWdhdG9yLm1zUG9pbnRlckVuYWJsZWQsXG4gICAgICAgICAgICB0b3VjaCAgICAgICAgICAgICAgPSB7XG4gICAgICAgICAgICAgICAgc3RhcnQ6IG1zUG9pbnRlclN1cHBvcnRlZCA/ICdNU1BvaW50ZXJEb3duJyA6ICd0b3VjaHN0YXJ0JyxcbiAgICAgICAgICAgICAgICBtb3ZlOiBtc1BvaW50ZXJTdXBwb3J0ZWQgPyAnTVNQb2ludGVyTW92ZScgOiAndG91Y2htb3ZlJyxcbiAgICAgICAgICAgICAgICBlbmQ6IG1zUG9pbnRlclN1cHBvcnRlZCA/ICdNU1BvaW50ZXJVcCcgOiAndG91Y2hlbmQnXG4gICAgICAgICAgICB9O1xuXG4gICAgICAgIHRoaXMuX3Njcm9sbEJvdW5kID0gZGVjb3VwbGUod2luZG93LCAnc2Nyb2xsJywgdGhpcy5ib3VuZCgnX2JvZHlTY3JvbGwnKSk7XG4gICAgICAgIHRoaXMuYm9keUVsLm9uKHRvdWNoLm1vdmUsIHRoaXMuYm91bmQoJ19ib2R5TW92ZScpKTtcbiAgICAgICAgdGhpcy5wYW5lbC5vbih0b3VjaC5zdGFydCwgdGhpcy5ib3VuZCgnX3RvdWNoU3RhcnQnKSk7XG4gICAgICAgIHRoaXMucGFuZWwub24oJ3RvdWNoY2FuY2VsJywgdGhpcy5ib3VuZCgnX3RvdWNoQ2FuY2VsJykpO1xuICAgICAgICB0aGlzLnBhbmVsLm9uKHRvdWNoLmVuZCwgdGhpcy5ib3VuZCgnX3RvdWNoRW5kJykpO1xuICAgICAgICB0aGlzLnBhbmVsLm9uKHRvdWNoLm1vdmUsIHRoaXMuYm91bmQoJ190b3VjaE1vdmUnKSk7XG4gICAgfSxcblxuICAgIGRldGFjaDogZnVuY3Rpb24oKSB7XG4gICAgICAgIHRoaXMuYXR0YWNoZWQgPSBmYWxzZTtcblxuICAgICAgICBpZiAodGhpcy5vcHRpb25zLnRvdWNoICYmIGhhc1RvdWNoRXZlbnRzKSB7XG4gICAgICAgICAgICB0aGlzLmRldGFjaFRvdWNoRXZlbnRzKCk7XG4gICAgICAgIH1cblxuICAgICAgICBmb3JFYWNoKFsndG9nZ2xlJywgJ29wZW4nLCAnY2xvc2UnXSwgYmluZChmdW5jdGlvbihtb2RlKSB7XG4gICAgICAgICAgICB0aGlzLmJvZHlFbC51bmRlbGVnYXRlKCdjbGljaycsICdbZGF0YS1vZmZjYW52YXMtJyArIG1vZGUgKyAnXScsIHRoaXMuYm91bmQobW9kZSkpO1xuICAgICAgICAgICAgaWYgKGhhc1RvdWNoRXZlbnRzKSB7IHRoaXMuYm9keUVsLnVuZGVsZWdhdGUoJ3RvdWNoZW5kJywgJ1tkYXRhLW9mZmNhbnZhcy0nICsgbW9kZSArICddJywgdGhpcy5ib3VuZChtb2RlKSk7IH1cbiAgICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAgIHRoaXMuZGV0YWNoTXV0YXRpb25FdmVudCgpO1xuICAgICAgICB0aGlzLm92ZXJsYXkucmVtb3ZlKCk7XG5cbiAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgfSxcblxuICAgIGRldGFjaE11dGF0aW9uRXZlbnQ6IGZ1bmN0aW9uKCkge1xuICAgICAgICB0aGlzLm9mZmNhbnZhcy5vZmYoJ0RPTVN1YnRyZWVNb2RpZmllZCcsIHRoaXMuYm91bmQoJ19jaGVja1RvZ2dsZXJzJykpO1xuICAgIH0sXG5cbiAgICBkZXRhY2hUb3VjaEV2ZW50czogZnVuY3Rpb24oKSB7XG4gICAgICAgIHZhciBtc1BvaW50ZXJTdXBwb3J0ZWQgPSB3aW5kb3cubmF2aWdhdG9yLm1zUG9pbnRlckVuYWJsZWQsXG4gICAgICAgICAgICB0b3VjaCAgICAgICAgICAgICAgPSB7XG4gICAgICAgICAgICAgICAgc3RhcnQ6IG1zUG9pbnRlclN1cHBvcnRlZCA/ICdNU1BvaW50ZXJEb3duJyA6ICd0b3VjaHN0YXJ0JyxcbiAgICAgICAgICAgICAgICBtb3ZlOiBtc1BvaW50ZXJTdXBwb3J0ZWQgPyAnTVNQb2ludGVyTW92ZScgOiAndG91Y2htb3ZlJyxcbiAgICAgICAgICAgICAgICBlbmQ6IG1zUG9pbnRlclN1cHBvcnRlZCA/ICdNU1BvaW50ZXJVcCcgOiAndG91Y2hlbmQnXG4gICAgICAgICAgICB9O1xuXG4gICAgICAgIHdpbmRvdy5yZW1vdmVFdmVudExpc3RlbmVyKCdzY3JvbGwnLCB0aGlzLl9zY3JvbGxCb3VuZCk7XG4gICAgICAgIHRoaXMuYm9keUVsLm9mZih0b3VjaC5tb3ZlLCB0aGlzLmJvdW5kKCdfYm9keU1vdmUnKSk7XG4gICAgICAgIHRoaXMucGFuZWwub2ZmKHRvdWNoLnN0YXJ0LCB0aGlzLmJvdW5kKCdfdG91Y2hTdGFydCcpKTtcbiAgICAgICAgdGhpcy5wYW5lbC5vZmYoJ3RvdWNoY2FuY2VsJywgdGhpcy5ib3VuZCgnX3RvdWNoQ2FuY2VsJykpO1xuICAgICAgICB0aGlzLnBhbmVsLm9mZih0b3VjaC5lbmQsIHRoaXMuYm91bmQoJ190b3VjaEVuZCcpKTtcbiAgICAgICAgdGhpcy5wYW5lbC5vZmYodG91Y2gubW92ZSwgdGhpcy5ib3VuZCgnX3RvdWNoTW92ZScpKTtcbiAgICB9LFxuXG5cbiAgICBvcGVuOiBmdW5jdGlvbihldmVudCkge1xuICAgICAgICBpZiAoZXZlbnQgJiYgZXZlbnQudHlwZS5tYXRjaCgvXnRvdWNoL2kpKSB7IGV2ZW50LnByZXZlbnREZWZhdWx0KCk7IH1cbiAgICAgICAgZWxzZSB7IHRoaXMuZHJhZ2dpbmcgPSBmYWxzZTsgfVxuXG4gICAgICAgIGlmICh0aGlzLm9wZW5lZCkgeyByZXR1cm4gdGhpczsgfVxuXG4gICAgICAgIHRoaXMuaHRtbEVsLmFkZENsYXNzKHRoaXMub3B0aW9ucy5vcGVuQ2xhc3MpO1xuICAgICAgICB0aGlzLmh0bWxFbC5hZGRDbGFzcyh0aGlzLm9wdGlvbnMub3BlbmluZ0NsYXNzKTtcblxuICAgICAgICB0aGlzLm92ZXJsYXlbMF0uc3R5bGUub3BhY2l0eSA9IDE7XG5cbiAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5jc3MzKSB7XG4gICAgICAgICAgICAvLyBmb3IgdHJhbnNsYXRlM2RcbiAgICAgICAgICAgIHRoaXMucGFuZWxbMF0uc3R5bGVbdGhpcy5nZXRPZmZjYW52YXNQb3NpdGlvbigpXSA9ICdpbmhlcml0JztcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMuX3NldFRyYW5zaXRpb24oKTtcbiAgICAgICAgdGhpcy5fdHJhbnNsYXRlWFRvKCh0aGlzLmJvZHlFbC5oYXNDbGFzcygnZy1vZmZjYW52YXMtcmlnaHQnKSA/IC0xIDogMSkgKiB0aGlzLm9wdGlvbnMucGFkZGluZyk7XG4gICAgICAgIHRoaXMub3BlbmVkID0gdHJ1ZTtcblxuICAgICAgICBzZXRUaW1lb3V0KGJpbmQoZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICB2YXIgcGFuZWwgPSB0aGlzLnBhbmVsWzBdO1xuXG4gICAgICAgICAgICB0aGlzLmh0bWxFbC5yZW1vdmVDbGFzcyh0aGlzLm9wdGlvbnMub3BlbmluZ0NsYXNzKTtcbiAgICAgICAgICAgIHBhbmVsLnN0eWxlLnRyYW5zaXRpb24gPSBwYW5lbC5zdHlsZVtwcmVmaXguY3NzICsgJ3RyYW5zaXRpb24nXSA9ICcnO1xuICAgICAgICB9LCB0aGlzKSwgdGhpcy5vcHRpb25zLmR1cmF0aW9uKTtcblxuICAgICAgICByZXR1cm4gdGhpcztcbiAgICB9LFxuXG4gICAgY2xvc2U6IGZ1bmN0aW9uKGV2ZW50LCBlbGVtZW50KSB7XG4gICAgICAgIGlmIChldmVudCAmJiBldmVudC50eXBlLm1hdGNoKC9edG91Y2gvaSkpIHsgZXZlbnQucHJldmVudERlZmF1bHQoKTsgfVxuICAgICAgICBlbHNlIHsgdGhpcy5kcmFnZ2luZyA9IGZhbHNlOyB9XG5cbiAgICAgICAgZWxlbWVudCA9IGVsZW1lbnQgfHwgd2luZG93O1xuXG4gICAgICAgIGlmICghdGhpcy5vcGVuZWQgJiYgIXRoaXMub3BlbmluZykgeyByZXR1cm4gdGhpczsgfVxuICAgICAgICBpZiAodGhpcy5wYW5lbCAhPT0gZWxlbWVudCAmJiB0aGlzLmRyYWdnaW5nKSB7IHJldHVybiBmYWxzZTsgfVxuXG4gICAgICAgIHRoaXMuaHRtbEVsLmFkZENsYXNzKHRoaXMub3B0aW9ucy5jbG9zaW5nQ2xhc3MpO1xuXG4gICAgICAgIHRoaXMub3ZlcmxheVswXS5zdHlsZS5vcGFjaXR5ID0gMDtcblxuICAgICAgICB0aGlzLl9zZXRUcmFuc2l0aW9uKCk7XG4gICAgICAgIHRoaXMuX3RyYW5zbGF0ZVhUbygwKTtcbiAgICAgICAgdGhpcy5vcGVuZWQgPSBmYWxzZTtcblxuICAgICAgICBzZXRUaW1lb3V0KGJpbmQoZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICB2YXIgcGFuZWwgPSB0aGlzLnBhbmVsWzBdO1xuXG4gICAgICAgICAgICB0aGlzLmh0bWxFbC5yZW1vdmVDbGFzcyh0aGlzLm9wdGlvbnMub3BlbkNsYXNzKTtcbiAgICAgICAgICAgIHRoaXMuaHRtbEVsLnJlbW92ZUNsYXNzKHRoaXMub3B0aW9ucy5jbG9zaW5nQ2xhc3MpO1xuICAgICAgICAgICAgcGFuZWwuc3R5bGUudHJhbnNpdGlvbiA9IHBhbmVsLnN0eWxlW3ByZWZpeC5jc3MgKyAndHJhbnNpdGlvbiddID0gJyc7XG4gICAgICAgICAgICBwYW5lbC5zdHlsZS50cmFuc2Zvcm0gPSBwYW5lbC5zdHlsZVtwcmVmaXguY3NzICsgJ3RyYW5zZm9ybSddID0gJyc7XG4gICAgICAgICAgICBwYW5lbC5zdHlsZVt0aGlzLmdldE9mZmNhbnZhc1Bvc2l0aW9uKCldID0gJyc7XG4gICAgICAgIH0sIHRoaXMpLCB0aGlzLm9wdGlvbnMuZHVyYXRpb24pO1xuXG5cbiAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgfSxcblxuICAgIHRvZ2dsZTogZnVuY3Rpb24oZXZlbnQsIGVsZW1lbnQpIHtcbiAgICAgICAgaWYgKGV2ZW50ICYmIGV2ZW50LnR5cGUubWF0Y2goL150b3VjaC9pKSkgeyBldmVudC5wcmV2ZW50RGVmYXVsdCgpOyB9XG4gICAgICAgIGVsc2UgeyB0aGlzLmRyYWdnaW5nID0gZmFsc2U7IH1cblxuICAgICAgICByZXR1cm4gdGhpc1t0aGlzLm9wZW5lZCA/ICdjbG9zZScgOiAnb3BlbiddKGV2ZW50LCBlbGVtZW50KTtcbiAgICB9LFxuXG4gICAgZ2V0T2ZmY2FudmFzUG9zaXRpb246IGZ1bmN0aW9uKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5ib2R5RWwuaGFzQ2xhc3MoJ2ctb2ZmY2FudmFzLXJpZ2h0JykgPyAncmlnaHQnIDogJ2xlZnQnO1xuICAgIH0sXG5cbiAgICBfc2V0VHJhbnNpdGlvbjogZnVuY3Rpb24oKSB7XG4gICAgICAgIHZhciBwYW5lbCA9IHRoaXMucGFuZWxbMF07XG5cbiAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5jc3MzKSB7XG4gICAgICAgICAgICAvLyBmb3IgdHJhbnNsYXRlM2RcbiAgICAgICAgICAgIHBhbmVsLnN0eWxlW3ByZWZpeC5jc3MgKyAndHJhbnNpdGlvbiddID0gcGFuZWwuc3R5bGUudHJhbnNpdGlvbiA9IHByZWZpeC5jc3MgKyAndHJhbnNmb3JtICcgKyB0aGlzLm9wdGlvbnMuZHVyYXRpb24gKyAnbXMgJyArIHRoaXMub3B0aW9ucy5lZmZlY3Q7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAvLyBsZWZ0L3JpZ2h0IHRyYW5zaXRpb25cbiAgICAgICAgICAgIHBhbmVsLnN0eWxlW3ByZWZpeC5jc3MgKyAndHJhbnNpdGlvbiddID0gcGFuZWwuc3R5bGUudHJhbnNpdGlvbiA9ICdsZWZ0ICcgKyB0aGlzLm9wdGlvbnMuZHVyYXRpb24gKyAnbXMgJyArIHRoaXMub3B0aW9ucy5lZmZlY3QgKyAnLCByaWdodCAnICsgdGhpcy5vcHRpb25zLmR1cmF0aW9uICsgJ21zICcgKyB0aGlzLm9wdGlvbnMuZWZmZWN0O1xuICAgICAgICB9XG4gICAgfSxcblxuICAgIF90cmFuc2xhdGVYVG86IGZ1bmN0aW9uKHgpIHtcbiAgICAgICAgdmFyIHBhbmVsICAgICA9IHRoaXMucGFuZWxbMF0sXG4gICAgICAgICAgICBwbGFjZW1lbnQgPSB0aGlzLmdldE9mZmNhbnZhc1Bvc2l0aW9uKCk7XG5cbiAgICAgICAgdGhpcy5vZmZzZXQueC5jdXJyZW50ID0geDtcblxuICAgICAgICBpZiAodGhpcy5vcHRpb25zLmNzczMpIHtcbiAgICAgICAgICAgIC8vIGZvciB0cmFuc2xhdGUzZFxuICAgICAgICAgICAgcGFuZWwuc3R5bGVbcHJlZml4LmNzcyArICd0cmFuc2Zvcm0nXSA9IHBhbmVsLnN0eWxlLnRyYW5zZm9ybSA9ICd0cmFuc2xhdGUzZCgnICsgeCArICdweCwgMCwgMCknO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgLy8gbGVmdC9yaWdodCB0cmFuc2l0aW9uXG4gICAgICAgICAgICBwYW5lbC5zdHlsZVtwbGFjZW1lbnRdID0gTWF0aC5hYnMoeCkgKyAncHgnO1xuICAgICAgICB9XG4gICAgfSxcblxuICAgIF9ib2R5U2Nyb2xsOiBmdW5jdGlvbigpIHtcbiAgICAgICAgaWYgKCF0aGlzLm1vdmVkKSB7XG4gICAgICAgICAgICBjbGVhclRpbWVvdXQoc2Nyb2xsVGltZW91dCk7XG4gICAgICAgICAgICBpc1Njcm9sbGluZyA9IHRydWU7XG4gICAgICAgICAgICBzY3JvbGxUaW1lb3V0ID0gc2V0VGltZW91dChmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICBpc1Njcm9sbGluZyA9IGZhbHNlO1xuICAgICAgICAgICAgfSwgMjUwKTtcbiAgICAgICAgfVxuICAgIH0sXG5cbiAgICBfYm9keU1vdmU6IGZ1bmN0aW9uKCkge1xuICAgICAgICBpZiAodGhpcy5tb3ZlZCkgeyBldmVudC5wcmV2ZW50RGVmYXVsdCgpOyB9XG4gICAgICAgIHRoaXMuZHJhZ2dpbmcgPSB0cnVlO1xuXG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9LFxuXG4gICAgX3RvdWNoU3RhcnQ6IGZ1bmN0aW9uKGV2ZW50KSB7XG4gICAgICAgIGlmICghZXZlbnQudG91Y2hlcykgeyByZXR1cm47IH1cblxuICAgICAgICB0aGlzLm1vdmVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMub3BlbmluZyA9IGZhbHNlO1xuICAgICAgICB0aGlzLmRyYWdnaW5nID0gZmFsc2U7XG4gICAgICAgIHRoaXMub2Zmc2V0Lnguc3RhcnQgPSBldmVudC50b3VjaGVzWzBdLnBhZ2VYO1xuICAgICAgICB0aGlzLm9mZnNldC55LnN0YXJ0ID0gZXZlbnQudG91Y2hlc1swXS5wYWdlWTtcbiAgICAgICAgdGhpcy5wcmV2ZW50T3BlbiA9ICghdGhpcy5vcGVuZWQgJiYgdGhpcy5vZmZjYW52YXNbMF0uY2xpZW50V2lkdGggIT09IDApO1xuICAgIH0sXG5cbiAgICBfdG91Y2hDYW5jZWw6IGZ1bmN0aW9uKCkge1xuICAgICAgICB0aGlzLm1vdmVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMub3BlbmluZyA9IGZhbHNlO1xuICAgIH0sXG5cbiAgICBfdG91Y2hNb3ZlOiBmdW5jdGlvbihldmVudCkge1xuICAgICAgICBpZiAoaXNTY3JvbGxpbmcgfHwgdGhpcy5wcmV2ZW50T3BlbiB8fCAhZXZlbnQudG91Y2hlcykgeyByZXR1cm47IH1cbiAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5jc3MzKSB7XG4gICAgICAgICAgICB0aGlzLnBhbmVsWzBdLnN0eWxlW3RoaXMuZ2V0T2ZmY2FudmFzUG9zaXRpb24oKV0gPSAnaW5oZXJpdCc7XG4gICAgICAgIH1cblxuICAgICAgICB2YXIgcGxhY2VtZW50ICA9IHRoaXMuZ2V0T2ZmY2FudmFzUG9zaXRpb24oKSxcbiAgICAgICAgICAgIGRpZmZYICAgICAgPSBjbGFtcChldmVudC50b3VjaGVzWzBdLmNsaWVudFggLSB0aGlzLm9mZnNldC54LnN0YXJ0LCAtdGhpcy5vcHRpb25zLnBhZGRpbmcsIHRoaXMub3B0aW9ucy5wYWRkaW5nKSxcbiAgICAgICAgICAgIHRyYW5zbGF0ZVggPSB0aGlzLm9mZnNldC54LmN1cnJlbnQgPSBkaWZmWCxcbiAgICAgICAgICAgIGRpZmZZICA9IE1hdGguYWJzKGV2ZW50LnRvdWNoZXNbMF0ucGFnZVkgLSB0aGlzLm9mZnNldC55LnN0YXJ0KSxcbiAgICAgICAgICAgIG9mZnNldCA9IHBsYWNlbWVudCA9PSAncmlnaHQnID8gLTEgOiAxLFxuICAgICAgICAgICAgb3ZlcmxheU9wYWNpdHk7XG5cbiAgICAgICAgaWYgKE1hdGguYWJzKHRyYW5zbGF0ZVgpID4gdGhpcy5vcHRpb25zLnBhZGRpbmcpIHsgcmV0dXJuOyB9XG4gICAgICAgIGlmIChkaWZmWSA+IDUgJiYgIXRoaXMubW92ZWQpIHsgcmV0dXJuOyB9XG5cbiAgICAgICAgaWYgKE1hdGguYWJzKGRpZmZYKSA+IDApIHtcbiAgICAgICAgICAgIHRoaXMub3BlbmluZyA9IHRydWU7XG5cbiAgICAgICAgICAgIC8vIG9mZmNhbnZhcyBvbiBsZWZ0XG4gICAgICAgICAgICBpZiAocGxhY2VtZW50ID09ICdsZWZ0JyAmJiAodGhpcy5vcGVuZWQgJiYgZGlmZlggPiAwIHx8ICF0aGlzLm9wZW5lZCAmJiBkaWZmWCA8IDApKSB7IHJldHVybjsgfVxuXG4gICAgICAgICAgICAvLyBvZmZjYW52YXMgb24gcmlnaHRcbiAgICAgICAgICAgIGlmIChwbGFjZW1lbnQgPT0gJ3JpZ2h0JyAmJiAodGhpcy5vcGVuZWQgJiYgZGlmZlggPCAwIHx8ICF0aGlzLm9wZW5lZCAmJiBkaWZmWCA+IDApKSB7IHJldHVybjsgfVxuXG4gICAgICAgICAgICBpZiAoIXRoaXMubW92ZWQgJiYgIXRoaXMuaHRtbEVsLmhhc0NsYXNzKHRoaXMub3B0aW9ucy5vcGVuQ2xhc3MpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5odG1sRWwuYWRkQ2xhc3ModGhpcy5vcHRpb25zLm9wZW5DbGFzcyk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGlmICgocGxhY2VtZW50ID09ICdsZWZ0JyAmJiBkaWZmWCA8PSAwKSB8fCAocGxhY2VtZW50ID09ICdyaWdodCcgJiYgZGlmZlggPj0gMCkpIHtcbiAgICAgICAgICAgICAgICB0cmFuc2xhdGVYID0gZGlmZlggKyAob2Zmc2V0ICogdGhpcy5vcHRpb25zLnBhZGRpbmcpO1xuICAgICAgICAgICAgICAgIHRoaXMub3BlbmluZyA9IGZhbHNlO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBvdmVybGF5T3BhY2l0eSA9IG1hcE51bWJlcihNYXRoLmFicyh0cmFuc2xhdGVYKSwgMCwgdGhpcy5vcHRpb25zLnBhZGRpbmcsIDAsIDEpO1xuICAgICAgICAgICAgdGhpcy5vdmVybGF5WzBdLnN0eWxlLm9wYWNpdHkgPSBvdmVybGF5T3BhY2l0eTtcblxuICAgICAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5jc3MzKSB7XG4gICAgICAgICAgICAgICAgLy8gZm9yIHRyYW5zbGF0ZTNkXG4gICAgICAgICAgICAgICAgdGhpcy5wYW5lbFswXS5zdHlsZVtwcmVmaXguY3NzICsgJ3RyYW5zZm9ybSddID0gdGhpcy5wYW5lbFswXS5zdHlsZS50cmFuc2Zvcm0gPSAndHJhbnNsYXRlM2QoJyArIHRyYW5zbGF0ZVggKyAncHgsIDAsIDApJztcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgLy8gbGVmdC9yaWdodCB0cmFuc2l0aW9uXG4gICAgICAgICAgICAgICAgdGhpcy5wYW5lbFswXS5zdHlsZVtwbGFjZW1lbnRdID0gTWF0aC5hYnModHJhbnNsYXRlWCkgKyAncHgnO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICB0aGlzLm1vdmVkID0gdHJ1ZTtcbiAgICAgICAgfVxuICAgIH0sXG5cbiAgICBfdG91Y2hFbmQ6IGZ1bmN0aW9uKGV2ZW50KSB7XG4gICAgICAgIGlmICh0aGlzLm1vdmVkKSB7XG4gICAgICAgICAgICB2YXIgdG9sZXJhbmNlID0gTWF0aC5hYnModGhpcy5vZmZzZXQueC5jdXJyZW50KSA+IHRoaXMudG9sZXJhbmNlLFxuICAgICAgICAgICAgICAgIHBsYWNlbWVudCA9IHRoaXMuYm9keUVsLmhhc0NsYXNzKCdnLW9mZmNhbnZhcy1yaWdodCcpID8gdHJ1ZSA6IGZhbHNlLFxuICAgICAgICAgICAgICAgIGRpcmVjdGlvbiA9ICFwbGFjZW1lbnQgPyAodGhpcy5vZmZzZXQueC5jdXJyZW50IDwgMCkgOiAodGhpcy5vZmZzZXQueC5jdXJyZW50ID4gMCk7XG5cbiAgICAgICAgICAgIHRoaXMub3BlbmluZyA9IHRvbGVyYW5jZSA/ICFkaXJlY3Rpb24gOiBkaXJlY3Rpb247XG4gICAgICAgICAgICB0aGlzLm9wZW5lZCA9ICF0aGlzLm9wZW5pbmc7XG4gICAgICAgICAgICB0aGlzW3RoaXMub3BlbmluZyA/ICdvcGVuJyA6ICdjbG9zZSddKGV2ZW50LCB0aGlzLnBhbmVsKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMubW92ZWQgPSBmYWxzZTtcblxuICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICB9LFxuXG4gICAgX2NoZWNrVG9nZ2xlcnM6IGZ1bmN0aW9uKG11dGF0b3IpIHtcbiAgICAgICAgdmFyIHRvZ2dsZXJzICAgICAgICA9ICQoJ1tkYXRhLW9mZmNhbnZhcy10b2dnbGVdLCBbZGF0YS1vZmZjYW52YXMtb3Blbl0sIFtkYXRhLW9mZmNhbnZhcy1jbG9zZV0nKSxcbiAgICAgICAgICAgIG1vYmlsZUNvbnRhaW5lciA9ICQoJyNnLW1vYmlsZW1lbnUtY29udGFpbmVyJyksXG4gICAgICAgICAgICBibG9ja3MsIG1DdGV4dDtcblxuICAgICAgICBpZiAoIXRvZ2dsZXJzIHx8IChtdXRhdG9yICYmICgobXV0YXRvci50YXJnZXQgfHwgbXV0YXRvci5zcmNFbGVtZW50KSAhPT0gbW9iaWxlQ29udGFpbmVyWzBdKSkpIHsgcmV0dXJuOyB9XG4gICAgICAgIGlmICh0aGlzLm9wZW5lZCkgeyB0aGlzLmNsb3NlKCk7IH1cblxuICAgICAgICB0aW1lb3V0KGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgYmxvY2tzID0gdGhpcy5vZmZjYW52YXMuc2VhcmNoKCcuZy1ibG9jaycpO1xuICAgICAgICAgICAgbUN0ZXh0ID0gbW9iaWxlQ29udGFpbmVyID8gbW9iaWxlQ29udGFpbmVyLnRleHQoKS5sZW5ndGggOiAwO1xuICAgICAgICAgICAgdmFyIHNob3VsZENvbGxhcHNlID0gKGJsb2NrcyAmJiBibG9ja3MubGVuZ3RoID09IDEpICYmIG1vYmlsZUNvbnRhaW5lciAmJiAhdHJpbSh0aGlzLm9mZmNhbnZhcy50ZXh0KCkpLmxlbmd0aDtcblxuICAgICAgICAgICAgdG9nZ2xlcnNbc2hvdWxkQ29sbGFwc2UgPyAnYWRkQ2xhc3MnIDogJ3JlbW92ZUNsYXNzJ10oJ2ctb2ZmY2FudmFzLWhpZGUnKTtcbiAgICAgICAgICAgIGlmIChtb2JpbGVDb250YWluZXIpIHtcbiAgICAgICAgICAgICAgICBtb2JpbGVDb250YWluZXIucGFyZW50KCcuZy1ibG9jaycpWyFtQ3RleHQgPyAnYWRkQ2xhc3MnIDogJ3JlbW92ZUNsYXNzJ10oJ2hpZGRlbicpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBpZiAoIXNob3VsZENvbGxhcHNlICYmICF0aGlzLmF0dGFjaGVkKSB7IHRoaXMuYXR0YWNoKCk7IH1cbiAgICAgICAgICAgIGVsc2UgaWYgKHNob3VsZENvbGxhcHNlICYmIHRoaXMuYXR0YWNoZWQpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmRldGFjaCgpO1xuICAgICAgICAgICAgICAgIHRoaXMuYXR0YWNoTXV0YXRpb25FdmVudCgpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LCAwLCB0aGlzKTtcbiAgICB9XG59KTtcblxubW9kdWxlLmV4cG9ydHMgPSBPZmZjYW52YXM7XG4iLCJcInVzZSBzdHJpY3RcIjtcblxudmFyIHJlYWR5ID0gcmVxdWlyZSgnZG9tcmVhZHknKSxcbiAgICAkICAgICA9IHJlcXVpcmUoJy4uL3V0aWxzL2RvbGxhci1leHRyYXMnKTtcblxudmFyIHRpbWVPdXQsXG4gICAgc2Nyb2xsVG9Ub3AgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgaWYgKGRvY3VtZW50LmJvZHkuc2Nyb2xsVG9wICE9IDAgfHwgZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LnNjcm9sbFRvcCAhPSAwKSB7XG4gICAgICAgICAgICB3aW5kb3cuc2Nyb2xsQnkoMCwgLTUwKTtcbiAgICAgICAgICAgIHRpbWVPdXQgPSBzZXRUaW1lb3V0KHNjcm9sbFRvVG9wLCAxMCk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBjbGVhclRpbWVvdXQodGltZU91dCk7XG4gICAgICAgIH1cbiAgICB9O1xuXG5yZWFkeShmdW5jdGlvbigpIHtcbiAgICB2YXIgdG90b3AgPSAkKCcjZy10b3RvcCcpO1xuICAgIGlmICghdG90b3ApIHsgcmV0dXJuOyB9XG5cbiAgICB0b3RvcC5vbignY2xpY2snLCBmdW5jdGlvbihlKSB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgc2Nyb2xsVG9Ub3AoKTtcbiAgICB9KTtcbn0pO1xuXG5tb2R1bGUuZXhwb3J0cyA9IHt9O1xuIiwiJ3VzZSBzdHJpY3QnO1xuXG52YXIgckFGID0gKGZ1bmN0aW9uKCkge1xuICAgIHJldHVybiB3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lIHx8XG4gICAgICAgIHdpbmRvdy53ZWJraXRSZXF1ZXN0QW5pbWF0aW9uRnJhbWUgfHxcbiAgICAgICAgZnVuY3Rpb24oY2FsbGJhY2spIHsgd2luZG93LnNldFRpbWVvdXQoY2FsbGJhY2ssIDEwMDAgLyA2MCk7IH07XG59KCkpO1xuXG52YXIgZGVjb3VwbGUgPSBmdW5jdGlvbihlbGVtZW50LCBldmVudCwgY2FsbGJhY2spIHtcbiAgICB2YXIgZXZ0LCB0cmFja2luZyA9IGZhbHNlO1xuICAgIGVsZW1lbnQgPSBlbGVtZW50WzBdIHx8IGVsZW1lbnQ7XG5cbiAgICB2YXIgY2FwdHVyZSA9IGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgZXZ0ID0gZTtcbiAgICAgICAgdHJhY2soKTtcbiAgICB9O1xuXG4gICAgdmFyIHRyYWNrID0gZnVuY3Rpb24oKSB7XG4gICAgICAgIGlmICghdHJhY2tpbmcpIHtcbiAgICAgICAgICAgIHJBRih1cGRhdGUpO1xuICAgICAgICAgICAgdHJhY2tpbmcgPSB0cnVlO1xuICAgICAgICB9XG4gICAgfTtcblxuICAgIHZhciB1cGRhdGUgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgY2FsbGJhY2suY2FsbChlbGVtZW50LCBldnQpO1xuICAgICAgICB0cmFja2luZyA9IGZhbHNlO1xuICAgIH07XG5cbiAgICB0cnkge1xuICAgICAgICBlbGVtZW50LmFkZEV2ZW50TGlzdGVuZXIoZXZlbnQsIGNhcHR1cmUsIGZhbHNlKTtcbiAgICB9IGNhdGNoIChlKSB7fVxuXG4gICAgcmV0dXJuIGNhcHR1cmU7XG59O1xuXG5tb2R1bGUuZXhwb3J0cyA9IGRlY291cGxlOyIsIlwidXNlIHN0cmljdFwiO1xudmFyICQgICAgICAgICAgPSByZXF1aXJlKCdlbGVtZW50cycpLFxuICAgIG1hcCAgICAgICAgPSByZXF1aXJlKCdtb3V0L2FycmF5L21hcCcpLFxuICAgIHNsaWNrICAgICAgPSByZXF1aXJlKCdzbGljaycpO1xuXG52YXIgd2FsayA9IGZ1bmN0aW9uKGNvbWJpbmF0b3IsIG1ldGhvZCkge1xuXG4gICAgcmV0dXJuIGZ1bmN0aW9uKGV4cHJlc3Npb24pIHtcbiAgICAgICAgdmFyIHBhcnRzID0gc2xpY2sucGFyc2UoZXhwcmVzc2lvbiB8fCBcIipcIik7XG5cbiAgICAgICAgZXhwcmVzc2lvbiA9IG1hcChwYXJ0cywgZnVuY3Rpb24ocGFydCkge1xuICAgICAgICAgICAgcmV0dXJuIGNvbWJpbmF0b3IgKyBcIiBcIiArIHBhcnQ7XG4gICAgICAgIH0pLmpvaW4oJywgJyk7XG5cbiAgICAgICAgcmV0dXJuIHRoaXNbbWV0aG9kXShleHByZXNzaW9uKTtcbiAgICB9O1xuXG59O1xuXG5cbiQuaW1wbGVtZW50KHtcbiAgICBzaWJsaW5nOiB3YWxrKCcrKycsICdmaW5kJyksXG4gICAgc2libGluZ3M6IHdhbGsoJ35+JywgJ3NlYXJjaCcpXG59KTtcblxuXG5tb2R1bGUuZXhwb3J0cyA9ICQ7XG4iLCIvKiFcbiAgKiBkb21yZWFkeSAoYykgRHVzdGluIERpYXogMjAxNCAtIExpY2Vuc2UgTUlUXG4gICovXG4hZnVuY3Rpb24gKG5hbWUsIGRlZmluaXRpb24pIHtcblxuICBpZiAodHlwZW9mIG1vZHVsZSAhPSAndW5kZWZpbmVkJykgbW9kdWxlLmV4cG9ydHMgPSBkZWZpbml0aW9uKClcbiAgZWxzZSBpZiAodHlwZW9mIGRlZmluZSA9PSAnZnVuY3Rpb24nICYmIHR5cGVvZiBkZWZpbmUuYW1kID09ICdvYmplY3QnKSBkZWZpbmUoZGVmaW5pdGlvbilcbiAgZWxzZSB0aGlzW25hbWVdID0gZGVmaW5pdGlvbigpXG5cbn0oJ2RvbXJlYWR5JywgZnVuY3Rpb24gKCkge1xuXG4gIHZhciBmbnMgPSBbXSwgbGlzdGVuZXJcbiAgICAsIGRvYyA9IGRvY3VtZW50XG4gICAgLCBoYWNrID0gZG9jLmRvY3VtZW50RWxlbWVudC5kb1Njcm9sbFxuICAgICwgZG9tQ29udGVudExvYWRlZCA9ICdET01Db250ZW50TG9hZGVkJ1xuICAgICwgbG9hZGVkID0gKGhhY2sgPyAvXmxvYWRlZHxeYy8gOiAvXmxvYWRlZHxeaXxeYy8pLnRlc3QoZG9jLnJlYWR5U3RhdGUpXG5cblxuICBpZiAoIWxvYWRlZClcbiAgZG9jLmFkZEV2ZW50TGlzdGVuZXIoZG9tQ29udGVudExvYWRlZCwgbGlzdGVuZXIgPSBmdW5jdGlvbiAoKSB7XG4gICAgZG9jLnJlbW92ZUV2ZW50TGlzdGVuZXIoZG9tQ29udGVudExvYWRlZCwgbGlzdGVuZXIpXG4gICAgbG9hZGVkID0gMVxuICAgIHdoaWxlIChsaXN0ZW5lciA9IGZucy5zaGlmdCgpKSBsaXN0ZW5lcigpXG4gIH0pXG5cbiAgcmV0dXJuIGZ1bmN0aW9uIChmbikge1xuICAgIGxvYWRlZCA/IHNldFRpbWVvdXQoZm4sIDApIDogZm5zLnB1c2goZm4pXG4gIH1cblxufSk7XG4iLCIvKlxuYXR0cmlidXRlc1xuKi9cInVzZSBzdHJpY3RcIlxuXG52YXIgJCAgICAgICA9IHJlcXVpcmUoXCIuL2Jhc2VcIilcblxudmFyIHRyaW0gICAgPSByZXF1aXJlKFwibW91dC9zdHJpbmcvdHJpbVwiKSxcbiAgICBmb3JFYWNoID0gcmVxdWlyZShcIm1vdXQvYXJyYXkvZm9yRWFjaFwiKSxcbiAgICBmaWx0ZXIgID0gcmVxdWlyZShcIm1vdXQvYXJyYXkvZmlsdGVyXCIpLFxuICAgIGluZGV4T2YgPSByZXF1aXJlKFwibW91dC9hcnJheS9pbmRleE9mXCIpXG5cbi8vIGF0dHJpYnV0ZXNcblxuJC5pbXBsZW1lbnQoe1xuXG4gICAgc2V0QXR0cmlidXRlOiBmdW5jdGlvbihuYW1lLCB2YWx1ZSl7XG4gICAgICAgIHJldHVybiB0aGlzLmZvckVhY2goZnVuY3Rpb24obm9kZSl7XG4gICAgICAgICAgICBub2RlLnNldEF0dHJpYnV0ZShuYW1lLCB2YWx1ZSlcbiAgICAgICAgfSlcbiAgICB9LFxuXG4gICAgZ2V0QXR0cmlidXRlOiBmdW5jdGlvbihuYW1lKXtcbiAgICAgICAgdmFyIGF0dHIgPSB0aGlzWzBdLmdldEF0dHJpYnV0ZU5vZGUobmFtZSlcbiAgICAgICAgcmV0dXJuIChhdHRyICYmIGF0dHIuc3BlY2lmaWVkKSA/IGF0dHIudmFsdWUgOiBudWxsXG4gICAgfSxcblxuICAgIGhhc0F0dHJpYnV0ZTogZnVuY3Rpb24obmFtZSl7XG4gICAgICAgIHZhciBub2RlID0gdGhpc1swXVxuICAgICAgICBpZiAobm9kZS5oYXNBdHRyaWJ1dGUpIHJldHVybiBub2RlLmhhc0F0dHJpYnV0ZShuYW1lKVxuICAgICAgICB2YXIgYXR0ciA9IG5vZGUuZ2V0QXR0cmlidXRlTm9kZShuYW1lKVxuICAgICAgICByZXR1cm4gISEoYXR0ciAmJiBhdHRyLnNwZWNpZmllZClcbiAgICB9LFxuXG4gICAgcmVtb3ZlQXR0cmlidXRlOiBmdW5jdGlvbihuYW1lKXtcbiAgICAgICAgcmV0dXJuIHRoaXMuZm9yRWFjaChmdW5jdGlvbihub2RlKXtcbiAgICAgICAgICAgIHZhciBhdHRyID0gbm9kZS5nZXRBdHRyaWJ1dGVOb2RlKG5hbWUpXG4gICAgICAgICAgICBpZiAoYXR0cikgbm9kZS5yZW1vdmVBdHRyaWJ1dGVOb2RlKGF0dHIpXG4gICAgICAgIH0pXG4gICAgfVxuXG59KVxuXG52YXIgYWNjZXNzb3JzID0ge31cblxuZm9yRWFjaChbXCJ0eXBlXCIsIFwidmFsdWVcIiwgXCJuYW1lXCIsIFwiaHJlZlwiLCBcInRpdGxlXCIsIFwiaWRcIl0sIGZ1bmN0aW9uKG5hbWUpe1xuXG4gICAgYWNjZXNzb3JzW25hbWVdID0gZnVuY3Rpb24odmFsdWUpe1xuICAgICAgICByZXR1cm4gKHZhbHVlICE9PSB1bmRlZmluZWQpID8gdGhpcy5mb3JFYWNoKGZ1bmN0aW9uKG5vZGUpe1xuICAgICAgICAgICAgbm9kZVtuYW1lXSA9IHZhbHVlXG4gICAgICAgIH0pIDogdGhpc1swXVtuYW1lXVxuICAgIH1cblxufSlcblxuLy8gYm9vbGVhbnNcblxuZm9yRWFjaChbXCJjaGVja2VkXCIsIFwiZGlzYWJsZWRcIiwgXCJzZWxlY3RlZFwiXSwgZnVuY3Rpb24obmFtZSl7XG5cbiAgICBhY2Nlc3NvcnNbbmFtZV0gPSBmdW5jdGlvbih2YWx1ZSl7XG4gICAgICAgIHJldHVybiAodmFsdWUgIT09IHVuZGVmaW5lZCkgPyB0aGlzLmZvckVhY2goZnVuY3Rpb24obm9kZSl7XG4gICAgICAgICAgICBub2RlW25hbWVdID0gISF2YWx1ZVxuICAgICAgICB9KSA6ICEhdGhpc1swXVtuYW1lXVxuICAgIH1cblxufSlcblxuLy8gY2xhc3NOYW1lXG5cbnZhciBjbGFzc2VzID0gZnVuY3Rpb24oY2xhc3NOYW1lKXtcbiAgICB2YXIgY2xhc3NOYW1lcyA9IHRyaW0oY2xhc3NOYW1lKS5yZXBsYWNlKC9cXHMrL2csIFwiIFwiKS5zcGxpdChcIiBcIiksXG4gICAgICAgIHVuaXF1ZXMgICAgPSB7fVxuXG4gICAgcmV0dXJuIGZpbHRlcihjbGFzc05hbWVzLCBmdW5jdGlvbihjbGFzc05hbWUpe1xuICAgICAgICBpZiAoY2xhc3NOYW1lICE9PSBcIlwiICYmICF1bmlxdWVzW2NsYXNzTmFtZV0pIHJldHVybiB1bmlxdWVzW2NsYXNzTmFtZV0gPSBjbGFzc05hbWVcbiAgICB9KS5zb3J0KClcbn1cblxuYWNjZXNzb3JzLmNsYXNzTmFtZSA9IGZ1bmN0aW9uKGNsYXNzTmFtZSl7XG4gICAgcmV0dXJuIChjbGFzc05hbWUgIT09IHVuZGVmaW5lZCkgPyB0aGlzLmZvckVhY2goZnVuY3Rpb24obm9kZSl7XG4gICAgICAgIG5vZGUuY2xhc3NOYW1lID0gY2xhc3NlcyhjbGFzc05hbWUpLmpvaW4oXCIgXCIpXG4gICAgfSkgOiBjbGFzc2VzKHRoaXNbMF0uY2xhc3NOYW1lKS5qb2luKFwiIFwiKVxufVxuXG4vLyBhdHRyaWJ1dGVcblxuJC5pbXBsZW1lbnQoe1xuXG4gICAgYXR0cmlidXRlOiBmdW5jdGlvbihuYW1lLCB2YWx1ZSl7XG4gICAgICAgIHZhciBhY2Nlc3NvciA9IGFjY2Vzc29yc1tuYW1lXVxuICAgICAgICBpZiAoYWNjZXNzb3IpIHJldHVybiBhY2Nlc3Nvci5jYWxsKHRoaXMsIHZhbHVlKVxuICAgICAgICBpZiAodmFsdWUgIT0gbnVsbCkgcmV0dXJuIHRoaXMuc2V0QXR0cmlidXRlKG5hbWUsIHZhbHVlKVxuICAgICAgICBpZiAodmFsdWUgPT09IG51bGwpIHJldHVybiB0aGlzLnJlbW92ZUF0dHJpYnV0ZShuYW1lKVxuICAgICAgICBpZiAodmFsdWUgPT09IHVuZGVmaW5lZCkgcmV0dXJuIHRoaXMuZ2V0QXR0cmlidXRlKG5hbWUpXG4gICAgfVxuXG59KVxuXG4kLmltcGxlbWVudChhY2Nlc3NvcnMpXG5cbi8vIHNob3J0Y3V0c1xuXG4kLmltcGxlbWVudCh7XG5cbiAgICBjaGVjazogZnVuY3Rpb24oKXtcbiAgICAgICAgcmV0dXJuIHRoaXMuY2hlY2tlZCh0cnVlKVxuICAgIH0sXG5cbiAgICB1bmNoZWNrOiBmdW5jdGlvbigpe1xuICAgICAgICByZXR1cm4gdGhpcy5jaGVja2VkKGZhbHNlKVxuICAgIH0sXG5cbiAgICBkaXNhYmxlOiBmdW5jdGlvbigpe1xuICAgICAgICByZXR1cm4gdGhpcy5kaXNhYmxlZCh0cnVlKVxuICAgIH0sXG5cbiAgICBlbmFibGU6IGZ1bmN0aW9uKCl7XG4gICAgICAgIHJldHVybiB0aGlzLmRpc2FibGVkKGZhbHNlKVxuICAgIH0sXG5cbiAgICBzZWxlY3Q6IGZ1bmN0aW9uKCl7XG4gICAgICAgIHJldHVybiB0aGlzLnNlbGVjdGVkKHRydWUpXG4gICAgfSxcblxuICAgIGRlc2VsZWN0OiBmdW5jdGlvbigpe1xuICAgICAgICByZXR1cm4gdGhpcy5zZWxlY3RlZChmYWxzZSlcbiAgICB9XG5cbn0pXG5cbi8vIGNsYXNzTmFtZXMsIGhhcyAvIGFkZCAvIHJlbW92ZSBDbGFzc1xuXG4kLmltcGxlbWVudCh7XG5cbiAgICBjbGFzc05hbWVzOiBmdW5jdGlvbigpe1xuICAgICAgICByZXR1cm4gY2xhc3Nlcyh0aGlzWzBdLmNsYXNzTmFtZSlcbiAgICB9LFxuXG4gICAgaGFzQ2xhc3M6IGZ1bmN0aW9uKGNsYXNzTmFtZSl7XG4gICAgICAgIHJldHVybiBpbmRleE9mKHRoaXMuY2xhc3NOYW1lcygpLCBjbGFzc05hbWUpID4gLTFcbiAgICB9LFxuXG4gICAgYWRkQ2xhc3M6IGZ1bmN0aW9uKGNsYXNzTmFtZSl7XG4gICAgICAgIHJldHVybiB0aGlzLmZvckVhY2goZnVuY3Rpb24obm9kZSl7XG4gICAgICAgICAgICB2YXIgbm9kZUNsYXNzTmFtZSA9IG5vZGUuY2xhc3NOYW1lXG4gICAgICAgICAgICB2YXIgY2xhc3NOYW1lcyA9IGNsYXNzZXMobm9kZUNsYXNzTmFtZSArIFwiIFwiICsgY2xhc3NOYW1lKS5qb2luKFwiIFwiKVxuICAgICAgICAgICAgaWYgKG5vZGVDbGFzc05hbWUgIT09IGNsYXNzTmFtZXMpIG5vZGUuY2xhc3NOYW1lID0gY2xhc3NOYW1lc1xuICAgICAgICB9KVxuICAgIH0sXG5cbiAgICByZW1vdmVDbGFzczogZnVuY3Rpb24oY2xhc3NOYW1lKXtcbiAgICAgICAgcmV0dXJuIHRoaXMuZm9yRWFjaChmdW5jdGlvbihub2RlKXtcbiAgICAgICAgICAgIHZhciBjbGFzc05hbWVzID0gY2xhc3Nlcyhub2RlLmNsYXNzTmFtZSlcbiAgICAgICAgICAgIGZvckVhY2goY2xhc3NlcyhjbGFzc05hbWUpLCBmdW5jdGlvbihjbGFzc05hbWUpe1xuICAgICAgICAgICAgICAgIHZhciBpbmRleCA9IGluZGV4T2YoY2xhc3NOYW1lcywgY2xhc3NOYW1lKVxuICAgICAgICAgICAgICAgIGlmIChpbmRleCA+IC0xKSBjbGFzc05hbWVzLnNwbGljZShpbmRleCwgMSlcbiAgICAgICAgICAgIH0pXG4gICAgICAgICAgICBub2RlLmNsYXNzTmFtZSA9IGNsYXNzTmFtZXMuam9pbihcIiBcIilcbiAgICAgICAgfSlcbiAgICB9LFxuXG4gICAgdG9nZ2xlQ2xhc3M6IGZ1bmN0aW9uKGNsYXNzTmFtZSwgZm9yY2Upe1xuICAgICAgICB2YXIgYWRkID0gZm9yY2UgIT09IHVuZGVmaW5lZCA/IGZvcmNlIDogIXRoaXMuaGFzQ2xhc3MoY2xhc3NOYW1lKVxuICAgICAgICBpZiAoYWRkKVxuICAgICAgICAgICAgdGhpcy5hZGRDbGFzcyhjbGFzc05hbWUpXG4gICAgICAgIGVsc2VcbiAgICAgICAgICAgIHRoaXMucmVtb3ZlQ2xhc3MoY2xhc3NOYW1lKVxuICAgICAgICByZXR1cm4gISFhZGRcbiAgICB9XG5cbn0pXG5cbi8vIHRvU3RyaW5nXG5cbiQucHJvdG90eXBlLnRvU3RyaW5nID0gZnVuY3Rpb24oKXtcbiAgICB2YXIgdGFnICAgICA9IHRoaXMudGFnKCksXG4gICAgICAgIGlkICAgICAgPSB0aGlzLmlkKCksXG4gICAgICAgIGNsYXNzZXMgPSB0aGlzLmNsYXNzTmFtZXMoKVxuXG4gICAgdmFyIHN0ciA9IHRhZ1xuICAgIGlmIChpZCkgc3RyICs9ICcjJyArIGlkXG4gICAgaWYgKGNsYXNzZXMubGVuZ3RoKSBzdHIgKz0gJy4nICsgY2xhc3Nlcy5qb2luKFwiLlwiKVxuICAgIHJldHVybiBzdHJcbn1cblxudmFyIHRleHRQcm9wZXJ0eSA9IChkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKS50ZXh0Q29udGVudCA9PSBudWxsKSA/ICdpbm5lclRleHQnIDogJ3RleHRDb250ZW50J1xuXG4vLyB0YWcsIGh0bWwsIHRleHQsIGRhdGFcblxuJC5pbXBsZW1lbnQoe1xuXG4gICAgdGFnOiBmdW5jdGlvbigpe1xuICAgICAgICByZXR1cm4gdGhpc1swXS50YWdOYW1lLnRvTG93ZXJDYXNlKClcbiAgICB9LFxuXG4gICAgaHRtbDogZnVuY3Rpb24oaHRtbCl7XG4gICAgICAgIHJldHVybiAoaHRtbCAhPT0gdW5kZWZpbmVkKSA/IHRoaXMuZm9yRWFjaChmdW5jdGlvbihub2RlKXtcbiAgICAgICAgICAgIG5vZGUuaW5uZXJIVE1MID0gaHRtbFxuICAgICAgICB9KSA6IHRoaXNbMF0uaW5uZXJIVE1MXG4gICAgfSxcblxuICAgIHRleHQ6IGZ1bmN0aW9uKHRleHQpe1xuICAgICAgICByZXR1cm4gKHRleHQgIT09IHVuZGVmaW5lZCkgPyB0aGlzLmZvckVhY2goZnVuY3Rpb24obm9kZSl7XG4gICAgICAgICAgICBub2RlW3RleHRQcm9wZXJ0eV0gPSB0ZXh0XG4gICAgICAgIH0pIDogdGhpc1swXVt0ZXh0UHJvcGVydHldXG4gICAgfSxcblxuICAgIGRhdGE6IGZ1bmN0aW9uKGtleSwgdmFsdWUpe1xuICAgICAgICBzd2l0Y2godmFsdWUpIHtcbiAgICAgICAgICAgIGNhc2UgdW5kZWZpbmVkOiByZXR1cm4gdGhpcy5nZXRBdHRyaWJ1dGUoXCJkYXRhLVwiICsga2V5KVxuICAgICAgICAgICAgY2FzZSBudWxsOiByZXR1cm4gdGhpcy5yZW1vdmVBdHRyaWJ1dGUoXCJkYXRhLVwiICsga2V5KVxuICAgICAgICAgICAgZGVmYXVsdDogcmV0dXJuIHRoaXMuc2V0QXR0cmlidXRlKFwiZGF0YS1cIiArIGtleSwgdmFsdWUpXG4gICAgICAgIH1cbiAgICB9XG5cbn0pXG5cbm1vZHVsZS5leHBvcnRzID0gJFxuIiwiLypcbmVsZW1lbnRzXG4qL1widXNlIHN0cmljdFwiXG5cbnZhciBwcmltZSAgID0gcmVxdWlyZShcInByaW1lXCIpXG5cbnZhciBmb3JFYWNoID0gcmVxdWlyZShcIm1vdXQvYXJyYXkvZm9yRWFjaFwiKSxcbiAgICBtYXAgICAgID0gcmVxdWlyZShcIm1vdXQvYXJyYXkvbWFwXCIpLFxuICAgIGZpbHRlciAgPSByZXF1aXJlKFwibW91dC9hcnJheS9maWx0ZXJcIiksXG4gICAgZXZlcnkgICA9IHJlcXVpcmUoXCJtb3V0L2FycmF5L2V2ZXJ5XCIpLFxuICAgIHNvbWUgICAgPSByZXF1aXJlKFwibW91dC9hcnJheS9zb21lXCIpXG5cbi8vIHVuaXF1ZUlEXG5cbnZhciBpbmRleCA9IDAsXG4gICAgX19kYyA9IGRvY3VtZW50Ll9fY291bnRlcixcbiAgICBjb3VudGVyID0gZG9jdW1lbnQuX19jb3VudGVyID0gKF9fZGMgPyBwYXJzZUludChfX2RjLCAzNikgKyAxIDogMCkudG9TdHJpbmcoMzYpLFxuICAgIGtleSA9IFwidWlkOlwiICsgY291bnRlclxuXG52YXIgdW5pcXVlSUQgPSBmdW5jdGlvbihuKXtcbiAgICBpZiAobiA9PT0gd2luZG93KSByZXR1cm4gXCJ3aW5kb3dcIlxuICAgIGlmIChuID09PSBkb2N1bWVudCkgcmV0dXJuIFwiZG9jdW1lbnRcIlxuICAgIGlmIChuID09PSBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQpIHJldHVybiBcImh0bWxcIlxuICAgIHJldHVybiBuW2tleV0gfHwgKG5ba2V5XSA9IChpbmRleCsrKS50b1N0cmluZygzNikpXG59XG5cbnZhciBpbnN0YW5jZXMgPSB7fVxuXG4vLyBlbGVtZW50cyBwcmltZVxuXG52YXIgJCA9IHByaW1lKHtjb25zdHJ1Y3RvcjogZnVuY3Rpb24gJChuLCBjb250ZXh0KXtcblxuICAgIGlmIChuID09IG51bGwpIHJldHVybiAodGhpcyAmJiB0aGlzLmNvbnN0cnVjdG9yID09PSAkKSA/IG5ldyBFbGVtZW50cyA6IG51bGxcblxuICAgIHZhciBzZWxmLCB1aWRcblxuICAgIGlmIChuLmNvbnN0cnVjdG9yICE9PSBFbGVtZW50cyl7XG5cbiAgICAgICAgc2VsZiA9IG5ldyBFbGVtZW50c1xuXG4gICAgICAgIGlmICh0eXBlb2YgbiA9PT0gXCJzdHJpbmdcIil7XG4gICAgICAgICAgICBpZiAoIXNlbGYuc2VhcmNoKSByZXR1cm4gbnVsbFxuICAgICAgICAgICAgc2VsZltzZWxmLmxlbmd0aCsrXSA9IGNvbnRleHQgfHwgZG9jdW1lbnRcbiAgICAgICAgICAgIHJldHVybiBzZWxmLnNlYXJjaChuKVxuICAgICAgICB9XG5cbiAgICAgICAgaWYgKG4ubm9kZVR5cGUgfHwgbiA9PT0gd2luZG93KXtcblxuICAgICAgICAgICAgc2VsZltzZWxmLmxlbmd0aCsrXSA9IG5cblxuICAgICAgICB9IGVsc2UgaWYgKG4ubGVuZ3RoKXtcblxuICAgICAgICAgICAgLy8gdGhpcyBjb3VsZCBiZSBhbiBhcnJheSwgb3IgYW55IG9iamVjdCB3aXRoIGEgbGVuZ3RoIGF0dHJpYnV0ZSxcbiAgICAgICAgICAgIC8vIGluY2x1ZGluZyBhbm90aGVyIGluc3RhbmNlIG9mIGVsZW1lbnRzIGZyb20gYW5vdGhlciBpbnRlcmZhY2UuXG5cbiAgICAgICAgICAgIHZhciB1bmlxdWVzID0ge31cblxuICAgICAgICAgICAgZm9yICh2YXIgaSA9IDAsIGwgPSBuLmxlbmd0aDsgaSA8IGw7IGkrKyl7IC8vIHBlcmZvcm0gZWxlbWVudHMgZmxhdHRlbmluZ1xuICAgICAgICAgICAgICAgIHZhciBub2RlcyA9ICQobltpXSwgY29udGV4dClcbiAgICAgICAgICAgICAgICBpZiAobm9kZXMgJiYgbm9kZXMubGVuZ3RoKSBmb3IgKHZhciBqID0gMCwgayA9IG5vZGVzLmxlbmd0aDsgaiA8IGs7IGorKyl7XG4gICAgICAgICAgICAgICAgICAgIHZhciBub2RlID0gbm9kZXNbal1cbiAgICAgICAgICAgICAgICAgICAgdWlkID0gdW5pcXVlSUQobm9kZSlcbiAgICAgICAgICAgICAgICAgICAgaWYgKCF1bmlxdWVzW3VpZF0pe1xuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZltzZWxmLmxlbmd0aCsrXSA9IG5vZGVcbiAgICAgICAgICAgICAgICAgICAgICAgIHVuaXF1ZXNbdWlkXSA9IHRydWVcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cblxuICAgICAgICB9XG5cbiAgICB9IGVsc2Uge1xuICAgICAgc2VsZiA9IG5cbiAgICB9XG5cbiAgICBpZiAoIXNlbGYubGVuZ3RoKSByZXR1cm4gbnVsbFxuXG4gICAgLy8gd2hlbiBsZW5ndGggaXMgMSBhbHdheXMgdXNlIHRoZSBzYW1lIGVsZW1lbnRzIGluc3RhbmNlXG5cbiAgICBpZiAoc2VsZi5sZW5ndGggPT09IDEpe1xuICAgICAgICB1aWQgPSB1bmlxdWVJRChzZWxmWzBdKVxuICAgICAgICByZXR1cm4gaW5zdGFuY2VzW3VpZF0gfHwgKGluc3RhbmNlc1t1aWRdID0gc2VsZilcbiAgICB9XG5cbiAgICByZXR1cm4gc2VsZlxuXG59fSlcblxudmFyIEVsZW1lbnRzID0gcHJpbWUoe1xuXG4gICAgaW5oZXJpdHM6ICQsXG5cbiAgICBjb25zdHJ1Y3RvcjogZnVuY3Rpb24gRWxlbWVudHMoKXtcbiAgICAgICAgdGhpcy5sZW5ndGggPSAwXG4gICAgfSxcblxuICAgIHVubGluazogZnVuY3Rpb24oKXtcbiAgICAgICAgcmV0dXJuIHRoaXMubWFwKGZ1bmN0aW9uKG5vZGUpe1xuICAgICAgICAgICAgZGVsZXRlIGluc3RhbmNlc1t1bmlxdWVJRChub2RlKV1cbiAgICAgICAgICAgIHJldHVybiBub2RlXG4gICAgICAgIH0pXG4gICAgfSxcblxuICAgIC8vIG1ldGhvZHNcblxuICAgIGZvckVhY2g6IGZ1bmN0aW9uKG1ldGhvZCwgY29udGV4dCl7XG4gICAgICAgIGZvckVhY2godGhpcywgbWV0aG9kLCBjb250ZXh0KVxuICAgICAgICByZXR1cm4gdGhpc1xuICAgIH0sXG5cbiAgICBtYXA6IGZ1bmN0aW9uKG1ldGhvZCwgY29udGV4dCl7XG4gICAgICAgIHJldHVybiBtYXAodGhpcywgbWV0aG9kLCBjb250ZXh0KVxuICAgIH0sXG5cbiAgICBmaWx0ZXI6IGZ1bmN0aW9uKG1ldGhvZCwgY29udGV4dCl7XG4gICAgICAgIHJldHVybiBmaWx0ZXIodGhpcywgbWV0aG9kLCBjb250ZXh0KVxuICAgIH0sXG5cbiAgICBldmVyeTogZnVuY3Rpb24obWV0aG9kLCBjb250ZXh0KXtcbiAgICAgICAgcmV0dXJuIGV2ZXJ5KHRoaXMsIG1ldGhvZCwgY29udGV4dClcbiAgICB9LFxuXG4gICAgc29tZTogZnVuY3Rpb24obWV0aG9kLCBjb250ZXh0KXtcbiAgICAgICAgcmV0dXJuIHNvbWUodGhpcywgbWV0aG9kLCBjb250ZXh0KVxuICAgIH1cblxufSlcblxubW9kdWxlLmV4cG9ydHMgPSAkXG4iLCIvKlxuZGVsZWdhdGlvblxuKi9cInVzZSBzdHJpY3RcIlxuXG52YXIgTWFwID0gcmVxdWlyZShcInByaW1lL21hcFwiKVxuXG52YXIgJCA9IHJlcXVpcmUoXCIuL2V2ZW50c1wiKVxuICAgICAgICByZXF1aXJlKCcuL3RyYXZlcnNhbCcpXG5cbiQuaW1wbGVtZW50KHtcblxuICAgIGRlbGVnYXRlOiBmdW5jdGlvbihldmVudCwgc2VsZWN0b3IsIGhhbmRsZSwgdXNlQ2FwdHVyZSl7XG5cbiAgICAgICAgcmV0dXJuIHRoaXMuZm9yRWFjaChmdW5jdGlvbihub2RlKXtcblxuICAgICAgICAgICAgdmFyIHNlbGYgPSAkKG5vZGUpXG5cbiAgICAgICAgICAgIHZhciBkZWxlZ2F0aW9uID0gc2VsZi5fZGVsZWdhdGlvbiB8fCAoc2VsZi5fZGVsZWdhdGlvbiA9IHt9KSxcbiAgICAgICAgICAgICAgICBldmVudHMgICAgID0gZGVsZWdhdGlvbltldmVudF0gfHwgKGRlbGVnYXRpb25bZXZlbnRdID0ge30pLFxuICAgICAgICAgICAgICAgIG1hcCAgICAgICAgPSAoZXZlbnRzW3NlbGVjdG9yXSB8fCAoZXZlbnRzW3NlbGVjdG9yXSA9IG5ldyBNYXApKVxuXG4gICAgICAgICAgICBpZiAobWFwLmdldChoYW5kbGUpKSByZXR1cm5cblxuICAgICAgICAgICAgdmFyIGFjdGlvbiA9IGZ1bmN0aW9uKGUpe1xuICAgICAgICAgICAgICAgIHZhciB0YXJnZXQgPSAkKGUudGFyZ2V0IHx8IGUuc3JjRWxlbWVudCksXG4gICAgICAgICAgICAgICAgICAgIG1hdGNoICA9IHRhcmdldC5tYXRjaGVzKHNlbGVjdG9yKSA/IHRhcmdldCA6IHRhcmdldC5wYXJlbnQoc2VsZWN0b3IpXG5cbiAgICAgICAgICAgICAgICB2YXIgcmVzXG5cbiAgICAgICAgICAgICAgICBpZiAobWF0Y2gpIHJlcyA9IGhhbmRsZS5jYWxsKHNlbGYsIGUsIG1hdGNoKVxuXG4gICAgICAgICAgICAgICAgcmV0dXJuIHJlc1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBtYXAuc2V0KGhhbmRsZSwgYWN0aW9uKVxuXG4gICAgICAgICAgICBzZWxmLm9uKGV2ZW50LCBhY3Rpb24sIHVzZUNhcHR1cmUpXG5cbiAgICAgICAgfSlcblxuICAgIH0sXG5cbiAgICB1bmRlbGVnYXRlOiBmdW5jdGlvbihldmVudCwgc2VsZWN0b3IsIGhhbmRsZSwgdXNlQ2FwdHVyZSl7XG5cbiAgICAgICAgcmV0dXJuIHRoaXMuZm9yRWFjaChmdW5jdGlvbihub2RlKXtcblxuICAgICAgICAgICAgdmFyIHNlbGYgPSAkKG5vZGUpLCBkZWxlZ2F0aW9uLCBldmVudHMsIG1hcFxuXG4gICAgICAgICAgICBpZiAoIShkZWxlZ2F0aW9uID0gc2VsZi5fZGVsZWdhdGlvbikgfHwgIShldmVudHMgPSBkZWxlZ2F0aW9uW2V2ZW50XSkgfHwgIShtYXAgPSBldmVudHNbc2VsZWN0b3JdKSkgcmV0dXJuO1xuXG4gICAgICAgICAgICB2YXIgYWN0aW9uID0gbWFwLmdldChoYW5kbGUpXG5cbiAgICAgICAgICAgIGlmIChhY3Rpb24pe1xuICAgICAgICAgICAgICAgIHNlbGYub2ZmKGV2ZW50LCBhY3Rpb24sIHVzZUNhcHR1cmUpXG4gICAgICAgICAgICAgICAgbWFwLnJlbW92ZShhY3Rpb24pXG5cbiAgICAgICAgICAgICAgICAvLyBpZiB0aGVyZSBhcmUgbm8gbW9yZSBoYW5kbGVzIGluIGEgZ2l2ZW4gc2VsZWN0b3IsIGRlbGV0ZSBpdFxuICAgICAgICAgICAgICAgIGlmICghbWFwLmNvdW50KCkpIGRlbGV0ZSBldmVudHNbc2VsZWN0b3JdXG4gICAgICAgICAgICAgICAgLy8gdmFyIGV2YyA9IGV2ZCA9IDAsIHhcbiAgICAgICAgICAgICAgICB2YXIgZTEgPSB0cnVlLCBlMiA9IHRydWUsIHhcbiAgICAgICAgICAgICAgICBmb3IgKHggaW4gZXZlbnRzKXtcbiAgICAgICAgICAgICAgICAgICAgZTEgPSBmYWxzZVxuICAgICAgICAgICAgICAgICAgICBicmVha1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAvLyBpZiBubyBtb3JlIHNlbGVjdG9ycyBpbiBhIGdpdmVuIGV2ZW50IHR5cGUsIGRlbGV0ZSBpdFxuICAgICAgICAgICAgICAgIGlmIChlMSkgZGVsZXRlIGRlbGVnYXRpb25bZXZlbnRdXG4gICAgICAgICAgICAgICAgZm9yICh4IGluIGRlbGVnYXRpb24pe1xuICAgICAgICAgICAgICAgICAgICBlMiA9IGZhbHNlXG4gICAgICAgICAgICAgICAgICAgIGJyZWFrXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIC8vIGlmIHRoZXJlIGFyZSBubyBtb3JlIGRlbGVnYXRpb24gZXZlbnRzIGluIHRoZSBlbGVtZW50LCBkZWxldGUgdGhlIF9kZWxlZ2F0aW9uIG9iamVjdFxuICAgICAgICAgICAgICAgIGlmIChlMikgZGVsZXRlIHNlbGYuX2RlbGVnYXRpb25cbiAgICAgICAgICAgIH1cblxuICAgICAgICB9KVxuXG4gICAgfVxuXG59KVxuXG5tb2R1bGUuZXhwb3J0cyA9ICRcbiIsIi8qXG5ldmVudHNcbiovXCJ1c2Ugc3RyaWN0XCJcblxudmFyIEVtaXR0ZXIgPSByZXF1aXJlKFwicHJpbWUvZW1pdHRlclwiKVxuXG52YXIgJCA9IHJlcXVpcmUoXCIuL2Jhc2VcIilcblxudmFyIGh0bWwgPSBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnRcblxudmFyIGFkZEV2ZW50TGlzdGVuZXIgPSBodG1sLmFkZEV2ZW50TGlzdGVuZXIgPyBmdW5jdGlvbihub2RlLCBldmVudCwgaGFuZGxlLCB1c2VDYXB0dXJlKXtcbiAgICBub2RlLmFkZEV2ZW50TGlzdGVuZXIoZXZlbnQsIGhhbmRsZSwgdXNlQ2FwdHVyZSB8fCBmYWxzZSlcbiAgICByZXR1cm4gaGFuZGxlXG59IDogZnVuY3Rpb24obm9kZSwgZXZlbnQsIGhhbmRsZSl7XG4gICAgbm9kZS5hdHRhY2hFdmVudCgnb24nICsgZXZlbnQsIGhhbmRsZSlcbiAgICByZXR1cm4gaGFuZGxlXG59XG5cbnZhciByZW1vdmVFdmVudExpc3RlbmVyID0gaHRtbC5yZW1vdmVFdmVudExpc3RlbmVyID8gZnVuY3Rpb24obm9kZSwgZXZlbnQsIGhhbmRsZSwgdXNlQ2FwdHVyZSl7XG4gICAgbm9kZS5yZW1vdmVFdmVudExpc3RlbmVyKGV2ZW50LCBoYW5kbGUsIHVzZUNhcHR1cmUgfHwgZmFsc2UpXG59IDogZnVuY3Rpb24obm9kZSwgZXZlbnQsIGhhbmRsZSl7XG4gICAgbm9kZS5kZXRhY2hFdmVudChcIm9uXCIgKyBldmVudCwgaGFuZGxlKVxufVxuXG4kLmltcGxlbWVudCh7XG5cbiAgICBvbjogZnVuY3Rpb24oZXZlbnQsIGhhbmRsZSwgdXNlQ2FwdHVyZSl7XG5cbiAgICAgICAgcmV0dXJuIHRoaXMuZm9yRWFjaChmdW5jdGlvbihub2RlKXtcbiAgICAgICAgICAgIHZhciBzZWxmID0gJChub2RlKVxuXG4gICAgICAgICAgICB2YXIgaW50ZXJuYWxFdmVudCA9IGV2ZW50ICsgKHVzZUNhcHR1cmUgPyBcIjpjYXB0dXJlXCIgOiBcIlwiKVxuXG4gICAgICAgICAgICBFbWl0dGVyLnByb3RvdHlwZS5vbi5jYWxsKHNlbGYsIGludGVybmFsRXZlbnQsIGhhbmRsZSlcblxuICAgICAgICAgICAgdmFyIGRvbUxpc3RlbmVycyA9IHNlbGYuX2RvbUxpc3RlbmVycyB8fCAoc2VsZi5fZG9tTGlzdGVuZXJzID0ge30pXG4gICAgICAgICAgICBpZiAoIWRvbUxpc3RlbmVyc1tpbnRlcm5hbEV2ZW50XSkgZG9tTGlzdGVuZXJzW2ludGVybmFsRXZlbnRdID0gYWRkRXZlbnRMaXN0ZW5lcihub2RlLCBldmVudCwgZnVuY3Rpb24oZSl7XG4gICAgICAgICAgICAgICAgRW1pdHRlci5wcm90b3R5cGUuZW1pdC5jYWxsKHNlbGYsIGludGVybmFsRXZlbnQsIGUgfHwgd2luZG93LmV2ZW50LCBFbWl0dGVyLkVNSVRfU1lOQylcbiAgICAgICAgICAgIH0sIHVzZUNhcHR1cmUpXG4gICAgICAgIH0pXG4gICAgfSxcblxuICAgIG9mZjogZnVuY3Rpb24oZXZlbnQsIGhhbmRsZSwgdXNlQ2FwdHVyZSl7XG5cbiAgICAgICAgcmV0dXJuIHRoaXMuZm9yRWFjaChmdW5jdGlvbihub2RlKXtcblxuICAgICAgICAgICAgdmFyIHNlbGYgPSAkKG5vZGUpXG5cbiAgICAgICAgICAgIHZhciBpbnRlcm5hbEV2ZW50ID0gZXZlbnQgKyAodXNlQ2FwdHVyZSA/IFwiOmNhcHR1cmVcIiA6IFwiXCIpXG5cbiAgICAgICAgICAgIHZhciBkb21MaXN0ZW5lcnMgPSBzZWxmLl9kb21MaXN0ZW5lcnMsIGRvbUV2ZW50LCBsaXN0ZW5lcnMgPSBzZWxmLl9saXN0ZW5lcnMsIGV2ZW50c1xuXG4gICAgICAgICAgICBpZiAoZG9tTGlzdGVuZXJzICYmIChkb21FdmVudCA9IGRvbUxpc3RlbmVyc1tpbnRlcm5hbEV2ZW50XSkgJiYgbGlzdGVuZXJzICYmIChldmVudHMgPSBsaXN0ZW5lcnNbaW50ZXJuYWxFdmVudF0pKXtcblxuICAgICAgICAgICAgICAgIEVtaXR0ZXIucHJvdG90eXBlLm9mZi5jYWxsKHNlbGYsIGludGVybmFsRXZlbnQsIGhhbmRsZSlcblxuICAgICAgICAgICAgICAgIGlmICghc2VsZi5fbGlzdGVuZXJzIHx8ICFzZWxmLl9saXN0ZW5lcnNbZXZlbnRdKXtcbiAgICAgICAgICAgICAgICAgICAgcmVtb3ZlRXZlbnRMaXN0ZW5lcihub2RlLCBldmVudCwgZG9tRXZlbnQpXG4gICAgICAgICAgICAgICAgICAgIGRlbGV0ZSBkb21MaXN0ZW5lcnNbZXZlbnRdXG5cbiAgICAgICAgICAgICAgICAgICAgZm9yICh2YXIgbCBpbiBkb21MaXN0ZW5lcnMpIHJldHVyblxuICAgICAgICAgICAgICAgICAgICBkZWxldGUgc2VsZi5fZG9tTGlzdGVuZXJzXG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICB9XG4gICAgICAgIH0pXG4gICAgfSxcblxuICAgIGVtaXQ6IGZ1bmN0aW9uKCl7XG4gICAgICAgIHZhciBhcmdzID0gYXJndW1lbnRzXG4gICAgICAgIHJldHVybiB0aGlzLmZvckVhY2goZnVuY3Rpb24obm9kZSl7XG4gICAgICAgICAgICBFbWl0dGVyLnByb3RvdHlwZS5lbWl0LmFwcGx5KCQobm9kZSksIGFyZ3MpXG4gICAgICAgIH0pXG4gICAgfVxuXG59KVxuXG5tb2R1bGUuZXhwb3J0cyA9ICRcbiIsIi8qXG5lbGVtZW50c1xuKi9cInVzZSBzdHJpY3RcIlxuXG52YXIgJCA9IHJlcXVpcmUoXCIuL2Jhc2VcIilcbiAgICAgICAgcmVxdWlyZShcIi4vYXR0cmlidXRlc1wiKVxuICAgICAgICByZXF1aXJlKFwiLi9ldmVudHNcIilcbiAgICAgICAgcmVxdWlyZShcIi4vaW5zZXJ0aW9uXCIpXG4gICAgICAgIHJlcXVpcmUoXCIuL3RyYXZlcnNhbFwiKVxuICAgICAgICByZXF1aXJlKFwiLi9kZWxlZ2F0aW9uXCIpXG5cbm1vZHVsZS5leHBvcnRzID0gJFxuIiwiLypcbmluc2VydGlvblxuKi9cInVzZSBzdHJpY3RcIlxuXG52YXIgJCA9IHJlcXVpcmUoXCIuL2Jhc2VcIilcblxuLy8gYmFzZSBpbnNlcnRpb25cblxuJC5pbXBsZW1lbnQoe1xuXG4gICAgYXBwZW5kQ2hpbGQ6IGZ1bmN0aW9uKGNoaWxkKXtcbiAgICAgICAgdGhpc1swXS5hcHBlbmRDaGlsZCgkKGNoaWxkKVswXSlcbiAgICAgICAgcmV0dXJuIHRoaXNcbiAgICB9LFxuXG4gICAgaW5zZXJ0QmVmb3JlOiBmdW5jdGlvbihjaGlsZCwgcmVmKXtcbiAgICAgICAgdGhpc1swXS5pbnNlcnRCZWZvcmUoJChjaGlsZClbMF0sICQocmVmKVswXSlcbiAgICAgICAgcmV0dXJuIHRoaXNcbiAgICB9LFxuXG4gICAgcmVtb3ZlQ2hpbGQ6IGZ1bmN0aW9uKGNoaWxkKXtcbiAgICAgICAgdGhpc1swXS5yZW1vdmVDaGlsZCgkKGNoaWxkKVswXSlcbiAgICAgICAgcmV0dXJuIHRoaXNcbiAgICB9LFxuXG4gICAgcmVwbGFjZUNoaWxkOiBmdW5jdGlvbihjaGlsZCwgcmVmKXtcbiAgICAgICAgdGhpc1swXS5yZXBsYWNlQ2hpbGQoJChjaGlsZClbMF0sICQocmVmKVswXSlcbiAgICAgICAgcmV0dXJuIHRoaXNcbiAgICB9XG5cbn0pXG5cbi8vIGJlZm9yZSwgYWZ0ZXIsIGJvdHRvbSwgdG9wXG5cbiQuaW1wbGVtZW50KHtcblxuICAgIGJlZm9yZTogZnVuY3Rpb24oZWxlbWVudCl7XG4gICAgICAgIGVsZW1lbnQgPSAkKGVsZW1lbnQpWzBdXG4gICAgICAgIHZhciBwYXJlbnQgPSBlbGVtZW50LnBhcmVudE5vZGVcbiAgICAgICAgaWYgKHBhcmVudCkgdGhpcy5mb3JFYWNoKGZ1bmN0aW9uKG5vZGUpe1xuICAgICAgICAgICAgcGFyZW50Lmluc2VydEJlZm9yZShub2RlLCBlbGVtZW50KVxuICAgICAgICB9KVxuICAgICAgICByZXR1cm4gdGhpc1xuICAgIH0sXG5cbiAgICBhZnRlcjogZnVuY3Rpb24oZWxlbWVudCl7XG4gICAgICAgIGVsZW1lbnQgPSAkKGVsZW1lbnQpWzBdXG4gICAgICAgIHZhciBwYXJlbnQgPSBlbGVtZW50LnBhcmVudE5vZGVcbiAgICAgICAgaWYgKHBhcmVudCkgdGhpcy5mb3JFYWNoKGZ1bmN0aW9uKG5vZGUpe1xuICAgICAgICAgICAgcGFyZW50Lmluc2VydEJlZm9yZShub2RlLCBlbGVtZW50Lm5leHRTaWJsaW5nKVxuICAgICAgICB9KVxuICAgICAgICByZXR1cm4gdGhpc1xuICAgIH0sXG5cbiAgICBib3R0b206IGZ1bmN0aW9uKGVsZW1lbnQpe1xuICAgICAgICBlbGVtZW50ID0gJChlbGVtZW50KVswXVxuICAgICAgICByZXR1cm4gdGhpcy5mb3JFYWNoKGZ1bmN0aW9uKG5vZGUpe1xuICAgICAgICAgICAgZWxlbWVudC5hcHBlbmRDaGlsZChub2RlKVxuICAgICAgICB9KVxuICAgIH0sXG5cbiAgICB0b3A6IGZ1bmN0aW9uKGVsZW1lbnQpe1xuICAgICAgICBlbGVtZW50ID0gJChlbGVtZW50KVswXVxuICAgICAgICByZXR1cm4gdGhpcy5mb3JFYWNoKGZ1bmN0aW9uKG5vZGUpe1xuICAgICAgICAgICAgZWxlbWVudC5pbnNlcnRCZWZvcmUobm9kZSwgZWxlbWVudC5maXJzdENoaWxkKVxuICAgICAgICB9KVxuICAgIH1cblxufSlcblxuLy8gaW5zZXJ0LCByZXBsYWNlXG5cbiQuaW1wbGVtZW50KHtcblxuICAgIGluc2VydDogJC5wcm90b3R5cGUuYm90dG9tLFxuXG4gICAgcmVtb3ZlOiBmdW5jdGlvbigpe1xuICAgICAgICByZXR1cm4gdGhpcy5mb3JFYWNoKGZ1bmN0aW9uKG5vZGUpe1xuICAgICAgICAgICAgdmFyIHBhcmVudCA9IG5vZGUucGFyZW50Tm9kZVxuICAgICAgICAgICAgaWYgKHBhcmVudCkgcGFyZW50LnJlbW92ZUNoaWxkKG5vZGUpXG4gICAgICAgIH0pXG4gICAgfSxcblxuICAgIHJlcGxhY2U6IGZ1bmN0aW9uKGVsZW1lbnQpe1xuICAgICAgICBlbGVtZW50ID0gJChlbGVtZW50KVswXVxuICAgICAgICBlbGVtZW50LnBhcmVudE5vZGUucmVwbGFjZUNoaWxkKHRoaXNbMF0sIGVsZW1lbnQpXG4gICAgICAgIHJldHVybiB0aGlzXG4gICAgfVxuXG59KVxuXG5tb2R1bGUuZXhwb3J0cyA9ICRcbiIsInZhciBtYWtlSXRlcmF0b3IgPSByZXF1aXJlKCcuLi9mdW5jdGlvbi9tYWtlSXRlcmF0b3JfJyk7XG5cbiAgICAvKipcbiAgICAgKiBBcnJheSBldmVyeVxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGV2ZXJ5KGFyciwgY2FsbGJhY2ssIHRoaXNPYmopIHtcbiAgICAgICAgY2FsbGJhY2sgPSBtYWtlSXRlcmF0b3IoY2FsbGJhY2ssIHRoaXNPYmopO1xuICAgICAgICB2YXIgcmVzdWx0ID0gdHJ1ZTtcbiAgICAgICAgaWYgKGFyciA9PSBudWxsKSB7XG4gICAgICAgICAgICByZXR1cm4gcmVzdWx0O1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIGkgPSAtMSwgbGVuID0gYXJyLmxlbmd0aDtcbiAgICAgICAgd2hpbGUgKCsraSA8IGxlbikge1xuICAgICAgICAgICAgLy8gd2UgaXRlcmF0ZSBvdmVyIHNwYXJzZSBpdGVtcyBzaW5jZSB0aGVyZSBpcyBubyB3YXkgdG8gbWFrZSBpdFxuICAgICAgICAgICAgLy8gd29yayBwcm9wZXJseSBvbiBJRSA3LTguIHNlZSAjNjRcbiAgICAgICAgICAgIGlmICghY2FsbGJhY2soYXJyW2ldLCBpLCBhcnIpICkge1xuICAgICAgICAgICAgICAgIHJlc3VsdCA9IGZhbHNlO1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIHJlc3VsdDtcbiAgICB9XG5cbiAgICBtb2R1bGUuZXhwb3J0cyA9IGV2ZXJ5O1xuXG4iLCJ2YXIgbWFrZUl0ZXJhdG9yID0gcmVxdWlyZSgnLi4vZnVuY3Rpb24vbWFrZUl0ZXJhdG9yXycpO1xuXG4gICAgLyoqXG4gICAgICogQXJyYXkgZmlsdGVyXG4gICAgICovXG4gICAgZnVuY3Rpb24gZmlsdGVyKGFyciwgY2FsbGJhY2ssIHRoaXNPYmopIHtcbiAgICAgICAgY2FsbGJhY2sgPSBtYWtlSXRlcmF0b3IoY2FsbGJhY2ssIHRoaXNPYmopO1xuICAgICAgICB2YXIgcmVzdWx0cyA9IFtdO1xuICAgICAgICBpZiAoYXJyID09IG51bGwpIHtcbiAgICAgICAgICAgIHJldHVybiByZXN1bHRzO1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIGkgPSAtMSwgbGVuID0gYXJyLmxlbmd0aCwgdmFsdWU7XG4gICAgICAgIHdoaWxlICgrK2kgPCBsZW4pIHtcbiAgICAgICAgICAgIHZhbHVlID0gYXJyW2ldO1xuICAgICAgICAgICAgaWYgKGNhbGxiYWNrKHZhbHVlLCBpLCBhcnIpKSB7XG4gICAgICAgICAgICAgICAgcmVzdWx0cy5wdXNoKHZhbHVlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiByZXN1bHRzO1xuICAgIH1cblxuICAgIG1vZHVsZS5leHBvcnRzID0gZmlsdGVyO1xuXG5cbiIsIlxuXG4gICAgLyoqXG4gICAgICogQXJyYXkgZm9yRWFjaFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGZvckVhY2goYXJyLCBjYWxsYmFjaywgdGhpc09iaikge1xuICAgICAgICBpZiAoYXJyID09IG51bGwpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICB2YXIgaSA9IC0xLFxuICAgICAgICAgICAgbGVuID0gYXJyLmxlbmd0aDtcbiAgICAgICAgd2hpbGUgKCsraSA8IGxlbikge1xuICAgICAgICAgICAgLy8gd2UgaXRlcmF0ZSBvdmVyIHNwYXJzZSBpdGVtcyBzaW5jZSB0aGVyZSBpcyBubyB3YXkgdG8gbWFrZSBpdFxuICAgICAgICAgICAgLy8gd29yayBwcm9wZXJseSBvbiBJRSA3LTguIHNlZSAjNjRcbiAgICAgICAgICAgIGlmICggY2FsbGJhY2suY2FsbCh0aGlzT2JqLCBhcnJbaV0sIGksIGFycikgPT09IGZhbHNlICkge1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuXG4gICAgbW9kdWxlLmV4cG9ydHMgPSBmb3JFYWNoO1xuXG5cbiIsIlxuXG4gICAgLyoqXG4gICAgICogQXJyYXkuaW5kZXhPZlxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGluZGV4T2YoYXJyLCBpdGVtLCBmcm9tSW5kZXgpIHtcbiAgICAgICAgZnJvbUluZGV4ID0gZnJvbUluZGV4IHx8IDA7XG4gICAgICAgIGlmIChhcnIgPT0gbnVsbCkge1xuICAgICAgICAgICAgcmV0dXJuIC0xO1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIGxlbiA9IGFyci5sZW5ndGgsXG4gICAgICAgICAgICBpID0gZnJvbUluZGV4IDwgMCA/IGxlbiArIGZyb21JbmRleCA6IGZyb21JbmRleDtcbiAgICAgICAgd2hpbGUgKGkgPCBsZW4pIHtcbiAgICAgICAgICAgIC8vIHdlIGl0ZXJhdGUgb3ZlciBzcGFyc2UgaXRlbXMgc2luY2UgdGhlcmUgaXMgbm8gd2F5IHRvIG1ha2UgaXRcbiAgICAgICAgICAgIC8vIHdvcmsgcHJvcGVybHkgb24gSUUgNy04LiBzZWUgIzY0XG4gICAgICAgICAgICBpZiAoYXJyW2ldID09PSBpdGVtKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIGk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGkrKztcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiAtMTtcbiAgICB9XG5cbiAgICBtb2R1bGUuZXhwb3J0cyA9IGluZGV4T2Y7XG5cbiIsInZhciBtYWtlSXRlcmF0b3IgPSByZXF1aXJlKCcuLi9mdW5jdGlvbi9tYWtlSXRlcmF0b3JfJyk7XG5cbiAgICAvKipcbiAgICAgKiBBcnJheSBtYXBcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBtYXAoYXJyLCBjYWxsYmFjaywgdGhpc09iaikge1xuICAgICAgICBjYWxsYmFjayA9IG1ha2VJdGVyYXRvcihjYWxsYmFjaywgdGhpc09iaik7XG4gICAgICAgIHZhciByZXN1bHRzID0gW107XG4gICAgICAgIGlmIChhcnIgPT0gbnVsbCl7XG4gICAgICAgICAgICByZXR1cm4gcmVzdWx0cztcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciBpID0gLTEsIGxlbiA9IGFyci5sZW5ndGg7XG4gICAgICAgIHdoaWxlICgrK2kgPCBsZW4pIHtcbiAgICAgICAgICAgIHJlc3VsdHNbaV0gPSBjYWxsYmFjayhhcnJbaV0sIGksIGFycik7XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gcmVzdWx0cztcbiAgICB9XG5cbiAgICAgbW9kdWxlLmV4cG9ydHMgPSBtYXA7XG5cbiIsInZhciBtYWtlSXRlcmF0b3IgPSByZXF1aXJlKCcuLi9mdW5jdGlvbi9tYWtlSXRlcmF0b3JfJyk7XG5cbiAgICAvKipcbiAgICAgKiBBcnJheSBzb21lXG4gICAgICovXG4gICAgZnVuY3Rpb24gc29tZShhcnIsIGNhbGxiYWNrLCB0aGlzT2JqKSB7XG4gICAgICAgIGNhbGxiYWNrID0gbWFrZUl0ZXJhdG9yKGNhbGxiYWNrLCB0aGlzT2JqKTtcbiAgICAgICAgdmFyIHJlc3VsdCA9IGZhbHNlO1xuICAgICAgICBpZiAoYXJyID09IG51bGwpIHtcbiAgICAgICAgICAgIHJldHVybiByZXN1bHQ7XG4gICAgICAgIH1cblxuICAgICAgICB2YXIgaSA9IC0xLCBsZW4gPSBhcnIubGVuZ3RoO1xuICAgICAgICB3aGlsZSAoKytpIDwgbGVuKSB7XG4gICAgICAgICAgICAvLyB3ZSBpdGVyYXRlIG92ZXIgc3BhcnNlIGl0ZW1zIHNpbmNlIHRoZXJlIGlzIG5vIHdheSB0byBtYWtlIGl0XG4gICAgICAgICAgICAvLyB3b3JrIHByb3Blcmx5IG9uIElFIDctOC4gc2VlICM2NFxuICAgICAgICAgICAgaWYgKCBjYWxsYmFjayhhcnJbaV0sIGksIGFycikgKSB7XG4gICAgICAgICAgICAgICAgcmVzdWx0ID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiByZXN1bHQ7XG4gICAgfVxuXG4gICAgbW9kdWxlLmV4cG9ydHMgPSBzb21lO1xuXG4iLCJcblxuICAgIC8qKlxuICAgICAqIFJldHVybnMgdGhlIGZpcnN0IGFyZ3VtZW50IHByb3ZpZGVkIHRvIGl0LlxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGlkZW50aXR5KHZhbCl7XG4gICAgICAgIHJldHVybiB2YWw7XG4gICAgfVxuXG4gICAgbW9kdWxlLmV4cG9ydHMgPSBpZGVudGl0eTtcblxuXG4iLCJ2YXIgaWRlbnRpdHkgPSByZXF1aXJlKCcuL2lkZW50aXR5Jyk7XG52YXIgcHJvcCA9IHJlcXVpcmUoJy4vcHJvcCcpO1xudmFyIGRlZXBNYXRjaGVzID0gcmVxdWlyZSgnLi4vb2JqZWN0L2RlZXBNYXRjaGVzJyk7XG5cbiAgICAvKipcbiAgICAgKiBDb252ZXJ0cyBhcmd1bWVudCBpbnRvIGEgdmFsaWQgaXRlcmF0b3IuXG4gICAgICogVXNlZCBpbnRlcm5hbGx5IG9uIG1vc3QgYXJyYXkvb2JqZWN0L2NvbGxlY3Rpb24gbWV0aG9kcyB0aGF0IHJlY2VpdmVzIGFcbiAgICAgKiBjYWxsYmFjay9pdGVyYXRvciBwcm92aWRpbmcgYSBzaG9ydGN1dCBzeW50YXguXG4gICAgICovXG4gICAgZnVuY3Rpb24gbWFrZUl0ZXJhdG9yKHNyYywgdGhpc09iail7XG4gICAgICAgIGlmIChzcmMgPT0gbnVsbCkge1xuICAgICAgICAgICAgcmV0dXJuIGlkZW50aXR5O1xuICAgICAgICB9XG4gICAgICAgIHN3aXRjaCh0eXBlb2Ygc3JjKSB7XG4gICAgICAgICAgICBjYXNlICdmdW5jdGlvbic6XG4gICAgICAgICAgICAgICAgLy8gZnVuY3Rpb24gaXMgdGhlIGZpcnN0IHRvIGltcHJvdmUgcGVyZiAobW9zdCBjb21tb24gY2FzZSlcbiAgICAgICAgICAgICAgICAvLyBhbHNvIGF2b2lkIHVzaW5nIGBGdW5jdGlvbiNjYWxsYCBpZiBub3QgbmVlZGVkLCB3aGljaCBib29zdHNcbiAgICAgICAgICAgICAgICAvLyBwZXJmIGEgbG90IGluIHNvbWUgY2FzZXNcbiAgICAgICAgICAgICAgICByZXR1cm4gKHR5cGVvZiB0aGlzT2JqICE9PSAndW5kZWZpbmVkJyk/IGZ1bmN0aW9uKHZhbCwgaSwgYXJyKXtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHNyYy5jYWxsKHRoaXNPYmosIHZhbCwgaSwgYXJyKTtcbiAgICAgICAgICAgICAgICB9IDogc3JjO1xuICAgICAgICAgICAgY2FzZSAnb2JqZWN0JzpcbiAgICAgICAgICAgICAgICByZXR1cm4gZnVuY3Rpb24odmFsKXtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGRlZXBNYXRjaGVzKHZhbCwgc3JjKTtcbiAgICAgICAgICAgICAgICB9O1xuICAgICAgICAgICAgY2FzZSAnc3RyaW5nJzpcbiAgICAgICAgICAgIGNhc2UgJ251bWJlcic6XG4gICAgICAgICAgICAgICAgcmV0dXJuIHByb3Aoc3JjKTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIG1vZHVsZS5leHBvcnRzID0gbWFrZUl0ZXJhdG9yO1xuXG5cbiIsIlxuXG4gICAgLyoqXG4gICAgICogUmV0dXJucyBhIGZ1bmN0aW9uIHRoYXQgZ2V0cyBhIHByb3BlcnR5IG9mIHRoZSBwYXNzZWQgb2JqZWN0XG4gICAgICovXG4gICAgZnVuY3Rpb24gcHJvcChuYW1lKXtcbiAgICAgICAgcmV0dXJuIGZ1bmN0aW9uKG9iail7XG4gICAgICAgICAgICByZXR1cm4gb2JqW25hbWVdO1xuICAgICAgICB9O1xuICAgIH1cblxuICAgIG1vZHVsZS5leHBvcnRzID0gcHJvcDtcblxuXG4iLCJ2YXIgaXNLaW5kID0gcmVxdWlyZSgnLi9pc0tpbmQnKTtcbiAgICAvKipcbiAgICAgKi9cbiAgICB2YXIgaXNBcnJheSA9IEFycmF5LmlzQXJyYXkgfHwgZnVuY3Rpb24gKHZhbCkge1xuICAgICAgICByZXR1cm4gaXNLaW5kKHZhbCwgJ0FycmF5Jyk7XG4gICAgfTtcbiAgICBtb2R1bGUuZXhwb3J0cyA9IGlzQXJyYXk7XG5cbiIsInZhciBraW5kT2YgPSByZXF1aXJlKCcuL2tpbmRPZicpO1xuICAgIC8qKlxuICAgICAqIENoZWNrIGlmIHZhbHVlIGlzIGZyb20gYSBzcGVjaWZpYyBcImtpbmRcIi5cbiAgICAgKi9cbiAgICBmdW5jdGlvbiBpc0tpbmQodmFsLCBraW5kKXtcbiAgICAgICAgcmV0dXJuIGtpbmRPZih2YWwpID09PSBraW5kO1xuICAgIH1cbiAgICBtb2R1bGUuZXhwb3J0cyA9IGlzS2luZDtcblxuIiwiXG5cbiAgICB2YXIgX3JLaW5kID0gL15cXFtvYmplY3QgKC4qKVxcXSQvLFxuICAgICAgICBfdG9TdHJpbmcgPSBPYmplY3QucHJvdG90eXBlLnRvU3RyaW5nLFxuICAgICAgICBVTkRFRjtcblxuICAgIC8qKlxuICAgICAqIEdldHMgdGhlIFwia2luZFwiIG9mIHZhbHVlLiAoZS5nLiBcIlN0cmluZ1wiLCBcIk51bWJlclwiLCBldGMpXG4gICAgICovXG4gICAgZnVuY3Rpb24ga2luZE9mKHZhbCkge1xuICAgICAgICBpZiAodmFsID09PSBudWxsKSB7XG4gICAgICAgICAgICByZXR1cm4gJ051bGwnO1xuICAgICAgICB9IGVsc2UgaWYgKHZhbCA9PT0gVU5ERUYpIHtcbiAgICAgICAgICAgIHJldHVybiAnVW5kZWZpbmVkJztcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiBfcktpbmQuZXhlYyggX3RvU3RyaW5nLmNhbGwodmFsKSApWzFdO1xuICAgICAgICB9XG4gICAgfVxuICAgIG1vZHVsZS5leHBvcnRzID0ga2luZE9mO1xuXG4iLCJcblxuICAgIC8qKlxuICAgICAqIFR5cGVjYXN0IGEgdmFsdWUgdG8gYSBTdHJpbmcsIHVzaW5nIGFuIGVtcHR5IHN0cmluZyB2YWx1ZSBmb3IgbnVsbCBvclxuICAgICAqIHVuZGVmaW5lZC5cbiAgICAgKi9cbiAgICBmdW5jdGlvbiB0b1N0cmluZyh2YWwpe1xuICAgICAgICByZXR1cm4gdmFsID09IG51bGwgPyAnJyA6IHZhbC50b1N0cmluZygpO1xuICAgIH1cblxuICAgIG1vZHVsZS5leHBvcnRzID0gdG9TdHJpbmc7XG5cblxuIiwidmFyIGZvck93biA9IHJlcXVpcmUoJy4vZm9yT3duJyk7XG52YXIgaXNBcnJheSA9IHJlcXVpcmUoJy4uL2xhbmcvaXNBcnJheScpO1xuXG4gICAgZnVuY3Rpb24gY29udGFpbnNNYXRjaChhcnJheSwgcGF0dGVybikge1xuICAgICAgICB2YXIgaSA9IC0xLCBsZW5ndGggPSBhcnJheS5sZW5ndGg7XG4gICAgICAgIHdoaWxlICgrK2kgPCBsZW5ndGgpIHtcbiAgICAgICAgICAgIGlmIChkZWVwTWF0Y2hlcyhhcnJheVtpXSwgcGF0dGVybikpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9XG5cbiAgICBmdW5jdGlvbiBtYXRjaEFycmF5KHRhcmdldCwgcGF0dGVybikge1xuICAgICAgICB2YXIgaSA9IC0xLCBwYXR0ZXJuTGVuZ3RoID0gcGF0dGVybi5sZW5ndGg7XG4gICAgICAgIHdoaWxlICgrK2kgPCBwYXR0ZXJuTGVuZ3RoKSB7XG4gICAgICAgICAgICBpZiAoIWNvbnRhaW5zTWF0Y2godGFyZ2V0LCBwYXR0ZXJuW2ldKSkge1xuICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cblxuICAgIGZ1bmN0aW9uIG1hdGNoT2JqZWN0KHRhcmdldCwgcGF0dGVybikge1xuICAgICAgICB2YXIgcmVzdWx0ID0gdHJ1ZTtcbiAgICAgICAgZm9yT3duKHBhdHRlcm4sIGZ1bmN0aW9uKHZhbCwga2V5KSB7XG4gICAgICAgICAgICBpZiAoIWRlZXBNYXRjaGVzKHRhcmdldFtrZXldLCB2YWwpKSB7XG4gICAgICAgICAgICAgICAgLy8gUmV0dXJuIGZhbHNlIHRvIGJyZWFrIG91dCBvZiBmb3JPd24gZWFybHlcbiAgICAgICAgICAgICAgICByZXR1cm4gKHJlc3VsdCA9IGZhbHNlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG5cbiAgICAgICAgcmV0dXJuIHJlc3VsdDtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBSZWN1cnNpdmVseSBjaGVjayBpZiB0aGUgb2JqZWN0cyBtYXRjaC5cbiAgICAgKi9cbiAgICBmdW5jdGlvbiBkZWVwTWF0Y2hlcyh0YXJnZXQsIHBhdHRlcm4pe1xuICAgICAgICBpZiAodGFyZ2V0ICYmIHR5cGVvZiB0YXJnZXQgPT09ICdvYmplY3QnKSB7XG4gICAgICAgICAgICBpZiAoaXNBcnJheSh0YXJnZXQpICYmIGlzQXJyYXkocGF0dGVybikpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gbWF0Y2hBcnJheSh0YXJnZXQsIHBhdHRlcm4pO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gbWF0Y2hPYmplY3QodGFyZ2V0LCBwYXR0ZXJuKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiB0YXJnZXQgPT09IHBhdHRlcm47XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBtb2R1bGUuZXhwb3J0cyA9IGRlZXBNYXRjaGVzO1xuXG5cbiIsInZhciBoYXNPd24gPSByZXF1aXJlKCcuL2hhc093bicpO1xuXG4gICAgdmFyIF9oYXNEb250RW51bUJ1ZyxcbiAgICAgICAgX2RvbnRFbnVtcztcblxuICAgIGZ1bmN0aW9uIGNoZWNrRG9udEVudW0oKXtcbiAgICAgICAgX2RvbnRFbnVtcyA9IFtcbiAgICAgICAgICAgICAgICAndG9TdHJpbmcnLFxuICAgICAgICAgICAgICAgICd0b0xvY2FsZVN0cmluZycsXG4gICAgICAgICAgICAgICAgJ3ZhbHVlT2YnLFxuICAgICAgICAgICAgICAgICdoYXNPd25Qcm9wZXJ0eScsXG4gICAgICAgICAgICAgICAgJ2lzUHJvdG90eXBlT2YnLFxuICAgICAgICAgICAgICAgICdwcm9wZXJ0eUlzRW51bWVyYWJsZScsXG4gICAgICAgICAgICAgICAgJ2NvbnN0cnVjdG9yJ1xuICAgICAgICAgICAgXTtcblxuICAgICAgICBfaGFzRG9udEVudW1CdWcgPSB0cnVlO1xuXG4gICAgICAgIGZvciAodmFyIGtleSBpbiB7J3RvU3RyaW5nJzogbnVsbH0pIHtcbiAgICAgICAgICAgIF9oYXNEb250RW51bUJ1ZyA9IGZhbHNlO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogU2ltaWxhciB0byBBcnJheS9mb3JFYWNoIGJ1dCB3b3JrcyBvdmVyIG9iamVjdCBwcm9wZXJ0aWVzIGFuZCBmaXhlcyBEb24ndFxuICAgICAqIEVudW0gYnVnIG9uIElFLlxuICAgICAqIGJhc2VkIG9uOiBodHRwOi8vd2hhdHRoZWhlYWRzYWlkLmNvbS8yMDEwLzEwL2Etc2FmZXItb2JqZWN0LWtleXMtY29tcGF0aWJpbGl0eS1pbXBsZW1lbnRhdGlvblxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGZvckluKG9iaiwgZm4sIHRoaXNPYmope1xuICAgICAgICB2YXIga2V5LCBpID0gMDtcbiAgICAgICAgLy8gbm8gbmVlZCB0byBjaGVjayBpZiBhcmd1bWVudCBpcyBhIHJlYWwgb2JqZWN0IHRoYXQgd2F5IHdlIGNhbiB1c2VcbiAgICAgICAgLy8gaXQgZm9yIGFycmF5cywgZnVuY3Rpb25zLCBkYXRlLCBldGMuXG5cbiAgICAgICAgLy9wb3N0LXBvbmUgY2hlY2sgdGlsbCBuZWVkZWRcbiAgICAgICAgaWYgKF9oYXNEb250RW51bUJ1ZyA9PSBudWxsKSBjaGVja0RvbnRFbnVtKCk7XG5cbiAgICAgICAgZm9yIChrZXkgaW4gb2JqKSB7XG4gICAgICAgICAgICBpZiAoZXhlYyhmbiwgb2JqLCBrZXksIHRoaXNPYmopID09PSBmYWxzZSkge1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cblxuICAgICAgICBpZiAoX2hhc0RvbnRFbnVtQnVnKSB7XG4gICAgICAgICAgICB2YXIgY3RvciA9IG9iai5jb25zdHJ1Y3RvcixcbiAgICAgICAgICAgICAgICBpc1Byb3RvID0gISFjdG9yICYmIG9iaiA9PT0gY3Rvci5wcm90b3R5cGU7XG5cbiAgICAgICAgICAgIHdoaWxlIChrZXkgPSBfZG9udEVudW1zW2krK10pIHtcbiAgICAgICAgICAgICAgICAvLyBGb3IgY29uc3RydWN0b3IsIGlmIGl0IGlzIGEgcHJvdG90eXBlIG9iamVjdCB0aGUgY29uc3RydWN0b3JcbiAgICAgICAgICAgICAgICAvLyBpcyBhbHdheXMgbm9uLWVudW1lcmFibGUgdW5sZXNzIGRlZmluZWQgb3RoZXJ3aXNlIChhbmRcbiAgICAgICAgICAgICAgICAvLyBlbnVtZXJhdGVkIGFib3ZlKS4gIEZvciBub24tcHJvdG90eXBlIG9iamVjdHMsIGl0IHdpbGwgaGF2ZVxuICAgICAgICAgICAgICAgIC8vIHRvIGJlIGRlZmluZWQgb24gdGhpcyBvYmplY3QsIHNpbmNlIGl0IGNhbm5vdCBiZSBkZWZpbmVkIG9uXG4gICAgICAgICAgICAgICAgLy8gYW55IHByb3RvdHlwZSBvYmplY3RzLlxuICAgICAgICAgICAgICAgIC8vXG4gICAgICAgICAgICAgICAgLy8gRm9yIG90aGVyIFtbRG9udEVudW1dXSBwcm9wZXJ0aWVzLCBjaGVjayBpZiB0aGUgdmFsdWUgaXNcbiAgICAgICAgICAgICAgICAvLyBkaWZmZXJlbnQgdGhhbiBPYmplY3QgcHJvdG90eXBlIHZhbHVlLlxuICAgICAgICAgICAgICAgIGlmIChcbiAgICAgICAgICAgICAgICAgICAgKGtleSAhPT0gJ2NvbnN0cnVjdG9yJyB8fFxuICAgICAgICAgICAgICAgICAgICAgICAgKCFpc1Byb3RvICYmIGhhc093bihvYmosIGtleSkpKSAmJlxuICAgICAgICAgICAgICAgICAgICBvYmpba2V5XSAhPT0gT2JqZWN0LnByb3RvdHlwZVtrZXldXG4gICAgICAgICAgICAgICAgKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChleGVjKGZuLCBvYmosIGtleSwgdGhpc09iaikgPT09IGZhbHNlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cblxuICAgIGZ1bmN0aW9uIGV4ZWMoZm4sIG9iaiwga2V5LCB0aGlzT2JqKXtcbiAgICAgICAgcmV0dXJuIGZuLmNhbGwodGhpc09iaiwgb2JqW2tleV0sIGtleSwgb2JqKTtcbiAgICB9XG5cbiAgICBtb2R1bGUuZXhwb3J0cyA9IGZvckluO1xuXG5cbiIsInZhciBoYXNPd24gPSByZXF1aXJlKCcuL2hhc093bicpO1xudmFyIGZvckluID0gcmVxdWlyZSgnLi9mb3JJbicpO1xuXG4gICAgLyoqXG4gICAgICogU2ltaWxhciB0byBBcnJheS9mb3JFYWNoIGJ1dCB3b3JrcyBvdmVyIG9iamVjdCBwcm9wZXJ0aWVzIGFuZCBmaXhlcyBEb24ndFxuICAgICAqIEVudW0gYnVnIG9uIElFLlxuICAgICAqIGJhc2VkIG9uOiBodHRwOi8vd2hhdHRoZWhlYWRzYWlkLmNvbS8yMDEwLzEwL2Etc2FmZXItb2JqZWN0LWtleXMtY29tcGF0aWJpbGl0eS1pbXBsZW1lbnRhdGlvblxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGZvck93bihvYmosIGZuLCB0aGlzT2JqKXtcbiAgICAgICAgZm9ySW4ob2JqLCBmdW5jdGlvbih2YWwsIGtleSl7XG4gICAgICAgICAgICBpZiAoaGFzT3duKG9iaiwga2V5KSkge1xuICAgICAgICAgICAgICAgIHJldHVybiBmbi5jYWxsKHRoaXNPYmosIG9ialtrZXldLCBrZXksIG9iaik7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgIH1cblxuICAgIG1vZHVsZS5leHBvcnRzID0gZm9yT3duO1xuXG5cbiIsIlxuXG4gICAgLyoqXG4gICAgICogU2FmZXIgT2JqZWN0Lmhhc093blByb3BlcnR5XG4gICAgICovXG4gICAgIGZ1bmN0aW9uIGhhc093bihvYmosIHByb3Ape1xuICAgICAgICAgcmV0dXJuIE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbChvYmosIHByb3ApO1xuICAgICB9XG5cbiAgICAgbW9kdWxlLmV4cG9ydHMgPSBoYXNPd247XG5cblxuIiwiXG4gICAgLyoqXG4gICAgICogQ29udGFpbnMgYWxsIFVuaWNvZGUgd2hpdGUtc3BhY2VzLiBUYWtlbiBmcm9tXG4gICAgICogaHR0cDovL2VuLndpa2lwZWRpYS5vcmcvd2lraS9XaGl0ZXNwYWNlX2NoYXJhY3Rlci5cbiAgICAgKi9cbiAgICBtb2R1bGUuZXhwb3J0cyA9IFtcbiAgICAgICAgJyAnLCAnXFxuJywgJ1xccicsICdcXHQnLCAnXFxmJywgJ1xcdicsICdcXHUwMEEwJywgJ1xcdTE2ODAnLCAnXFx1MTgwRScsXG4gICAgICAgICdcXHUyMDAwJywgJ1xcdTIwMDEnLCAnXFx1MjAwMicsICdcXHUyMDAzJywgJ1xcdTIwMDQnLCAnXFx1MjAwNScsICdcXHUyMDA2JyxcbiAgICAgICAgJ1xcdTIwMDcnLCAnXFx1MjAwOCcsICdcXHUyMDA5JywgJ1xcdTIwMEEnLCAnXFx1MjAyOCcsICdcXHUyMDI5JywgJ1xcdTIwMkYnLFxuICAgICAgICAnXFx1MjA1RicsICdcXHUzMDAwJ1xuICAgIF07XG5cbiIsInZhciB0b1N0cmluZyA9IHJlcXVpcmUoJy4uL2xhbmcvdG9TdHJpbmcnKTtcbnZhciBXSElURV9TUEFDRVMgPSByZXF1aXJlKCcuL1dISVRFX1NQQUNFUycpO1xuICAgIC8qKlxuICAgICAqIFJlbW92ZSBjaGFycyBmcm9tIGJlZ2lubmluZyBvZiBzdHJpbmcuXG4gICAgICovXG4gICAgZnVuY3Rpb24gbHRyaW0oc3RyLCBjaGFycykge1xuICAgICAgICBzdHIgPSB0b1N0cmluZyhzdHIpO1xuICAgICAgICBjaGFycyA9IGNoYXJzIHx8IFdISVRFX1NQQUNFUztcblxuICAgICAgICB2YXIgc3RhcnQgPSAwLFxuICAgICAgICAgICAgbGVuID0gc3RyLmxlbmd0aCxcbiAgICAgICAgICAgIGNoYXJMZW4gPSBjaGFycy5sZW5ndGgsXG4gICAgICAgICAgICBmb3VuZCA9IHRydWUsXG4gICAgICAgICAgICBpLCBjO1xuXG4gICAgICAgIHdoaWxlIChmb3VuZCAmJiBzdGFydCA8IGxlbikge1xuICAgICAgICAgICAgZm91bmQgPSBmYWxzZTtcbiAgICAgICAgICAgIGkgPSAtMTtcbiAgICAgICAgICAgIGMgPSBzdHIuY2hhckF0KHN0YXJ0KTtcblxuICAgICAgICAgICAgd2hpbGUgKCsraSA8IGNoYXJMZW4pIHtcbiAgICAgICAgICAgICAgICBpZiAoYyA9PT0gY2hhcnNbaV0pIHtcbiAgICAgICAgICAgICAgICAgICAgZm91bmQgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICBzdGFydCsrO1xuICAgICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gKHN0YXJ0ID49IGxlbikgPyAnJyA6IHN0ci5zdWJzdHIoc3RhcnQsIGxlbik7XG4gICAgfVxuXG4gICAgbW9kdWxlLmV4cG9ydHMgPSBsdHJpbTtcblxuIiwidmFyIHRvU3RyaW5nID0gcmVxdWlyZSgnLi4vbGFuZy90b1N0cmluZycpO1xudmFyIFdISVRFX1NQQUNFUyA9IHJlcXVpcmUoJy4vV0hJVEVfU1BBQ0VTJyk7XG4gICAgLyoqXG4gICAgICogUmVtb3ZlIGNoYXJzIGZyb20gZW5kIG9mIHN0cmluZy5cbiAgICAgKi9cbiAgICBmdW5jdGlvbiBydHJpbShzdHIsIGNoYXJzKSB7XG4gICAgICAgIHN0ciA9IHRvU3RyaW5nKHN0cik7XG4gICAgICAgIGNoYXJzID0gY2hhcnMgfHwgV0hJVEVfU1BBQ0VTO1xuXG4gICAgICAgIHZhciBlbmQgPSBzdHIubGVuZ3RoIC0gMSxcbiAgICAgICAgICAgIGNoYXJMZW4gPSBjaGFycy5sZW5ndGgsXG4gICAgICAgICAgICBmb3VuZCA9IHRydWUsXG4gICAgICAgICAgICBpLCBjO1xuXG4gICAgICAgIHdoaWxlIChmb3VuZCAmJiBlbmQgPj0gMCkge1xuICAgICAgICAgICAgZm91bmQgPSBmYWxzZTtcbiAgICAgICAgICAgIGkgPSAtMTtcbiAgICAgICAgICAgIGMgPSBzdHIuY2hhckF0KGVuZCk7XG5cbiAgICAgICAgICAgIHdoaWxlICgrK2kgPCBjaGFyTGVuKSB7XG4gICAgICAgICAgICAgICAgaWYgKGMgPT09IGNoYXJzW2ldKSB7XG4gICAgICAgICAgICAgICAgICAgIGZvdW5kID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgZW5kLS07XG4gICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiAoZW5kID49IDApID8gc3RyLnN1YnN0cmluZygwLCBlbmQgKyAxKSA6ICcnO1xuICAgIH1cblxuICAgIG1vZHVsZS5leHBvcnRzID0gcnRyaW07XG5cbiIsInZhciB0b1N0cmluZyA9IHJlcXVpcmUoJy4uL2xhbmcvdG9TdHJpbmcnKTtcbnZhciBXSElURV9TUEFDRVMgPSByZXF1aXJlKCcuL1dISVRFX1NQQUNFUycpO1xudmFyIGx0cmltID0gcmVxdWlyZSgnLi9sdHJpbScpO1xudmFyIHJ0cmltID0gcmVxdWlyZSgnLi9ydHJpbScpO1xuICAgIC8qKlxuICAgICAqIFJlbW92ZSB3aGl0ZS1zcGFjZXMgZnJvbSBiZWdpbm5pbmcgYW5kIGVuZCBvZiBzdHJpbmcuXG4gICAgICovXG4gICAgZnVuY3Rpb24gdHJpbShzdHIsIGNoYXJzKSB7XG4gICAgICAgIHN0ciA9IHRvU3RyaW5nKHN0cik7XG4gICAgICAgIGNoYXJzID0gY2hhcnMgfHwgV0hJVEVfU1BBQ0VTO1xuICAgICAgICByZXR1cm4gbHRyaW0ocnRyaW0oc3RyLCBjaGFycyksIGNoYXJzKTtcbiAgICB9XG5cbiAgICBtb2R1bGUuZXhwb3J0cyA9IHRyaW07XG5cbiIsIi8qXG50cmF2ZXJzYWxcbiovXCJ1c2Ugc3RyaWN0XCJcblxudmFyIG1hcCA9IHJlcXVpcmUoXCJtb3V0L2FycmF5L21hcFwiKVxuXG52YXIgc2xpY2sgPSByZXF1aXJlKFwic2xpY2tcIilcblxudmFyICQgPSByZXF1aXJlKFwiLi9iYXNlXCIpXG5cbnZhciBnZW4gPSBmdW5jdGlvbihjb21iaW5hdG9yLCBleHByZXNzaW9uKXtcbiAgICByZXR1cm4gbWFwKHNsaWNrLnBhcnNlKGV4cHJlc3Npb24gfHwgXCIqXCIpLCBmdW5jdGlvbihwYXJ0KXtcbiAgICAgICAgcmV0dXJuIGNvbWJpbmF0b3IgKyBcIiBcIiArIHBhcnRcbiAgICB9KS5qb2luKFwiLCBcIilcbn1cblxudmFyIHB1c2hfID0gQXJyYXkucHJvdG90eXBlLnB1c2hcblxuJC5pbXBsZW1lbnQoe1xuXG4gICAgc2VhcmNoOiBmdW5jdGlvbihleHByZXNzaW9uKXtcbiAgICAgICAgaWYgKHRoaXMubGVuZ3RoID09PSAxKSByZXR1cm4gJChzbGljay5zZWFyY2goZXhwcmVzc2lvbiwgdGhpc1swXSwgbmV3ICQpKVxuXG4gICAgICAgIHZhciBidWZmZXIgPSBbXVxuICAgICAgICBmb3IgKHZhciBpID0gMCwgbm9kZTsgbm9kZSA9IHRoaXNbaV07IGkrKykgcHVzaF8uYXBwbHkoYnVmZmVyLCBzbGljay5zZWFyY2goZXhwcmVzc2lvbiwgbm9kZSkpXG4gICAgICAgIGJ1ZmZlciA9ICQoYnVmZmVyKVxuICAgICAgICByZXR1cm4gYnVmZmVyICYmIGJ1ZmZlci5zb3J0KClcbiAgICB9LFxuXG4gICAgZmluZDogZnVuY3Rpb24oZXhwcmVzc2lvbil7XG4gICAgICAgIGlmICh0aGlzLmxlbmd0aCA9PT0gMSkgcmV0dXJuICQoc2xpY2suZmluZChleHByZXNzaW9uLCB0aGlzWzBdKSlcblxuICAgICAgICBmb3IgKHZhciBpID0gMCwgbm9kZTsgbm9kZSA9IHRoaXNbaV07IGkrKykge1xuICAgICAgICAgICAgdmFyIGZvdW5kID0gc2xpY2suZmluZChleHByZXNzaW9uLCBub2RlKVxuICAgICAgICAgICAgaWYgKGZvdW5kKSByZXR1cm4gJChmb3VuZClcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiBudWxsXG4gICAgfSxcblxuICAgIHNvcnQ6IGZ1bmN0aW9uKCl7XG4gICAgICAgIHJldHVybiBzbGljay5zb3J0KHRoaXMpXG4gICAgfSxcblxuICAgIG1hdGNoZXM6IGZ1bmN0aW9uKGV4cHJlc3Npb24pe1xuICAgICAgICByZXR1cm4gc2xpY2subWF0Y2hlcyh0aGlzWzBdLCBleHByZXNzaW9uKVxuICAgIH0sXG5cbiAgICBjb250YWluczogZnVuY3Rpb24obm9kZSl7XG4gICAgICAgIHJldHVybiBzbGljay5jb250YWlucyh0aGlzWzBdLCBub2RlKVxuICAgIH0sXG5cbiAgICBuZXh0U2libGluZ3M6IGZ1bmN0aW9uKGV4cHJlc3Npb24pe1xuICAgICAgICByZXR1cm4gdGhpcy5zZWFyY2goZ2VuKCd+JywgZXhwcmVzc2lvbikpXG4gICAgfSxcblxuICAgIG5leHRTaWJsaW5nOiBmdW5jdGlvbihleHByZXNzaW9uKXtcbiAgICAgICAgcmV0dXJuIHRoaXMuZmluZChnZW4oJysnLCBleHByZXNzaW9uKSlcbiAgICB9LFxuXG4gICAgcHJldmlvdXNTaWJsaW5nczogZnVuY3Rpb24oZXhwcmVzc2lvbil7XG4gICAgICAgIHJldHVybiB0aGlzLnNlYXJjaChnZW4oJyF+JywgZXhwcmVzc2lvbikpXG4gICAgfSxcblxuICAgIHByZXZpb3VzU2libGluZzogZnVuY3Rpb24oZXhwcmVzc2lvbil7XG4gICAgICAgIHJldHVybiB0aGlzLmZpbmQoZ2VuKCchKycsIGV4cHJlc3Npb24pKVxuICAgIH0sXG5cbiAgICBjaGlsZHJlbjogZnVuY3Rpb24oZXhwcmVzc2lvbil7XG4gICAgICAgIHJldHVybiB0aGlzLnNlYXJjaChnZW4oJz4nLCBleHByZXNzaW9uKSlcbiAgICB9LFxuXG4gICAgZmlyc3RDaGlsZDogZnVuY3Rpb24oZXhwcmVzc2lvbil7XG4gICAgICAgIHJldHVybiB0aGlzLmZpbmQoZ2VuKCdeJywgZXhwcmVzc2lvbikpXG4gICAgfSxcblxuICAgIGxhc3RDaGlsZDogZnVuY3Rpb24oZXhwcmVzc2lvbil7XG4gICAgICAgIHJldHVybiB0aGlzLmZpbmQoZ2VuKCchXicsIGV4cHJlc3Npb24pKVxuICAgIH0sXG5cbiAgICBwYXJlbnQ6IGZ1bmN0aW9uKGV4cHJlc3Npb24pe1xuICAgICAgICB2YXIgYnVmZmVyID0gW11cbiAgICAgICAgbG9vcDogZm9yICh2YXIgaSA9IDAsIG5vZGU7IG5vZGUgPSB0aGlzW2ldOyBpKyspIHdoaWxlICgobm9kZSA9IG5vZGUucGFyZW50Tm9kZSkgJiYgKG5vZGUgIT09IGRvY3VtZW50KSl7XG4gICAgICAgICAgICBpZiAoIWV4cHJlc3Npb24gfHwgc2xpY2subWF0Y2hlcyhub2RlLCBleHByZXNzaW9uKSl7XG4gICAgICAgICAgICAgICAgYnVmZmVyLnB1c2gobm9kZSlcbiAgICAgICAgICAgICAgICBicmVhayBsb29wXG4gICAgICAgICAgICAgICAgYnJlYWtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gJChidWZmZXIpXG4gICAgfSxcblxuICAgIHBhcmVudHM6IGZ1bmN0aW9uKGV4cHJlc3Npb24pe1xuICAgICAgICB2YXIgYnVmZmVyID0gW11cbiAgICAgICAgZm9yICh2YXIgaSA9IDAsIG5vZGU7IG5vZGUgPSB0aGlzW2ldOyBpKyspIHdoaWxlICgobm9kZSA9IG5vZGUucGFyZW50Tm9kZSkgJiYgKG5vZGUgIT09IGRvY3VtZW50KSl7XG4gICAgICAgICAgICBpZiAoIWV4cHJlc3Npb24gfHwgc2xpY2subWF0Y2hlcyhub2RlLCBleHByZXNzaW9uKSkgYnVmZmVyLnB1c2gobm9kZSlcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gJChidWZmZXIpXG4gICAgfVxuXG59KVxuXG5tb2R1bGUuZXhwb3J0cyA9ICRcbiIsIi8qXG56ZW5cbiovXCJ1c2Ugc3RyaWN0XCJcblxudmFyIGZvckVhY2ggPSByZXF1aXJlKFwibW91dC9hcnJheS9mb3JFYWNoXCIpLFxuICAgIG1hcCAgICAgPSByZXF1aXJlKFwibW91dC9hcnJheS9tYXBcIilcblxudmFyIHBhcnNlID0gcmVxdWlyZShcInNsaWNrL3BhcnNlclwiKVxuXG52YXIgJCA9IHJlcXVpcmUoXCIuL2Jhc2VcIilcblxubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbihleHByZXNzaW9uLCBkb2Mpe1xuXG4gICAgcmV0dXJuICQobWFwKHBhcnNlKGV4cHJlc3Npb24pLCBmdW5jdGlvbihleHByZXNzaW9uKXtcblxuICAgICAgICB2YXIgcHJldmlvdXMsIHJlc3VsdFxuXG4gICAgICAgIGZvckVhY2goZXhwcmVzc2lvbiwgZnVuY3Rpb24ocGFydCwgaSl7XG5cbiAgICAgICAgICAgIHZhciBub2RlID0gKGRvYyB8fCBkb2N1bWVudCkuY3JlYXRlRWxlbWVudChwYXJ0LnRhZylcblxuICAgICAgICAgICAgaWYgKHBhcnQuaWQpIG5vZGUuaWQgPSBwYXJ0LmlkXG5cbiAgICAgICAgICAgIGlmIChwYXJ0LmNsYXNzTGlzdCkgbm9kZS5jbGFzc05hbWUgPSBwYXJ0LmNsYXNzTGlzdC5qb2luKFwiIFwiKVxuXG4gICAgICAgICAgICBpZiAocGFydC5hdHRyaWJ1dGVzKSBmb3JFYWNoKHBhcnQuYXR0cmlidXRlcywgZnVuY3Rpb24oYXR0cmlidXRlKXtcbiAgICAgICAgICAgICAgICBub2RlLnNldEF0dHJpYnV0ZShhdHRyaWJ1dGUubmFtZSwgYXR0cmlidXRlLnZhbHVlIHx8IFwiXCIpXG4gICAgICAgICAgICB9KVxuXG4gICAgICAgICAgICBpZiAocGFydC5wc2V1ZG9zKSBmb3JFYWNoKHBhcnQucHNldWRvcywgZnVuY3Rpb24ocHNldWRvKXtcbiAgICAgICAgICAgICAgICB2YXIgbiA9ICQobm9kZSksIG1ldGhvZCA9IG5bcHNldWRvLm5hbWVdXG4gICAgICAgICAgICAgICAgaWYgKG1ldGhvZCkgbWV0aG9kLmNhbGwobiwgcHNldWRvLnZhbHVlKVxuICAgICAgICAgICAgfSlcblxuICAgICAgICAgICAgaWYgKGkgPT09IDApe1xuXG4gICAgICAgICAgICAgICAgcmVzdWx0ID0gbm9kZVxuXG4gICAgICAgICAgICB9IGVsc2UgaWYgKHBhcnQuY29tYmluYXRvciA9PT0gXCIgXCIpe1xuXG4gICAgICAgICAgICAgICAgcHJldmlvdXMuYXBwZW5kQ2hpbGQobm9kZSlcblxuICAgICAgICAgICAgfSBlbHNlIGlmIChwYXJ0LmNvbWJpbmF0b3IgPT09IFwiK1wiKXtcbiAgICAgICAgICAgICAgICB2YXIgcGFyZW50Tm9kZSA9IHByZXZpb3VzLnBhcmVudE5vZGVcbiAgICAgICAgICAgICAgICBpZiAocGFyZW50Tm9kZSkgcGFyZW50Tm9kZS5hcHBlbmRDaGlsZChub2RlKVxuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBwcmV2aW91cyA9IG5vZGVcblxuICAgICAgICB9KVxuXG4gICAgICAgIHJldHVybiByZXN1bHRcblxuICAgIH0pKVxuXG59XG4iLCJcblxuICAgIC8qKlxuICAgICAqIENyZWF0ZSBzbGljZSBvZiBzb3VyY2UgYXJyYXkgb3IgYXJyYXktbGlrZSBvYmplY3RcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBzbGljZShhcnIsIHN0YXJ0LCBlbmQpe1xuICAgICAgICB2YXIgbGVuID0gYXJyLmxlbmd0aDtcblxuICAgICAgICBpZiAoc3RhcnQgPT0gbnVsbCkge1xuICAgICAgICAgICAgc3RhcnQgPSAwO1xuICAgICAgICB9IGVsc2UgaWYgKHN0YXJ0IDwgMCkge1xuICAgICAgICAgICAgc3RhcnQgPSBNYXRoLm1heChsZW4gKyBzdGFydCwgMCk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBzdGFydCA9IE1hdGgubWluKHN0YXJ0LCBsZW4pO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKGVuZCA9PSBudWxsKSB7XG4gICAgICAgICAgICBlbmQgPSBsZW47XG4gICAgICAgIH0gZWxzZSBpZiAoZW5kIDwgMCkge1xuICAgICAgICAgICAgZW5kID0gTWF0aC5tYXgobGVuICsgZW5kLCAwKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGVuZCA9IE1hdGgubWluKGVuZCwgbGVuKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciByZXN1bHQgPSBbXTtcbiAgICAgICAgd2hpbGUgKHN0YXJ0IDwgZW5kKSB7XG4gICAgICAgICAgICByZXN1bHQucHVzaChhcnJbc3RhcnQrK10pO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIHJlc3VsdDtcbiAgICB9XG5cbiAgICBtb2R1bGUuZXhwb3J0cyA9IHNsaWNlO1xuXG5cbiIsInZhciBzbGljZSA9IHJlcXVpcmUoJy4uL2FycmF5L3NsaWNlJyk7XG5cbiAgICAvKipcbiAgICAgKiBSZXR1cm4gYSBmdW5jdGlvbiB0aGF0IHdpbGwgZXhlY3V0ZSBpbiB0aGUgZ2l2ZW4gY29udGV4dCwgb3B0aW9uYWxseSBhZGRpbmcgYW55IGFkZGl0aW9uYWwgc3VwcGxpZWQgcGFyYW1ldGVycyB0byB0aGUgYmVnaW5uaW5nIG9mIHRoZSBhcmd1bWVudHMgY29sbGVjdGlvbi5cbiAgICAgKiBAcGFyYW0ge0Z1bmN0aW9ufSBmbiAgRnVuY3Rpb24uXG4gICAgICogQHBhcmFtIHtvYmplY3R9IGNvbnRleHQgICBFeGVjdXRpb24gY29udGV4dC5cbiAgICAgKiBAcGFyYW0ge3Jlc3R9IGFyZ3MgICAgQXJndW1lbnRzICgwLi4ubiBhcmd1bWVudHMpLlxuICAgICAqIEByZXR1cm4ge0Z1bmN0aW9ufSBXcmFwcGVkIEZ1bmN0aW9uLlxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGJpbmQoZm4sIGNvbnRleHQsIGFyZ3Mpe1xuICAgICAgICB2YXIgYXJnc0FyciA9IHNsaWNlKGFyZ3VtZW50cywgMik7IC8vY3VycmllZCBhcmdzXG4gICAgICAgIHJldHVybiBmdW5jdGlvbigpe1xuICAgICAgICAgICAgcmV0dXJuIGZuLmFwcGx5KGNvbnRleHQsIGFyZ3NBcnIuY29uY2F0KHNsaWNlKGFyZ3VtZW50cykpKTtcbiAgICAgICAgfTtcbiAgICB9XG5cbiAgICBtb2R1bGUuZXhwb3J0cyA9IGJpbmQ7XG5cblxuIiwidmFyIHNsaWNlID0gcmVxdWlyZSgnLi4vYXJyYXkvc2xpY2UnKTtcblxuICAgIC8qKlxuICAgICAqIERlbGF5cyB0aGUgY2FsbCBvZiBhIGZ1bmN0aW9uIHdpdGhpbiBhIGdpdmVuIGNvbnRleHQuXG4gICAgICovXG4gICAgZnVuY3Rpb24gdGltZW91dChmbiwgbWlsbGlzLCBjb250ZXh0KXtcblxuICAgICAgICB2YXIgYXJncyA9IHNsaWNlKGFyZ3VtZW50cywgMyk7XG5cbiAgICAgICAgcmV0dXJuIHNldFRpbWVvdXQoZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICBmbi5hcHBseShjb250ZXh0LCBhcmdzKTtcbiAgICAgICAgfSwgbWlsbGlzKTtcbiAgICB9XG5cbiAgICBtb2R1bGUuZXhwb3J0cyA9IHRpbWVvdXQ7XG5cblxuIiwiXG4gICAgLyoqXG4gICAgICogQ2xhbXBzIHZhbHVlIGluc2lkZSByYW5nZS5cbiAgICAgKi9cbiAgICBmdW5jdGlvbiBjbGFtcCh2YWwsIG1pbiwgbWF4KXtcbiAgICAgICAgcmV0dXJuIHZhbCA8IG1pbj8gbWluIDogKHZhbCA+IG1heD8gbWF4IDogdmFsKTtcbiAgICB9XG4gICAgbW9kdWxlLmV4cG9ydHMgPSBjbGFtcDtcblxuIiwiXG4gICAgLyoqXG4gICAgKiBMaW5lYXIgaW50ZXJwb2xhdGlvbi5cbiAgICAqIElNUE9SVEFOVDp3aWxsIHJldHVybiBgSW5maW5pdHlgIGlmIG51bWJlcnMgb3ZlcmZsb3cgTnVtYmVyLk1BWF9WQUxVRVxuICAgICovXG4gICAgZnVuY3Rpb24gbGVycChyYXRpbywgc3RhcnQsIGVuZCl7XG4gICAgICAgIHJldHVybiBzdGFydCArIChlbmQgLSBzdGFydCkgKiByYXRpbztcbiAgICB9XG5cbiAgICBtb2R1bGUuZXhwb3J0cyA9IGxlcnA7XG5cbiIsInZhciBsZXJwID0gcmVxdWlyZSgnLi9sZXJwJyk7XG52YXIgbm9ybSA9IHJlcXVpcmUoJy4vbm9ybScpO1xuICAgIC8qKlxuICAgICogTWFwcyBhIG51bWJlciBmcm9tIG9uZSBzY2FsZSB0byBhbm90aGVyLlxuICAgICogQGV4YW1wbGUgbWFwKDMsIDAsIDQsIC0xLCAxKSAtPiAwLjVcbiAgICAqL1xuICAgIGZ1bmN0aW9uIG1hcCh2YWwsIG1pbjEsIG1heDEsIG1pbjIsIG1heDIpe1xuICAgICAgICByZXR1cm4gbGVycCggbm9ybSh2YWwsIG1pbjEsIG1heDEpLCBtaW4yLCBtYXgyICk7XG4gICAgfVxuICAgIG1vZHVsZS5leHBvcnRzID0gbWFwO1xuXG4iLCJcbiAgICAvKipcbiAgICAqIEdldHMgbm9ybWFsaXplZCByYXRpbyBvZiB2YWx1ZSBpbnNpZGUgcmFuZ2UuXG4gICAgKi9cbiAgICBmdW5jdGlvbiBub3JtKHZhbCwgbWluLCBtYXgpe1xuICAgICAgICBpZiAodmFsIDwgbWluIHx8IHZhbCA+IG1heCkge1xuICAgICAgICAgICAgdGhyb3cgbmV3IFJhbmdlRXJyb3IoJ3ZhbHVlICgnICsgdmFsICsgJykgbXVzdCBiZSBiZXR3ZWVuICcgKyBtaW4gKyAnIGFuZCAnICsgbWF4KTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiB2YWwgPT09IG1heCA/IDEgOiAodmFsIC0gbWluKSAvIChtYXggLSBtaW4pO1xuICAgIH1cbiAgICBtb2R1bGUuZXhwb3J0cyA9IG5vcm07XG5cbiIsInZhciBmb3JPd24gPSByZXF1aXJlKCcuL2Zvck93bicpO1xudmFyIGlzQXJyYXkgPSByZXF1aXJlKCcuLi9sYW5nL2lzQXJyYXknKTtcblxuICAgIGZ1bmN0aW9uIGNvbnRhaW5zTWF0Y2goYXJyYXksIHBhdHRlcm4pIHtcbiAgICAgICAgdmFyIGkgPSAtMSwgbGVuZ3RoID0gYXJyYXkubGVuZ3RoO1xuICAgICAgICB3aGlsZSAoKytpIDwgbGVuZ3RoKSB7XG4gICAgICAgICAgICBpZiAoZGVlcE1hdGNoZXMoYXJyYXlbaV0sIHBhdHRlcm4pKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgfVxuXG4gICAgZnVuY3Rpb24gbWF0Y2hBcnJheSh0YXJnZXQsIHBhdHRlcm4pIHtcbiAgICAgICAgdmFyIGkgPSAtMSwgcGF0dGVybkxlbmd0aCA9IHBhdHRlcm4ubGVuZ3RoO1xuICAgICAgICB3aGlsZSAoKytpIDwgcGF0dGVybkxlbmd0aCkge1xuICAgICAgICAgICAgaWYgKCFjb250YWluc01hdGNoKHRhcmdldCwgcGF0dGVybltpXSkpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICB9XG5cbiAgICBmdW5jdGlvbiBtYXRjaE9iamVjdCh0YXJnZXQsIHBhdHRlcm4pIHtcbiAgICAgICAgdmFyIHJlc3VsdCA9IHRydWU7XG4gICAgICAgIGZvck93bihwYXR0ZXJuLCBmdW5jdGlvbih2YWwsIGtleSkge1xuICAgICAgICAgICAgaWYgKCFkZWVwTWF0Y2hlcyh0YXJnZXRba2V5XSwgdmFsKSkge1xuICAgICAgICAgICAgICAgIC8vIFJldHVybiBmYWxzZSB0byBicmVhayBvdXQgb2YgZm9yT3duIGVhcmx5XG4gICAgICAgICAgICAgICAgcmV0dXJuIChyZXN1bHQgPSBmYWxzZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgICAgIHJldHVybiByZXN1bHQ7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogUmVjdXJzaXZlbHkgY2hlY2sgaWYgdGhlIG9iamVjdHMgbWF0Y2guXG4gICAgICovXG4gICAgZnVuY3Rpb24gZGVlcE1hdGNoZXModGFyZ2V0LCBwYXR0ZXJuKXtcbiAgICAgICAgaWYgKHRhcmdldCAmJiB0eXBlb2YgdGFyZ2V0ID09PSAnb2JqZWN0JyAmJlxuICAgICAgICAgICAgcGF0dGVybiAmJiB0eXBlb2YgcGF0dGVybiA9PT0gJ29iamVjdCcpIHtcbiAgICAgICAgICAgIGlmIChpc0FycmF5KHRhcmdldCkgJiYgaXNBcnJheShwYXR0ZXJuKSkge1xuICAgICAgICAgICAgICAgIHJldHVybiBtYXRjaEFycmF5KHRhcmdldCwgcGF0dGVybik7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIHJldHVybiBtYXRjaE9iamVjdCh0YXJnZXQsIHBhdHRlcm4pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIHRhcmdldCA9PT0gcGF0dGVybjtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIG1vZHVsZS5leHBvcnRzID0gZGVlcE1hdGNoZXM7XG5cblxuIiwidmFyIGtpbmRPZiA9IHJlcXVpcmUoJy4va2luZE9mJyk7XG52YXIgaXNQbGFpbk9iamVjdCA9IHJlcXVpcmUoJy4vaXNQbGFpbk9iamVjdCcpO1xudmFyIG1peEluID0gcmVxdWlyZSgnLi4vb2JqZWN0L21peEluJyk7XG5cbiAgICAvKipcbiAgICAgKiBDbG9uZSBuYXRpdmUgdHlwZXMuXG4gICAgICovXG4gICAgZnVuY3Rpb24gY2xvbmUodmFsKXtcbiAgICAgICAgc3dpdGNoIChraW5kT2YodmFsKSkge1xuICAgICAgICAgICAgY2FzZSAnT2JqZWN0JzpcbiAgICAgICAgICAgICAgICByZXR1cm4gY2xvbmVPYmplY3QodmFsKTtcbiAgICAgICAgICAgIGNhc2UgJ0FycmF5JzpcbiAgICAgICAgICAgICAgICByZXR1cm4gY2xvbmVBcnJheSh2YWwpO1xuICAgICAgICAgICAgY2FzZSAnUmVnRXhwJzpcbiAgICAgICAgICAgICAgICByZXR1cm4gY2xvbmVSZWdFeHAodmFsKTtcbiAgICAgICAgICAgIGNhc2UgJ0RhdGUnOlxuICAgICAgICAgICAgICAgIHJldHVybiBjbG9uZURhdGUodmFsKTtcbiAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICAgICAgcmV0dXJuIHZhbDtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIGZ1bmN0aW9uIGNsb25lT2JqZWN0KHNvdXJjZSkge1xuICAgICAgICBpZiAoaXNQbGFpbk9iamVjdChzb3VyY2UpKSB7XG4gICAgICAgICAgICByZXR1cm4gbWl4SW4oe30sIHNvdXJjZSk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gc291cmNlO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgZnVuY3Rpb24gY2xvbmVSZWdFeHAocikge1xuICAgICAgICB2YXIgZmxhZ3MgPSAnJztcbiAgICAgICAgZmxhZ3MgKz0gci5tdWx0aWxpbmUgPyAnbScgOiAnJztcbiAgICAgICAgZmxhZ3MgKz0gci5nbG9iYWwgPyAnZycgOiAnJztcbiAgICAgICAgZmxhZ3MgKz0gci5pZ25vcmVjYXNlID8gJ2knIDogJyc7XG4gICAgICAgIHJldHVybiBuZXcgUmVnRXhwKHIuc291cmNlLCBmbGFncyk7XG4gICAgfVxuXG4gICAgZnVuY3Rpb24gY2xvbmVEYXRlKGRhdGUpIHtcbiAgICAgICAgcmV0dXJuIG5ldyBEYXRlKCtkYXRlKTtcbiAgICB9XG5cbiAgICBmdW5jdGlvbiBjbG9uZUFycmF5KGFycikge1xuICAgICAgICByZXR1cm4gYXJyLnNsaWNlKCk7XG4gICAgfVxuXG4gICAgbW9kdWxlLmV4cG9ydHMgPSBjbG9uZTtcblxuXG4iLCJ2YXIgY2xvbmUgPSByZXF1aXJlKCcuL2Nsb25lJyk7XG52YXIgZm9yT3duID0gcmVxdWlyZSgnLi4vb2JqZWN0L2Zvck93bicpO1xudmFyIGtpbmRPZiA9IHJlcXVpcmUoJy4va2luZE9mJyk7XG52YXIgaXNQbGFpbk9iamVjdCA9IHJlcXVpcmUoJy4vaXNQbGFpbk9iamVjdCcpO1xuXG4gICAgLyoqXG4gICAgICogUmVjdXJzaXZlbHkgY2xvbmUgbmF0aXZlIHR5cGVzLlxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGRlZXBDbG9uZSh2YWwsIGluc3RhbmNlQ2xvbmUpIHtcbiAgICAgICAgc3dpdGNoICgga2luZE9mKHZhbCkgKSB7XG4gICAgICAgICAgICBjYXNlICdPYmplY3QnOlxuICAgICAgICAgICAgICAgIHJldHVybiBjbG9uZU9iamVjdCh2YWwsIGluc3RhbmNlQ2xvbmUpO1xuICAgICAgICAgICAgY2FzZSAnQXJyYXknOlxuICAgICAgICAgICAgICAgIHJldHVybiBjbG9uZUFycmF5KHZhbCwgaW5zdGFuY2VDbG9uZSk7XG4gICAgICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgICAgIHJldHVybiBjbG9uZSh2YWwpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgZnVuY3Rpb24gY2xvbmVPYmplY3Qoc291cmNlLCBpbnN0YW5jZUNsb25lKSB7XG4gICAgICAgIGlmIChpc1BsYWluT2JqZWN0KHNvdXJjZSkpIHtcbiAgICAgICAgICAgIHZhciBvdXQgPSB7fTtcbiAgICAgICAgICAgIGZvck93bihzb3VyY2UsIGZ1bmN0aW9uKHZhbCwga2V5KSB7XG4gICAgICAgICAgICAgICAgdGhpc1trZXldID0gZGVlcENsb25lKHZhbCwgaW5zdGFuY2VDbG9uZSk7XG4gICAgICAgICAgICB9LCBvdXQpO1xuICAgICAgICAgICAgcmV0dXJuIG91dDtcbiAgICAgICAgfSBlbHNlIGlmIChpbnN0YW5jZUNsb25lKSB7XG4gICAgICAgICAgICByZXR1cm4gaW5zdGFuY2VDbG9uZShzb3VyY2UpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIHNvdXJjZTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIGZ1bmN0aW9uIGNsb25lQXJyYXkoYXJyLCBpbnN0YW5jZUNsb25lKSB7XG4gICAgICAgIHZhciBvdXQgPSBbXSxcbiAgICAgICAgICAgIGkgPSAtMSxcbiAgICAgICAgICAgIG4gPSBhcnIubGVuZ3RoLFxuICAgICAgICAgICAgdmFsO1xuICAgICAgICB3aGlsZSAoKytpIDwgbikge1xuICAgICAgICAgICAgb3V0W2ldID0gZGVlcENsb25lKGFycltpXSwgaW5zdGFuY2VDbG9uZSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIG91dDtcbiAgICB9XG5cbiAgICBtb2R1bGUuZXhwb3J0cyA9IGRlZXBDbG9uZTtcblxuXG5cbiIsInZhciBpc0tpbmQgPSByZXF1aXJlKCcuL2lzS2luZCcpO1xuICAgIC8qKlxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGlzT2JqZWN0KHZhbCkge1xuICAgICAgICByZXR1cm4gaXNLaW5kKHZhbCwgJ09iamVjdCcpO1xuICAgIH1cbiAgICBtb2R1bGUuZXhwb3J0cyA9IGlzT2JqZWN0O1xuXG4iLCJcblxuICAgIC8qKlxuICAgICAqIENoZWNrcyBpZiB0aGUgdmFsdWUgaXMgY3JlYXRlZCBieSB0aGUgYE9iamVjdGAgY29uc3RydWN0b3IuXG4gICAgICovXG4gICAgZnVuY3Rpb24gaXNQbGFpbk9iamVjdCh2YWx1ZSkge1xuICAgICAgICByZXR1cm4gKCEhdmFsdWUgJiYgdHlwZW9mIHZhbHVlID09PSAnb2JqZWN0JyAmJlxuICAgICAgICAgICAgdmFsdWUuY29uc3RydWN0b3IgPT09IE9iamVjdCk7XG4gICAgfVxuXG4gICAgbW9kdWxlLmV4cG9ydHMgPSBpc1BsYWluT2JqZWN0O1xuXG5cbiIsInZhciBoYXNPd24gPSByZXF1aXJlKCcuL2hhc093bicpO1xudmFyIGRlZXBDbG9uZSA9IHJlcXVpcmUoJy4uL2xhbmcvZGVlcENsb25lJyk7XG52YXIgaXNPYmplY3QgPSByZXF1aXJlKCcuLi9sYW5nL2lzT2JqZWN0Jyk7XG5cbiAgICAvKipcbiAgICAgKiBEZWVwIG1lcmdlIG9iamVjdHMuXG4gICAgICovXG4gICAgZnVuY3Rpb24gbWVyZ2UoKSB7XG4gICAgICAgIHZhciBpID0gMSxcbiAgICAgICAgICAgIGtleSwgdmFsLCBvYmosIHRhcmdldDtcblxuICAgICAgICAvLyBtYWtlIHN1cmUgd2UgZG9uJ3QgbW9kaWZ5IHNvdXJjZSBlbGVtZW50IGFuZCBpdCdzIHByb3BlcnRpZXNcbiAgICAgICAgLy8gb2JqZWN0cyBhcmUgcGFzc2VkIGJ5IHJlZmVyZW5jZVxuICAgICAgICB0YXJnZXQgPSBkZWVwQ2xvbmUoIGFyZ3VtZW50c1swXSApO1xuXG4gICAgICAgIHdoaWxlIChvYmogPSBhcmd1bWVudHNbaSsrXSkge1xuICAgICAgICAgICAgZm9yIChrZXkgaW4gb2JqKSB7XG4gICAgICAgICAgICAgICAgaWYgKCAhIGhhc093bihvYmosIGtleSkgKSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIHZhbCA9IG9ialtrZXldO1xuXG4gICAgICAgICAgICAgICAgaWYgKCBpc09iamVjdCh2YWwpICYmIGlzT2JqZWN0KHRhcmdldFtrZXldKSApe1xuICAgICAgICAgICAgICAgICAgICAvLyBpbmNlcHRpb24sIGRlZXAgbWVyZ2Ugb2JqZWN0c1xuICAgICAgICAgICAgICAgICAgICB0YXJnZXRba2V5XSA9IG1lcmdlKHRhcmdldFtrZXldLCB2YWwpO1xuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIC8vIG1ha2Ugc3VyZSBhcnJheXMsIHJlZ2V4cCwgZGF0ZSwgb2JqZWN0cyBhcmUgY2xvbmVkXG4gICAgICAgICAgICAgICAgICAgIHRhcmdldFtrZXldID0gZGVlcENsb25lKHZhbCk7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gdGFyZ2V0O1xuICAgIH1cblxuICAgIG1vZHVsZS5leHBvcnRzID0gbWVyZ2U7XG5cblxuIiwidmFyIGZvck93biA9IHJlcXVpcmUoJy4vZm9yT3duJyk7XG5cbiAgICAvKipcbiAgICAqIENvbWJpbmUgcHJvcGVydGllcyBmcm9tIGFsbCB0aGUgb2JqZWN0cyBpbnRvIGZpcnN0IG9uZS5cbiAgICAqIC0gVGhpcyBtZXRob2QgYWZmZWN0cyB0YXJnZXQgb2JqZWN0IGluIHBsYWNlLCBpZiB5b3Ugd2FudCB0byBjcmVhdGUgYSBuZXcgT2JqZWN0IHBhc3MgYW4gZW1wdHkgb2JqZWN0IGFzIGZpcnN0IHBhcmFtLlxuICAgICogQHBhcmFtIHtvYmplY3R9IHRhcmdldCAgICBUYXJnZXQgT2JqZWN0XG4gICAgKiBAcGFyYW0gey4uLm9iamVjdH0gb2JqZWN0cyAgICBPYmplY3RzIHRvIGJlIGNvbWJpbmVkICgwLi4ubiBvYmplY3RzKS5cbiAgICAqIEByZXR1cm4ge29iamVjdH0gVGFyZ2V0IE9iamVjdC5cbiAgICAqL1xuICAgIGZ1bmN0aW9uIG1peEluKHRhcmdldCwgb2JqZWN0cyl7XG4gICAgICAgIHZhciBpID0gMCxcbiAgICAgICAgICAgIG4gPSBhcmd1bWVudHMubGVuZ3RoLFxuICAgICAgICAgICAgb2JqO1xuICAgICAgICB3aGlsZSgrK2kgPCBuKXtcbiAgICAgICAgICAgIG9iaiA9IGFyZ3VtZW50c1tpXTtcbiAgICAgICAgICAgIGlmIChvYmogIT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIGZvck93bihvYmosIGNvcHlQcm9wLCB0YXJnZXQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIHJldHVybiB0YXJnZXQ7XG4gICAgfVxuXG4gICAgZnVuY3Rpb24gY29weVByb3AodmFsLCBrZXkpe1xuICAgICAgICB0aGlzW2tleV0gPSB2YWw7XG4gICAgfVxuXG4gICAgbW9kdWxlLmV4cG9ydHMgPSBtaXhJbjtcblxuIiwiLypcbnByaW1lXG4gLSBwcm90b3R5cGFsIGluaGVyaXRhbmNlXG4qL1widXNlIHN0cmljdFwiXG5cbnZhciBoYXNPd24gPSByZXF1aXJlKFwibW91dC9vYmplY3QvaGFzT3duXCIpLFxuICAgIG1peEluICA9IHJlcXVpcmUoXCJtb3V0L29iamVjdC9taXhJblwiKSxcbiAgICBjcmVhdGUgPSByZXF1aXJlKFwibW91dC9sYW5nL2NyZWF0ZU9iamVjdFwiKSxcbiAgICBraW5kT2YgPSByZXF1aXJlKFwibW91dC9sYW5nL2tpbmRPZlwiKVxuXG52YXIgaGFzRGVzY3JpcHRvcnMgPSB0cnVlXG5cbnRyeSB7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KHt9LCBcIn5cIiwge30pXG4gICAgT2JqZWN0LmdldE93blByb3BlcnR5RGVzY3JpcHRvcih7fSwgXCJ+XCIpXG59IGNhdGNoIChlKXtcbiAgICBoYXNEZXNjcmlwdG9ycyA9IGZhbHNlXG59XG5cbi8vIHdlIG9ubHkgbmVlZCB0byBiZSBhYmxlIHRvIGltcGxlbWVudCBcInRvU3RyaW5nXCIgYW5kIFwidmFsdWVPZlwiIGluIElFIDwgOVxudmFyIGhhc0VudW1CdWcgPSAhKHt2YWx1ZU9mOiAwfSkucHJvcGVydHlJc0VudW1lcmFibGUoXCJ2YWx1ZU9mXCIpLFxuICAgIGJ1Z2d5ICAgICAgPSBbXCJ0b1N0cmluZ1wiLCBcInZhbHVlT2ZcIl1cblxudmFyIHZlcmJzID0gL15jb25zdHJ1Y3Rvcnxpbmhlcml0c3xtaXhpbiQvXG5cbnZhciBpbXBsZW1lbnQgPSBmdW5jdGlvbihwcm90byl7XG4gICAgdmFyIHByb3RvdHlwZSA9IHRoaXMucHJvdG90eXBlXG5cbiAgICBmb3IgKHZhciBrZXkgaW4gcHJvdG8pe1xuICAgICAgICBpZiAoa2V5Lm1hdGNoKHZlcmJzKSkgY29udGludWVcbiAgICAgICAgaWYgKGhhc0Rlc2NyaXB0b3JzKXtcbiAgICAgICAgICAgIHZhciBkZXNjcmlwdG9yID0gT2JqZWN0LmdldE93blByb3BlcnR5RGVzY3JpcHRvcihwcm90bywga2V5KVxuICAgICAgICAgICAgaWYgKGRlc2NyaXB0b3Ipe1xuICAgICAgICAgICAgICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShwcm90b3R5cGUsIGtleSwgZGVzY3JpcHRvcilcbiAgICAgICAgICAgICAgICBjb250aW51ZVxuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIHByb3RvdHlwZVtrZXldID0gcHJvdG9ba2V5XVxuICAgIH1cblxuICAgIGlmIChoYXNFbnVtQnVnKSBmb3IgKHZhciBpID0gMDsgKGtleSA9IGJ1Z2d5W2ldKTsgaSsrKXtcbiAgICAgICAgdmFyIHZhbHVlID0gcHJvdG9ba2V5XVxuICAgICAgICBpZiAodmFsdWUgIT09IE9iamVjdC5wcm90b3R5cGVba2V5XSkgcHJvdG90eXBlW2tleV0gPSB2YWx1ZVxuICAgIH1cblxuICAgIHJldHVybiB0aGlzXG59XG5cbnZhciBwcmltZSA9IGZ1bmN0aW9uKHByb3RvKXtcblxuICAgIGlmIChraW5kT2YocHJvdG8pID09PSBcIkZ1bmN0aW9uXCIpIHByb3RvID0ge2NvbnN0cnVjdG9yOiBwcm90b31cblxuICAgIHZhciBzdXBlcnByaW1lID0gcHJvdG8uaW5oZXJpdHNcblxuICAgIC8vIGlmIG91ciBuaWNlIHByb3RvIG9iamVjdCBoYXMgbm8gb3duIGNvbnN0cnVjdG9yIHByb3BlcnR5XG4gICAgLy8gdGhlbiB3ZSBwcm9jZWVkIHVzaW5nIGEgZ2hvc3RpbmcgY29uc3RydWN0b3IgdGhhdCBhbGwgaXQgZG9lcyBpc1xuICAgIC8vIGNhbGwgdGhlIHBhcmVudCdzIGNvbnN0cnVjdG9yIGlmIGl0IGhhcyBhIHN1cGVycHJpbWUsIGVsc2UgYW4gZW1wdHkgY29uc3RydWN0b3JcbiAgICAvLyBwcm90by5jb25zdHJ1Y3RvciBiZWNvbWVzIHRoZSBlZmZlY3RpdmUgY29uc3RydWN0b3JcbiAgICB2YXIgY29uc3RydWN0b3IgPSAoaGFzT3duKHByb3RvLCBcImNvbnN0cnVjdG9yXCIpKSA/IHByb3RvLmNvbnN0cnVjdG9yIDogKHN1cGVycHJpbWUpID8gZnVuY3Rpb24oKXtcbiAgICAgICAgcmV0dXJuIHN1cGVycHJpbWUuYXBwbHkodGhpcywgYXJndW1lbnRzKVxuICAgIH0gOiBmdW5jdGlvbigpe31cblxuICAgIGlmIChzdXBlcnByaW1lKXtcblxuICAgICAgICBtaXhJbihjb25zdHJ1Y3Rvciwgc3VwZXJwcmltZSlcblxuICAgICAgICB2YXIgc3VwZXJwcm90byA9IHN1cGVycHJpbWUucHJvdG90eXBlXG4gICAgICAgIC8vIGluaGVyaXQgZnJvbSBzdXBlcnByaW1lXG4gICAgICAgIHZhciBjcHJvdG8gPSBjb25zdHJ1Y3Rvci5wcm90b3R5cGUgPSBjcmVhdGUoc3VwZXJwcm90bylcblxuICAgICAgICAvLyBzZXR0aW5nIGNvbnN0cnVjdG9yLnBhcmVudCB0byBzdXBlcnByaW1lLnByb3RvdHlwZVxuICAgICAgICAvLyBiZWNhdXNlIGl0J3MgdGhlIHNob3J0ZXN0IHBvc3NpYmxlIGFic29sdXRlIHJlZmVyZW5jZVxuICAgICAgICBjb25zdHJ1Y3Rvci5wYXJlbnQgPSBzdXBlcnByb3RvXG4gICAgICAgIGNwcm90by5jb25zdHJ1Y3RvciA9IGNvbnN0cnVjdG9yXG4gICAgfVxuXG4gICAgaWYgKCFjb25zdHJ1Y3Rvci5pbXBsZW1lbnQpIGNvbnN0cnVjdG9yLmltcGxlbWVudCA9IGltcGxlbWVudFxuXG4gICAgdmFyIG1peGlucyA9IHByb3RvLm1peGluXG4gICAgaWYgKG1peGlucyl7XG4gICAgICAgIGlmIChraW5kT2YobWl4aW5zKSAhPT0gXCJBcnJheVwiKSBtaXhpbnMgPSBbbWl4aW5zXVxuICAgICAgICBmb3IgKHZhciBpID0gMDsgaSA8IG1peGlucy5sZW5ndGg7IGkrKykgY29uc3RydWN0b3IuaW1wbGVtZW50KGNyZWF0ZShtaXhpbnNbaV0ucHJvdG90eXBlKSlcbiAgICB9XG5cbiAgICAvLyBpbXBsZW1lbnQgcHJvdG8gYW5kIHJldHVybiBjb25zdHJ1Y3RvclxuICAgIHJldHVybiBjb25zdHJ1Y3Rvci5pbXBsZW1lbnQocHJvdG8pXG5cbn1cblxubW9kdWxlLmV4cG9ydHMgPSBwcmltZVxuIiwidmFyIG1peEluID0gcmVxdWlyZSgnLi4vb2JqZWN0L21peEluJyk7XG5cbiAgICAvKipcbiAgICAgKiBDcmVhdGUgT2JqZWN0IHVzaW5nIHByb3RvdHlwYWwgaW5oZXJpdGFuY2UgYW5kIHNldHRpbmcgY3VzdG9tIHByb3BlcnRpZXMuXG4gICAgICogLSBNaXggYmV0d2VlbiBEb3VnbGFzIENyb2NrZm9yZCBQcm90b3R5cGFsIEluaGVyaXRhbmNlIDxodHRwOi8vamF2YXNjcmlwdC5jcm9ja2ZvcmQuY29tL3Byb3RvdHlwYWwuaHRtbD4gYW5kIHRoZSBFY21hU2NyaXB0IDUgYE9iamVjdC5jcmVhdGUoKWAgbWV0aG9kLlxuICAgICAqIEBwYXJhbSB7b2JqZWN0fSBwYXJlbnQgICAgUGFyZW50IE9iamVjdC5cbiAgICAgKiBAcGFyYW0ge29iamVjdH0gW3Byb3BzXSBPYmplY3QgcHJvcGVydGllcy5cbiAgICAgKiBAcmV0dXJuIHtvYmplY3R9IENyZWF0ZWQgb2JqZWN0LlxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGNyZWF0ZU9iamVjdChwYXJlbnQsIHByb3BzKXtcbiAgICAgICAgZnVuY3Rpb24gRigpe31cbiAgICAgICAgRi5wcm90b3R5cGUgPSBwYXJlbnQ7XG4gICAgICAgIHJldHVybiBtaXhJbihuZXcgRigpLCBwcm9wcyk7XG5cbiAgICB9XG4gICAgbW9kdWxlLmV4cG9ydHMgPSBjcmVhdGVPYmplY3Q7XG5cblxuIiwiXCJ1c2Ugc3RyaWN0XCI7XG5cbi8vIGNyZWRpdHMgdG8gQGNwb2plcidzIENsYXNzLkJpbmRzLCByZWxlYXNlZCB1bmRlciB0aGUgTUlUIGxpY2Vuc2Vcbi8vIGh0dHBzOi8vZ2l0aHViLmNvbS9jcG9qZXIvbW9vdG9vbHMtY2xhc3MtZXh0cmFzL2Jsb2IvbWFzdGVyL1NvdXJjZS9DbGFzcy5CaW5kcy5qc1xuXG52YXIgcHJpbWUgPSByZXF1aXJlKFwicHJpbWVcIilcbnZhciBiaW5kID0gcmVxdWlyZShcIm1vdXQvZnVuY3Rpb24vYmluZFwiKVxuXG52YXIgYm91bmQgPSBwcmltZSh7XG5cbiAgICBib3VuZDogZnVuY3Rpb24obmFtZSl7XG4gICAgICAgIHZhciBib3VuZCA9IHRoaXMuX2JvdW5kIHx8ICh0aGlzLl9ib3VuZCA9IHt9KVxuICAgICAgICByZXR1cm4gYm91bmRbbmFtZV0gfHwgKGJvdW5kW25hbWVdID0gYmluZCh0aGlzW25hbWVdLCB0aGlzKSlcbiAgICB9XG5cbn0pXG5cbm1vZHVsZS5leHBvcnRzID0gYm91bmRcbiIsIlwidXNlIHN0cmljdFwiO1xuXG52YXIgcHJpbWUgPSByZXF1aXJlKFwicHJpbWVcIilcbnZhciBtZXJnZSA9IHJlcXVpcmUoXCJtb3V0L29iamVjdC9tZXJnZVwiKVxuXG52YXIgT3B0aW9ucyA9IHByaW1lKHtcblxuICAgIHNldE9wdGlvbnM6IGZ1bmN0aW9uKG9wdGlvbnMpe1xuICAgICAgICB2YXIgYXJncyA9IFt7fSwgdGhpcy5vcHRpb25zXVxuICAgICAgICBhcmdzLnB1c2guYXBwbHkoYXJncywgYXJndW1lbnRzKVxuICAgICAgICB0aGlzLm9wdGlvbnMgPSBtZXJnZS5hcHBseShudWxsLCBhcmdzKVxuICAgICAgICByZXR1cm4gdGhpc1xuICAgIH1cblxufSlcblxubW9kdWxlLmV4cG9ydHMgPSBPcHRpb25zXG4iLCIvKlxyXG5kZWZlclxyXG4qL1widXNlIHN0cmljdFwiXHJcblxyXG52YXIga2luZE9mICA9IHJlcXVpcmUoXCJtb3V0L2xhbmcva2luZE9mXCIpLFxyXG4gICAgbm93ICAgICA9IHJlcXVpcmUoXCJtb3V0L3RpbWUvbm93XCIpLFxyXG4gICAgZm9yRWFjaCA9IHJlcXVpcmUoXCJtb3V0L2FycmF5L2ZvckVhY2hcIiksXHJcbiAgICBpbmRleE9mID0gcmVxdWlyZShcIm1vdXQvYXJyYXkvaW5kZXhPZlwiKVxyXG5cclxudmFyIGNhbGxiYWNrcyA9IHtcclxuICAgIHRpbWVvdXQ6IHt9LFxyXG4gICAgZnJhbWU6IFtdLFxyXG4gICAgaW1tZWRpYXRlOiBbXVxyXG59XHJcblxyXG52YXIgcHVzaCA9IGZ1bmN0aW9uKGNvbGxlY3Rpb24sIGNhbGxiYWNrLCBjb250ZXh0LCBkZWZlcil7XHJcblxyXG4gICAgdmFyIGl0ZXJhdG9yID0gZnVuY3Rpb24oKXtcclxuICAgICAgICBpdGVyYXRlKGNvbGxlY3Rpb24pXHJcbiAgICB9XHJcblxyXG4gICAgaWYgKCFjb2xsZWN0aW9uLmxlbmd0aCkgZGVmZXIoaXRlcmF0b3IpXHJcblxyXG4gICAgdmFyIGVudHJ5ID0ge1xyXG4gICAgICAgIGNhbGxiYWNrOiBjYWxsYmFjayxcclxuICAgICAgICBjb250ZXh0OiBjb250ZXh0XHJcbiAgICB9XHJcblxyXG4gICAgY29sbGVjdGlvbi5wdXNoKGVudHJ5KVxyXG5cclxuICAgIHJldHVybiBmdW5jdGlvbigpe1xyXG4gICAgICAgIHZhciBpbyA9IGluZGV4T2YoY29sbGVjdGlvbiwgZW50cnkpXHJcbiAgICAgICAgaWYgKGlvID4gLTEpIGNvbGxlY3Rpb24uc3BsaWNlKGlvLCAxKVxyXG4gICAgfVxyXG59XHJcblxyXG52YXIgaXRlcmF0ZSA9IGZ1bmN0aW9uKGNvbGxlY3Rpb24pe1xyXG4gICAgdmFyIHRpbWUgPSBub3coKVxyXG5cclxuICAgIGZvckVhY2goY29sbGVjdGlvbi5zcGxpY2UoMCksIGZ1bmN0aW9uKGVudHJ5KSB7XHJcbiAgICAgICAgZW50cnkuY2FsbGJhY2suY2FsbChlbnRyeS5jb250ZXh0LCB0aW1lKVxyXG4gICAgfSlcclxufVxyXG5cclxudmFyIGRlZmVyID0gZnVuY3Rpb24oY2FsbGJhY2ssIGFyZ3VtZW50LCBjb250ZXh0KXtcclxuICAgIHJldHVybiAoa2luZE9mKGFyZ3VtZW50KSA9PT0gXCJOdW1iZXJcIikgPyBkZWZlci50aW1lb3V0KGNhbGxiYWNrLCBhcmd1bWVudCwgY29udGV4dCkgOiBkZWZlci5pbW1lZGlhdGUoY2FsbGJhY2ssIGFyZ3VtZW50KVxyXG59XHJcblxyXG5pZiAoZ2xvYmFsLnByb2Nlc3MgJiYgcHJvY2Vzcy5uZXh0VGljayl7XHJcblxyXG4gICAgZGVmZXIuaW1tZWRpYXRlID0gZnVuY3Rpb24oY2FsbGJhY2ssIGNvbnRleHQpe1xyXG4gICAgICAgIHJldHVybiBwdXNoKGNhbGxiYWNrcy5pbW1lZGlhdGUsIGNhbGxiYWNrLCBjb250ZXh0LCBwcm9jZXNzLm5leHRUaWNrKVxyXG4gICAgfVxyXG5cclxufSBlbHNlIGlmIChnbG9iYWwuc2V0SW1tZWRpYXRlKXtcclxuXHJcbiAgICBkZWZlci5pbW1lZGlhdGUgPSBmdW5jdGlvbihjYWxsYmFjaywgY29udGV4dCl7XHJcbiAgICAgICAgcmV0dXJuIHB1c2goY2FsbGJhY2tzLmltbWVkaWF0ZSwgY2FsbGJhY2ssIGNvbnRleHQsIHNldEltbWVkaWF0ZSlcclxuICAgIH1cclxuXHJcbn0gZWxzZSBpZiAoZ2xvYmFsLnBvc3RNZXNzYWdlICYmIGdsb2JhbC5hZGRFdmVudExpc3RlbmVyKXtcclxuXHJcbiAgICBhZGRFdmVudExpc3RlbmVyKFwibWVzc2FnZVwiLCBmdW5jdGlvbihldmVudCl7XHJcbiAgICAgICAgaWYgKGV2ZW50LnNvdXJjZSA9PT0gZ2xvYmFsICYmIGV2ZW50LmRhdGEgPT09IFwiQGRlZmVycmVkXCIpe1xyXG4gICAgICAgICAgICBldmVudC5zdG9wUHJvcGFnYXRpb24oKVxyXG4gICAgICAgICAgICBpdGVyYXRlKGNhbGxiYWNrcy5pbW1lZGlhdGUpXHJcbiAgICAgICAgfVxyXG4gICAgfSwgdHJ1ZSlcclxuXHJcbiAgICBkZWZlci5pbW1lZGlhdGUgPSBmdW5jdGlvbihjYWxsYmFjaywgY29udGV4dCl7XHJcbiAgICAgICAgcmV0dXJuIHB1c2goY2FsbGJhY2tzLmltbWVkaWF0ZSwgY2FsbGJhY2ssIGNvbnRleHQsIGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgICAgIHBvc3RNZXNzYWdlKFwiQGRlZmVycmVkXCIsIFwiKlwiKVxyXG4gICAgICAgIH0pXHJcbiAgICB9XHJcblxyXG59IGVsc2Uge1xyXG5cclxuICAgIGRlZmVyLmltbWVkaWF0ZSA9IGZ1bmN0aW9uKGNhbGxiYWNrLCBjb250ZXh0KXtcclxuICAgICAgICByZXR1cm4gcHVzaChjYWxsYmFja3MuaW1tZWRpYXRlLCBjYWxsYmFjaywgY29udGV4dCwgZnVuY3Rpb24oaXRlcmF0b3Ipe1xyXG4gICAgICAgICAgICBzZXRUaW1lb3V0KGl0ZXJhdG9yLCAwKVxyXG4gICAgICAgIH0pXHJcbiAgICB9XHJcblxyXG59XHJcblxyXG52YXIgcmVxdWVzdEFuaW1hdGlvbkZyYW1lID0gZ2xvYmFsLnJlcXVlc3RBbmltYXRpb25GcmFtZSB8fFxyXG4gICAgZ2xvYmFsLndlYmtpdFJlcXVlc3RBbmltYXRpb25GcmFtZSB8fFxyXG4gICAgZ2xvYmFsLm1velJlcXVlc3RBbmltYXRpb25GcmFtZSB8fFxyXG4gICAgZ2xvYmFsLm9SZXF1ZXN0QW5pbWF0aW9uRnJhbWUgfHxcclxuICAgIGdsb2JhbC5tc1JlcXVlc3RBbmltYXRpb25GcmFtZSB8fFxyXG4gICAgZnVuY3Rpb24oY2FsbGJhY2spIHtcclxuICAgICAgICBzZXRUaW1lb3V0KGNhbGxiYWNrLCAxZTMgLyA2MClcclxuICAgIH1cclxuXHJcbmRlZmVyLmZyYW1lID0gZnVuY3Rpb24oY2FsbGJhY2ssIGNvbnRleHQpe1xyXG4gICAgcmV0dXJuIHB1c2goY2FsbGJhY2tzLmZyYW1lLCBjYWxsYmFjaywgY29udGV4dCwgcmVxdWVzdEFuaW1hdGlvbkZyYW1lKVxyXG59XHJcblxyXG52YXIgY2xlYXJcclxuXHJcbmRlZmVyLnRpbWVvdXQgPSBmdW5jdGlvbihjYWxsYmFjaywgbXMsIGNvbnRleHQpe1xyXG4gICAgdmFyIGN0ID0gY2FsbGJhY2tzLnRpbWVvdXRcclxuXHJcbiAgICBpZiAoIWNsZWFyKSBjbGVhciA9IGRlZmVyLmltbWVkaWF0ZShmdW5jdGlvbigpe1xyXG4gICAgICAgIGNsZWFyID0gbnVsbFxyXG4gICAgICAgIGNhbGxiYWNrcy50aW1lb3V0ID0ge31cclxuICAgIH0pXHJcblxyXG4gICAgcmV0dXJuIHB1c2goY3RbbXNdIHx8IChjdFttc10gPSBbXSksIGNhbGxiYWNrLCBjb250ZXh0LCBmdW5jdGlvbihpdGVyYXRvcil7XHJcbiAgICAgICAgc2V0VGltZW91dChpdGVyYXRvciwgbXMpXHJcbiAgICB9KVxyXG59XHJcblxyXG5tb2R1bGUuZXhwb3J0cyA9IGRlZmVyXHJcbiIsIi8qXHJcbkVtaXR0ZXJcclxuKi9cInVzZSBzdHJpY3RcIlxyXG5cclxudmFyIGluZGV4T2YgPSByZXF1aXJlKFwibW91dC9hcnJheS9pbmRleE9mXCIpLFxyXG4gICAgZm9yRWFjaCA9IHJlcXVpcmUoXCJtb3V0L2FycmF5L2ZvckVhY2hcIilcclxuXHJcbnZhciBwcmltZSA9IHJlcXVpcmUoXCIuL2luZGV4XCIpLFxyXG4gICAgZGVmZXIgPSByZXF1aXJlKFwiLi9kZWZlclwiKVxyXG5cclxudmFyIHNsaWNlID0gQXJyYXkucHJvdG90eXBlLnNsaWNlO1xyXG5cclxudmFyIEVtaXR0ZXIgPSBwcmltZSh7XHJcblxyXG4gICAgY29uc3RydWN0b3I6IGZ1bmN0aW9uKHN0b3BwYWJsZSl7XHJcbiAgICAgICAgdGhpcy5fc3RvcHBhYmxlID0gc3RvcHBhYmxlXHJcbiAgICB9LFxyXG5cclxuICAgIG9uOiBmdW5jdGlvbihldmVudCwgZm4pe1xyXG4gICAgICAgIHZhciBsaXN0ZW5lcnMgPSB0aGlzLl9saXN0ZW5lcnMgfHwgKHRoaXMuX2xpc3RlbmVycyA9IHt9KSxcclxuICAgICAgICAgICAgZXZlbnRzID0gbGlzdGVuZXJzW2V2ZW50XSB8fCAobGlzdGVuZXJzW2V2ZW50XSA9IFtdKVxyXG5cclxuICAgICAgICBpZiAoaW5kZXhPZihldmVudHMsIGZuKSA9PT0gLTEpIGV2ZW50cy5wdXNoKGZuKVxyXG5cclxuICAgICAgICByZXR1cm4gdGhpc1xyXG4gICAgfSxcclxuXHJcbiAgICBvZmY6IGZ1bmN0aW9uKGV2ZW50LCBmbil7XHJcbiAgICAgICAgdmFyIGxpc3RlbmVycyA9IHRoaXMuX2xpc3RlbmVycywgZXZlbnRzXHJcbiAgICAgICAgaWYgKGxpc3RlbmVycyAmJiAoZXZlbnRzID0gbGlzdGVuZXJzW2V2ZW50XSkpe1xyXG5cclxuICAgICAgICAgICAgdmFyIGlvID0gaW5kZXhPZihldmVudHMsIGZuKVxyXG4gICAgICAgICAgICBpZiAoaW8gPiAtMSkgZXZlbnRzLnNwbGljZShpbywgMSlcclxuICAgICAgICAgICAgaWYgKCFldmVudHMubGVuZ3RoKSBkZWxldGUgbGlzdGVuZXJzW2V2ZW50XTtcclxuICAgICAgICAgICAgZm9yICh2YXIgbCBpbiBsaXN0ZW5lcnMpIHJldHVybiB0aGlzXHJcbiAgICAgICAgICAgIGRlbGV0ZSB0aGlzLl9saXN0ZW5lcnNcclxuICAgICAgICB9XHJcbiAgICAgICAgcmV0dXJuIHRoaXNcclxuICAgIH0sXHJcblxyXG4gICAgZW1pdDogZnVuY3Rpb24oZXZlbnQpe1xyXG4gICAgICAgIHZhciBzZWxmID0gdGhpcyxcclxuICAgICAgICAgICAgYXJncyA9IHNsaWNlLmNhbGwoYXJndW1lbnRzLCAxKVxyXG5cclxuICAgICAgICB2YXIgZW1pdCA9IGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgICAgIHZhciBsaXN0ZW5lcnMgPSBzZWxmLl9saXN0ZW5lcnMsIGV2ZW50c1xyXG4gICAgICAgICAgICBpZiAobGlzdGVuZXJzICYmIChldmVudHMgPSBsaXN0ZW5lcnNbZXZlbnRdKSl7XHJcbiAgICAgICAgICAgICAgICBmb3JFYWNoKGV2ZW50cy5zbGljZSgwKSwgZnVuY3Rpb24oZXZlbnQpe1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciByZXN1bHQgPSBldmVudC5hcHBseShzZWxmLCBhcmdzKVxyXG4gICAgICAgICAgICAgICAgICAgIGlmIChzZWxmLl9zdG9wcGFibGUpIHJldHVybiByZXN1bHRcclxuICAgICAgICAgICAgICAgIH0pXHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmIChhcmdzW2FyZ3MubGVuZ3RoIC0gMV0gPT09IEVtaXR0ZXIuRU1JVF9TWU5DKXtcclxuICAgICAgICAgICAgYXJncy5wb3AoKVxyXG4gICAgICAgICAgICBlbWl0KClcclxuICAgICAgICB9IGVsc2Uge1xyXG4gICAgICAgICAgICBkZWZlcihlbWl0KVxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgcmV0dXJuIHRoaXNcclxuICAgIH1cclxuXHJcbn0pXHJcblxyXG5FbWl0dGVyLkVNSVRfU1lOQyA9IHt9XHJcblxyXG5tb2R1bGUuZXhwb3J0cyA9IEVtaXR0ZXJcclxuIiwiLypcclxucHJpbWVcclxuIC0gcHJvdG90eXBhbCBpbmhlcml0YW5jZVxyXG4qL1widXNlIHN0cmljdFwiXHJcblxyXG52YXIgaGFzT3duID0gcmVxdWlyZShcIm1vdXQvb2JqZWN0L2hhc093blwiKSxcclxuICAgIG1peEluICA9IHJlcXVpcmUoXCJtb3V0L29iamVjdC9taXhJblwiKSxcclxuICAgIGNyZWF0ZSA9IHJlcXVpcmUoXCJtb3V0L2xhbmcvY3JlYXRlT2JqZWN0XCIpLFxyXG4gICAga2luZE9mID0gcmVxdWlyZShcIm1vdXQvbGFuZy9raW5kT2ZcIilcclxuXHJcbnZhciBoYXNEZXNjcmlwdG9ycyA9IHRydWVcclxuXHJcbnRyeSB7XHJcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoe30sIFwiflwiLCB7fSlcclxuICAgIE9iamVjdC5nZXRPd25Qcm9wZXJ0eURlc2NyaXB0b3Ioe30sIFwiflwiKVxyXG59IGNhdGNoIChlKXtcclxuICAgIGhhc0Rlc2NyaXB0b3JzID0gZmFsc2VcclxufVxyXG5cclxuLy8gd2Ugb25seSBuZWVkIHRvIGJlIGFibGUgdG8gaW1wbGVtZW50IFwidG9TdHJpbmdcIiBhbmQgXCJ2YWx1ZU9mXCIgaW4gSUUgPCA5XHJcbnZhciBoYXNFbnVtQnVnID0gISh7dmFsdWVPZjogMH0pLnByb3BlcnR5SXNFbnVtZXJhYmxlKFwidmFsdWVPZlwiKSxcclxuICAgIGJ1Z2d5ICAgICAgPSBbXCJ0b1N0cmluZ1wiLCBcInZhbHVlT2ZcIl1cclxuXHJcbnZhciB2ZXJicyA9IC9eY29uc3RydWN0b3J8aW5oZXJpdHN8bWl4aW4kL1xyXG5cclxudmFyIGltcGxlbWVudCA9IGZ1bmN0aW9uKHByb3RvKXtcclxuICAgIHZhciBwcm90b3R5cGUgPSB0aGlzLnByb3RvdHlwZVxyXG5cclxuICAgIGZvciAodmFyIGtleSBpbiBwcm90byl7XHJcbiAgICAgICAgaWYgKGtleS5tYXRjaCh2ZXJicykpIGNvbnRpbnVlXHJcbiAgICAgICAgaWYgKGhhc0Rlc2NyaXB0b3JzKXtcclxuICAgICAgICAgICAgdmFyIGRlc2NyaXB0b3IgPSBPYmplY3QuZ2V0T3duUHJvcGVydHlEZXNjcmlwdG9yKHByb3RvLCBrZXkpXHJcbiAgICAgICAgICAgIGlmIChkZXNjcmlwdG9yKXtcclxuICAgICAgICAgICAgICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShwcm90b3R5cGUsIGtleSwgZGVzY3JpcHRvcilcclxuICAgICAgICAgICAgICAgIGNvbnRpbnVlXHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9XHJcbiAgICAgICAgcHJvdG90eXBlW2tleV0gPSBwcm90b1trZXldXHJcbiAgICB9XHJcblxyXG4gICAgaWYgKGhhc0VudW1CdWcpIGZvciAodmFyIGkgPSAwOyAoa2V5ID0gYnVnZ3lbaV0pOyBpKyspe1xyXG4gICAgICAgIHZhciB2YWx1ZSA9IHByb3RvW2tleV1cclxuICAgICAgICBpZiAodmFsdWUgIT09IE9iamVjdC5wcm90b3R5cGVba2V5XSkgcHJvdG90eXBlW2tleV0gPSB2YWx1ZVxyXG4gICAgfVxyXG5cclxuICAgIHJldHVybiB0aGlzXHJcbn1cclxuXHJcbnZhciBwcmltZSA9IGZ1bmN0aW9uKHByb3RvKXtcclxuXHJcbiAgICBpZiAoa2luZE9mKHByb3RvKSA9PT0gXCJGdW5jdGlvblwiKSBwcm90byA9IHtjb25zdHJ1Y3RvcjogcHJvdG99XHJcblxyXG4gICAgdmFyIHN1cGVycHJpbWUgPSBwcm90by5pbmhlcml0c1xyXG5cclxuICAgIC8vIGlmIG91ciBuaWNlIHByb3RvIG9iamVjdCBoYXMgbm8gb3duIGNvbnN0cnVjdG9yIHByb3BlcnR5XHJcbiAgICAvLyB0aGVuIHdlIHByb2NlZWQgdXNpbmcgYSBnaG9zdGluZyBjb25zdHJ1Y3RvciB0aGF0IGFsbCBpdCBkb2VzIGlzXHJcbiAgICAvLyBjYWxsIHRoZSBwYXJlbnQncyBjb25zdHJ1Y3RvciBpZiBpdCBoYXMgYSBzdXBlcnByaW1lLCBlbHNlIGFuIGVtcHR5IGNvbnN0cnVjdG9yXHJcbiAgICAvLyBwcm90by5jb25zdHJ1Y3RvciBiZWNvbWVzIHRoZSBlZmZlY3RpdmUgY29uc3RydWN0b3JcclxuICAgIHZhciBjb25zdHJ1Y3RvciA9IChoYXNPd24ocHJvdG8sIFwiY29uc3RydWN0b3JcIikpID8gcHJvdG8uY29uc3RydWN0b3IgOiAoc3VwZXJwcmltZSkgPyBmdW5jdGlvbigpe1xyXG4gICAgICAgIHJldHVybiBzdXBlcnByaW1lLmFwcGx5KHRoaXMsIGFyZ3VtZW50cylcclxuICAgIH0gOiBmdW5jdGlvbigpe31cclxuXHJcbiAgICBpZiAoc3VwZXJwcmltZSl7XHJcblxyXG4gICAgICAgIG1peEluKGNvbnN0cnVjdG9yLCBzdXBlcnByaW1lKVxyXG5cclxuICAgICAgICB2YXIgc3VwZXJwcm90byA9IHN1cGVycHJpbWUucHJvdG90eXBlXHJcbiAgICAgICAgLy8gaW5oZXJpdCBmcm9tIHN1cGVycHJpbWVcclxuICAgICAgICB2YXIgY3Byb3RvID0gY29uc3RydWN0b3IucHJvdG90eXBlID0gY3JlYXRlKHN1cGVycHJvdG8pXHJcblxyXG4gICAgICAgIC8vIHNldHRpbmcgY29uc3RydWN0b3IucGFyZW50IHRvIHN1cGVycHJpbWUucHJvdG90eXBlXHJcbiAgICAgICAgLy8gYmVjYXVzZSBpdCdzIHRoZSBzaG9ydGVzdCBwb3NzaWJsZSBhYnNvbHV0ZSByZWZlcmVuY2VcclxuICAgICAgICBjb25zdHJ1Y3Rvci5wYXJlbnQgPSBzdXBlcnByb3RvXHJcbiAgICAgICAgY3Byb3RvLmNvbnN0cnVjdG9yID0gY29uc3RydWN0b3JcclxuICAgIH1cclxuXHJcbiAgICBpZiAoIWNvbnN0cnVjdG9yLmltcGxlbWVudCkgY29uc3RydWN0b3IuaW1wbGVtZW50ID0gaW1wbGVtZW50XHJcblxyXG4gICAgdmFyIG1peGlucyA9IHByb3RvLm1peGluXHJcbiAgICBpZiAobWl4aW5zKXtcclxuICAgICAgICBpZiAoa2luZE9mKG1peGlucykgIT09IFwiQXJyYXlcIikgbWl4aW5zID0gW21peGluc11cclxuICAgICAgICBmb3IgKHZhciBpID0gMDsgaSA8IG1peGlucy5sZW5ndGg7IGkrKykgY29uc3RydWN0b3IuaW1wbGVtZW50KGNyZWF0ZShtaXhpbnNbaV0ucHJvdG90eXBlKSlcclxuICAgIH1cclxuXHJcbiAgICAvLyBpbXBsZW1lbnQgcHJvdG8gYW5kIHJldHVybiBjb25zdHJ1Y3RvclxyXG4gICAgcmV0dXJuIGNvbnN0cnVjdG9yLmltcGxlbWVudChwcm90bylcclxuXHJcbn1cclxuXHJcbm1vZHVsZS5leHBvcnRzID0gcHJpbWVcclxuIiwiLypcclxuTWFwXHJcbiovXCJ1c2Ugc3RyaWN0XCJcclxuXHJcbnZhciBpbmRleE9mID0gcmVxdWlyZShcIm1vdXQvYXJyYXkvaW5kZXhPZlwiKVxyXG5cclxudmFyIHByaW1lID0gcmVxdWlyZShcIi4vaW5kZXhcIilcclxuXHJcbnZhciBNYXAgPSBwcmltZSh7XHJcblxyXG4gICAgY29uc3RydWN0b3I6IGZ1bmN0aW9uIE1hcCgpe1xyXG4gICAgICAgIHRoaXMubGVuZ3RoID0gMFxyXG4gICAgICAgIHRoaXMuX3ZhbHVlcyA9IFtdXHJcbiAgICAgICAgdGhpcy5fa2V5cyA9IFtdXHJcbiAgICB9LFxyXG5cclxuICAgIHNldDogZnVuY3Rpb24oa2V5LCB2YWx1ZSl7XHJcbiAgICAgICAgdmFyIGluZGV4ID0gaW5kZXhPZih0aGlzLl9rZXlzLCBrZXkpXHJcblxyXG4gICAgICAgIGlmIChpbmRleCA9PT0gLTEpe1xyXG4gICAgICAgICAgICB0aGlzLl9rZXlzLnB1c2goa2V5KVxyXG4gICAgICAgICAgICB0aGlzLl92YWx1ZXMucHVzaCh2YWx1ZSlcclxuICAgICAgICAgICAgdGhpcy5sZW5ndGgrK1xyXG4gICAgICAgIH0gZWxzZSB7XHJcbiAgICAgICAgICAgIHRoaXMuX3ZhbHVlc1tpbmRleF0gPSB2YWx1ZVxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgcmV0dXJuIHRoaXNcclxuICAgIH0sXHJcblxyXG4gICAgZ2V0OiBmdW5jdGlvbihrZXkpe1xyXG4gICAgICAgIHZhciBpbmRleCA9IGluZGV4T2YodGhpcy5fa2V5cywga2V5KVxyXG4gICAgICAgIHJldHVybiAoaW5kZXggPT09IC0xKSA/IG51bGwgOiB0aGlzLl92YWx1ZXNbaW5kZXhdXHJcbiAgICB9LFxyXG5cclxuICAgIGNvdW50OiBmdW5jdGlvbigpe1xyXG4gICAgICAgIHJldHVybiB0aGlzLmxlbmd0aFxyXG4gICAgfSxcclxuXHJcbiAgICBmb3JFYWNoOiBmdW5jdGlvbihtZXRob2QsIGNvbnRleHQpe1xyXG4gICAgICAgIGZvciAodmFyIGkgPSAwLCBsID0gdGhpcy5sZW5ndGg7IGkgPCBsOyBpKyspe1xyXG4gICAgICAgICAgICBpZiAobWV0aG9kLmNhbGwoY29udGV4dCwgdGhpcy5fdmFsdWVzW2ldLCB0aGlzLl9rZXlzW2ldLCB0aGlzKSA9PT0gZmFsc2UpIGJyZWFrXHJcbiAgICAgICAgfVxyXG4gICAgICAgIHJldHVybiB0aGlzXHJcbiAgICB9LFxyXG5cclxuICAgIG1hcDogZnVuY3Rpb24obWV0aG9kLCBjb250ZXh0KXtcclxuICAgICAgICB2YXIgcmVzdWx0cyA9IG5ldyBNYXBcclxuICAgICAgICB0aGlzLmZvckVhY2goZnVuY3Rpb24odmFsdWUsIGtleSl7XHJcbiAgICAgICAgICAgIHJlc3VsdHMuc2V0KGtleSwgbWV0aG9kLmNhbGwoY29udGV4dCwgdmFsdWUsIGtleSwgdGhpcykpXHJcbiAgICAgICAgfSwgdGhpcylcclxuICAgICAgICByZXR1cm4gcmVzdWx0c1xyXG4gICAgfSxcclxuXHJcbiAgICBmaWx0ZXI6IGZ1bmN0aW9uKG1ldGhvZCwgY29udGV4dCl7XHJcbiAgICAgICAgdmFyIHJlc3VsdHMgPSBuZXcgTWFwXHJcbiAgICAgICAgdGhpcy5mb3JFYWNoKGZ1bmN0aW9uKHZhbHVlLCBrZXkpe1xyXG4gICAgICAgICAgICBpZiAobWV0aG9kLmNhbGwoY29udGV4dCwgdmFsdWUsIGtleSwgdGhpcykpIHJlc3VsdHMuc2V0KGtleSwgdmFsdWUpXHJcbiAgICAgICAgfSwgdGhpcylcclxuICAgICAgICByZXR1cm4gcmVzdWx0c1xyXG4gICAgfSxcclxuXHJcbiAgICBldmVyeTogZnVuY3Rpb24obWV0aG9kLCBjb250ZXh0KXtcclxuICAgICAgICB2YXIgZXZlcnkgPSB0cnVlXHJcbiAgICAgICAgdGhpcy5mb3JFYWNoKGZ1bmN0aW9uKHZhbHVlLCBrZXkpe1xyXG4gICAgICAgICAgICBpZiAoIW1ldGhvZC5jYWxsKGNvbnRleHQsIHZhbHVlLCBrZXksIHRoaXMpKSByZXR1cm4gKGV2ZXJ5ID0gZmFsc2UpXHJcbiAgICAgICAgfSwgdGhpcylcclxuICAgICAgICByZXR1cm4gZXZlcnlcclxuICAgIH0sXHJcblxyXG4gICAgc29tZTogZnVuY3Rpb24obWV0aG9kLCBjb250ZXh0KXtcclxuICAgICAgICB2YXIgc29tZSA9IGZhbHNlXHJcbiAgICAgICAgdGhpcy5mb3JFYWNoKGZ1bmN0aW9uKHZhbHVlLCBrZXkpe1xyXG4gICAgICAgICAgICBpZiAobWV0aG9kLmNhbGwoY29udGV4dCwgdmFsdWUsIGtleSwgdGhpcykpIHJldHVybiAhKHNvbWUgPSB0cnVlKVxyXG4gICAgICAgIH0sIHRoaXMpXHJcbiAgICAgICAgcmV0dXJuIHNvbWVcclxuICAgIH0sXHJcblxyXG4gICAgaW5kZXhPZjogZnVuY3Rpb24odmFsdWUpe1xyXG4gICAgICAgIHZhciBpbmRleCA9IGluZGV4T2YodGhpcy5fdmFsdWVzLCB2YWx1ZSlcclxuICAgICAgICByZXR1cm4gKGluZGV4ID4gLTEpID8gdGhpcy5fa2V5c1tpbmRleF0gOiBudWxsXHJcbiAgICB9LFxyXG5cclxuICAgIHJlbW92ZTogZnVuY3Rpb24odmFsdWUpe1xyXG4gICAgICAgIHZhciBpbmRleCA9IGluZGV4T2YodGhpcy5fdmFsdWVzLCB2YWx1ZSlcclxuXHJcbiAgICAgICAgaWYgKGluZGV4ICE9PSAtMSl7XHJcbiAgICAgICAgICAgIHRoaXMuX3ZhbHVlcy5zcGxpY2UoaW5kZXgsIDEpXHJcbiAgICAgICAgICAgIHRoaXMubGVuZ3RoLS1cclxuICAgICAgICAgICAgcmV0dXJuIHRoaXMuX2tleXMuc3BsaWNlKGluZGV4LCAxKVswXVxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgcmV0dXJuIG51bGxcclxuICAgIH0sXHJcblxyXG4gICAgdW5zZXQ6IGZ1bmN0aW9uKGtleSl7XHJcbiAgICAgICAgdmFyIGluZGV4ID0gaW5kZXhPZih0aGlzLl9rZXlzLCBrZXkpXHJcblxyXG4gICAgICAgIGlmIChpbmRleCAhPT0gLTEpe1xyXG4gICAgICAgICAgICB0aGlzLl9rZXlzLnNwbGljZShpbmRleCwgMSlcclxuICAgICAgICAgICAgdGhpcy5sZW5ndGgtLVxyXG4gICAgICAgICAgICByZXR1cm4gdGhpcy5fdmFsdWVzLnNwbGljZShpbmRleCwgMSlbMF1cclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHJldHVybiBudWxsXHJcbiAgICB9LFxyXG5cclxuICAgIGtleXM6IGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgcmV0dXJuIHRoaXMuX2tleXMuc2xpY2UoKVxyXG4gICAgfSxcclxuXHJcbiAgICB2YWx1ZXM6IGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgcmV0dXJuIHRoaXMuX3ZhbHVlcy5zbGljZSgpXHJcbiAgICB9XHJcblxyXG59KVxyXG5cclxudmFyIG1hcCA9IGZ1bmN0aW9uKCl7XHJcbiAgICByZXR1cm4gbmV3IE1hcFxyXG59XHJcblxyXG5tYXAucHJvdG90eXBlID0gTWFwLnByb3RvdHlwZVxyXG5cclxubW9kdWxlLmV4cG9ydHMgPSBtYXBcclxuIiwiXG5cbiAgICAvKipcbiAgICAgKiBHZXQgY3VycmVudCB0aW1lIGluIG1pbGlzZWNvbmRzXG4gICAgICovXG4gICAgZnVuY3Rpb24gbm93KCl7XG4gICAgICAgIC8vIHllcywgd2UgZGVmZXIgdGhlIHdvcmsgdG8gYW5vdGhlciBmdW5jdGlvbiB0byBhbGxvdyBtb2NraW5nIGl0XG4gICAgICAgIC8vIGR1cmluZyB0aGUgdGVzdHNcbiAgICAgICAgcmV0dXJuIG5vdy5nZXQoKTtcbiAgICB9XG5cbiAgICBub3cuZ2V0ID0gKHR5cGVvZiBEYXRlLm5vdyA9PT0gJ2Z1bmN0aW9uJyk/IERhdGUubm93IDogZnVuY3Rpb24oKXtcbiAgICAgICAgcmV0dXJuICsobmV3IERhdGUoKSk7XG4gICAgfTtcblxuICAgIG1vZHVsZS5leHBvcnRzID0gbm93O1xuXG5cbiIsIi8qXHJcblNsaWNrIEZpbmRlclxyXG4qL1widXNlIHN0cmljdFwiXHJcblxyXG4vLyBOb3RhYmxlIGNoYW5nZXMgZnJvbSBTbGljay5GaW5kZXIgMS4wLnhcclxuXHJcbi8vIGZhc3RlciBib3R0b20gLT4gdXAgZXhwcmVzc2lvbiBtYXRjaGluZ1xyXG4vLyBwcmVmZXJzIG1lbnRhbCBzYW5pdHkgb3ZlciAqb2JzZXNzaXZlIGNvbXB1bHNpdmUqIG1pbGxpc2Vjb25kcyBzYXZpbmdzXHJcbi8vIHVzZXMgcHJvdG90eXBlcyBpbnN0ZWFkIG9mIG9iamVjdHNcclxuLy8gdHJpZXMgdG8gdXNlIG1hdGNoZXNTZWxlY3RvciBzbWFydGx5LCB3aGVuZXZlciBhdmFpbGFibGVcclxuLy8gY2FuIHBvcHVsYXRlIG9iamVjdHMgYXMgd2VsbCBhcyBhcnJheXNcclxuLy8gbG90cyBvZiBzdHVmZiBpcyBicm9rZW4gb3Igbm90IGltcGxlbWVudGVkXHJcblxyXG52YXIgcGFyc2UgPSByZXF1aXJlKFwiLi9wYXJzZXJcIilcclxuXHJcbi8vIHV0aWxpdGllc1xyXG5cclxudmFyIGluZGV4ID0gMCxcclxuICAgIGNvdW50ZXIgPSBkb2N1bWVudC5fX2NvdW50ZXIgPSAocGFyc2VJbnQoZG9jdW1lbnQuX19jb3VudGVyIHx8IC0xLCAzNikgKyAxKS50b1N0cmluZygzNiksXHJcbiAgICBrZXkgPSBcInVpZDpcIiArIGNvdW50ZXJcclxuXHJcbnZhciB1bmlxdWVJRCA9IGZ1bmN0aW9uKG4sIHhtbCl7XHJcbiAgICBpZiAobiA9PT0gd2luZG93KSByZXR1cm4gXCJ3aW5kb3dcIlxyXG4gICAgaWYgKG4gPT09IGRvY3VtZW50KSByZXR1cm4gXCJkb2N1bWVudFwiXHJcbiAgICBpZiAobiA9PT0gZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50KSByZXR1cm4gXCJodG1sXCJcclxuXHJcbiAgICBpZiAoeG1sKSB7XHJcbiAgICAgICAgdmFyIHVpZCA9IG4uZ2V0QXR0cmlidXRlKGtleSlcclxuICAgICAgICBpZiAoIXVpZCkge1xyXG4gICAgICAgICAgICB1aWQgPSAoaW5kZXgrKykudG9TdHJpbmcoMzYpXHJcbiAgICAgICAgICAgIG4uc2V0QXR0cmlidXRlKGtleSwgdWlkKVxyXG4gICAgICAgIH1cclxuICAgICAgICByZXR1cm4gdWlkXHJcbiAgICB9IGVsc2Uge1xyXG4gICAgICAgIHJldHVybiBuW2tleV0gfHwgKG5ba2V5XSA9IChpbmRleCsrKS50b1N0cmluZygzNikpXHJcbiAgICB9XHJcbn1cclxuXHJcbnZhciB1bmlxdWVJRFhNTCA9IGZ1bmN0aW9uKG4pIHtcclxuICAgIHJldHVybiB1bmlxdWVJRChuLCB0cnVlKVxyXG59XHJcblxyXG52YXIgaXNBcnJheSA9IEFycmF5LmlzQXJyYXkgfHwgZnVuY3Rpb24ob2JqZWN0KXtcclxuICAgIHJldHVybiBPYmplY3QucHJvdG90eXBlLnRvU3RyaW5nLmNhbGwob2JqZWN0KSA9PT0gXCJbb2JqZWN0IEFycmF5XVwiXHJcbn1cclxuXHJcbi8vIHRlc3RzXHJcblxyXG52YXIgdW5pcXVlSW5kZXggPSAwO1xyXG5cclxudmFyIEhBUyA9IHtcclxuXHJcbiAgICBHRVRfRUxFTUVOVF9CWV9JRDogZnVuY3Rpb24odGVzdCwgaWQpe1xyXG4gICAgICAgIGlkID0gXCJzbGlja19cIiArICh1bmlxdWVJbmRleCsrKTtcclxuICAgICAgICAvLyBjaGVja3MgaWYgdGhlIGRvY3VtZW50IGhhcyBnZXRFbGVtZW50QnlJZCwgYW5kIGl0IHdvcmtzXHJcbiAgICAgICAgdGVzdC5pbm5lckhUTUwgPSAnPGEgaWQ9XCInICsgaWQgKyAnXCI+PC9hPidcclxuICAgICAgICByZXR1cm4gISF0aGlzLmdldEVsZW1lbnRCeUlkKGlkKVxyXG4gICAgfSxcclxuXHJcbiAgICBRVUVSWV9TRUxFQ1RPUjogZnVuY3Rpb24odGVzdCl7XHJcbiAgICAgICAgLy8gdGhpcyBzdXBwb3NlZGx5IGZpeGVzIGEgd2Via2l0IGJ1ZyB3aXRoIG1hdGNoZXNTZWxlY3RvciAvIHF1ZXJ5U2VsZWN0b3IgJiBudGgtY2hpbGRcclxuICAgICAgICB0ZXN0LmlubmVySFRNTCA9ICdfPHN0eWxlPjpudGgtY2hpbGQoMil7fTwvc3R5bGU+J1xyXG5cclxuICAgICAgICAvLyBjaGVja3MgaWYgdGhlIGRvY3VtZW50IGhhcyBxdWVyeVNlbGVjdG9yQWxsLCBhbmQgaXQgd29ya3NcclxuICAgICAgICB0ZXN0LmlubmVySFRNTCA9ICc8YSBjbGFzcz1cIk1pWFwiPjwvYT4nXHJcblxyXG4gICAgICAgIHJldHVybiB0ZXN0LnF1ZXJ5U2VsZWN0b3JBbGwoJy5NaVgnKS5sZW5ndGggPT09IDFcclxuICAgIH0sXHJcblxyXG4gICAgRVhQQU5ET1M6IGZ1bmN0aW9uKHRlc3QsIGlkKXtcclxuICAgICAgICBpZCA9IFwic2xpY2tfXCIgKyAodW5pcXVlSW5kZXgrKyk7XHJcbiAgICAgICAgLy8gY2hlY2tzIGlmIHRoZSBkb2N1bWVudCBoYXMgZWxlbWVudHMgdGhhdCBzdXBwb3J0IGV4cGFuZG9zXHJcbiAgICAgICAgdGVzdC5fY3VzdG9tX3Byb3BlcnR5XyA9IGlkXHJcbiAgICAgICAgcmV0dXJuIHRlc3QuX2N1c3RvbV9wcm9wZXJ0eV8gPT09IGlkXHJcbiAgICB9LFxyXG5cclxuICAgIC8vIFRPRE86IHVzZSB0aGlzID9cclxuXHJcbiAgICAvLyBDSEVDS0VEX1FVRVJZX1NFTEVDVE9SOiBmdW5jdGlvbih0ZXN0KXtcclxuICAgIC8vXHJcbiAgICAvLyAgICAgLy8gY2hlY2tzIGlmIHRoZSBkb2N1bWVudCBzdXBwb3J0cyB0aGUgY2hlY2tlZCBxdWVyeSBzZWxlY3RvclxyXG4gICAgLy8gICAgIHRlc3QuaW5uZXJIVE1MID0gJzxzZWxlY3Q+PG9wdGlvbiBzZWxlY3RlZD1cInNlbGVjdGVkXCI+YTwvb3B0aW9uPjwvc2VsZWN0PidcclxuICAgIC8vICAgICByZXR1cm4gdGVzdC5xdWVyeVNlbGVjdG9yQWxsKCc6Y2hlY2tlZCcpLmxlbmd0aCA9PT0gMVxyXG4gICAgLy8gfSxcclxuXHJcbiAgICAvLyBUT0RPOiB1c2UgdGhpcyA/XHJcblxyXG4gICAgLy8gRU1QVFlfQVRUUklCVVRFX1FVRVJZX1NFTEVDVE9SOiBmdW5jdGlvbih0ZXN0KXtcclxuICAgIC8vXHJcbiAgICAvLyAgICAgLy8gY2hlY2tzIGlmIHRoZSBkb2N1bWVudCBzdXBwb3J0cyB0aGUgZW1wdHkgYXR0cmlidXRlIHF1ZXJ5IHNlbGVjdG9yXHJcbiAgICAvLyAgICAgdGVzdC5pbm5lckhUTUwgPSAnPGEgY2xhc3M9XCJcIj48L2E+J1xyXG4gICAgLy8gICAgIHJldHVybiB0ZXN0LnF1ZXJ5U2VsZWN0b3JBbGwoJ1tjbGFzcyo9XCJcIl0nKS5sZW5ndGggPT09IDFcclxuICAgIC8vIH0sXHJcblxyXG4gICAgTUFUQ0hFU19TRUxFQ1RPUjogZnVuY3Rpb24odGVzdCl7XHJcblxyXG4gICAgICAgIHRlc3QuY2xhc3NOYW1lID0gXCJNaVhcIlxyXG5cclxuICAgICAgICAvLyBjaGVja3MgaWYgdGhlIGRvY3VtZW50IGhhcyBtYXRjaGVzU2VsZWN0b3IsIGFuZCB3ZSBjYW4gdXNlIGl0LlxyXG5cclxuICAgICAgICB2YXIgbWF0Y2hlcyA9IHRlc3QubWF0Y2hlc1NlbGVjdG9yIHx8IHRlc3QubW96TWF0Y2hlc1NlbGVjdG9yIHx8IHRlc3Qud2Via2l0TWF0Y2hlc1NlbGVjdG9yXHJcblxyXG4gICAgICAgIC8vIGlmIG1hdGNoZXNTZWxlY3RvciB0cm93cyBlcnJvcnMgb24gaW5jb3JyZWN0IHN5bnRheCB3ZSBjYW4gdXNlIGl0XHJcbiAgICAgICAgaWYgKG1hdGNoZXMpIHRyeSB7XHJcbiAgICAgICAgICAgIG1hdGNoZXMuY2FsbCh0ZXN0LCAnOnNsaWNrJylcclxuICAgICAgICB9IGNhdGNoKGUpe1xyXG4gICAgICAgICAgICAvLyBqdXN0IGFzIGEgc2FmZXR5IHByZWNhdXRpb24sIGFsc28gdGVzdCBpZiBpdCB3b3JrcyBvbiBtaXhlZGNhc2UgKGxpa2UgcXVlcnlTZWxlY3RvckFsbClcclxuICAgICAgICAgICAgcmV0dXJuIG1hdGNoZXMuY2FsbCh0ZXN0LCBcIi5NaVhcIikgPyBtYXRjaGVzIDogZmFsc2VcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHJldHVybiBmYWxzZVxyXG4gICAgfSxcclxuXHJcbiAgICBHRVRfRUxFTUVOVFNfQllfQ0xBU1NfTkFNRTogZnVuY3Rpb24odGVzdCl7XHJcbiAgICAgICAgdGVzdC5pbm5lckhUTUwgPSAnPGEgY2xhc3M9XCJmXCI+PC9hPjxhIGNsYXNzPVwiYlwiPjwvYT4nXHJcbiAgICAgICAgaWYgKHRlc3QuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgnYicpLmxlbmd0aCAhPT0gMSkgcmV0dXJuIGZhbHNlXHJcblxyXG4gICAgICAgIHRlc3QuZmlyc3RDaGlsZC5jbGFzc05hbWUgPSAnYidcclxuICAgICAgICBpZiAodGVzdC5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKCdiJykubGVuZ3RoICE9PSAyKSByZXR1cm4gZmFsc2VcclxuXHJcbiAgICAgICAgLy8gT3BlcmEgOS42IGdldEVsZW1lbnRzQnlDbGFzc05hbWUgZG9lc250IGRldGVjdHMgdGhlIGNsYXNzIGlmIGl0cyBub3QgdGhlIGZpcnN0IG9uZVxyXG4gICAgICAgIHRlc3QuaW5uZXJIVE1MID0gJzxhIGNsYXNzPVwiYVwiPjwvYT48YSBjbGFzcz1cImYgYiBhXCI+PC9hPidcclxuICAgICAgICBpZiAodGVzdC5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKCdhJykubGVuZ3RoICE9PSAyKSByZXR1cm4gZmFsc2VcclxuXHJcbiAgICAgICAgLy8gdGVzdHMgcGFzc2VkXHJcbiAgICAgICAgcmV0dXJuIHRydWVcclxuICAgIH0sXHJcblxyXG4gICAgLy8gbm8gbmVlZCB0byBrbm93XHJcblxyXG4gICAgLy8gR0VUX0VMRU1FTlRfQllfSURfTk9UX05BTUU6IGZ1bmN0aW9uKHRlc3QsIGlkKXtcclxuICAgIC8vICAgICB0ZXN0LmlubmVySFRNTCA9ICc8YSBuYW1lPVwiJysgaWQgKydcIj48L2E+PGIgaWQ9XCInKyBpZCArJ1wiPjwvYj4nXHJcbiAgICAvLyAgICAgcmV0dXJuIHRoaXMuZ2V0RWxlbWVudEJ5SWQoaWQpICE9PSB0ZXN0LmZpcnN0Q2hpbGRcclxuICAgIC8vIH0sXHJcblxyXG4gICAgLy8gdGhpcyBpcyBhbHdheXMgY2hlY2tlZCBmb3IgYW5kIGZpeGVkXHJcblxyXG4gICAgLy8gU1RBUl9HRVRfRUxFTUVOVFNfQllfVEFHX05BTUU6IGZ1bmN0aW9uKHRlc3Qpe1xyXG4gICAgLy9cclxuICAgIC8vICAgICAvLyBJRSByZXR1cm5zIGNvbW1lbnQgbm9kZXMgZm9yIGdldEVsZW1lbnRzQnlUYWdOYW1lKCcqJykgZm9yIHNvbWUgZG9jdW1lbnRzXHJcbiAgICAvLyAgICAgdGVzdC5hcHBlbmRDaGlsZCh0aGlzLmNyZWF0ZUNvbW1lbnQoJycpKVxyXG4gICAgLy8gICAgIGlmICh0ZXN0LmdldEVsZW1lbnRzQnlUYWdOYW1lKCcqJykubGVuZ3RoID4gMCkgcmV0dXJuIGZhbHNlXHJcbiAgICAvL1xyXG4gICAgLy8gICAgIC8vIElFIHJldHVybnMgY2xvc2VkIG5vZGVzIChFRzpcIjwvZm9vPlwiKSBmb3IgZ2V0RWxlbWVudHNCeVRhZ05hbWUoJyonKSBmb3Igc29tZSBkb2N1bWVudHNcclxuICAgIC8vICAgICB0ZXN0LmlubmVySFRNTCA9ICdmb288L2Zvbz4nXHJcbiAgICAvLyAgICAgaWYgKHRlc3QuZ2V0RWxlbWVudHNCeVRhZ05hbWUoJyonKS5sZW5ndGgpIHJldHVybiBmYWxzZVxyXG4gICAgLy9cclxuICAgIC8vICAgICAvLyB0ZXN0cyBwYXNzZWRcclxuICAgIC8vICAgICByZXR1cm4gdHJ1ZVxyXG4gICAgLy8gfSxcclxuXHJcbiAgICAvLyB0aGlzIGlzIGFsd2F5cyBjaGVja2VkIGZvciBhbmQgZml4ZWRcclxuXHJcbiAgICAvLyBTVEFSX1FVRVJZX1NFTEVDVE9SOiBmdW5jdGlvbih0ZXN0KXtcclxuICAgIC8vXHJcbiAgICAvLyAgICAgLy8gcmV0dXJucyBjbG9zZWQgbm9kZXMgKEVHOlwiPC9mb28+XCIpIGZvciBxdWVyeVNlbGVjdG9yKCcqJykgZm9yIHNvbWUgZG9jdW1lbnRzXHJcbiAgICAvLyAgICAgdGVzdC5pbm5lckhUTUwgPSAnZm9vPC9mb28+J1xyXG4gICAgLy8gICAgIHJldHVybiAhISh0ZXN0LnF1ZXJ5U2VsZWN0b3JBbGwoJyonKS5sZW5ndGgpXHJcbiAgICAvLyB9LFxyXG5cclxuICAgIEdFVF9BVFRSSUJVVEU6IGZ1bmN0aW9uKHRlc3Qpe1xyXG4gICAgICAgIC8vIHRlc3RzIGZvciB3b3JraW5nIGdldEF0dHJpYnV0ZSBpbXBsZW1lbnRhdGlvblxyXG4gICAgICAgIHZhciBzaG91dCA9IFwiZnVzIHJvIGRhaFwiXHJcbiAgICAgICAgdGVzdC5pbm5lckhUTUwgPSAnPGEgY2xhc3M9XCInICsgc2hvdXQgKyAnXCI+PC9hPidcclxuICAgICAgICByZXR1cm4gdGVzdC5maXJzdENoaWxkLmdldEF0dHJpYnV0ZSgnY2xhc3MnKSA9PT0gc2hvdXRcclxuICAgIH1cclxuXHJcbn1cclxuXHJcbi8vIEZpbmRlclxyXG5cclxudmFyIEZpbmRlciA9IGZ1bmN0aW9uIEZpbmRlcihkb2N1bWVudCl7XHJcblxyXG4gICAgdGhpcy5kb2N1bWVudCAgICAgICAgPSBkb2N1bWVudFxyXG4gICAgdmFyIHJvb3QgPSB0aGlzLnJvb3QgPSBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnRcclxuICAgIHRoaXMudGVzdGVkICAgICAgICAgID0ge31cclxuXHJcbiAgICAvLyB1bmlxdWVJRFxyXG5cclxuICAgIHRoaXMudW5pcXVlSUQgPSB0aGlzLmhhcyhcIkVYUEFORE9TXCIpID8gdW5pcXVlSUQgOiB1bmlxdWVJRFhNTFxyXG5cclxuICAgIC8vIGdldEF0dHJpYnV0ZVxyXG5cclxuICAgIHRoaXMuZ2V0QXR0cmlidXRlID0gKHRoaXMuaGFzKFwiR0VUX0FUVFJJQlVURVwiKSkgPyBmdW5jdGlvbihub2RlLCBuYW1lKXtcclxuXHJcbiAgICAgICAgcmV0dXJuIG5vZGUuZ2V0QXR0cmlidXRlKG5hbWUpXHJcblxyXG4gICAgfSA6IGZ1bmN0aW9uKG5vZGUsIG5hbWUpe1xyXG5cclxuICAgICAgICBub2RlID0gbm9kZS5nZXRBdHRyaWJ1dGVOb2RlKG5hbWUpXHJcbiAgICAgICAgcmV0dXJuIChub2RlICYmIG5vZGUuc3BlY2lmaWVkKSA/IG5vZGUudmFsdWUgOiBudWxsXHJcblxyXG4gICAgfVxyXG5cclxuICAgIC8vIGhhc0F0dHJpYnV0ZVxyXG5cclxuICAgIHRoaXMuaGFzQXR0cmlidXRlID0gKHJvb3QuaGFzQXR0cmlidXRlKSA/IGZ1bmN0aW9uKG5vZGUsIGF0dHJpYnV0ZSl7XHJcblxyXG4gICAgICAgIHJldHVybiBub2RlLmhhc0F0dHJpYnV0ZShhdHRyaWJ1dGUpXHJcblxyXG4gICAgfSA6IGZ1bmN0aW9uKG5vZGUsIGF0dHJpYnV0ZSkge1xyXG5cclxuICAgICAgICBub2RlID0gbm9kZS5nZXRBdHRyaWJ1dGVOb2RlKGF0dHJpYnV0ZSlcclxuICAgICAgICByZXR1cm4gISEobm9kZSAmJiBub2RlLnNwZWNpZmllZClcclxuXHJcbiAgICB9XHJcblxyXG4gICAgLy8gY29udGFpbnNcclxuXHJcbiAgICB0aGlzLmNvbnRhaW5zID0gKGRvY3VtZW50LmNvbnRhaW5zICYmIHJvb3QuY29udGFpbnMpID8gZnVuY3Rpb24oY29udGV4dCwgbm9kZSl7XHJcblxyXG4gICAgICAgIHJldHVybiBjb250ZXh0LmNvbnRhaW5zKG5vZGUpXHJcblxyXG4gICAgfSA6IChyb290LmNvbXBhcmVEb2N1bWVudFBvc2l0aW9uKSA/IGZ1bmN0aW9uKGNvbnRleHQsIG5vZGUpe1xyXG5cclxuICAgICAgICByZXR1cm4gY29udGV4dCA9PT0gbm9kZSB8fCAhIShjb250ZXh0LmNvbXBhcmVEb2N1bWVudFBvc2l0aW9uKG5vZGUpICYgMTYpXHJcblxyXG4gICAgfSA6IGZ1bmN0aW9uKGNvbnRleHQsIG5vZGUpe1xyXG5cclxuICAgICAgICBkbyB7XHJcbiAgICAgICAgICAgIGlmIChub2RlID09PSBjb250ZXh0KSByZXR1cm4gdHJ1ZVxyXG4gICAgICAgIH0gd2hpbGUgKChub2RlID0gbm9kZS5wYXJlbnROb2RlKSlcclxuXHJcbiAgICAgICAgcmV0dXJuIGZhbHNlXHJcbiAgICB9XHJcblxyXG4gICAgLy8gc29ydFxyXG4gICAgLy8gY3JlZGl0cyB0byBTaXp6bGUgKGh0dHA6Ly9zaXp6bGVqcy5jb20vKVxyXG5cclxuICAgIHRoaXMuc29ydGVyID0gKHJvb3QuY29tcGFyZURvY3VtZW50UG9zaXRpb24pID8gZnVuY3Rpb24oYSwgYil7XHJcblxyXG4gICAgICAgIGlmICghYS5jb21wYXJlRG9jdW1lbnRQb3NpdGlvbiB8fCAhYi5jb21wYXJlRG9jdW1lbnRQb3NpdGlvbikgcmV0dXJuIDBcclxuICAgICAgICByZXR1cm4gYS5jb21wYXJlRG9jdW1lbnRQb3NpdGlvbihiKSAmIDQgPyAtMSA6IGEgPT09IGIgPyAwIDogMVxyXG5cclxuICAgIH0gOiAoJ3NvdXJjZUluZGV4JyBpbiByb290KSA/IGZ1bmN0aW9uKGEsIGIpe1xyXG5cclxuICAgICAgICBpZiAoIWEuc291cmNlSW5kZXggfHwgIWIuc291cmNlSW5kZXgpIHJldHVybiAwXHJcbiAgICAgICAgcmV0dXJuIGEuc291cmNlSW5kZXggLSBiLnNvdXJjZUluZGV4XHJcblxyXG4gICAgfSA6IChkb2N1bWVudC5jcmVhdGVSYW5nZSkgPyBmdW5jdGlvbihhLCBiKXtcclxuXHJcbiAgICAgICAgaWYgKCFhLm93bmVyRG9jdW1lbnQgfHwgIWIub3duZXJEb2N1bWVudCkgcmV0dXJuIDBcclxuICAgICAgICB2YXIgYVJhbmdlID0gYS5vd25lckRvY3VtZW50LmNyZWF0ZVJhbmdlKCksXHJcbiAgICAgICAgICAgIGJSYW5nZSA9IGIub3duZXJEb2N1bWVudC5jcmVhdGVSYW5nZSgpXHJcblxyXG4gICAgICAgIGFSYW5nZS5zZXRTdGFydChhLCAwKVxyXG4gICAgICAgIGFSYW5nZS5zZXRFbmQoYSwgMClcclxuICAgICAgICBiUmFuZ2Uuc2V0U3RhcnQoYiwgMClcclxuICAgICAgICBiUmFuZ2Uuc2V0RW5kKGIsIDApXHJcbiAgICAgICAgcmV0dXJuIGFSYW5nZS5jb21wYXJlQm91bmRhcnlQb2ludHMoUmFuZ2UuU1RBUlRfVE9fRU5ELCBiUmFuZ2UpXHJcblxyXG4gICAgfSA6IG51bGxcclxuXHJcbiAgICB0aGlzLmZhaWxlZCA9IHt9XHJcblxyXG4gICAgdmFyIG5hdGl2ZU1hdGNoZXMgPSB0aGlzLmhhcyhcIk1BVENIRVNfU0VMRUNUT1JcIilcclxuXHJcbiAgICBpZiAobmF0aXZlTWF0Y2hlcykgdGhpcy5tYXRjaGVzU2VsZWN0b3IgPSBmdW5jdGlvbihub2RlLCBleHByZXNzaW9uKXtcclxuXHJcbiAgICAgICAgaWYgKHRoaXMuZmFpbGVkW2V4cHJlc3Npb25dKSByZXR1cm4gbnVsbFxyXG5cclxuICAgICAgICB0cnkge1xyXG4gICAgICAgICAgICByZXR1cm4gbmF0aXZlTWF0Y2hlcy5jYWxsKG5vZGUsIGV4cHJlc3Npb24pXHJcbiAgICAgICAgfSBjYXRjaChlKXtcclxuICAgICAgICAgICAgaWYgKHNsaWNrLmRlYnVnKSBjb25zb2xlLndhcm4oXCJtYXRjaGVzU2VsZWN0b3IgZmFpbGVkIG9uIFwiICsgZXhwcmVzc2lvbilcclxuICAgICAgICAgICAgdGhpcy5mYWlsZWRbZXhwcmVzc2lvbl0gPSB0cnVlXHJcbiAgICAgICAgICAgIHJldHVybiBudWxsXHJcbiAgICAgICAgfVxyXG5cclxuICAgIH1cclxuXHJcbiAgICBpZiAodGhpcy5oYXMoXCJRVUVSWV9TRUxFQ1RPUlwiKSl7XHJcblxyXG4gICAgICAgIHRoaXMucXVlcnlTZWxlY3RvckFsbCA9IGZ1bmN0aW9uKG5vZGUsIGV4cHJlc3Npb24pe1xyXG5cclxuICAgICAgICAgICAgaWYgKHRoaXMuZmFpbGVkW2V4cHJlc3Npb25dKSByZXR1cm4gdHJ1ZVxyXG5cclxuICAgICAgICAgICAgdmFyIHJlc3VsdCwgX2lkLCBfZXhwcmVzc2lvbiwgX2NvbWJpbmF0b3IsIF9ub2RlXHJcblxyXG5cclxuICAgICAgICAgICAgLy8gbm9uLWRvY3VtZW50IHJvb3RlZCBRU0FcclxuICAgICAgICAgICAgLy8gY3JlZGl0cyB0byBBbmRyZXcgRHVwb250XHJcblxyXG4gICAgICAgICAgICBpZiAobm9kZSAhPT0gdGhpcy5kb2N1bWVudCl7XHJcblxyXG4gICAgICAgICAgICAgICAgX2NvbWJpbmF0b3IgPSBleHByZXNzaW9uWzBdLmNvbWJpbmF0b3JcclxuXHJcbiAgICAgICAgICAgICAgICBfaWQgICAgICAgICA9IG5vZGUuZ2V0QXR0cmlidXRlKFwiaWRcIilcclxuICAgICAgICAgICAgICAgIF9leHByZXNzaW9uID0gZXhwcmVzc2lvblxyXG5cclxuICAgICAgICAgICAgICAgIGlmICghX2lkKXtcclxuICAgICAgICAgICAgICAgICAgICBfbm9kZSA9IG5vZGVcclxuICAgICAgICAgICAgICAgICAgICBfaWQgPSBcIl9fc2xpY2tfX1wiXHJcbiAgICAgICAgICAgICAgICAgICAgX25vZGUuc2V0QXR0cmlidXRlKFwiaWRcIiwgX2lkKVxyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIGV4cHJlc3Npb24gPSBcIiNcIiArIF9pZCArIFwiIFwiICsgX2V4cHJlc3Npb25cclxuXHJcblxyXG4gICAgICAgICAgICAgICAgLy8gdGhlc2UgY29tYmluYXRvcnMgbmVlZCBhIHBhcmVudE5vZGUgZHVlIHRvIGhvdyBxdWVyeVNlbGVjdG9yQWxsIHdvcmtzLCB3aGljaCBpczpcclxuICAgICAgICAgICAgICAgIC8vIGZpbmRpbmcgYWxsIHRoZSBlbGVtZW50cyB0aGF0IG1hdGNoIHRoZSBnaXZlbiBzZWxlY3RvclxyXG4gICAgICAgICAgICAgICAgLy8gdGhlbiBmaWx0ZXJpbmcgYnkgdGhlIG9uZXMgdGhhdCBoYXZlIHRoZSBzcGVjaWZpZWQgZWxlbWVudCBhcyBhbiBhbmNlc3RvclxyXG4gICAgICAgICAgICAgICAgaWYgKF9jb21iaW5hdG9yLmluZGV4T2YoXCJ+XCIpID4gLTEgfHwgX2NvbWJpbmF0b3IuaW5kZXhPZihcIitcIikgPiAtMSl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIG5vZGUgPSBub2RlLnBhcmVudE5vZGVcclxuICAgICAgICAgICAgICAgICAgICBpZiAoIW5vZGUpIHJlc3VsdCA9IHRydWVcclxuICAgICAgICAgICAgICAgICAgICAvLyBpZiBub2RlIGhhcyBubyBwYXJlbnROb2RlLCB3ZSByZXR1cm4gXCJ0cnVlXCIgYXMgaWYgaXQgZmFpbGVkLCB3aXRob3V0IHBvbGx1dGluZyB0aGUgZmFpbGVkIGNhY2hlXHJcblxyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgaWYgKCFyZXN1bHQpIHRyeSB7XHJcbiAgICAgICAgICAgICAgICByZXN1bHQgPSBub2RlLnF1ZXJ5U2VsZWN0b3JBbGwoZXhwcmVzc2lvbi50b1N0cmluZygpKVxyXG4gICAgICAgICAgICB9IGNhdGNoKGUpe1xyXG4gICAgICAgICAgICAgICAgaWYgKHNsaWNrLmRlYnVnKSBjb25zb2xlLndhcm4oXCJxdWVyeVNlbGVjdG9yQWxsIGZhaWxlZCBvbiBcIiArIChfZXhwcmVzc2lvbiB8fCBleHByZXNzaW9uKSlcclxuICAgICAgICAgICAgICAgIHJlc3VsdCA9IHRoaXMuZmFpbGVkW19leHByZXNzaW9uIHx8IGV4cHJlc3Npb25dID0gdHJ1ZVxyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZiAoX25vZGUpIF9ub2RlLnJlbW92ZUF0dHJpYnV0ZShcImlkXCIpXHJcblxyXG4gICAgICAgICAgICByZXR1cm4gcmVzdWx0XHJcblxyXG4gICAgICAgIH1cclxuXHJcbiAgICB9XHJcblxyXG59XHJcblxyXG5GaW5kZXIucHJvdG90eXBlLmhhcyA9IGZ1bmN0aW9uKEZFQVRVUkUpe1xyXG5cclxuICAgIHZhciB0ZXN0ZWQgICAgICAgID0gdGhpcy50ZXN0ZWQsXHJcbiAgICAgICAgdGVzdGVkRkVBVFVSRSA9IHRlc3RlZFtGRUFUVVJFXVxyXG5cclxuICAgIGlmICh0ZXN0ZWRGRUFUVVJFICE9IG51bGwpIHJldHVybiB0ZXN0ZWRGRUFUVVJFXHJcblxyXG4gICAgdmFyIHJvb3QgICAgID0gdGhpcy5yb290LFxyXG4gICAgICAgIGRvY3VtZW50ID0gdGhpcy5kb2N1bWVudCxcclxuICAgICAgICB0ZXN0Tm9kZSA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJkaXZcIilcclxuXHJcbiAgICB0ZXN0Tm9kZS5zZXRBdHRyaWJ1dGUoXCJzdHlsZVwiLCBcImRpc3BsYXk6IG5vbmU7XCIpXHJcblxyXG4gICAgcm9vdC5hcHBlbmRDaGlsZCh0ZXN0Tm9kZSlcclxuXHJcbiAgICB2YXIgVEVTVCA9IEhBU1tGRUFUVVJFXSwgcmVzdWx0ID0gZmFsc2VcclxuXHJcbiAgICBpZiAoVEVTVCkgdHJ5IHtcclxuICAgICAgICByZXN1bHQgPSBURVNULmNhbGwoZG9jdW1lbnQsIHRlc3ROb2RlKVxyXG4gICAgfSBjYXRjaChlKXt9XHJcblxyXG4gICAgaWYgKHNsaWNrLmRlYnVnICYmICFyZXN1bHQpIGNvbnNvbGUud2FybihcImRvY3VtZW50IGhhcyBubyBcIiArIEZFQVRVUkUpXHJcblxyXG4gICAgcm9vdC5yZW1vdmVDaGlsZCh0ZXN0Tm9kZSlcclxuXHJcbiAgICByZXR1cm4gdGVzdGVkW0ZFQVRVUkVdID0gcmVzdWx0XHJcblxyXG59XHJcblxyXG52YXIgY29tYmluYXRvcnMgPSB7XHJcblxyXG4gICAgXCIgXCI6IGZ1bmN0aW9uKG5vZGUsIHBhcnQsIHB1c2gpe1xyXG5cclxuICAgICAgICB2YXIgaXRlbSwgaXRlbXNcclxuXHJcbiAgICAgICAgdmFyIG5vSWQgPSAhcGFydC5pZCwgbm9UYWcgPSAhcGFydC50YWcsIG5vQ2xhc3MgPSAhcGFydC5jbGFzc2VzXHJcblxyXG4gICAgICAgIGlmIChwYXJ0LmlkICYmIG5vZGUuZ2V0RWxlbWVudEJ5SWQgJiYgdGhpcy5oYXMoXCJHRVRfRUxFTUVOVF9CWV9JRFwiKSl7XHJcbiAgICAgICAgICAgIGl0ZW0gPSBub2RlLmdldEVsZW1lbnRCeUlkKHBhcnQuaWQpXHJcblxyXG4gICAgICAgICAgICAvLyByZXR1cm4gb25seSBpZiBpZCBpcyBmb3VuZCwgZWxzZSBrZWVwIGNoZWNraW5nXHJcbiAgICAgICAgICAgIC8vIG1pZ2h0IGJlIGEgdGFkIHNsb3dlciBvbiBub24tZXhpc3RpbmcgaWRzLCBidXQgbGVzcyBpbnNhbmVcclxuXHJcbiAgICAgICAgICAgIGlmIChpdGVtICYmIGl0ZW0uZ2V0QXR0cmlidXRlKCdpZCcpID09PSBwYXJ0LmlkKXtcclxuICAgICAgICAgICAgICAgIGl0ZW1zID0gW2l0ZW1dXHJcbiAgICAgICAgICAgICAgICBub0lkID0gdHJ1ZVxyXG4gICAgICAgICAgICAgICAgLy8gaWYgdGFnIGlzIHN0YXIsIG5vIG5lZWQgdG8gY2hlY2sgaXQgaW4gbWF0Y2goKVxyXG4gICAgICAgICAgICAgICAgaWYgKHBhcnQudGFnID09PSBcIipcIikgbm9UYWcgPSB0cnVlXHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmICghaXRlbXMpe1xyXG5cclxuICAgICAgICAgICAgaWYgKHBhcnQuY2xhc3NlcyAmJiBub2RlLmdldEVsZW1lbnRzQnlDbGFzc05hbWUgJiYgdGhpcy5oYXMoXCJHRVRfRUxFTUVOVFNfQllfQ0xBU1NfTkFNRVwiKSl7XHJcbiAgICAgICAgICAgICAgICBpdGVtcyA9IG5vZGUuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZShwYXJ0LmNsYXNzTGlzdClcclxuICAgICAgICAgICAgICAgIG5vQ2xhc3MgPSB0cnVlXHJcbiAgICAgICAgICAgICAgICAvLyBpZiB0YWcgaXMgc3Rhciwgbm8gbmVlZCB0byBjaGVjayBpdCBpbiBtYXRjaCgpXHJcbiAgICAgICAgICAgICAgICBpZiAocGFydC50YWcgPT09IFwiKlwiKSBub1RhZyA9IHRydWVcclxuICAgICAgICAgICAgfSBlbHNlIHtcclxuICAgICAgICAgICAgICAgIGl0ZW1zID0gbm9kZS5nZXRFbGVtZW50c0J5VGFnTmFtZShwYXJ0LnRhZylcclxuICAgICAgICAgICAgICAgIC8vIGlmIHRhZyBpcyBzdGFyLCBuZWVkIHRvIGNoZWNrIGl0IGluIG1hdGNoIGJlY2F1c2UgaXQgY291bGQgc2VsZWN0IGp1bmssIGJvaG9cclxuICAgICAgICAgICAgICAgIGlmIChwYXJ0LnRhZyAhPT0gXCIqXCIpIG5vVGFnID0gdHJ1ZVxyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZiAoIWl0ZW1zIHx8ICFpdGVtcy5sZW5ndGgpIHJldHVybiBmYWxzZVxyXG5cclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGZvciAodmFyIGkgPSAwOyBpdGVtID0gaXRlbXNbaSsrXTspXHJcbiAgICAgICAgICAgIGlmICgobm9UYWcgJiYgbm9JZCAmJiBub0NsYXNzICYmICFwYXJ0LmF0dHJpYnV0ZXMgJiYgIXBhcnQucHNldWRvcykgfHwgdGhpcy5tYXRjaChpdGVtLCBwYXJ0LCBub1RhZywgbm9JZCwgbm9DbGFzcykpXHJcbiAgICAgICAgICAgICAgICBwdXNoKGl0ZW0pXHJcblxyXG4gICAgICAgIHJldHVybiB0cnVlXHJcblxyXG4gICAgfSxcclxuXHJcbiAgICBcIj5cIjogZnVuY3Rpb24obm9kZSwgcGFydCwgcHVzaCl7IC8vIGRpcmVjdCBjaGlsZHJlblxyXG4gICAgICAgIGlmICgobm9kZSA9IG5vZGUuZmlyc3RDaGlsZCkpIGRvIHtcclxuICAgICAgICAgICAgaWYgKG5vZGUubm9kZVR5cGUgPT0gMSAmJiB0aGlzLm1hdGNoKG5vZGUsIHBhcnQpKSBwdXNoKG5vZGUpXHJcbiAgICAgICAgfSB3aGlsZSAoKG5vZGUgPSBub2RlLm5leHRTaWJsaW5nKSlcclxuICAgIH0sXHJcblxyXG4gICAgXCIrXCI6IGZ1bmN0aW9uKG5vZGUsIHBhcnQsIHB1c2gpeyAvLyBuZXh0IHNpYmxpbmdcclxuICAgICAgICB3aGlsZSAoKG5vZGUgPSBub2RlLm5leHRTaWJsaW5nKSkgaWYgKG5vZGUubm9kZVR5cGUgPT0gMSl7XHJcbiAgICAgICAgICAgIGlmICh0aGlzLm1hdGNoKG5vZGUsIHBhcnQpKSBwdXNoKG5vZGUpXHJcbiAgICAgICAgICAgIGJyZWFrXHJcbiAgICAgICAgfVxyXG4gICAgfSxcclxuXHJcbiAgICBcIl5cIjogZnVuY3Rpb24obm9kZSwgcGFydCwgcHVzaCl7IC8vIGZpcnN0IGNoaWxkXHJcbiAgICAgICAgbm9kZSA9IG5vZGUuZmlyc3RDaGlsZFxyXG4gICAgICAgIGlmIChub2RlKXtcclxuICAgICAgICAgICAgaWYgKG5vZGUubm9kZVR5cGUgPT09IDEpe1xyXG4gICAgICAgICAgICAgICAgaWYgKHRoaXMubWF0Y2gobm9kZSwgcGFydCkpIHB1c2gobm9kZSlcclxuICAgICAgICAgICAgfSBlbHNlIHtcclxuICAgICAgICAgICAgICAgIGNvbWJpbmF0b3JzWycrJ10uY2FsbCh0aGlzLCBub2RlLCBwYXJ0LCBwdXNoKVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG4gICAgfSxcclxuXHJcbiAgICBcIn5cIjogZnVuY3Rpb24obm9kZSwgcGFydCwgcHVzaCl7IC8vIG5leHQgc2libGluZ3NcclxuICAgICAgICB3aGlsZSAoKG5vZGUgPSBub2RlLm5leHRTaWJsaW5nKSl7XHJcbiAgICAgICAgICAgIGlmIChub2RlLm5vZGVUeXBlID09PSAxICYmIHRoaXMubWF0Y2gobm9kZSwgcGFydCkpIHB1c2gobm9kZSlcclxuICAgICAgICB9XHJcbiAgICB9LFxyXG5cclxuICAgIFwiKytcIjogZnVuY3Rpb24obm9kZSwgcGFydCwgcHVzaCl7IC8vIG5leHQgc2libGluZyBhbmQgcHJldmlvdXMgc2libGluZ1xyXG4gICAgICAgIGNvbWJpbmF0b3JzWycrJ10uY2FsbCh0aGlzLCBub2RlLCBwYXJ0LCBwdXNoKVxyXG4gICAgICAgIGNvbWJpbmF0b3JzWychKyddLmNhbGwodGhpcywgbm9kZSwgcGFydCwgcHVzaClcclxuICAgIH0sXHJcblxyXG4gICAgXCJ+flwiOiBmdW5jdGlvbihub2RlLCBwYXJ0LCBwdXNoKXsgLy8gbmV4dCBzaWJsaW5ncyBhbmQgcHJldmlvdXMgc2libGluZ3NcclxuICAgICAgICBjb21iaW5hdG9yc1snfiddLmNhbGwodGhpcywgbm9kZSwgcGFydCwgcHVzaClcclxuICAgICAgICBjb21iaW5hdG9yc1snIX4nXS5jYWxsKHRoaXMsIG5vZGUsIHBhcnQsIHB1c2gpXHJcbiAgICB9LFxyXG5cclxuICAgIFwiIVwiOiBmdW5jdGlvbihub2RlLCBwYXJ0LCBwdXNoKXsgLy8gYWxsIHBhcmVudCBub2RlcyB1cCB0byBkb2N1bWVudFxyXG4gICAgICAgIHdoaWxlICgobm9kZSA9IG5vZGUucGFyZW50Tm9kZSkpIGlmIChub2RlICE9PSB0aGlzLmRvY3VtZW50ICYmIHRoaXMubWF0Y2gobm9kZSwgcGFydCkpIHB1c2gobm9kZSlcclxuICAgIH0sXHJcblxyXG4gICAgXCIhPlwiOiBmdW5jdGlvbihub2RlLCBwYXJ0LCBwdXNoKXsgLy8gZGlyZWN0IHBhcmVudCAob25lIGxldmVsKVxyXG4gICAgICAgIG5vZGUgPSBub2RlLnBhcmVudE5vZGVcclxuICAgICAgICBpZiAobm9kZSAhPT0gdGhpcy5kb2N1bWVudCAmJiB0aGlzLm1hdGNoKG5vZGUsIHBhcnQpKSBwdXNoKG5vZGUpXHJcbiAgICB9LFxyXG5cclxuICAgIFwiIStcIjogZnVuY3Rpb24obm9kZSwgcGFydCwgcHVzaCl7IC8vIHByZXZpb3VzIHNpYmxpbmdcclxuICAgICAgICB3aGlsZSAoKG5vZGUgPSBub2RlLnByZXZpb3VzU2libGluZykpIGlmIChub2RlLm5vZGVUeXBlID09IDEpe1xyXG4gICAgICAgICAgICBpZiAodGhpcy5tYXRjaChub2RlLCBwYXJ0KSkgcHVzaChub2RlKVxyXG4gICAgICAgICAgICBicmVha1xyXG4gICAgICAgIH1cclxuICAgIH0sXHJcblxyXG4gICAgXCIhXlwiOiBmdW5jdGlvbihub2RlLCBwYXJ0LCBwdXNoKXsgLy8gbGFzdCBjaGlsZFxyXG4gICAgICAgIG5vZGUgPSBub2RlLmxhc3RDaGlsZFxyXG4gICAgICAgIGlmIChub2RlKXtcclxuICAgICAgICAgICAgaWYgKG5vZGUubm9kZVR5cGUgPT0gMSl7XHJcbiAgICAgICAgICAgICAgICBpZiAodGhpcy5tYXRjaChub2RlLCBwYXJ0KSkgcHVzaChub2RlKVxyXG4gICAgICAgICAgICB9IGVsc2Uge1xyXG4gICAgICAgICAgICAgICAgY29tYmluYXRvcnNbJyErJ10uY2FsbCh0aGlzLCBub2RlLCBwYXJ0LCBwdXNoKVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG4gICAgfSxcclxuXHJcbiAgICBcIiF+XCI6IGZ1bmN0aW9uKG5vZGUsIHBhcnQsIHB1c2gpeyAvLyBwcmV2aW91cyBzaWJsaW5nc1xyXG4gICAgICAgIHdoaWxlICgobm9kZSA9IG5vZGUucHJldmlvdXNTaWJsaW5nKSl7XHJcbiAgICAgICAgICAgIGlmIChub2RlLm5vZGVUeXBlID09PSAxICYmIHRoaXMubWF0Y2gobm9kZSwgcGFydCkpIHB1c2gobm9kZSlcclxuICAgICAgICB9XHJcbiAgICB9XHJcblxyXG59XHJcblxyXG5GaW5kZXIucHJvdG90eXBlLnNlYXJjaCA9IGZ1bmN0aW9uKGNvbnRleHQsIGV4cHJlc3Npb24sIGZvdW5kKXtcclxuXHJcbiAgICBpZiAoIWNvbnRleHQpIGNvbnRleHQgPSB0aGlzLmRvY3VtZW50XHJcbiAgICBlbHNlIGlmICghY29udGV4dC5ub2RlVHlwZSAmJiBjb250ZXh0LmRvY3VtZW50KSBjb250ZXh0ID0gY29udGV4dC5kb2N1bWVudFxyXG5cclxuICAgIHZhciBleHByZXNzaW9ucyA9IHBhcnNlKGV4cHJlc3Npb24pXHJcblxyXG4gICAgLy8gbm8gZXhwcmVzc2lvbnMgd2VyZSBwYXJzZWQuIHRvZG86IGlzIHRoaXMgcmVhbGx5IG5lY2Vzc2FyeT9cclxuICAgIGlmICghZXhwcmVzc2lvbnMgfHwgIWV4cHJlc3Npb25zLmxlbmd0aCkgdGhyb3cgbmV3IEVycm9yKFwiaW52YWxpZCBleHByZXNzaW9uXCIpXHJcblxyXG4gICAgaWYgKCFmb3VuZCkgZm91bmQgPSBbXVxyXG5cclxuICAgIHZhciB1bmlxdWVzLCBwdXNoID0gaXNBcnJheShmb3VuZCkgPyBmdW5jdGlvbihub2RlKXtcclxuICAgICAgICBmb3VuZFtmb3VuZC5sZW5ndGhdID0gbm9kZVxyXG4gICAgfSA6IGZ1bmN0aW9uKG5vZGUpe1xyXG4gICAgICAgIGZvdW5kW2ZvdW5kLmxlbmd0aCsrXSA9IG5vZGVcclxuICAgIH1cclxuXHJcbiAgICAvLyBpZiB0aGVyZSBpcyBtb3JlIHRoYW4gb25lIGV4cHJlc3Npb24gd2UgbmVlZCB0byBjaGVjayBmb3IgZHVwbGljYXRlcyB3aGVuIHdlIHB1c2ggdG8gZm91bmRcclxuICAgIC8vIHRoaXMgc2ltcGx5IHNhdmVzIHRoZSBvbGQgcHVzaCBhbmQgd3JhcHMgaXQgYXJvdW5kIGFuIHVpZCBkdXBlIGNoZWNrLlxyXG4gICAgaWYgKGV4cHJlc3Npb25zLmxlbmd0aCA+IDEpe1xyXG4gICAgICAgIHVuaXF1ZXMgPSB7fVxyXG4gICAgICAgIHZhciBwbHVzaCA9IHB1c2hcclxuICAgICAgICBwdXNoID0gZnVuY3Rpb24obm9kZSl7XHJcbiAgICAgICAgICAgIHZhciB1aWQgPSB1bmlxdWVJRChub2RlKVxyXG4gICAgICAgICAgICBpZiAoIXVuaXF1ZXNbdWlkXSl7XHJcbiAgICAgICAgICAgICAgICB1bmlxdWVzW3VpZF0gPSB0cnVlXHJcbiAgICAgICAgICAgICAgICBwbHVzaChub2RlKVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG4gICAgfVxyXG5cclxuICAgIC8vIHdhbGtlclxyXG5cclxuICAgIHZhciBub2RlLCBub2RlcywgcGFydFxyXG5cclxuICAgIG1haW46IGZvciAodmFyIGkgPSAwOyBleHByZXNzaW9uID0gZXhwcmVzc2lvbnNbaSsrXTspe1xyXG5cclxuICAgICAgICAvLyBxdWVyeVNlbGVjdG9yXHJcblxyXG4gICAgICAgIC8vIFRPRE86IG1vcmUgZnVuY3Rpb25hbCB0ZXN0c1xyXG5cclxuICAgICAgICAvLyBpZiB0aGVyZSBpcyBxdWVyeVNlbGVjdG9yQWxsIChhbmQgdGhlIGV4cHJlc3Npb24gZG9lcyBub3QgZmFpbCkgdXNlIGl0LlxyXG4gICAgICAgIGlmICghc2xpY2subm9RU0EgJiYgdGhpcy5xdWVyeVNlbGVjdG9yQWxsKXtcclxuXHJcbiAgICAgICAgICAgIG5vZGVzID0gdGhpcy5xdWVyeVNlbGVjdG9yQWxsKGNvbnRleHQsIGV4cHJlc3Npb24pXHJcbiAgICAgICAgICAgIGlmIChub2RlcyAhPT0gdHJ1ZSl7XHJcbiAgICAgICAgICAgICAgICBpZiAobm9kZXMgJiYgbm9kZXMubGVuZ3RoKSBmb3IgKHZhciBqID0gMDsgbm9kZSA9IG5vZGVzW2orK107KSBpZiAobm9kZS5ub2RlTmFtZSA+ICdAJyl7XHJcbiAgICAgICAgICAgICAgICAgICAgcHVzaChub2RlKVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgY29udGludWUgbWFpblxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICAvLyBpZiB0aGVyZSBpcyBvbmx5IG9uZSBwYXJ0IGluIHRoZSBleHByZXNzaW9uIHdlIGRvbid0IG5lZWQgdG8gY2hlY2sgZWFjaCBwYXJ0IGZvciBkdXBsaWNhdGVzLlxyXG4gICAgICAgIC8vIHRvZG86IHRoaXMgbWlnaHQgYmUgdG9vIG5haXZlLiB3aGlsZSBzb2xpZCwgdGhlcmUgY2FuIGJlIGV4cHJlc3Npb24gc2VxdWVuY2VzIHRoYXQgZG8gbm90XHJcbiAgICAgICAgLy8gcHJvZHVjZSBkdXBsaWNhdGVzLiBcImJvZHkgZGl2XCIgZm9yIGluc3RhbmNlLCBjYW4gbmV2ZXIgZ2l2ZSB5b3UgZWFjaCBkaXYgbW9yZSB0aGFuIG9uY2UuXHJcbiAgICAgICAgLy8gXCJib2R5IGRpdiBhXCIgb24gdGhlIG90aGVyIGhhbmQgbWlnaHQuXHJcbiAgICAgICAgaWYgKGV4cHJlc3Npb24ubGVuZ3RoID09PSAxKXtcclxuXHJcbiAgICAgICAgICAgIHBhcnQgPSBleHByZXNzaW9uWzBdXHJcbiAgICAgICAgICAgIGNvbWJpbmF0b3JzW3BhcnQuY29tYmluYXRvcl0uY2FsbCh0aGlzLCBjb250ZXh0LCBwYXJ0LCBwdXNoKVxyXG5cclxuICAgICAgICB9IGVsc2Uge1xyXG5cclxuICAgICAgICAgICAgdmFyIGNzID0gW2NvbnRleHRdLCBjLCBmLCB1LCBwID0gZnVuY3Rpb24obm9kZSl7XHJcbiAgICAgICAgICAgICAgICB2YXIgdWlkID0gdW5pcXVlSUQobm9kZSlcclxuICAgICAgICAgICAgICAgIGlmICghdVt1aWRdKXtcclxuICAgICAgICAgICAgICAgICAgICB1W3VpZF0gPSB0cnVlXHJcbiAgICAgICAgICAgICAgICAgICAgZltmLmxlbmd0aF0gPSBub2RlXHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIC8vIGxvb3AgdGhlIGV4cHJlc3Npb24gcGFydHNcclxuICAgICAgICAgICAgZm9yICh2YXIgaiA9IDA7IHBhcnQgPSBleHByZXNzaW9uW2orK107KXtcclxuICAgICAgICAgICAgICAgIGYgPSBbXTsgdSA9IHt9XHJcbiAgICAgICAgICAgICAgICAvLyBsb29wIHRoZSBjb250ZXh0c1xyXG4gICAgICAgICAgICAgICAgZm9yICh2YXIgayA9IDA7IGMgPSBjc1trKytdOykgY29tYmluYXRvcnNbcGFydC5jb21iaW5hdG9yXS5jYWxsKHRoaXMsIGMsIHBhcnQsIHApXHJcbiAgICAgICAgICAgICAgICAvLyBub3RoaW5nIHdhcyBmb3VuZCwgdGhlIGV4cHJlc3Npb24gZmFpbGVkLCBjb250aW51ZSB0byB0aGUgbmV4dCBleHByZXNzaW9uLlxyXG4gICAgICAgICAgICAgICAgaWYgKCFmLmxlbmd0aCkgY29udGludWUgbWFpblxyXG4gICAgICAgICAgICAgICAgY3MgPSBmIC8vIHNldCB0aGUgY29udGV4dHMgZm9yIGZ1dHVyZSBwYXJ0cyAoaWYgYW55KVxyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZiAoaSA9PT0gMCkgZm91bmQgPSBmIC8vIGZpcnN0IGV4cHJlc3Npb24uIGRpcmVjdGx5IHNldCBmb3VuZC5cclxuICAgICAgICAgICAgZWxzZSBmb3IgKHZhciBsID0gMDsgbCA8IGYubGVuZ3RoOyBsKyspIHB1c2goZltsXSkgLy8gYW55IG90aGVyIGV4cHJlc3Npb24gbmVlZHMgdG8gcHVzaCB0byBmb3VuZC5cclxuICAgICAgICB9XHJcblxyXG4gICAgfVxyXG5cclxuICAgIGlmICh1bmlxdWVzICYmIGZvdW5kICYmIGZvdW5kLmxlbmd0aCA+IDEpIHRoaXMuc29ydChmb3VuZClcclxuXHJcbiAgICByZXR1cm4gZm91bmRcclxuXHJcbn1cclxuXHJcbkZpbmRlci5wcm90b3R5cGUuc29ydCA9IGZ1bmN0aW9uKG5vZGVzKXtcclxuICAgIHJldHVybiB0aGlzLnNvcnRlciA/IEFycmF5LnByb3RvdHlwZS5zb3J0LmNhbGwobm9kZXMsIHRoaXMuc29ydGVyKSA6IG5vZGVzXHJcbn1cclxuXHJcbi8vIFRPRE86IG1vc3Qgb2YgdGhlc2UgcHNldWRvIHNlbGVjdG9ycyBpbmNsdWRlIDxodG1sPiBhbmQgcXNhIGRvZXNudC4gZml4bWUuXHJcblxyXG52YXIgcHNldWRvcyA9IHtcclxuXHJcblxyXG4gICAgLy8gVE9ETzogcmV0dXJucyBkaWZmZXJlbnQgcmVzdWx0cyB0aGFuIHFzYSBlbXB0eS5cclxuXHJcbiAgICAnZW1wdHknOiBmdW5jdGlvbigpe1xyXG4gICAgICAgIHJldHVybiAhKHRoaXMgJiYgdGhpcy5ub2RlVHlwZSA9PT0gMSkgJiYgISh0aGlzLmlubmVyVGV4dCB8fCB0aGlzLnRleHRDb250ZW50IHx8ICcnKS5sZW5ndGhcclxuICAgIH0sXHJcblxyXG4gICAgJ25vdCc6IGZ1bmN0aW9uKGV4cHJlc3Npb24pe1xyXG4gICAgICAgIHJldHVybiAhc2xpY2subWF0Y2hlcyh0aGlzLCBleHByZXNzaW9uKVxyXG4gICAgfSxcclxuXHJcbiAgICAnY29udGFpbnMnOiBmdW5jdGlvbih0ZXh0KXtcclxuICAgICAgICByZXR1cm4gKHRoaXMuaW5uZXJUZXh0IHx8IHRoaXMudGV4dENvbnRlbnQgfHwgJycpLmluZGV4T2YodGV4dCkgPiAtMVxyXG4gICAgfSxcclxuXHJcbiAgICAnZmlyc3QtY2hpbGQnOiBmdW5jdGlvbigpe1xyXG4gICAgICAgIHZhciBub2RlID0gdGhpc1xyXG4gICAgICAgIHdoaWxlICgobm9kZSA9IG5vZGUucHJldmlvdXNTaWJsaW5nKSkgaWYgKG5vZGUubm9kZVR5cGUgPT0gMSkgcmV0dXJuIGZhbHNlXHJcbiAgICAgICAgcmV0dXJuIHRydWVcclxuICAgIH0sXHJcblxyXG4gICAgJ2xhc3QtY2hpbGQnOiBmdW5jdGlvbigpe1xyXG4gICAgICAgIHZhciBub2RlID0gdGhpc1xyXG4gICAgICAgIHdoaWxlICgobm9kZSA9IG5vZGUubmV4dFNpYmxpbmcpKSBpZiAobm9kZS5ub2RlVHlwZSA9PSAxKSByZXR1cm4gZmFsc2VcclxuICAgICAgICByZXR1cm4gdHJ1ZVxyXG4gICAgfSxcclxuXHJcbiAgICAnb25seS1jaGlsZCc6IGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgdmFyIHByZXYgPSB0aGlzXHJcbiAgICAgICAgd2hpbGUgKChwcmV2ID0gcHJldi5wcmV2aW91c1NpYmxpbmcpKSBpZiAocHJldi5ub2RlVHlwZSA9PSAxKSByZXR1cm4gZmFsc2VcclxuXHJcbiAgICAgICAgdmFyIG5leHQgPSB0aGlzXHJcbiAgICAgICAgd2hpbGUgKChuZXh0ID0gbmV4dC5uZXh0U2libGluZykpIGlmIChuZXh0Lm5vZGVUeXBlID09IDEpIHJldHVybiBmYWxzZVxyXG5cclxuICAgICAgICByZXR1cm4gdHJ1ZVxyXG4gICAgfSxcclxuXHJcbiAgICAnZmlyc3Qtb2YtdHlwZSc6IGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgdmFyIG5vZGUgPSB0aGlzLCBub2RlTmFtZSA9IG5vZGUubm9kZU5hbWVcclxuICAgICAgICB3aGlsZSAoKG5vZGUgPSBub2RlLnByZXZpb3VzU2libGluZykpIGlmIChub2RlLm5vZGVOYW1lID09IG5vZGVOYW1lKSByZXR1cm4gZmFsc2VcclxuICAgICAgICByZXR1cm4gdHJ1ZVxyXG4gICAgfSxcclxuXHJcbiAgICAnbGFzdC1vZi10eXBlJzogZnVuY3Rpb24oKXtcclxuICAgICAgICB2YXIgbm9kZSA9IHRoaXMsIG5vZGVOYW1lID0gbm9kZS5ub2RlTmFtZVxyXG4gICAgICAgIHdoaWxlICgobm9kZSA9IG5vZGUubmV4dFNpYmxpbmcpKSBpZiAobm9kZS5ub2RlTmFtZSA9PSBub2RlTmFtZSkgcmV0dXJuIGZhbHNlXHJcbiAgICAgICAgcmV0dXJuIHRydWVcclxuICAgIH0sXHJcblxyXG4gICAgJ29ubHktb2YtdHlwZSc6IGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgdmFyIHByZXYgPSB0aGlzLCBub2RlTmFtZSA9IHRoaXMubm9kZU5hbWVcclxuICAgICAgICB3aGlsZSAoKHByZXYgPSBwcmV2LnByZXZpb3VzU2libGluZykpIGlmIChwcmV2Lm5vZGVOYW1lID09IG5vZGVOYW1lKSByZXR1cm4gZmFsc2VcclxuICAgICAgICB2YXIgbmV4dCA9IHRoaXNcclxuICAgICAgICB3aGlsZSAoKG5leHQgPSBuZXh0Lm5leHRTaWJsaW5nKSkgaWYgKG5leHQubm9kZU5hbWUgPT0gbm9kZU5hbWUpIHJldHVybiBmYWxzZVxyXG4gICAgICAgIHJldHVybiB0cnVlXHJcbiAgICB9LFxyXG5cclxuICAgICdlbmFibGVkJzogZnVuY3Rpb24oKXtcclxuICAgICAgICByZXR1cm4gIXRoaXMuZGlzYWJsZWRcclxuICAgIH0sXHJcblxyXG4gICAgJ2Rpc2FibGVkJzogZnVuY3Rpb24oKXtcclxuICAgICAgICByZXR1cm4gdGhpcy5kaXNhYmxlZFxyXG4gICAgfSxcclxuXHJcbiAgICAnY2hlY2tlZCc6IGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgcmV0dXJuIHRoaXMuY2hlY2tlZCB8fCB0aGlzLnNlbGVjdGVkXHJcbiAgICB9LFxyXG5cclxuICAgICdzZWxlY3RlZCc6IGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgcmV0dXJuIHRoaXMuc2VsZWN0ZWRcclxuICAgIH0sXHJcblxyXG4gICAgJ2ZvY3VzJzogZnVuY3Rpb24oKXtcclxuICAgICAgICB2YXIgZG9jID0gdGhpcy5vd25lckRvY3VtZW50XHJcbiAgICAgICAgcmV0dXJuIGRvYy5hY3RpdmVFbGVtZW50ID09PSB0aGlzICYmICh0aGlzLmhyZWYgfHwgdGhpcy50eXBlIHx8IHNsaWNrLmhhc0F0dHJpYnV0ZSh0aGlzLCAndGFiaW5kZXgnKSlcclxuICAgIH0sXHJcblxyXG4gICAgJ3Jvb3QnOiBmdW5jdGlvbigpe1xyXG4gICAgICAgIHJldHVybiAodGhpcyA9PT0gdGhpcy5vd25lckRvY3VtZW50LmRvY3VtZW50RWxlbWVudClcclxuICAgIH1cclxuXHJcbn1cclxuXHJcbkZpbmRlci5wcm90b3R5cGUubWF0Y2ggPSBmdW5jdGlvbihub2RlLCBiaXQsIG5vVGFnLCBub0lkLCBub0NsYXNzKXtcclxuXHJcbiAgICAvLyBUT0RPOiBtb3JlIGZ1bmN0aW9uYWwgdGVzdHMgP1xyXG5cclxuICAgIGlmICghc2xpY2subm9RU0EgJiYgdGhpcy5tYXRjaGVzU2VsZWN0b3Ipe1xyXG4gICAgICAgIHZhciBtYXRjaGVzID0gdGhpcy5tYXRjaGVzU2VsZWN0b3Iobm9kZSwgYml0KVxyXG4gICAgICAgIGlmIChtYXRjaGVzICE9PSBudWxsKSByZXR1cm4gbWF0Y2hlc1xyXG4gICAgfVxyXG5cclxuICAgIC8vIG5vcm1hbCBtYXRjaGluZ1xyXG5cclxuICAgIGlmICghbm9UYWcgJiYgYml0LnRhZyl7XHJcblxyXG4gICAgICAgIHZhciBub2RlTmFtZSA9IG5vZGUubm9kZU5hbWUudG9Mb3dlckNhc2UoKVxyXG4gICAgICAgIGlmIChiaXQudGFnID09PSBcIipcIil7XHJcbiAgICAgICAgICAgIGlmIChub2RlTmFtZSA8IFwiQFwiKSByZXR1cm4gZmFsc2VcclxuICAgICAgICB9IGVsc2UgaWYgKG5vZGVOYW1lICE9IGJpdC50YWcpe1xyXG4gICAgICAgICAgICByZXR1cm4gZmFsc2VcclxuICAgICAgICB9XHJcblxyXG4gICAgfVxyXG5cclxuICAgIGlmICghbm9JZCAmJiBiaXQuaWQgJiYgbm9kZS5nZXRBdHRyaWJ1dGUoJ2lkJykgIT09IGJpdC5pZCkgcmV0dXJuIGZhbHNlXHJcblxyXG4gICAgdmFyIGksIHBhcnRcclxuXHJcbiAgICBpZiAoIW5vQ2xhc3MgJiYgYml0LmNsYXNzZXMpe1xyXG5cclxuICAgICAgICB2YXIgY2xhc3NOYW1lID0gdGhpcy5nZXRBdHRyaWJ1dGUobm9kZSwgXCJjbGFzc1wiKVxyXG4gICAgICAgIGlmICghY2xhc3NOYW1lKSByZXR1cm4gZmFsc2VcclxuXHJcbiAgICAgICAgZm9yIChwYXJ0IGluIGJpdC5jbGFzc2VzKSBpZiAoIVJlZ0V4cCgnKF58XFxcXHMpJyArIGJpdC5jbGFzc2VzW3BhcnRdICsgJyhcXFxcc3wkKScpLnRlc3QoY2xhc3NOYW1lKSkgcmV0dXJuIGZhbHNlXHJcbiAgICB9XHJcblxyXG4gICAgdmFyIG5hbWUsIHZhbHVlXHJcblxyXG4gICAgaWYgKGJpdC5hdHRyaWJ1dGVzKSBmb3IgKGkgPSAwOyBwYXJ0ID0gYml0LmF0dHJpYnV0ZXNbaSsrXTspe1xyXG5cclxuICAgICAgICB2YXIgb3BlcmF0b3IgID0gcGFydC5vcGVyYXRvcixcclxuICAgICAgICAgICAgZXNjYXBlZCAgID0gcGFydC5lc2NhcGVkVmFsdWVcclxuXHJcbiAgICAgICAgbmFtZSAgPSBwYXJ0Lm5hbWVcclxuICAgICAgICB2YWx1ZSA9IHBhcnQudmFsdWVcclxuXHJcbiAgICAgICAgaWYgKCFvcGVyYXRvcil7XHJcblxyXG4gICAgICAgICAgICBpZiAoIXRoaXMuaGFzQXR0cmlidXRlKG5vZGUsIG5hbWUpKSByZXR1cm4gZmFsc2VcclxuXHJcbiAgICAgICAgfSBlbHNlIHtcclxuXHJcbiAgICAgICAgICAgIHZhciBhY3R1YWwgPSB0aGlzLmdldEF0dHJpYnV0ZShub2RlLCBuYW1lKVxyXG4gICAgICAgICAgICBpZiAoYWN0dWFsID09IG51bGwpIHJldHVybiBmYWxzZVxyXG5cclxuICAgICAgICAgICAgc3dpdGNoIChvcGVyYXRvcil7XHJcbiAgICAgICAgICAgICAgICBjYXNlICdePScgOiBpZiAoIVJlZ0V4cCggICAgICAnXicgKyBlc2NhcGVkICAgICAgICAgICAgKS50ZXN0KGFjdHVhbCkpIHJldHVybiBmYWxzZTsgYnJlYWtcclxuICAgICAgICAgICAgICAgIGNhc2UgJyQ9JyA6IGlmICghUmVnRXhwKCAgICAgICAgICAgIGVzY2FwZWQgKyAnJCcgICAgICApLnRlc3QoYWN0dWFsKSkgcmV0dXJuIGZhbHNlOyBicmVha1xyXG4gICAgICAgICAgICAgICAgY2FzZSAnfj0nIDogaWYgKCFSZWdFeHAoJyhefFxcXFxzKScgKyBlc2NhcGVkICsgJyhcXFxcc3wkKScpLnRlc3QoYWN0dWFsKSkgcmV0dXJuIGZhbHNlOyBicmVha1xyXG4gICAgICAgICAgICAgICAgY2FzZSAnfD0nIDogaWYgKCFSZWdFeHAoICAgICAgJ14nICsgZXNjYXBlZCArICcoLXwkKScgICkudGVzdChhY3R1YWwpKSByZXR1cm4gZmFsc2U7IGJyZWFrXHJcblxyXG4gICAgICAgICAgICAgICAgY2FzZSAnPScgIDogaWYgKGFjdHVhbCAhPT0gdmFsdWUpIHJldHVybiBmYWxzZTsgYnJlYWtcclxuICAgICAgICAgICAgICAgIGNhc2UgJyo9JyA6IGlmIChhY3R1YWwuaW5kZXhPZih2YWx1ZSkgPT09IC0xKSByZXR1cm4gZmFsc2U7IGJyZWFrXHJcbiAgICAgICAgICAgICAgICBkZWZhdWx0ICAgOiByZXR1cm4gZmFsc2VcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICB9XHJcbiAgICB9XHJcblxyXG4gICAgaWYgKGJpdC5wc2V1ZG9zKSBmb3IgKGkgPSAwOyBwYXJ0ID0gYml0LnBzZXVkb3NbaSsrXTspe1xyXG5cclxuICAgICAgICBuYW1lICA9IHBhcnQubmFtZVxyXG4gICAgICAgIHZhbHVlID0gcGFydC52YWx1ZVxyXG5cclxuICAgICAgICBpZiAocHNldWRvc1tuYW1lXSkgcmV0dXJuIHBzZXVkb3NbbmFtZV0uY2FsbChub2RlLCB2YWx1ZSlcclxuXHJcbiAgICAgICAgaWYgKHZhbHVlICE9IG51bGwpe1xyXG4gICAgICAgICAgICBpZiAodGhpcy5nZXRBdHRyaWJ1dGUobm9kZSwgbmFtZSkgIT09IHZhbHVlKSByZXR1cm4gZmFsc2VcclxuICAgICAgICB9IGVsc2Uge1xyXG4gICAgICAgICAgICBpZiAoIXRoaXMuaGFzQXR0cmlidXRlKG5vZGUsIG5hbWUpKSByZXR1cm4gZmFsc2VcclxuICAgICAgICB9XHJcblxyXG4gICAgfVxyXG5cclxuICAgIHJldHVybiB0cnVlXHJcblxyXG59XHJcblxyXG5GaW5kZXIucHJvdG90eXBlLm1hdGNoZXMgPSBmdW5jdGlvbihub2RlLCBleHByZXNzaW9uKXtcclxuXHJcbiAgICB2YXIgZXhwcmVzc2lvbnMgPSBwYXJzZShleHByZXNzaW9uKVxyXG5cclxuICAgIGlmIChleHByZXNzaW9ucy5sZW5ndGggPT09IDEgJiYgZXhwcmVzc2lvbnNbMF0ubGVuZ3RoID09PSAxKXsgLy8gc2ltcGxlc3QgbWF0Y2hcclxuICAgICAgICByZXR1cm4gdGhpcy5tYXRjaChub2RlLCBleHByZXNzaW9uc1swXVswXSlcclxuICAgIH1cclxuXHJcbiAgICAvLyBUT0RPOiBtb3JlIGZ1bmN0aW9uYWwgdGVzdHMgP1xyXG5cclxuICAgIGlmICghc2xpY2subm9RU0EgJiYgdGhpcy5tYXRjaGVzU2VsZWN0b3Ipe1xyXG4gICAgICAgIHZhciBtYXRjaGVzID0gdGhpcy5tYXRjaGVzU2VsZWN0b3Iobm9kZSwgZXhwcmVzc2lvbnMpXHJcbiAgICAgICAgaWYgKG1hdGNoZXMgIT09IG51bGwpIHJldHVybiBtYXRjaGVzXHJcbiAgICB9XHJcblxyXG4gICAgdmFyIG5vZGVzID0gdGhpcy5zZWFyY2godGhpcy5kb2N1bWVudCwgZXhwcmVzc2lvbiwge2xlbmd0aDogMH0pXHJcblxyXG4gICAgZm9yICh2YXIgaSA9IDAsIHJlczsgcmVzID0gbm9kZXNbaSsrXTspIGlmIChub2RlID09PSByZXMpIHJldHVybiB0cnVlXHJcbiAgICByZXR1cm4gZmFsc2VcclxuXHJcbn1cclxuXHJcbnZhciBmaW5kZXJzID0ge31cclxuXHJcbnZhciBmaW5kZXIgPSBmdW5jdGlvbihjb250ZXh0KXtcclxuICAgIHZhciBkb2MgPSBjb250ZXh0IHx8IGRvY3VtZW50XHJcbiAgICBpZiAoZG9jLm93bmVyRG9jdW1lbnQpIGRvYyA9IGRvYy5vd25lckRvY3VtZW50XHJcbiAgICBlbHNlIGlmIChkb2MuZG9jdW1lbnQpIGRvYyA9IGRvYy5kb2N1bWVudFxyXG5cclxuICAgIGlmIChkb2Mubm9kZVR5cGUgIT09IDkpIHRocm93IG5ldyBUeXBlRXJyb3IoXCJpbnZhbGlkIGRvY3VtZW50XCIpXHJcblxyXG4gICAgdmFyIHVpZCA9IHVuaXF1ZUlEKGRvYylcclxuICAgIHJldHVybiBmaW5kZXJzW3VpZF0gfHwgKGZpbmRlcnNbdWlkXSA9IG5ldyBGaW5kZXIoZG9jKSlcclxufVxyXG5cclxuLy8gLi4uIEFQSSAuLi5cclxuXHJcbnZhciBzbGljayA9IGZ1bmN0aW9uKGV4cHJlc3Npb24sIGNvbnRleHQpe1xyXG4gICAgcmV0dXJuIHNsaWNrLnNlYXJjaChleHByZXNzaW9uLCBjb250ZXh0KVxyXG59XHJcblxyXG5zbGljay5zZWFyY2ggPSBmdW5jdGlvbihleHByZXNzaW9uLCBjb250ZXh0LCBmb3VuZCl7XHJcbiAgICByZXR1cm4gZmluZGVyKGNvbnRleHQpLnNlYXJjaChjb250ZXh0LCBleHByZXNzaW9uLCBmb3VuZClcclxufVxyXG5cclxuc2xpY2suZmluZCA9IGZ1bmN0aW9uKGV4cHJlc3Npb24sIGNvbnRleHQpe1xyXG4gICAgcmV0dXJuIGZpbmRlcihjb250ZXh0KS5zZWFyY2goY29udGV4dCwgZXhwcmVzc2lvbilbMF0gfHwgbnVsbFxyXG59XHJcblxyXG5zbGljay5nZXRBdHRyaWJ1dGUgPSBmdW5jdGlvbihub2RlLCBuYW1lKXtcclxuICAgIHJldHVybiBmaW5kZXIobm9kZSkuZ2V0QXR0cmlidXRlKG5vZGUsIG5hbWUpXHJcbn1cclxuXHJcbnNsaWNrLmhhc0F0dHJpYnV0ZSA9IGZ1bmN0aW9uKG5vZGUsIG5hbWUpe1xyXG4gICAgcmV0dXJuIGZpbmRlcihub2RlKS5oYXNBdHRyaWJ1dGUobm9kZSwgbmFtZSlcclxufVxyXG5cclxuc2xpY2suY29udGFpbnMgPSBmdW5jdGlvbihjb250ZXh0LCBub2RlKXtcclxuICAgIHJldHVybiBmaW5kZXIoY29udGV4dCkuY29udGFpbnMoY29udGV4dCwgbm9kZSlcclxufVxyXG5cclxuc2xpY2subWF0Y2hlcyA9IGZ1bmN0aW9uKG5vZGUsIGV4cHJlc3Npb24pe1xyXG4gICAgcmV0dXJuIGZpbmRlcihub2RlKS5tYXRjaGVzKG5vZGUsIGV4cHJlc3Npb24pXHJcbn1cclxuXHJcbnNsaWNrLnNvcnQgPSBmdW5jdGlvbihub2Rlcyl7XHJcbiAgICBpZiAobm9kZXMgJiYgbm9kZXMubGVuZ3RoID4gMSkgZmluZGVyKG5vZGVzWzBdKS5zb3J0KG5vZGVzKVxyXG4gICAgcmV0dXJuIG5vZGVzXHJcbn1cclxuXHJcbnNsaWNrLnBhcnNlID0gcGFyc2U7XHJcblxyXG4vLyBzbGljay5kZWJ1ZyA9IHRydWVcclxuLy8gc2xpY2subm9RU0EgID0gdHJ1ZVxyXG5cclxubW9kdWxlLmV4cG9ydHMgPSBzbGlja1xyXG4iLCIvKlxyXG5zbGlja1xyXG4qL1widXNlIHN0cmljdFwiXHJcblxyXG5tb2R1bGUuZXhwb3J0cyA9IFwiZG9jdW1lbnRcIiBpbiBnbG9iYWwgPyByZXF1aXJlKFwiLi9maW5kZXJcIikgOiB7IHBhcnNlOiByZXF1aXJlKFwiLi9wYXJzZXJcIikgfVxyXG4iLCIvKlxyXG5TbGljayBQYXJzZXJcclxuIC0gb3JpZ2luYWxseSBjcmVhdGVkIGJ5IHRoZSBhbG1pZ2h0eSBUaG9tYXMgQXlsb3R0IDxAc3VidGxlZ3JhZGllbnQ+IChodHRwOi8vc3VidGxlZ3JhZGllbnQuY29tKVxyXG4qL1widXNlIHN0cmljdFwiXHJcblxyXG4vLyBOb3RhYmxlIGNoYW5nZXMgZnJvbSBTbGljay5QYXJzZXIgMS4wLnhcclxuXHJcbi8vIFRoZSBwYXJzZXIgbm93IHVzZXMgMiBjbGFzc2VzOiBFeHByZXNzaW9ucyBhbmQgRXhwcmVzc2lvblxyXG4vLyBgbmV3IEV4cHJlc3Npb25zYCBwcm9kdWNlcyBhbiBhcnJheS1saWtlIG9iamVjdCBjb250YWluaW5nIGEgbGlzdCBvZiBFeHByZXNzaW9uIG9iamVjdHNcclxuLy8gLSBFeHByZXNzaW9uczo6dG9TdHJpbmcoKSBwcm9kdWNlcyBhIGNsZWFuZWQgdXAgZXhwcmVzc2lvbnMgc3RyaW5nXHJcbi8vIGBuZXcgRXhwcmVzc2lvbmAgcHJvZHVjZXMgYW4gYXJyYXktbGlrZSBvYmplY3RcclxuLy8gLSBFeHByZXNzaW9uOjp0b1N0cmluZygpIHByb2R1Y2VzIGEgY2xlYW5lZCB1cCBleHByZXNzaW9uIHN0cmluZ1xyXG4vLyBUaGUgb25seSBleHBvc2VkIG1ldGhvZCBpcyBwYXJzZSwgd2hpY2ggcHJvZHVjZXMgYSAoY2FjaGVkKSBgbmV3IEV4cHJlc3Npb25zYCBpbnN0YW5jZVxyXG4vLyBwYXJzZWQucmF3IGlzIG5vIGxvbmdlciBwcmVzZW50LCB1c2UgLnRvU3RyaW5nKClcclxuLy8gcGFyc2VkLmV4cHJlc3Npb24gaXMgbm93IHVzZWxlc3MsIGp1c3QgdXNlIHRoZSBpbmRpY2VzXHJcbi8vIHBhcnNlZC5yZXZlcnNlKCkgaGFzIGJlZW4gcmVtb3ZlZCBmb3Igbm93LCBkdWUgdG8gaXRzIGFwcGFyZW50IHVzZWxlc3NuZXNzXHJcbi8vIE90aGVyIGNoYW5nZXMgaW4gdGhlIEV4cHJlc3Npb25zIG9iamVjdDpcclxuLy8gLSBjbGFzc05hbWVzIGFyZSBub3cgdW5pcXVlLCBhbmQgc2F2ZSBib3RoIGVzY2FwZWQgYW5kIHVuZXNjYXBlZCB2YWx1ZXNcclxuLy8gLSBhdHRyaWJ1dGVzIG5vdyBzYXZlIGJvdGggZXNjYXBlZCBhbmQgdW5lc2NhcGVkIHZhbHVlc1xyXG4vLyAtIHBzZXVkb3Mgbm93IHNhdmUgYm90aCBlc2NhcGVkIGFuZCB1bmVzY2FwZWQgdmFsdWVzXHJcblxyXG52YXIgZXNjYXBlUmUgICA9IC8oWy0uKis/XiR7fSgpfFtcXF1cXC9cXFxcXSkvZyxcclxuICAgIHVuZXNjYXBlUmUgPSAvXFxcXC9nXHJcblxyXG52YXIgZXNjYXBlID0gZnVuY3Rpb24oc3RyaW5nKXtcclxuICAgIC8vIFhSZWdFeHAgdjIuMC4wLWJldGEtM1xyXG4gICAgLy8gwqsgaHR0cHM6Ly9naXRodWIuY29tL3NsZXZpdGhhbi9YUmVnRXhwL2Jsb2IvbWFzdGVyL3NyYy94cmVnZXhwLmpzXHJcbiAgICByZXR1cm4gKHN0cmluZyArIFwiXCIpLnJlcGxhY2UoZXNjYXBlUmUsICdcXFxcJDEnKVxyXG59XHJcblxyXG52YXIgdW5lc2NhcGUgPSBmdW5jdGlvbihzdHJpbmcpe1xyXG4gICAgcmV0dXJuIChzdHJpbmcgKyBcIlwiKS5yZXBsYWNlKHVuZXNjYXBlUmUsICcnKVxyXG59XHJcblxyXG52YXIgc2xpY2tSZSA9IFJlZ0V4cChcclxuLypcclxuIyEvdXNyL2Jpbi9lbnYgcnVieVxyXG5wdXRzIFwiXFx0XFx0XCIgKyBEQVRBLnJlYWQuZ3N1YigvXFwoXFw/eFxcKXxcXHMrIy4qJHxcXHMrfFxcXFwkfFxcXFxuLywnJylcclxuX19FTkRfX1xyXG4gICAgXCIoP3gpXig/OlxcXHJcbiAgICAgIFxcXFxzKiAoICwgKSBcXFxccyogICAgICAgICAgICAgICAjIFNlcGFyYXRvciAgICAgICAgICBcXG5cXFxyXG4gICAgfCBcXFxccyogKCA8Y29tYmluYXRvcj4rICkgXFxcXHMqICAgIyBDb21iaW5hdG9yICAgICAgICAgXFxuXFxcclxuICAgIHwgICAgICAoIFxcXFxzKyApICAgICAgICAgICAgICAgICAjIENvbWJpbmF0b3JDaGlsZHJlbiBcXG5cXFxyXG4gICAgfCAgICAgICggPHVuaWNvZGU+KyB8IFxcXFwqICkgICAgICMgVGFnICAgICAgICAgICAgICAgIFxcblxcXHJcbiAgICB8IFxcXFwjICAoIDx1bmljb2RlPisgICAgICAgKSAgICAgIyBJRCAgICAgICAgICAgICAgICAgXFxuXFxcclxuICAgIHwgXFxcXC4gICggPHVuaWNvZGU+KyAgICAgICApICAgICAjIENsYXNzTmFtZSAgICAgICAgICBcXG5cXFxyXG4gICAgfCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAjIEF0dHJpYnV0ZSAgICAgICAgICBcXG5cXFxyXG4gICAgXFxcXFsgIFxcXHJcbiAgICAgICAgXFxcXHMqICg8dW5pY29kZTE+KykgICg/OiAgXFxcclxuICAgICAgICAgICAgXFxcXHMqIChbKl4kIX58XT89KSAgKD86ICBcXFxyXG4gICAgICAgICAgICAgICAgXFxcXHMqICg/OlxcXHJcbiAgICAgICAgICAgICAgICAgICAgKFtcXFwiJ10/KSguKj8pXFxcXDkgXFxcclxuICAgICAgICAgICAgICAgIClcXFxyXG4gICAgICAgICAgICApICBcXFxyXG4gICAgICAgICk/ICBcXFxccyogIFxcXHJcbiAgICBcXFxcXSg/IVxcXFxdKSBcXG5cXFxyXG4gICAgfCAgIDorICggPHVuaWNvZGU+KyApKD86XFxcclxuICAgIFxcXFwoICg/OlxcXHJcbiAgICAgICAgKD86KFtcXFwiJ10pKFteXFxcXDEyXSopXFxcXDEyKXwoKD86XFxcXChbXildK1xcXFwpfFteKCldKikrKVxcXHJcbiAgICApIFxcXFwpXFxcclxuICAgICk/XFxcclxuICAgIClcIlxyXG4qL1xyXG5cIl4oPzpcXFxccyooLClcXFxccyp8XFxcXHMqKDxjb21iaW5hdG9yPispXFxcXHMqfChcXFxccyspfCg8dW5pY29kZT4rfFxcXFwqKXxcXFxcIyg8dW5pY29kZT4rKXxcXFxcLig8dW5pY29kZT4rKXxcXFxcW1xcXFxzKig8dW5pY29kZTE+KykoPzpcXFxccyooWypeJCF+fF0/PSkoPzpcXFxccyooPzooW1xcXCInXT8pKC4qPylcXFxcOSkpKT9cXFxccypcXFxcXSg/IVxcXFxdKXwoOispKDx1bmljb2RlPispKD86XFxcXCgoPzooPzooW1xcXCInXSkoW15cXFxcMTNdKilcXFxcMTMpfCgoPzpcXFxcKFteKV0rXFxcXCl8W14oKV0qKSspKVxcXFwpKT8pXCJcclxuICAgIC5yZXBsYWNlKC88Y29tYmluYXRvcj4vLCAnWycgKyBlc2NhcGUoXCI+K35gIUAkJV4mPXt9XFxcXDs8L1wiKSArICddJylcclxuICAgIC5yZXBsYWNlKC88dW5pY29kZT4vZywgJyg/OltcXFxcd1xcXFx1MDBhMS1cXFxcdUZGRkYtXXxcXFxcXFxcXFteXFxcXHMwLTlhLWZdKScpXHJcbiAgICAucmVwbGFjZSgvPHVuaWNvZGUxPi9nLCAnKD86WzpcXFxcd1xcXFx1MDBhMS1cXFxcdUZGRkYtXXxcXFxcXFxcXFteXFxcXHMwLTlhLWZdKScpXHJcbilcclxuXHJcbi8vIFBhcnRcclxuXHJcbnZhciBQYXJ0ID0gZnVuY3Rpb24gUGFydChjb21iaW5hdG9yKXtcclxuICAgIHRoaXMuY29tYmluYXRvciA9IGNvbWJpbmF0b3IgfHwgXCIgXCJcclxuICAgIHRoaXMudGFnID0gXCIqXCJcclxufVxyXG5cclxuUGFydC5wcm90b3R5cGUudG9TdHJpbmcgPSBmdW5jdGlvbigpe1xyXG5cclxuICAgIGlmICghdGhpcy5yYXcpe1xyXG5cclxuICAgICAgICB2YXIgeHByID0gXCJcIiwgaywgcGFydFxyXG5cclxuICAgICAgICB4cHIgKz0gdGhpcy50YWcgfHwgXCIqXCJcclxuICAgICAgICBpZiAodGhpcy5pZCkgeHByICs9IFwiI1wiICsgdGhpcy5pZFxyXG4gICAgICAgIGlmICh0aGlzLmNsYXNzZXMpIHhwciArPSBcIi5cIiArIHRoaXMuY2xhc3NMaXN0LmpvaW4oXCIuXCIpXHJcbiAgICAgICAgaWYgKHRoaXMuYXR0cmlidXRlcykgZm9yIChrID0gMDsgcGFydCA9IHRoaXMuYXR0cmlidXRlc1trKytdOyl7XHJcbiAgICAgICAgICAgIHhwciArPSBcIltcIiArIHBhcnQubmFtZSArIChwYXJ0Lm9wZXJhdG9yID8gcGFydC5vcGVyYXRvciArICdcIicgKyBwYXJ0LnZhbHVlICsgJ1wiJyA6ICcnKSArIFwiXVwiXHJcbiAgICAgICAgfVxyXG4gICAgICAgIGlmICh0aGlzLnBzZXVkb3MpIGZvciAoayA9IDA7IHBhcnQgPSB0aGlzLnBzZXVkb3NbaysrXTspe1xyXG4gICAgICAgICAgICB4cHIgKz0gXCI6XCIgKyBwYXJ0Lm5hbWVcclxuICAgICAgICAgICAgaWYgKHBhcnQudmFsdWUpIHhwciArPSBcIihcIiArIHBhcnQudmFsdWUgKyBcIilcIlxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5yYXcgPSB4cHJcclxuXHJcbiAgICB9XHJcblxyXG4gICAgcmV0dXJuIHRoaXMucmF3XHJcbn1cclxuXHJcbi8vIEV4cHJlc3Npb25cclxuXHJcbnZhciBFeHByZXNzaW9uID0gZnVuY3Rpb24gRXhwcmVzc2lvbigpe1xyXG4gICAgdGhpcy5sZW5ndGggPSAwXHJcbn1cclxuXHJcbkV4cHJlc3Npb24ucHJvdG90eXBlLnRvU3RyaW5nID0gZnVuY3Rpb24oKXtcclxuXHJcbiAgICBpZiAoIXRoaXMucmF3KXtcclxuXHJcbiAgICAgICAgdmFyIHhwciA9IFwiXCJcclxuXHJcbiAgICAgICAgZm9yICh2YXIgaiA9IDAsIGJpdDsgYml0ID0gdGhpc1tqKytdOyl7XHJcbiAgICAgICAgICAgIGlmIChqICE9PSAxKSB4cHIgKz0gXCIgXCJcclxuICAgICAgICAgICAgaWYgKGJpdC5jb21iaW5hdG9yICE9PSBcIiBcIikgeHByICs9IGJpdC5jb21iaW5hdG9yICsgXCIgXCJcclxuICAgICAgICAgICAgeHByICs9IGJpdFxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5yYXcgPSB4cHJcclxuXHJcbiAgICB9XHJcblxyXG4gICAgcmV0dXJuIHRoaXMucmF3XHJcbn1cclxuXHJcbnZhciByZXBsYWNlciA9IGZ1bmN0aW9uKFxyXG4gICAgcmF3TWF0Y2gsXHJcblxyXG4gICAgc2VwYXJhdG9yLFxyXG4gICAgY29tYmluYXRvcixcclxuICAgIGNvbWJpbmF0b3JDaGlsZHJlbixcclxuXHJcbiAgICB0YWdOYW1lLFxyXG4gICAgaWQsXHJcbiAgICBjbGFzc05hbWUsXHJcblxyXG4gICAgYXR0cmlidXRlS2V5LFxyXG4gICAgYXR0cmlidXRlT3BlcmF0b3IsXHJcbiAgICBhdHRyaWJ1dGVRdW90ZSxcclxuICAgIGF0dHJpYnV0ZVZhbHVlLFxyXG5cclxuICAgIHBzZXVkb01hcmtlcixcclxuICAgIHBzZXVkb0NsYXNzLFxyXG4gICAgcHNldWRvUXVvdGUsXHJcbiAgICBwc2V1ZG9DbGFzc1F1b3RlZFZhbHVlLFxyXG4gICAgcHNldWRvQ2xhc3NWYWx1ZVxyXG4pe1xyXG5cclxuICAgIHZhciBleHByZXNzaW9uLCBjdXJyZW50XHJcblxyXG4gICAgaWYgKHNlcGFyYXRvciB8fCAhdGhpcy5sZW5ndGgpe1xyXG4gICAgICAgIGV4cHJlc3Npb24gPSB0aGlzW3RoaXMubGVuZ3RoKytdID0gbmV3IEV4cHJlc3Npb25cclxuICAgICAgICBpZiAoc2VwYXJhdG9yKSByZXR1cm4gJydcclxuICAgIH1cclxuXHJcbiAgICBpZiAoIWV4cHJlc3Npb24pIGV4cHJlc3Npb24gPSB0aGlzW3RoaXMubGVuZ3RoIC0gMV1cclxuXHJcbiAgICBpZiAoY29tYmluYXRvciB8fCBjb21iaW5hdG9yQ2hpbGRyZW4gfHwgIWV4cHJlc3Npb24ubGVuZ3RoKXtcclxuICAgICAgICBjdXJyZW50ID0gZXhwcmVzc2lvbltleHByZXNzaW9uLmxlbmd0aCsrXSA9IG5ldyBQYXJ0KGNvbWJpbmF0b3IpXHJcbiAgICB9XHJcblxyXG4gICAgaWYgKCFjdXJyZW50KSBjdXJyZW50ID0gZXhwcmVzc2lvbltleHByZXNzaW9uLmxlbmd0aCAtIDFdXHJcblxyXG4gICAgaWYgKHRhZ05hbWUpe1xyXG5cclxuICAgICAgICBjdXJyZW50LnRhZyA9IHVuZXNjYXBlKHRhZ05hbWUpXHJcblxyXG4gICAgfSBlbHNlIGlmIChpZCl7XHJcblxyXG4gICAgICAgIGN1cnJlbnQuaWQgPSB1bmVzY2FwZShpZClcclxuXHJcbiAgICB9IGVsc2UgaWYgKGNsYXNzTmFtZSl7XHJcblxyXG4gICAgICAgIHZhciB1bmVzY2FwZWQgPSB1bmVzY2FwZShjbGFzc05hbWUpXHJcblxyXG4gICAgICAgIHZhciBjbGFzc2VzID0gY3VycmVudC5jbGFzc2VzIHx8IChjdXJyZW50LmNsYXNzZXMgPSB7fSlcclxuICAgICAgICBpZiAoIWNsYXNzZXNbdW5lc2NhcGVkXSl7XHJcbiAgICAgICAgICAgIGNsYXNzZXNbdW5lc2NhcGVkXSA9IGVzY2FwZShjbGFzc05hbWUpXHJcbiAgICAgICAgICAgIHZhciBjbGFzc0xpc3QgPSBjdXJyZW50LmNsYXNzTGlzdCB8fCAoY3VycmVudC5jbGFzc0xpc3QgPSBbXSlcclxuICAgICAgICAgICAgY2xhc3NMaXN0LnB1c2godW5lc2NhcGVkKVxyXG4gICAgICAgICAgICBjbGFzc0xpc3Quc29ydCgpXHJcbiAgICAgICAgfVxyXG5cclxuICAgIH0gZWxzZSBpZiAocHNldWRvQ2xhc3Mpe1xyXG5cclxuICAgICAgICBwc2V1ZG9DbGFzc1ZhbHVlID0gcHNldWRvQ2xhc3NWYWx1ZSB8fCBwc2V1ZG9DbGFzc1F1b3RlZFZhbHVlXHJcblxyXG4gICAgICAgIDsoY3VycmVudC5wc2V1ZG9zIHx8IChjdXJyZW50LnBzZXVkb3MgPSBbXSkpLnB1c2goe1xyXG4gICAgICAgICAgICB0eXBlICAgICAgICAgOiBwc2V1ZG9NYXJrZXIubGVuZ3RoID09IDEgPyAnY2xhc3MnIDogJ2VsZW1lbnQnLFxyXG4gICAgICAgICAgICBuYW1lICAgICAgICAgOiB1bmVzY2FwZShwc2V1ZG9DbGFzcyksXHJcbiAgICAgICAgICAgIGVzY2FwZWROYW1lICA6IGVzY2FwZShwc2V1ZG9DbGFzcyksXHJcbiAgICAgICAgICAgIHZhbHVlICAgICAgICA6IHBzZXVkb0NsYXNzVmFsdWUgPyB1bmVzY2FwZShwc2V1ZG9DbGFzc1ZhbHVlKSA6IG51bGwsXHJcbiAgICAgICAgICAgIGVzY2FwZWRWYWx1ZSA6IHBzZXVkb0NsYXNzVmFsdWUgPyBlc2NhcGUocHNldWRvQ2xhc3NWYWx1ZSkgOiBudWxsXHJcbiAgICAgICAgfSlcclxuXHJcbiAgICB9IGVsc2UgaWYgKGF0dHJpYnV0ZUtleSl7XHJcblxyXG4gICAgICAgIGF0dHJpYnV0ZVZhbHVlID0gYXR0cmlidXRlVmFsdWUgPyBlc2NhcGUoYXR0cmlidXRlVmFsdWUpIDogbnVsbFxyXG5cclxuICAgICAgICA7KGN1cnJlbnQuYXR0cmlidXRlcyB8fCAoY3VycmVudC5hdHRyaWJ1dGVzID0gW10pKS5wdXNoKHtcclxuICAgICAgICAgICAgb3BlcmF0b3IgICAgIDogYXR0cmlidXRlT3BlcmF0b3IsXHJcbiAgICAgICAgICAgIG5hbWUgICAgICAgICA6IHVuZXNjYXBlKGF0dHJpYnV0ZUtleSksXHJcbiAgICAgICAgICAgIGVzY2FwZWROYW1lICA6IGVzY2FwZShhdHRyaWJ1dGVLZXkpLFxyXG4gICAgICAgICAgICB2YWx1ZSAgICAgICAgOiBhdHRyaWJ1dGVWYWx1ZSA/IHVuZXNjYXBlKGF0dHJpYnV0ZVZhbHVlKSA6IG51bGwsXHJcbiAgICAgICAgICAgIGVzY2FwZWRWYWx1ZSA6IGF0dHJpYnV0ZVZhbHVlID8gZXNjYXBlKGF0dHJpYnV0ZVZhbHVlKSA6IG51bGxcclxuICAgICAgICB9KVxyXG5cclxuICAgIH1cclxuXHJcbiAgICByZXR1cm4gJydcclxuXHJcbn1cclxuXHJcbi8vIEV4cHJlc3Npb25zXHJcblxyXG52YXIgRXhwcmVzc2lvbnMgPSBmdW5jdGlvbiBFeHByZXNzaW9ucyhleHByZXNzaW9uKXtcclxuICAgIHRoaXMubGVuZ3RoID0gMFxyXG5cclxuICAgIHZhciBzZWxmID0gdGhpc1xyXG5cclxuICAgIHZhciBvcmlnaW5hbCA9IGV4cHJlc3Npb24sIHJlcGxhY2VkXHJcblxyXG4gICAgd2hpbGUgKGV4cHJlc3Npb24pe1xyXG4gICAgICAgIHJlcGxhY2VkID0gZXhwcmVzc2lvbi5yZXBsYWNlKHNsaWNrUmUsIGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgICAgIHJldHVybiByZXBsYWNlci5hcHBseShzZWxmLCBhcmd1bWVudHMpXHJcbiAgICAgICAgfSlcclxuICAgICAgICBpZiAocmVwbGFjZWQgPT09IGV4cHJlc3Npb24pIHRocm93IG5ldyBFcnJvcihvcmlnaW5hbCArICcgaXMgYW4gaW52YWxpZCBleHByZXNzaW9uJylcclxuICAgICAgICBleHByZXNzaW9uID0gcmVwbGFjZWRcclxuICAgIH1cclxufVxyXG5cclxuRXhwcmVzc2lvbnMucHJvdG90eXBlLnRvU3RyaW5nID0gZnVuY3Rpb24oKXtcclxuICAgIGlmICghdGhpcy5yYXcpe1xyXG4gICAgICAgIHZhciBleHByZXNzaW9ucyA9IFtdXHJcbiAgICAgICAgZm9yICh2YXIgaSA9IDAsIGV4cHJlc3Npb247IGV4cHJlc3Npb24gPSB0aGlzW2krK107KSBleHByZXNzaW9ucy5wdXNoKGV4cHJlc3Npb24pXHJcbiAgICAgICAgdGhpcy5yYXcgPSBleHByZXNzaW9ucy5qb2luKFwiLCBcIilcclxuICAgIH1cclxuXHJcbiAgICByZXR1cm4gdGhpcy5yYXdcclxufVxyXG5cclxudmFyIGNhY2hlID0ge31cclxuXHJcbnZhciBwYXJzZSA9IGZ1bmN0aW9uKGV4cHJlc3Npb24pe1xyXG4gICAgaWYgKGV4cHJlc3Npb24gPT0gbnVsbCkgcmV0dXJuIG51bGxcclxuICAgIGV4cHJlc3Npb24gPSAoJycgKyBleHByZXNzaW9uKS5yZXBsYWNlKC9eXFxzK3xcXHMrJC9nLCAnJylcclxuICAgIHJldHVybiBjYWNoZVtleHByZXNzaW9uXSB8fCAoY2FjaGVbZXhwcmVzc2lvbl0gPSBuZXcgRXhwcmVzc2lvbnMoZXhwcmVzc2lvbikpXHJcbn1cclxuXHJcbm1vZHVsZS5leHBvcnRzID0gcGFyc2VcclxuIiwiLy8gc2hpbSBmb3IgdXNpbmcgcHJvY2VzcyBpbiBicm93c2VyXG52YXIgcHJvY2VzcyA9IG1vZHVsZS5leHBvcnRzID0ge307XG5cbi8vIGNhY2hlZCBmcm9tIHdoYXRldmVyIGdsb2JhbCBpcyBwcmVzZW50IHNvIHRoYXQgdGVzdCBydW5uZXJzIHRoYXQgc3R1YiBpdFxuLy8gZG9uJ3QgYnJlYWsgdGhpbmdzLiAgQnV0IHdlIG5lZWQgdG8gd3JhcCBpdCBpbiBhIHRyeSBjYXRjaCBpbiBjYXNlIGl0IGlzXG4vLyB3cmFwcGVkIGluIHN0cmljdCBtb2RlIGNvZGUgd2hpY2ggZG9lc24ndCBkZWZpbmUgYW55IGdsb2JhbHMuICBJdCdzIGluc2lkZSBhXG4vLyBmdW5jdGlvbiBiZWNhdXNlIHRyeS9jYXRjaGVzIGRlb3B0aW1pemUgaW4gY2VydGFpbiBlbmdpbmVzLlxuXG52YXIgY2FjaGVkU2V0VGltZW91dDtcbnZhciBjYWNoZWRDbGVhclRpbWVvdXQ7XG5cbmZ1bmN0aW9uIGRlZmF1bHRTZXRUaW1vdXQoKSB7XG4gICAgdGhyb3cgbmV3IEVycm9yKCdzZXRUaW1lb3V0IGhhcyBub3QgYmVlbiBkZWZpbmVkJyk7XG59XG5mdW5jdGlvbiBkZWZhdWx0Q2xlYXJUaW1lb3V0ICgpIHtcbiAgICB0aHJvdyBuZXcgRXJyb3IoJ2NsZWFyVGltZW91dCBoYXMgbm90IGJlZW4gZGVmaW5lZCcpO1xufVxuKGZ1bmN0aW9uICgpIHtcbiAgICB0cnkge1xuICAgICAgICBpZiAodHlwZW9mIHNldFRpbWVvdXQgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgICAgIGNhY2hlZFNldFRpbWVvdXQgPSBzZXRUaW1lb3V0O1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgY2FjaGVkU2V0VGltZW91dCA9IGRlZmF1bHRTZXRUaW1vdXQ7XG4gICAgICAgIH1cbiAgICB9IGNhdGNoIChlKSB7XG4gICAgICAgIGNhY2hlZFNldFRpbWVvdXQgPSBkZWZhdWx0U2V0VGltb3V0O1xuICAgIH1cbiAgICB0cnkge1xuICAgICAgICBpZiAodHlwZW9mIGNsZWFyVGltZW91dCA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgY2FjaGVkQ2xlYXJUaW1lb3V0ID0gY2xlYXJUaW1lb3V0O1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgY2FjaGVkQ2xlYXJUaW1lb3V0ID0gZGVmYXVsdENsZWFyVGltZW91dDtcbiAgICAgICAgfVxuICAgIH0gY2F0Y2ggKGUpIHtcbiAgICAgICAgY2FjaGVkQ2xlYXJUaW1lb3V0ID0gZGVmYXVsdENsZWFyVGltZW91dDtcbiAgICB9XG59ICgpKVxuZnVuY3Rpb24gcnVuVGltZW91dChmdW4pIHtcbiAgICBpZiAoY2FjaGVkU2V0VGltZW91dCA9PT0gc2V0VGltZW91dCkge1xuICAgICAgICAvL25vcm1hbCBlbnZpcm9tZW50cyBpbiBzYW5lIHNpdHVhdGlvbnNcbiAgICAgICAgcmV0dXJuIHNldFRpbWVvdXQoZnVuLCAwKTtcbiAgICB9XG4gICAgLy8gaWYgc2V0VGltZW91dCB3YXNuJ3QgYXZhaWxhYmxlIGJ1dCB3YXMgbGF0dGVyIGRlZmluZWRcbiAgICBpZiAoKGNhY2hlZFNldFRpbWVvdXQgPT09IGRlZmF1bHRTZXRUaW1vdXQgfHwgIWNhY2hlZFNldFRpbWVvdXQpICYmIHNldFRpbWVvdXQpIHtcbiAgICAgICAgY2FjaGVkU2V0VGltZW91dCA9IHNldFRpbWVvdXQ7XG4gICAgICAgIHJldHVybiBzZXRUaW1lb3V0KGZ1biwgMCk7XG4gICAgfVxuICAgIHRyeSB7XG4gICAgICAgIC8vIHdoZW4gd2hlbiBzb21lYm9keSBoYXMgc2NyZXdlZCB3aXRoIHNldFRpbWVvdXQgYnV0IG5vIEkuRS4gbWFkZG5lc3NcbiAgICAgICAgcmV0dXJuIGNhY2hlZFNldFRpbWVvdXQoZnVuLCAwKTtcbiAgICB9IGNhdGNoKGUpe1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgLy8gV2hlbiB3ZSBhcmUgaW4gSS5FLiBidXQgdGhlIHNjcmlwdCBoYXMgYmVlbiBldmFsZWQgc28gSS5FLiBkb2Vzbid0IHRydXN0IHRoZSBnbG9iYWwgb2JqZWN0IHdoZW4gY2FsbGVkIG5vcm1hbGx5XG4gICAgICAgICAgICByZXR1cm4gY2FjaGVkU2V0VGltZW91dC5jYWxsKG51bGwsIGZ1biwgMCk7XG4gICAgICAgIH0gY2F0Y2goZSl7XG4gICAgICAgICAgICAvLyBzYW1lIGFzIGFib3ZlIGJ1dCB3aGVuIGl0J3MgYSB2ZXJzaW9uIG9mIEkuRS4gdGhhdCBtdXN0IGhhdmUgdGhlIGdsb2JhbCBvYmplY3QgZm9yICd0aGlzJywgaG9wZnVsbHkgb3VyIGNvbnRleHQgY29ycmVjdCBvdGhlcndpc2UgaXQgd2lsbCB0aHJvdyBhIGdsb2JhbCBlcnJvclxuICAgICAgICAgICAgcmV0dXJuIGNhY2hlZFNldFRpbWVvdXQuY2FsbCh0aGlzLCBmdW4sIDApO1xuICAgICAgICB9XG4gICAgfVxuXG5cbn1cbmZ1bmN0aW9uIHJ1bkNsZWFyVGltZW91dChtYXJrZXIpIHtcbiAgICBpZiAoY2FjaGVkQ2xlYXJUaW1lb3V0ID09PSBjbGVhclRpbWVvdXQpIHtcbiAgICAgICAgLy9ub3JtYWwgZW52aXJvbWVudHMgaW4gc2FuZSBzaXR1YXRpb25zXG4gICAgICAgIHJldHVybiBjbGVhclRpbWVvdXQobWFya2VyKTtcbiAgICB9XG4gICAgLy8gaWYgY2xlYXJUaW1lb3V0IHdhc24ndCBhdmFpbGFibGUgYnV0IHdhcyBsYXR0ZXIgZGVmaW5lZFxuICAgIGlmICgoY2FjaGVkQ2xlYXJUaW1lb3V0ID09PSBkZWZhdWx0Q2xlYXJUaW1lb3V0IHx8ICFjYWNoZWRDbGVhclRpbWVvdXQpICYmIGNsZWFyVGltZW91dCkge1xuICAgICAgICBjYWNoZWRDbGVhclRpbWVvdXQgPSBjbGVhclRpbWVvdXQ7XG4gICAgICAgIHJldHVybiBjbGVhclRpbWVvdXQobWFya2VyKTtcbiAgICB9XG4gICAgdHJ5IHtcbiAgICAgICAgLy8gd2hlbiB3aGVuIHNvbWVib2R5IGhhcyBzY3Jld2VkIHdpdGggc2V0VGltZW91dCBidXQgbm8gSS5FLiBtYWRkbmVzc1xuICAgICAgICByZXR1cm4gY2FjaGVkQ2xlYXJUaW1lb3V0KG1hcmtlcik7XG4gICAgfSBjYXRjaCAoZSl7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICAvLyBXaGVuIHdlIGFyZSBpbiBJLkUuIGJ1dCB0aGUgc2NyaXB0IGhhcyBiZWVuIGV2YWxlZCBzbyBJLkUuIGRvZXNuJ3QgIHRydXN0IHRoZSBnbG9iYWwgb2JqZWN0IHdoZW4gY2FsbGVkIG5vcm1hbGx5XG4gICAgICAgICAgICByZXR1cm4gY2FjaGVkQ2xlYXJUaW1lb3V0LmNhbGwobnVsbCwgbWFya2VyKTtcbiAgICAgICAgfSBjYXRjaCAoZSl7XG4gICAgICAgICAgICAvLyBzYW1lIGFzIGFib3ZlIGJ1dCB3aGVuIGl0J3MgYSB2ZXJzaW9uIG9mIEkuRS4gdGhhdCBtdXN0IGhhdmUgdGhlIGdsb2JhbCBvYmplY3QgZm9yICd0aGlzJywgaG9wZnVsbHkgb3VyIGNvbnRleHQgY29ycmVjdCBvdGhlcndpc2UgaXQgd2lsbCB0aHJvdyBhIGdsb2JhbCBlcnJvci5cbiAgICAgICAgICAgIC8vIFNvbWUgdmVyc2lvbnMgb2YgSS5FLiBoYXZlIGRpZmZlcmVudCBydWxlcyBmb3IgY2xlYXJUaW1lb3V0IHZzIHNldFRpbWVvdXRcbiAgICAgICAgICAgIHJldHVybiBjYWNoZWRDbGVhclRpbWVvdXQuY2FsbCh0aGlzLCBtYXJrZXIpO1xuICAgICAgICB9XG4gICAgfVxuXG5cblxufVxudmFyIHF1ZXVlID0gW107XG52YXIgZHJhaW5pbmcgPSBmYWxzZTtcbnZhciBjdXJyZW50UXVldWU7XG52YXIgcXVldWVJbmRleCA9IC0xO1xuXG5mdW5jdGlvbiBjbGVhblVwTmV4dFRpY2soKSB7XG4gICAgaWYgKCFkcmFpbmluZyB8fCAhY3VycmVudFF1ZXVlKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgZHJhaW5pbmcgPSBmYWxzZTtcbiAgICBpZiAoY3VycmVudFF1ZXVlLmxlbmd0aCkge1xuICAgICAgICBxdWV1ZSA9IGN1cnJlbnRRdWV1ZS5jb25jYXQocXVldWUpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgIHF1ZXVlSW5kZXggPSAtMTtcbiAgICB9XG4gICAgaWYgKHF1ZXVlLmxlbmd0aCkge1xuICAgICAgICBkcmFpblF1ZXVlKCk7XG4gICAgfVxufVxuXG5mdW5jdGlvbiBkcmFpblF1ZXVlKCkge1xuICAgIGlmIChkcmFpbmluZykge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIHZhciB0aW1lb3V0ID0gcnVuVGltZW91dChjbGVhblVwTmV4dFRpY2spO1xuICAgIGRyYWluaW5nID0gdHJ1ZTtcblxuICAgIHZhciBsZW4gPSBxdWV1ZS5sZW5ndGg7XG4gICAgd2hpbGUobGVuKSB7XG4gICAgICAgIGN1cnJlbnRRdWV1ZSA9IHF1ZXVlO1xuICAgICAgICBxdWV1ZSA9IFtdO1xuICAgICAgICB3aGlsZSAoKytxdWV1ZUluZGV4IDwgbGVuKSB7XG4gICAgICAgICAgICBpZiAoY3VycmVudFF1ZXVlKSB7XG4gICAgICAgICAgICAgICAgY3VycmVudFF1ZXVlW3F1ZXVlSW5kZXhdLnJ1bigpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIHF1ZXVlSW5kZXggPSAtMTtcbiAgICAgICAgbGVuID0gcXVldWUubGVuZ3RoO1xuICAgIH1cbiAgICBjdXJyZW50UXVldWUgPSBudWxsO1xuICAgIGRyYWluaW5nID0gZmFsc2U7XG4gICAgcnVuQ2xlYXJUaW1lb3V0KHRpbWVvdXQpO1xufVxuXG5wcm9jZXNzLm5leHRUaWNrID0gZnVuY3Rpb24gKGZ1bikge1xuICAgIHZhciBhcmdzID0gbmV3IEFycmF5KGFyZ3VtZW50cy5sZW5ndGggLSAxKTtcbiAgICBpZiAoYXJndW1lbnRzLmxlbmd0aCA+IDEpIHtcbiAgICAgICAgZm9yICh2YXIgaSA9IDE7IGkgPCBhcmd1bWVudHMubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgICAgIGFyZ3NbaSAtIDFdID0gYXJndW1lbnRzW2ldO1xuICAgICAgICB9XG4gICAgfVxuICAgIHF1ZXVlLnB1c2gobmV3IEl0ZW0oZnVuLCBhcmdzKSk7XG4gICAgaWYgKHF1ZXVlLmxlbmd0aCA9PT0gMSAmJiAhZHJhaW5pbmcpIHtcbiAgICAgICAgcnVuVGltZW91dChkcmFpblF1ZXVlKTtcbiAgICB9XG59O1xuXG4vLyB2OCBsaWtlcyBwcmVkaWN0aWJsZSBvYmplY3RzXG5mdW5jdGlvbiBJdGVtKGZ1biwgYXJyYXkpIHtcbiAgICB0aGlzLmZ1biA9IGZ1bjtcbiAgICB0aGlzLmFycmF5ID0gYXJyYXk7XG59XG5JdGVtLnByb3RvdHlwZS5ydW4gPSBmdW5jdGlvbiAoKSB7XG4gICAgdGhpcy5mdW4uYXBwbHkobnVsbCwgdGhpcy5hcnJheSk7XG59O1xucHJvY2Vzcy50aXRsZSA9ICdicm93c2VyJztcbnByb2Nlc3MuYnJvd3NlciA9IHRydWU7XG5wcm9jZXNzLmVudiA9IHt9O1xucHJvY2Vzcy5hcmd2ID0gW107XG5wcm9jZXNzLnZlcnNpb24gPSAnJzsgLy8gZW1wdHkgc3RyaW5nIHRvIGF2b2lkIHJlZ2V4cCBpc3N1ZXNcbnByb2Nlc3MudmVyc2lvbnMgPSB7fTtcblxuZnVuY3Rpb24gbm9vcCgpIHt9XG5cbnByb2Nlc3Mub24gPSBub29wO1xucHJvY2Vzcy5hZGRMaXN0ZW5lciA9IG5vb3A7XG5wcm9jZXNzLm9uY2UgPSBub29wO1xucHJvY2Vzcy5vZmYgPSBub29wO1xucHJvY2Vzcy5yZW1vdmVMaXN0ZW5lciA9IG5vb3A7XG5wcm9jZXNzLnJlbW92ZUFsbExpc3RlbmVycyA9IG5vb3A7XG5wcm9jZXNzLmVtaXQgPSBub29wO1xuXG5wcm9jZXNzLmJpbmRpbmcgPSBmdW5jdGlvbiAobmFtZSkge1xuICAgIHRocm93IG5ldyBFcnJvcigncHJvY2Vzcy5iaW5kaW5nIGlzIG5vdCBzdXBwb3J0ZWQnKTtcbn07XG5cbnByb2Nlc3MuY3dkID0gZnVuY3Rpb24gKCkgeyByZXR1cm4gJy8nIH07XG5wcm9jZXNzLmNoZGlyID0gZnVuY3Rpb24gKGRpcikge1xuICAgIHRocm93IG5ldyBFcnJvcigncHJvY2Vzcy5jaGRpciBpcyBub3Qgc3VwcG9ydGVkJyk7XG59O1xucHJvY2Vzcy51bWFzayA9IGZ1bmN0aW9uKCkgeyByZXR1cm4gMDsgfTtcbiJdfQ==