"use strict";

var prime   = require('prime'),
    Emitter = require('prime/emitter'),
    Bound   = require('prime-util/prime/bound'),
    Options = require('prime-util/prime/options'),
    zen     = require('elements/zen'),
    $       = require('../utils/elements.utils.js'),
    storage = require('prime/map')(),

    bind    = require('mout/function/bind'),
    merge   = require('mout/object/merge');

var Toaster = new prime({
    mixin: [Bound, Options],

    inherits: Emitter,

    options: {
        tapToDismiss: true,
        noticeClass: 'g-notifications',
        containerID: 'g-notifications-container',

        types: {
            base: '',
            error: 'fa-minus-circle',
            info: 'fa-info-circle',
            success: 'fa-check-circle',
            warning: 'fa-exclamation-triangle'
        },

        showDuration: 300,
        showEquation: 'cubic-bezier(0.02, 0.01, 0.47, 1)',
        hideDuration: 500,
        hideEquation: 'cubic-bezier(0.02, 0.01, 0.47, 1)',

        timeOut: 2500, // timeOut and extendedTimeout to 0 == sticky
        extendedTimeout: 2500,

        location: 'bottom-right',

        titleClass: 'g-notifications-title',
        messageClass: 'g-notifications-message',
        closeButton: true,

        target: '#g5-container',
        targetLocation: 'bottom',

        newestOnTop: true,
        preventDuplicates: false,
        progressBar: true


        /*
        onShow: function() {},
        onHidden: function() {},
        onClick: function() {}
        */
    },

    constructor: function(options) {
        this.setOptions(options);

        this.id = 0;
        this.previousNotice = null;
        this.map = storage;
    },

    mergeOptions: function(options) {
        return merge(this.options, options || {});
    },

    base: function(message, title, options) {
        options = this.mergeOptions(options);

        return this.notify(merge(options, {
            title: title || '',
            type: options.type || 'base',
            message: message
        }));
    },

    success: function(message, title, options) {
        options = this.mergeOptions(options);

        return this.notify(merge(options, {
            title: title || 'Success!',
            type: 'success',
            message: message
        }));
    },

    info: function(message, title, options) {
        options = this.mergeOptions(options);

        return this.notify(merge(options, {
            title: title || 'Info',
            type: 'info',
            message: message
        }));
    },
    warning: function(message, title, options) {
        options = this.mergeOptions(options);

        return this.notify(merge(options, {
            title: title || 'Warning!',
            type: 'warning',
            message: message
        }));
    },

    error: function(message, title, options) {
        options = this.mergeOptions(options);

        return this.notify(merge(options, {
            title: title || 'Error!',
            type: 'error',
            message: message
        }));
    },

    notify: function(options) {
        options = this.mergeOptions(options);

        if (options.preventDuplicates && this.previousNotice === options.message) { return; }

        this.id++;
        this.previousNotice = options.message;

        var container = this.getContainer(options, true),
            element = zen('div'), title = zen('div'), message = zen('div'),
            icon = zen('i.fa'),
            progress = zen('div.g-notifications-progress'),
            close = zen('a.fa.fa-close[href="#"]');

        this.map.set(element, {
            container: container,
            interval: null,
            progressBar: {
                interval: null,
                hideETA: null,
                maxHideTime: null
            },
            response: {
                id: this.id,
                state: 'visible',
                start: new Date(),
                options: options
            },
            options: options
        });

        if (options.title) {
            element.appendChild(title.html(options.title).addClass(options.titleClass));
        }

        if (options.message) {
            element.appendChild(message.html(options.message).addClass(options.messageClass));
        }

        if (options.closeButton) {
            close.top(element);
        }

        if (options.progressBar) {
            progress.top(element);
        }

        if (options.type && options.title) {
            if (options.types[options.type]) {
                element.addClass('g-notifications-theme-' + options.type);
                icon.top(title).addClass(options.types[options.type]);
            }
        }

        element.style({ opacity: 0 });
        element[options.newestOnTop ? 'top' : 'bottom'](container);
        element.animate({ opacity: 1 }, {
            duration: options.showDuration,
            equation: options.showEquation,
            callback: options.onShow
        });

        if (options.timeOut > 0) {
            var map = this.map.get(element);
            map.interval = setTimeout(bind(function() {
                this.hide(element);
            }, this), options.timeOut);
            map.progressBar.maxHideTime = parseFloat(options.timeOut);
            map.progressBar.hideETA = new Date().getTime() + map.progressBar.maxHideTime;

            if (options.progressBar) {
                map.progressBar.interval = setInterval(bind(function() {
                    this.updateProgress(element, progress);
                }, this), 10);
            }

            this.map.set(element, map);
        }

        var stick = bind(function() { this.stickAround(element); }, this),
            delay = bind(function() { this.delayedHide(element); }, this);
        element.on('mouseover', stick);
        element.on('mouseout', delay);

        if (!options.onClick && options.tapToDismiss) {
            element.on('click', bind(function(){
                element.off('mouseover', stick);
                element.off('mouseout', delay);

                this.hide(element);
            }, this));
        }

        if (options.closeButton && close) {
            close.on('click', bind(function(event){
                event.stopPropagation();
                event.preventDefault();

                element.off('mouseover', stick);
                element.off('mouseout', delay);
                this.hide(element, true);
            }, this));
        }

    },

    stickAround: function(element) {
        var map = this.map.get(element);

        clearTimeout(map.interval);
        map.progressBar.hideETA = 0;
        element.animate({ opacity: 1 }, {
            duration: map.options.showDuration,
            equation: map.options.showEquation,
            callback: map.options.onShow
        });

        this.map.set(element, map);
    },

    hide: function(element, override) {
        if (element.find(':focus') && !override) { return; }

        var map = this.map.get(element);

        clearTimeout(map.progressBar.interval);

        this.map.set(element, map);
        return element.animate({ opacity: 0 }, {
            duration: map.options.hideDuration,
            equation: map.options.hideEquation,
            callback: bind(function() {
                this.remove(element);
                if (map.options.onHidden && map.response.state !== 'hidden') { map.options.onHidden(); }
                map.response.state = 'hidden';
                map.response.endTime = new Date();

                this.map.set(element, map);
            }, this)
        });
    },

    delayedHide: function(element, override) {
        var map = this.map.get(element);

        if (map.options.timeOut > 0 || map.options.extendedTimeout > 0) {
            map.interval = setTimeout(bind(function() {
                this.hide(element);
            }, this), map.options.extendedTimeout);
            map.progressBar.maxHideTime = parseFloat(map.options.extendedTimeout);
            map.progressBar.hideETA = new Date().getTime() + map.progressBar.maxHideTime;
        }

        this.map.set(element, map);
    },

    updateProgress: function(element, progress) {
        var map = this.map.get(element),
            percentage = ((map.progressBar.hideETA - (new Date().getTime())) / map.progressBar.maxHideTime) * 100;

        this.map.set(element, map);
        progress.style({ width: percentage + '%' });
    },

    getContainer: function(options, create) {
        options = this.mergeOptions(options);

        var container = $('#' + options.containerID);
        if (container) { return container; }

        if (create) { container = this.createContainer(options); }

        return container;
    },

    createContainer: function(options) {
        options = this.mergeOptions(options);

        return zen('div#' + options.containerID + '.' + options.location)[options.targetLocation](options.target).attribute('aria-live', 'polite').attribute('role', 'alert');
    },

    remove: function(element) {
        if (!element) { return; }

        var map = this.map.get(element);
        if (!map.container) { map.container = this.getContainer(map.options); }
        /*if ($toastElement.is(':visible')) {
            return;
        }*/

        element.remove();
        if (!map.container.children()) {
            map.container.remove();
            this.previousNotice = null;
        }

        this.map.set(element, map);
    }
});

var toaster = new Toaster();

module.exports = toaster;
