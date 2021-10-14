"use strict";

var prime         = require('prime'),
    $             = require('../utils/elements.utils'),
    zen           = require('elements/zen'),
    domready      = require('elements/domready'),
    storage       = require('prime/map')(),
    modal         = require('../ui').modal,

    size          = require('mout/collection/size'),
    indexOf       = require('mout/array/indexOf'),
    merge         = require('mout/object/merge'),
    keys          = require('mout/object/keys'),
    guid          = require('mout/random/guid'),
    toQueryString = require('mout/queryString/encode'),
    contains      = require('mout/string/contains'),

    getParam      = require('mout/queryString/getParam'),
    setParam      = require('mout/queryString/setParam'),

    request       = require('agent')(),
    History       = require('./history'),
    flags         = require('./flags-state'),
    parseAjaxURI  = require('./get-ajax-url').parse,
    getAjaxSuffix = require('./get-ajax-suffix'),
    lm            = require('../lm'),
    mm            = require('../menu'),
    assignments   = require('../assignments');

require('../ui/popover');

var ERROR = false, TMP_SELECTIZE_DISABLE = false, ConfNavIndex = -1;

History.Adapter.bind(window, 'statechange', function() {
    if (request.running()) {
        return false;
    }
    var body = $('body'),
        State = History.getState(),
        URI = State.url,
        Data = State.data,
        sidebar = $('#navbar'),
        mainheader = $('#main-header'),
        params = '';

    if (Data.doNothing) { return true; }

    if (size(Data) && Data.parsed !== false && storage.get(Data.uuid)) {
        Data = storage.get(Data.uuid);
    }

    if (Data.element) {
        var isTopNavOrMenu = Data.element.parent('#main-header') || Data.element.matches('.menu-select-wrap');
        body.emit('statechangeBefore', {
            target: Data.element,
            Data: Data
        });
    } else {
        var url = URI.replace(window.location.origin, '');
        Data.element = $('[href="' + url + '"]');
    }

    URI = parseAjaxURI(URI + getAjaxSuffix());

    var lis;
    if (sidebar && Data.element) {
        var active = sidebar.search('li.active');
        lis = sidebar.search('li');
        lis.removeClass('active');

        if (Data.element.parent('#navbar')) {
            Data.element.parent('li').addClass('active');
        }
    }

    if (mainheader && Data.element && (!Data.element.matches('a.menu-item') && !Data.element.matches('select.menu-select-wrap'))) {
        lis = mainheader.search('.float-right li');
        lis.removeClass('active');

        if (Data.element.parent('#main-header')) {
            Data.element.parent('li').addClass('active');
        }
    }

    if (Data.params) {
        params = toQueryString(JSON.parse(Data.params));
        if (contains(URI, '?')) { params = params.replace(/^\?/, '&'); }
    }

    if (!ERROR) { modal.closeAll(); }
    request.url(URI + params).data(Data.extras || {}).method(Data.extras ? 'post' : 'get').send(function(error, response) {
        if (!response.body.success) {
            if (!ERROR) {
                ERROR = true;
                modal.open({
                    content: response.body.html || response.body,
                    afterOpen: function(container) {
                        if (!response.body.html) { container.style({ width: '90%' }); }
                    }
                });

                History.back();
            } else {
                ERROR = false;
            }

            if (Data.element) {
                Data.element.hideIndicator();
            }

            return false;

        }

        var target = Data.parent ? Data.element.parent(Data.parent) : $(Data.target),
            destination = (target || $('[data-g5-content]') || body);

        if (response.body && response.body.html) {
            var fader;
            destination.html(response.body.html);
            if (fader = (destination.matches('[data-g5-content]') ? destination : destination.find('[data-g5-content]'))) {
                var navbar = $('#navbar');
                fader.style({ opacity: 0 });

                if (isTopNavOrMenu) {
                    $(navbar).attribute('tabindex', '-1').attribute('aria-hidden', 'true');
                }

                navbar[isTopNavOrMenu ? 'slideUp' : 'slideDown']();
                fader.animate({ opacity: 1 });
            }
        } else { destination.html(response.body); }

        body.getPopover().hideAll(true).destroy();

        if (Data.element) {
            body.emit('statechangeAfter', {
                target: Data.element,
                Data: Data
            });
        }

        var element = (Data.event && Data.event.activeSpinner) || Data.element;
        if (element) {
            element.hideIndicator();
        }

        var selects = $('[data-selectize]');
        if (selects) { selects.selectize(); }
        selectorChangeEvent();
        assignments.chromeFix();

        body.emit('statechangeEnd');
    });
});

var selectorChangeEvent = function() {
    var selectors = $('[data-selectize-ajaxify]');
    if (!selectors) { return; }

    selectors.forEach(function(selector) {
        selector = $(selector);
        var selectize = selector.selectize().selectizeInstance;
        if (!selectize || selectize.HasChangeEvent) { return; }

        selectize.on('change', function() {
            if (TMP_SELECTIZE_DISABLE) {
                TMP_SELECTIZE_DISABLE = false;
                return false;
            }
            var value = selectize.getValue(),
                options = selectize.Options,
                flagCallback = function() {
                    flags.off('update:pending', flagCallback);
                    modal.close();

                    selectize.input
                        .data('g5-ajaxify', '')
                        .data('g5-ajaxify-target', selector.data('g5-ajaxify-target') || '[data-g5-content-wrapper]')
                        .data('g5-ajaxify-target-parent', selector.data('g5-ajaxify-target-parent') || null)
                        .data('g5-ajaxify-href', options[value].url)
                        .data('g5-ajaxify-params', options[value].params ? JSON.stringify(options[value].params) : null);


                    var active = $('#navbar li.active') || $('#main-header li.active') || $('#navbar li:nth-child(2)');
                    if (active) { active.showIndicator(); }

                    $('body').emit('click', {
                        target: selectize.input,
                        activeSpinner: active
                    });
                };

            if (!options[value]) { return; }

            if (flags.get('pending')) {
                flags.warning({
                    callback: function(response, content) {
                        var saveContinue = content.find('[data-g-unsaved-save]'),
                            discardContinue = content.find('[data-g-unsaved-discard]');

                        if (!saveContinue) { return; }
                        saveContinue.on('click', function(e) {
                            e.preventDefault();
                            if (this.attribute('disabled')) { return false; }

                            $([saveContinue, discardContinue]).attribute('disabled');
                            flags.on('update:pending', flagCallback);
                            $('body').emit('click', { target: $('.button-save') });
                        });

                        discardContinue.on('click', function(e) {
                            e.preventDefault();
                            if (this.attribute('disabled')) { return false; }

                            $([saveContinue, discardContinue]).attribute('disabled');
                            flags.set('pending', false);
                            flagCallback();
                        });
                    },

                    afterclose: function() {
                        TMP_SELECTIZE_DISABLE = true;
                        selectize.setValue(selectize.getPreviousValue());
                    }
                });

                return;
            }

            flagCallback();
        });

        selectize.HasChangeEvent = true;
    });
};


domready(function() {
    var body = $('body');

    // Update NONCE if any
    if (GANTRY_AJAX_NONCE) {
        var currentURI = History.getPageUrl(),
            currentNonce, currentView;

        // hack to inject the default view in WP/Grav in case it's missing
        switch (GANTRY_PLATFORM) {
            case 'wordpress':
                currentNonce = getParam(currentURI, '_wpnonce');
                // currentView = getParam(currentURI, 'view');

                /*
                if (!currentView) {
                    currentURI = setParam(currentURI, 'view', 'configurations/default/styles');
                    History.replaceState({ uuid: guid(), doNothing: true }, window.document.title, currentURI);
                }
                */

                // refresh nonce
                if (currentNonce !== GANTRY_AJAX_NONCE) {
                    currentURI = setParam(currentURI, '_wpnonce', GANTRY_AJAX_NONCE);
                    History.replaceState({ uuid: guid(), doNothing: true }, window.document.title, currentURI);
                }
                break;

            case 'grav':
                currentNonce = getParam(currentURI, 'nonce');
                // currentView = contains(currentURI, 'configurations/default/styles');

                /*
                if (!currentView) {
                    currentURI += 'configurations/default/styles';
                    History.replaceState({ uuid: guid(), doNothing: true }, window.document.title, currentURI);
                }
                */

                // refresh nonce
                if (currentNonce !== GANTRY_AJAX_NONCE) {
                    currentURI = setParam(currentURI, 'nonce', GANTRY_AJAX_NONCE);
                    History.replaceState({ uuid: guid(), doNothing: true }, window.document.title, currentURI);
                }
                break;
        }


    }

    // back to configuration
    body.delegate('click', '.button-back-to-conf', function(event, element) {
        event.preventDefault();

        var confSelector = $('#configuration-selector'),
            outlineDeleted = body.outlineDeleted,
            currentOutline = confSelector.value();

        ConfNavIndex = ConfNavIndex == -1 ? 1 : ConfNavIndex;
        var navbar = $('#navbar'),
            item = navbar.find('li:nth-child(' + (ConfNavIndex + 1) + ') [data-g5-ajaxify]');

        if (flags.get('pending')) {
            flags.warning({
                callback: function(response, content) {
                    var saveContinue = content.find('[data-g-unsaved-save]'),
                        discardContinue = content.find('[data-g-unsaved-discard]'),
                        flagCallback = function() {
                            flags.off('update:pending', flagCallback);
                            modal.close();

                            body.emit('click', { target: item });
                            navbar.attribute('tabindex', null).attribute('aria-hidden', 'false');
                            navbar.slideDown();
                        };

                    if (!saveContinue) { return; }
                    saveContinue.on('click', function(e) {
                        e.preventDefault();
                        if (this.attribute('disabled')) { return false; }

                        $([saveContinue, discardContinue]).attribute('disabled');
                        flags.on('update:pending', flagCallback);
                        body.emit('click', { target: $('.button-save') });
                    });

                    discardContinue.on('click', function(e) {
                        e.preventDefault();
                        if (this.attribute('disabled')) { return false; }

                        $([saveContinue, discardContinue]).attribute('disabled');
                        flags.set('pending', false);
                        flagCallback();
                    });
                }
            });

            return;
        }

        element.showIndicator();

        if (outlineDeleted == currentOutline) {
            var ids = keys(confSelector.selectizeInstance.Options),
                id = ids.shift();
            body.outlineDeleted = null;
            item.href(item.href().replace('/' + outlineDeleted + '/', '/' + id + '/').replace('style=' + outlineDeleted, 'style=' + id));
        }

        body.emit('click', { target: item });
        navbar.attribute('tabindex', null);
        navbar.slideDown();
    });

    body.delegate('click', '#navbar a[data-g5-ajaxify]', function(event, element) {
        var navbar = $('#navbar'),
            lis = navbar.search('li a[data-g5-ajaxify]');

        ConfNavIndex = indexOf(lis, element[0]) + 1;
    });

    // generic ajaxified links
    body.delegate('click', '[data-g5-ajaxify]', function(event, element) {
        if (event && event.preventDefault) {
            if (event.which === 2 || event.metaKey || event.ctrlKey || event.altKey || event.shiftKey) {
                return true;
            }

            event.preventDefault();
        }

        if (flags.get('pending') && (!element.matches('a.menu-item') && !element.parent('[data-menu-items]'))) {
            flags.warning({
                callback: function(response, content) {
                    var saveContinue = content.find('[data-g-unsaved-save]'),
                        discardContinue = content.find('[data-g-unsaved-discard]'),
                        flagCallback = function() {
                            flags.off('update:pending', flagCallback);
                            modal.close();
                            body.emit('click', event);
                        };

                    if (!saveContinue) { return; }
                    saveContinue.on('click', function(e) {
                        e.preventDefault();
                        if (this.attribute('disabled')) { return false; }

                        $([saveContinue, discardContinue]).attribute('disabled');
                        flags.on('update:pending', flagCallback);
                        body.emit('click', { target: $('.button-save') });
                    });

                    discardContinue.on('click', function(e) {
                        e.preventDefault();
                        if (this.attribute('disabled')) { return false; }

                        $([saveContinue, discardContinue]).attribute('disabled');
                        flags.set('pending', false);
                        flagCallback();
                    });
                }
            });

            return;
        }

        element.showIndicator();

        var data = element.data('g5-ajaxify'),
            target = element.data('g5-ajaxify-target'),
            parent = element.data('g5-ajaxify-target-parent'),
            url = element.attribute('href') || element.data('g5-ajaxify-href'),
            params = element.data('g5-ajaxify-params') || false,
            title = element.attribute('title') || window.document.title;

        data = data ? JSON.parse(data) : { parsed: false };
        if (data) {
            var uuid = guid(), extras;

            // TODO: The menu needs to be able to receive POST
            if (element.data('mm-id') || element.parent('[data-mm-id]')) {
                extras = {};
                extras.menutype = $('select.menu-select-wrap').value();
                extras.settings = JSON.stringify(mm.menumanager.settings);
                extras.ordering = JSON.stringify(mm.menumanager.ordering);
                extras.items = JSON.stringify(mm.menumanager.items);
            }

            storage.set(uuid, merge({}, data, {
                target: target,
                parent: parent,
                element: element,
                params: params,
                extras: extras,
                event: event
            }));
            data = { uuid: uuid };
        }

        History.pushState(data, title, url);

        var navbar, active, actives = $('#navbar .active, #main-header .active');

        if (navbar = element.parent('#navbar, #main-header')) {
            if (actives) { actives.removeClass('active'); }

            active = navbar.search('.active');
            if (active) { active.removeClass('active'); }
            element.parent('li').addClass('active');
        }
    });

    // attach change events to configurations selector
    selectorChangeEvent();
});


module.exports = {};
