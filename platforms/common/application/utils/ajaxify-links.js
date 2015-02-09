"use strict";

var prime         = require('prime'),
    $             = require('../utils/elements.moofx'),
    zen           = require('elements/zen'),
    domready      = require('elements/domready'),
    storage       = require('prime/map')(),
    modal         = require('../ui').modal,

    size          = require('mout/collection/size'),
    merge         = require('mout/object/merge'),
    guid          = require('mout/random/guid'),
    toQueryString = require('mout/queryString/encode'),

    request       = require('agent')(),
    History       = require('./history'),
    getAjaxSuffix = require('./get-ajax-suffix');

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

    if (sidebar && Data.element && Data.element.parent('#navbar')) {
        var lis = sidebar.search('li');
        lis.removeClass('active');
        Data.element.parent('li').addClass('active');
    }

    if (Data.params) {
        params = toQueryString(JSON.parse(Data.params));
    }

    if (!ERROR) { modal.closeAll(); }

    request.url(URI + getAjaxSuffix() + params).method('get').send(function(error, response) {
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
                Data.element.hideSpinner();
            }

            return false;
        }

        var target = Data.parent ? Data.element.parent(Data.parent) : $(Data.target),
            destination = (target || $('[data-g5-content]') || body);

        if (response.body && response.body.html) { destination.html(response.body.html); }
        else { destination.html(response.body); }

        body.getPopover().hideAll(true).destroy();

        if (Data.element) {
            body.emit('statechangeAfter', { target: Data.element, Data: Data });
        }

        if (Data.event.activeSpinner || Data.element) {
            (Data.event.activeSpinner || Data.element)['hideSpinner']();
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
            if (active) { active.showSpinner(); }

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

        element.showSpinner();

        var data = element.data('g5-ajaxify'),
            target = element.data('g5-ajaxify-target'),
            parent = element.data('g5-ajaxify-target-parent'),
            url = element.attribute('href') || element.data('g5-ajaxify-href'),
            params = element.data('g5-ajaxify-params') || false,
            title = element.attribute('title') || window.document.title;

        data = data ? JSON.parse(data) : { parsed: false };
        if (data) {
            var uuid = guid();
            storage.set(uuid, merge({}, data, {
                target: target,
                parent: parent,
                element: element,
                params: params,
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