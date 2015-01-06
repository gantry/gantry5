"use strict";

var prime    = require('prime'),
    $        = require('../utils/elements.moofx'),
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

    request  = require('agent');

var animationEndSupport = false;

domready(function () {
    var style = (document.body || document.documentElement).style;

    forEach(['animation', 'WebkitAnimation', 'MozAnimation', 'MsAnimation', 'OAnimation'], function (animation, index) {
        if (animationEndSupport) {
            return;
        }
        animationEndSupport = style[animation] !== undefined ? Modal.prototype.animationEndEvent[index] : false;
    });
});

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
        closeClassName: '',
        closeCSS: '',

        afterOpen: null,
        afterClose: null
    },

    constructor: function (options) {
        this.setOptions(options);
        this.defaults = this.options;

        var self = this;
        domready(function () {
            $(window).on('keyup', function (event) {
                if (event.keyCode === 27) {
                    return self.closeByEscape();
                }
            });

            self.animationEndEvent = animationEndSupport;
        });

        this
            .on('dialogOpen', function (options) {
                $('body').addClass(options.baseClassNames.open);
            })
            .on('dialogAfterClose', bind(function (options) {
                var all = this.getAll();
                if (!all || !all.length) {
                    $('body').removeClass(options.baseClassNames.open);
                }
            }, this));
    },

    storage: function () {
        return storage;
    },

    open: function (options) {
        options = merge(this.options, options);
        options.id = this.globalID++;

        var elements = {};

        // container
        elements.container = zen('div')
            .addClass(options.baseClassNames.container)
            .addClass(options.className)
            .style(options.css);

        storage.set(elements.container, { dialog: options });

        // overlay
        elements.overlay = zen('div')
            .addClass(options.baseClassNames.overlay)
            .addClass(options.overlayClassName)
            .style(options.overlayCSS);

        storage.set(elements.overlay, { dialog: options });

        if (options.overlayClickToClose) {
            elements.overlay.on('click', bind(this._overlayClick, this, elements.overlay[0]));
        }

        elements.container.appendChild(elements.overlay);

        // content
        elements.content = zen('div')
            .addClass(options.baseClassNames.content)
            .addClass(options.contentClassName)
            .style(options.contentCSS)
            .html(options.content);

        storage.set(elements.content, { dialog: options });
        elements.container.appendChild(elements.content);

        // remote
        if (options.remote && options.remote.length > 1) {
            this.showLoading();

            options.method = options.method || 'get';
            var agent = request();
            agent.method(options.method);
            agent.url(options.remote);
            if (options.data) { agent.data(options.data); }
            agent.send(bind(function(error, response){
                elements.content.html(response.body.html || response.body);
                this.hideLoading();
                if (options.remoteLoaded) {
                    options.remoteLoaded(response, options);
                }
            }, this));
        }

        // close button
        if (options.showCloseButton) {
            elements.closeButton = zen('div')
                .addClass(options.baseClassNames.close)
                .addClass(options.closeClassName)
                .style(options.closeCSS);

            storage.set(elements.closeButton, { dialog: options });
            elements.closeButton.on('click', bind(this._closeButtonClick, this, elements.closeButton[0]));
            elements.content.appendChild(elements.closeButton);
        }

        // inject the dialog in the DOM
        $(options.appendNode).appendChild(elements.container);

        options.elements = elements;

        if (options.afterOpen) {
            options.afterOpen(elements.content, options);
        }

        setTimeout(bind(function () {
            return this.emit('dialogOpen', options);
        }, this), 0);

        return elements.content;
    },

    getAll: function () {
        var options = this.options;
        return $("." + options.baseClassNames.container + ":not(." + options.baseClassNames.closing + ") ." + options.baseClassNames.content);
    },

    getByID: function (id) {
        return $(this.getAll().filter(function (element) {
            element = $(element);
            return storage.get(element).dialog.id === id;
        }));
    },

    close: function (id) {
        if (!id) {
            var element = $(last(this.getAll()));
            if (!element) {
                return false;
            }

            id = storage.get(element).dialog.id;
        }

        return this.closeByID(id);
    },

    closeAll: function () {
        var ids;

        ids = map(this.getAll(), function (element) {
            element = $(element);

            return storage.get(element).dialog.id;
        });

        if (!ids.length) {
            return false;
        }

        forEach(ids.reverse(), function (value, id) {
            return this.closeByID(id);
        }, this);

        return true;
    },

    closeByID: function (id) {
        var content = this.getByID(id);
        if (!content || !content.length) {
            return false;
        }

        var container, options;

        container = storage.get(content).dialog.elements.container;
        options = merge({}, storage.get(content).dialog);

        var beforeClose = function () {
                if (options.beforeClose) {
                    return options.beforeClose(content, options);
                }
            },
            close = bind(function () {
                content.emit('dialogClose', options);
                container.remove();
                this.emit('dialogAfterClose', options);
                if (options.afterClose) {
                    return options.afterClose(content, options);
                }
            }, this);

        if (animationEndSupport) {
            beforeClose();
            container.off(this.animationEndEvent).on(this.animationEndEvent, function () {
                return close();
            }).addClass(options.baseClassNames.closing);
        } else {
            beforeClose();
            close();
        }

        return true;
    },

    closeByEscape: function () {
        var ids, id;

        ids = map(this.getAll(), function (element) {
            element = $(element);

            return storage.get(element).dialog.id;
        });

        if (!ids.length) {
            return false;
        }

        id = Math.max.apply(Math, ids);

        var element = this.getByID(id);

        if (!storage.get(element).dialog.escapeToClose) {
            return false;
        }

        return this.closeByID(id);

    },

    showLoading: function () {
        this.hideLoading();
        return $('body').appendChild(zen('div.g5-dialog-loading-spinner.' + this.options.className));
    },

    hideLoading: function () {
        var spinner = $('.g5-dialog-loading-spinner');
        return spinner ? spinner.remove() : false;
    },

    // private
    _overlayClick: function (element, event) {
        if (event.target !== element) {
            return;
        }

        return this.close(storage.get($(element)).dialog.id);
    },

    _closeButtonClick: function (element) {
        return this.close(storage.get($(element)).dialog.id);
    }
});

module.exports = Modal;