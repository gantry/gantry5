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
        //URI = AjaxURL(Data.view, Data.method || null);
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

            return false;
        }

        var target = $(Data.target);
        //console.log(Data, State);
        body.getPopover().hideAll(true).destroy();
        if (response.body && response.body.html) {
            (target || $('[data-g5-content]') || body).html(response.body.html);
        } else {
            (target || $('[data-g5-content]') || body).html(response.body);
        }

        if (Data.element) {
            body.emit('statechangeAfter', { target: Data.element, Data: Data });
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

            selectize.input
                .data('g5-ajaxify', '')
                .data('g5-ajaxify-target', selector.data('g5-ajaxify-target') || '[data-g5-content-wrapper]')
                .data('g5-ajaxify-href', options[value].url)
                .data('g5-ajaxify-params', options[value].params ? JSON.stringify(options[value].params) : null);

            $('body').emit('click', { target: selectize.input });
        });

        selectize.HasChangeEvent = true;
    });
};


domready(function() {
    var body = $('body');
    body.delegate('click', '[data-g5-ajaxify]', function(event, element) {
        if (event && event.preventDefault) {
            if (event.which === 2 || event.metaKey) {
                return true;
            }

            event.preventDefault();
        }

        var data = element.data('g5-ajaxify'),
            target = element.data('g5-ajaxify-target'),
            url = element.attribute('href') || element.data('g5-ajaxify-href'),
            params = element.data('g5-ajaxify-params') || false,
            title = element.attribute('title') || window.document.title;

        data = data ? JSON.parse(data) : { parsed: false };
        if (data) {
            var uuid = guid();
            storage.set(uuid, merge({}, data, {
                target: target,
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

    /*body.on('statechangeAfter', function(data){
        if (!data || (!data.Data.params && !data.target.data('selectize'))) { return true; }
        var params = JSON.parse(data.Data.params);

        if (!params.navbar && !data.target.data('selectize')) { return true; }

        selectorChangeEvent();
    });*/

    // attach change events to configurations selector
    selectorChangeEvent();
});


module.exports = {};