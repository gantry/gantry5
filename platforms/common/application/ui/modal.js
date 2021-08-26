"use strict";
// Based on Vex (https://github.com/hubspot/vex)

var prime    = require('prime'),
    $        = require('../utils/elements.utils'),
    zen      = require('elements/zen'),
    storage  = require('prime/map')(),
    Emitter  = require('prime/emitter'),
    Bound    = require('prime-util/prime/bound'),
    Options  = require('prime-util/prime/options'),
    domready = require('elements/domready'),

    bind     = require('mout/function/bind'),
    map      = require('mout/array/map'),
    forEach  = require('mout/array/forEach'),
    last     = require('mout/array/last'),
    merge    = require('mout/object/merge'),
    trim     = require('mout/string/trim'),

    request  = require('agent');

var animationEndSupport = false;

var Modal = new prime({
    mixin: [Bound, Options],

    inherits: Emitter,

    animationEndEvent: ['animationend', 'webkitAnimationEnd', 'mozAnimationEnd', 'MSAnimationEnd', 'oanimationend'],

    globalID: 1,

    options: {
        baseClassNames: {
            container: 'g5-dialog',
            content: 'g5-content',
            overlay: 'g5-overlay',
            close: 'g5-close',
            closing: 'g5-closing',
            open: 'g5-dialog-open'
        },

        content: '',
        remote: '',
        showCloseButton: true,
        escapeToClose: true,
        overlayClickToClose: true,
        appendNode: '#g5-container',
        className: 'g5-dialog-theme-default',
        css: {},
        overlayClassName: '',
        overlayCSS: '',
        contentClassName: '',
        contentCSS: '',
        closeClassName: 'g5-dialog-close',
        closeCSS: '',

        afterOpen: null,
        afterClose: null
    },

    constructor: function(options) {
        this.setOptions(options);
        this.defaults = this.options;

        var self = this;
        domready(function() {
            $(window).on('keydown', function(event) {
                if (event.keyCode === 27) {
                    return self.closeByEscape();
                }
            });

            self.animationEndEvent = animationEndSupport;
        });

        this
            .on('dialogOpen', function(options) {
                $('body').addClass(options.baseClassNames.open);
                $('html').addClass(options.baseClassNames.open);
            })
            .on('dialogAfterClose', bind(function(options) {
                var all = this.getAll();
                if (!all || !all.length) {
                    $('body').removeClass(options.baseClassNames.open);
                    $('html').removeClass(options.baseClassNames.open);
                }
            }, this));
    },

    storage: function() {
        return storage;
    },

    open: function(options) {
        options = merge(this.options, options);
        options.id = this.globalID++;

        var elements = {};

        // container
        elements.container = zen('div')
            .addClass(options.baseClassNames.container)
            .addClass(options.className)
            .style(options.css)
            .attribute('tabindex', '0')
            .attribute('role', 'dialog')
            .attribute('aria-hidden', 'true')
            .attribute('aria-labelledby', 'g-modal-labelledby')
            .attribute('aria-describedby', 'g-modal-describedby');

        storage.set(elements.container, { dialog: options });

        // overlay
        elements.overlay = zen('div')
            .addClass(options.baseClassNames.overlay)
            .addClass(options.overlayClassName)
            .style(options.overlayCSS);

        storage.set(elements.overlay, { dialog: options });

        if (options.overlayClickToClose) {
            elements.container.on('click', bind(this._overlayClick, this, elements.container[0]));
            elements.overlay.on('click', bind(this._overlayClick, this, elements.overlay[0]));
        }

        elements.container.appendChild(elements.overlay);

        // content
        elements.content = zen('div')
            .addClass(options.baseClassNames.content)
            .addClass(options.contentClassName)
            .style(options.contentCSS)
            .attribute('aria-live', 'assertive')
            .attribute('tabindex', '0')
            .html(options.content);

        storage.set(elements.content, { dialog: options });
        elements.container.appendChild(elements.content);

        if (options.overlayClickToClose) {
            elements.content.on('click', function(/*e*/){
                return true;
            });
        }

        // remote
        if (options.remote && options.remote.length > 1) {
            this.showLoading();

            options.method = options.method || 'get';
            var agent = request();
            agent.method(options.method);
            agent.url(options.remote);
            if (options.data) { agent.data(options.data); }

            agent.send(bind(function(error, response) {
                if (elements.container.hasClass(options.baseClassNames.closing)) {
                    this.hideLoading();
                    return;
                }

                elements.content.html(response.body.html || response.body);

                if (!response.body.success) {
                    if (!response.body.html) { elements.content.style({ width: '90%' }); }
                }

                this.hideLoading();
                if (options.remoteLoaded && !elements.container.hasClass(options.baseClassNames.closing)) {
                    options.remoteLoaded(response, options);
                }

                elements.container.attribute('aria-hidden', 'false');
                setTimeout(function(){ elements.content[0].focus(); }, 0);

                var selects = $('[data-selectize]');
                if (selects) { selects.selectize(); }
            }, this));
        } else {
            elements.container.attribute('aria-hidden', 'false');
            setTimeout(function(){ elements.content[0].focus(); }, 0);
        }

        // close button
        if (options.showCloseButton) {
            elements.closeButton = zen('div')
                .addClass(options.baseClassNames.close)
                .addClass(options.closeClassName)
                .attribute('role', 'button').attribute('aria-label', 'Close')
                .style(options.closeCSS);

            storage.set(elements.closeButton, { dialog: options });
            elements.content.appendChild(elements.closeButton);
        }

        // delegate container to pick g5-close clicks
        elements.container.delegate('click', '.g5-dialog-close', bind(function(event){
            event.preventDefault();
            this._closeButtonClick(elements.container);
        }, this));

        // inject the dialog in the DOM
        var container = $(options.appendNode);

        // wordpress workaround for out-of-scope cases
        if (GANTRY_PLATFORM == 'wordpress') {
            container = $('#widgets-editor') || $('#customize-preview') || $('#widgets-right') || $(options.appendNode);
            if ('#' + container.id() != options.appendNode) {
                var wpwrap = $('#wpwrap') || $('.wp-customizer'), sibling, workaround;
                if (wpwrap.id() == 'wpwrap') {
                    sibling = wpwrap.nextSibling(options.appendNode);
                    workaround =  sibling ? sibling : zen('div.g5wp-out-of-scope' + options.appendNode).after(wpwrap);
                } else {
                    sibling = wpwrap.find('> ' + options.appendNode);
                    workaround =  sibling ? sibling : zen('div.g5wp-out-of-scope' + options.appendNode).top(wpwrap);
                }
                container = workaround;
            }
        }

        container.appendChild(elements.container);

        options.elements = elements;

        if (options.afterOpen) {
            options.afterOpen(elements.content, options);
        }

        setTimeout(bind(function() {
            return this.emit('dialogOpen', options);
        }, this), 0);

        return elements.content;
    },

    getAll: function() {
        var options = this.options;
        return $("." + options.baseClassNames.container + ":not(." + options.baseClassNames.closing + ") ." + options.baseClassNames.content);
    },

    getByID: function(id) {
        var all = this.getAll();
        if (!all) { return []; }

        return $(all.filter(function(element) {
            element = $(element);
            return storage.get(element).dialog.id === id;
        }));
    },

    getLast: function() {
        var ids, id;

        ids = map(this.getAll(), function(element) {
            element = $(element);

            return storage.get(element).dialog.id;
        });

        if (!ids.length) {
            return false;
        }

        return Math.max.apply(Math, ids);
    },

    close: function(id) {
        if (!id) {
            var element = $(last(this.getAll()));
            if (!element) {
                return false;
            }

            id = storage.get(element).dialog.id;
        }

        return this.closeByID(id);
    },

    closeAll: function() {
        var ids;

        ids = map(this.getAll(), function(element) {
            element = $(element);

            return storage.get(element).dialog.id;
        });

        if (!ids.length) {
            return false;
        }

        forEach(ids.reverse(), function(id) {
            return this.closeByID(id);
        }, this);

        return true;
    },

    closeByID: function(id) {
        var content = this.getByID(id);
        if (!content || !content.length) {
            return false;
        }

        var container, options;

        container = storage.get(content).dialog.elements.container;
        options = merge({}, storage.get(content).dialog);

        var beforeClose = function() {
                if (options.beforeClose) {
                    return options.beforeClose(content, options);
                }
            },
            close = bind(function() {
                if (options.remoteLoaded) { options.remoteLoaded = function(){}; }
                content.emit('dialogClose', options);
                container.remove();
                this.emit('dialogAfterClose', options);
                if (options.afterClose) {
                    return options.afterClose(content, options);
                }

            }, this);

        if (animationEndSupport) {
            beforeClose();
            container.off(this.animationEndEvent).on(this.animationEndEvent, function() {
                return close();
            }).addClass(options.baseClassNames.closing);
        } else {
            beforeClose();
            close();
        }

        return true;
    },

    closeByEscape: function() {
        var id = this.getLast();

        if (id === false) {
            return false;
        }

        var element = this.getByID(id);

        if (!storage.get(element).dialog.escapeToClose) {
            return false;
        }

        return this.closeByID(id);

    },

    enableCloseByOverlay: function() {
        var id = this.getLast();

        if (id === false) {
            return false;
        }

        var elements = storage.get(this.getByID(id)).dialog.elements;

        elements.container.on('click', bind(this._overlayClick, this, elements.container[0]));
        elements.overlay.on('click', bind(this._overlayClick, this, elements.overlay[0]));

        elements.content.on('click', function(/*e*/){
            return true;
        });
    },

    showLoading: function() {
        this.hideLoading();
        return $('#g5-container').appendChild(zen('div.g5-dialog-loading-spinner.' + this.options.className));
    },

    hideLoading: function() {
        var spinner = $('.g5-dialog-loading-spinner');
        return spinner ? spinner.remove() : false;
    },

    // private
    _overlayClick: function(element, event) {
        if (event.target !== element) {
            return;
        }

        return this.close(storage.get($(element)).dialog.id);
    },

    _closeButtonClick: function(element) {
        return this.close(storage.get($(element)).dialog.id);
    }
});

domready(function() {
    var style = (document.body || document.documentElement).style;

    forEach(['animation', 'WebkitAnimation', 'MozAnimation', 'MsAnimation', 'OAnimation'], function(animation, index) {
        if (animationEndSupport) {
            return;
        }
        animationEndSupport = style[animation] !== undefined ? Modal.prototype.animationEndEvent[index] : false;
    });
});

var modal = new Modal();

module.exports = modal;
