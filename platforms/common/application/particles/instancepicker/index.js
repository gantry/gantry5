"use strict";

var $             = require('elements'),
    zen           = require('elements/zen'),
    ready         = require('elements/domready'),
    modal         = require('../../ui').modal,
    request       = require('agent'),
    trim          = require('mout/string/trim'),
    getAjaxURL    = require('../../utils/get-ajax-url').global,
    getAjaxSuffix = require('../../utils/get-ajax-suffix');


ready(function(){
    var body = $('body');

    body.delegate('click', '[data-g-instancepicker]', function(event, element){
        if (event) { event.preventDefault(); }

        var data = JSON.parse(element.data('g-instancepicker')),
            field = $('[name="' + data.field + '"]'),
            uri = 'particle' + ((data.type == 'module') ? '/module' : ''),
            value;

        if (!field) { return false; }

        value = field.value();

        modal.open({
            content: 'Loading',
            method: !value || data.type == 'module' ? 'get' : 'post',
            data: !value ? {} : {},
            remote: getAjaxURL(uri) + getAjaxSuffix(),
            remoteLoaded: function(response, modal) {
                var content = modal.elements.content;
                content.find('[data-mm-select]').data('g-instancepicker', element.data('g-instancepicker'));
            }
        });
    });
});

module.exports = {};