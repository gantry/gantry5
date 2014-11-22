"use strict";

var prime = require('prime'),
    $ = require('../utils/elements.moofx'),
    zen = require('elements/zen'),
    domready = require('elements/domready'),
    storage = require('prime/map')(),
    modal   = require('../ui').modal,

    size = require('mout/collection/size'),
    merge = require('mout/object/merge'),
    guid = require('mout/random/guid'),

    request = require('agent')(),
    History = require('./history'),
    AjaxURL = require('./ajax-uri');


History.Adapter.bind(window, 'statechange', function () {
    if (request.running()) {
        return false;
    }
    var State = History.getState(),
        URI = State.url,
        Data = State.data;

    if (size(Data) && storage.get(Data.uuid)) {
        Data = storage.get(Data.uuid);
        URI = History.getBaseUrl() + AjaxURL(Data.view, Data.method || null);
    }

    if (Data.element) {
        $('body').emit('statechangeBefore', { target: Data.element });
    }
    request.url(URI).send(function (error, response) {
        if (response.error){
            var messages = [];
            response.body.data.exceptions.forEach(function(error){
                messages.push('<h1>Error ' + error.code + ' - ' + error.message + '</h1><div>File: ' + error.file + ':' + error.line + '</div>')
            });

            modal.open({content: messages.join('<br />') });
            //History.back();

            return false;
        }

        var target = $(Data.target);
        if (response.body && response.body.data && response.body.data.html) {
            (target || $('body')).html(response.body.data.html);
        } else {
            (target || $('body')).html(response.body);
        }

        if (Data.element) {
            $('body').emit('statechangeAfter', { target: Data.element });
        }
    });
});


domready(function () {
    $('body').delegate('click', '[data-g5-ajaxify]', function (event, element) {
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

        data = data ? JSON.parse(data) : null;
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
        if (sidebar = element.parent('#sidebar')){
            sidebar.search('.active').removeClass('active');
            element.parent('li').addClass('active');
        }
    });
});


module.exports = {};