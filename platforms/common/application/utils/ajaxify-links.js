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

History.Adapter.bind(window, 'statechange', function() {
    if (request.running()) {
        return false;
    }
    var State = History.getState(),
        URI = State.url,
        Data = State.data,
        sidebar = $('#sidebar'),
        params = '';

    if (size(Data) && Data.parsed !== false && storage.get(Data.uuid)) {
        Data = storage.get(Data.uuid);
        //URI = AjaxURL(Data.view, Data.method || null);
    }

    if (Data.element) {
        $('body').emit('statechangeBefore', { target: Data.element });
    } else {
        var url = URI.replace(window.location.origin, '');
        Data.element = $('[href="' + url + '"]');
    }

    if (sidebar && Data.element && Data.element.parent('#sidebar')) {
        var lis = sidebar.search('li');
        lis.removeClass('active');
        Data.element.parent('li').addClass('active');
    }

    if (Data.params) {
        params = toQueryString(JSON.parse(Data.params));
    }

    request.url(URI + getAjaxSuffix() + params).method('get').send(function(error, response) {
        if (!response.body.success) {
            modal.open({ content: response.body.html });
            //History.back();

            return false;
        }

        var target = $(Data.target);
        //console.log(Data, State);
        $('body').getPopover().hideAll(true).destroy();
        if (response.body && response.body.html) {
            (target || $('[data-g5-content]') || $('body')).html(response.body.html);
        } else {
            (target || $('[data-g5-content]') || $('body')).html(response.body);
        }

        if (Data.element) {
            $('body').emit('statechangeAfter', { target: Data.element });
        }
    });
});


domready(function() {
    $('body').delegate('click', '[data-g5-ajaxify]', function(event, element) {
        if (event) {
            if (event.which === 2 || event.metaKey) {
                return true;
            }

            event.preventDefault();
        }

        var data = element.data('g5-ajaxify'),
            target = element.data('g5-ajaxify-target'),
            url = element.attribute('href') || element.data('g5-ajaxify-href'),
            params = element.data('g5-ajaxify-params') || false,
            title = element.attribute('title') || '';

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

        var sidebar, active;
        if (sidebar = element.parent('#sidebar')) {
            active = sidebar.search('.active');
            if (active) { active.removeClass('active'); }
            element.parent('li').addClass('active');
        }
    });
});


module.exports = {};