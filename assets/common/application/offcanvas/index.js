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

    attachMutationEvent: function () {
        this.observer = new MutationObserver(() => {
          this._checkTogglers();
        });
        this.observer.observe(this.offcanvas[0], {
          childList: true,
          subtree: true,
        });
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

    detachMutationEvent: function () {
        this.observer.disconnect();
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
            this.offcanvas.attribute('aria-expanded', true);
            $('[data-offcanvas-toggle]').attribute('aria-expanded', true);
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
        this.offcanvas.attribute('aria-expanded', false);
        $('[data-offcanvas-toggle]').attribute('aria-expanded', false);

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
            var shouldCollapse = (blocks && blocks.length === 1) && mobileContainer && (!trim(this.offcanvas.text()).length && !blocks.find('.g-menu-item'));

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
