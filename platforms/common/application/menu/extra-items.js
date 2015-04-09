"use strict";
var $             = require('elements'),
    zen           = require('elements/zen'),
    ready         = require('elements/domready'),
    modal         = require('../ui').modal,
    request       = require('agent'),
    getAjaxSuffix = require('../utils/get-ajax-suffix'),
    deepEquals    = require('mout/lang/deepEquals');

var StepOne = function(map, mode) { // mode [reorder, resize, evenResize]
    if (mode !== 'reorder') { return; }
    this.resizer.updateItemSizes();

    var save    = $('[data-save]'),
        current = {
            settings: this.settings,
            ordering: this.ordering,
            items: this.items
        };

    if (!deepEquals(map, current)) {
        save.showIndicator('fa fa-fw changes-indicator fa-circle-o');
    } else {
        save.hideIndicator();
    }

    if (this.isParticle && this.isNewParticle) {
        var blocktype = this.block.data('mm-blocktype');
        this.block.attribute('data-mm-blocktype', null).addClass('g-menu-item-' + blocktype).data('mm-original-type', blocktype);
        zen('span.menu-item-type.badge').text(blocktype).after(this.block.find('.menu-item .title'));
        modal.open({
            content: 'Loading',
            method: 'post',
            //data: data,
            remote: $(this.block).find('.config-cog').attribute('href') + getAjaxSuffix(),
            remoteLoaded: function(response, content) {

            }
        });
    }

    this.type = undefined;
};

var StepTwo = function(data, content, button) {
    var uri = content.find('[data-mm-particle-stepone]').data('mm-particle-stepone');

    request('post', uri + getAjaxSuffix(), data, function(error, response) {
        if (!response.body.success) {
            modal.open({
                content: response.body.html || response.body,
                afterOpen: function(container) {
                    if (!response.body.html) { container.style({ width: '90%' }); }
                }
            });

            button.hideIndicator();

            return;
        }

        content.html(response.body.html);

        var urlTemplate = content.find('.g-urltemplate');
        if (urlTemplate) { $('body').emit('input', {target: urlTemplate}); }
    });
};


ready(function(){
    var body = $('body');

    body.delegate('click', '.menu-editor-extras [data-lm-blocktype], .menu-editor-extras [data-mm-module]', function(event, element) {
        var container    = element.parent('.menu-editor-extras'),
            elements     = container.search('[data-lm-blocktype], [data-mm-module]'),
            selectButton = container.find('[data-mm-select]');

        elements.removeClass('selected');
        element.addClass('selected');

        selectButton.attribute('disabled', null);
    });

    // second step
    body.delegate('click', '.menu-editor-extras [data-mm-select]', function(event, element) {
        event.preventDefault();

        if (element.hasClass('disabled') || element.attribute('disabled')) { return false; }

        var container = element.parent('.menu-editor-extras'),
            selected  = container.find('[data-lm-blocktype].selected, [data-mm-module].selected'),
            type      = selected.data('mm-type'),
            data      = {type: 'particle'};

        switch (type) {
            case 'particle':
                data['particle'] = selected.data('lm-subtype');
                break;

            case 'module':
                data['particle'] = type;
                data['title'] = selected.find('[data-mm-title]').data('mm-title');
                data['attributes'] = { module_id: selected.data('mm-module') };
                break;
        }

        element.showIndicator();

        StepTwo(data, element.parent('.g5-content'), element);
    });
});

module.exports = StepOne;