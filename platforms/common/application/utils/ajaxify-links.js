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
        Data = State.data;

    if (size(Data) && Data.parsed !== false && storage.get(Data.uuid)) {
        Data = storage.get(Data.uuid);
        //URI = AjaxURL(Data.view, Data.method || null);
    }

    if (Data.element) {
        $('body').emit('statechangeBefore', { target: Data.element });
    }
    request.url(URI + getAjaxSuffix()).send(function(error, response) {
        if (!response.body.success) {
            modal.open({ content: response.body.html });
            //History.back();

            return false;
        }

        var target = $(Data.target);
        $('body').getPopover().hideAll(true).destroy();
        if (response.body && response.body.html) {
            (target || $('body')).html(response.body.html);
        } else {
            (target || $('body')).html(response.body);
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
            title = element.attribute('title') || '';

        data = data ? JSON.parse(data) : { parsed: false };
        if (data) {
            var uuid = guid();
            storage.set(uuid, merge({}, data, {
                target: target,
                element: element,
                event: event
            }));
            data = { uuid: uuid };
        }

        History.pushState(data, title, url);

        var sidebar;
        if (sidebar = element.parent('#sidebar')) {
            sidebar.search('.active').removeClass('active');
            element.parent('li').addClass('active');
        }
    });
});


module.exports = {};