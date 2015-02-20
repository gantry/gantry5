"use strict";

var prime         = require('prime'),
    $             = require('../utils/elements.utils'),
    zen           = require('elements/zen'),
    domready      = require('elements/domready'),
    storage       = require('prime/map')(),
    modal         = require('../ui').modal,

    size          = require('mout/collection/size'),
    merge         = require('mout/object/merge'),
    guid          = require('mout/random/guid'),
    toQueryString = require('mout/queryString/encode'),
    contains      = require('mout/string/contains'),

    request       = require('agent')(),
    History       = require('./history'),
    getAjaxSuffix = require('./get-ajax-suffix'),
    mm            = require('../menu');

require('../ui/popover');

var ERROR = false;

History.Adapter.bind(window, 'statechange', function() {
    if (request.running()) {
        return false;
    }
    var body = $('body'),
        State = History.getState(),
        URI = State.url,
        Data = State.data,
        sidebar = $('#navbar'),
        params = '';

    if (size(Data) && Data.parsed !== false && storage.get(Data.uuid)) {
        Data = storage.get(Data.uuid);
    }

    if (Data.element) {
        body.emit('statechangeBefore', { target: Data.element, Data: Data });
    } else {
        var url = URI.replace(window.location.origin, '');
        Data.element = $('[href="' + url + '"]');
    }

    URI = URI + getAjaxSuffix();

    if (sidebar && Data.element && Data.element.parent('#navbar')) {
        var lis = sidebar.search('li');
        lis.removeClass('active');
        Data.element.parent('li').addClass('active');
    }

    if (Data.params) {
        params = toQueryString(JSON.parse(Data.params));
        if (contains(URI, '?')) { params = params.replace(/^\?/, '&'); }
    }

    if (!ERROR) { modal.closeAll(); }

    request.url(URI + params).data(Data.extras || {}).method(Data.extras ? 'post' : 'get').send(function(error, response) {
        if (!response.body.success) {
            ERROR = true;
            modal.open({
                content: response.body.html || response.body,
                afterOpen: function(container) {
                    if (!response.body.html) { container.style({ width: '90%' }); }
                }
            });

            History.back();

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
                fader.style({opacity: 0}).animate({opacity: 1});
            }
        } else { destination.html(response.body); }

        body.getPopover().hideAll(true).destroy();

        if (Data.element) {
            body.emit('statechangeAfter', { target: Data.element, Data: Data });
        }

        var element = (Data.event && Data.event.activeSpinner) || Data.element;
        if (element) {
            element.hideIndicator();
        }

        var selects = $('[data-selectize]');
        if (selects) { selects.selectize(); }
        selectorChangeEvent();

        body.emit('statechangeEnd');
    });
});

var selectorChangeEvent = function(){
    var selectors = $('[data-selectize-ajaxify]');
    if (!selectors) { return; }

    selectors.forEach(function(selector) {
        selector = $(selector);
        var selectize = selector.selectize().selectizeInstance;
        if (!selectize || selectize.HasChangeEvent) { return; }

        selectize.on('change', function() {
            var value = selectize.getValue(),
                options = selectize.Options;

            if (!options[value]) { return; }

            selectize.input
                .data('g5-ajaxify', '')
                .data('g5-ajaxify-target', selector.data('g5-ajaxify-target') || '[data-g5-content-wrapper]')
                .data('g5-ajaxify-target-parent', selector.data('g5-ajaxify-target-parent') || null)
                .data('g5-ajaxify-href', options[value].url)
                .data('g5-ajaxify-params', options[value].params ? JSON.stringify(options[value].params) : null);


            var active = $('#navbar li.active') || $('#main-header li.active') || $('#navbar li:nth-child(2)');
            if (active) { active.showIndicator(); }

            $('body').emit('click', { target: selectize.input, activeSpinner: active });
        });

        selectize.HasChangeEvent = true;
    });
};


domready(function() {
    var body = $('body');
    body.delegate('click', '[data-g5-ajaxify]', function(event, element) {
        if (event && event.preventDefault) {
            if (event.which === 2 || event.metaKey || event.ctrlKey || event.altKey || event.shiftKey) {
                return true;
            }

            event.preventDefault();
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