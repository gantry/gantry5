"use strict";
var $             = require('../../utils/elements.moofx'),
    domready      = require('elements/domready'),
    modal         = require('../../ui').modal,
    getAjaxSuffix = require('../../utils/get-ajax-suffix'),
    getAjaxURL    = require('../../utils/get-ajax-url');

domready(function() {
    $('body').delegate('click', '[data-g5-iconpicker]', function(event, element) {
        modal.open({
            content: 'Loading',
            className: 'g5-dialog-theme-default g5-modal-icons',
            remote: getAjaxURL('icons') + getAjaxSuffix(),
            remoteLoaded: function(response, content) {
                var html, large;
                content.elements.content.search('[data-icon]').forEach(function(icon){
                    icon = $(icon);
                    html = '';
                    for(var i = 5, l = 0; i > l; i--){
                        large = (!i) ? 'lg' :  i + 'x';
                        html += '<i class="fa ' + icon.data('icon') + ' fa-' + large + '"></i> ';
                    }

                    icon.popover({
                        content: html,
                        placement: 'auto',
                        trigger: 'mouse',
                        style: 'above-modal, icons-preview',
                        width: 'auto',
                        targetEvents: false,
                        delay: 0
                    }).on('hidden.popover', function(instance){
                        if (instance.$target) { instance.$target.remove(); }
                    });
                });
            }
        });
        /*var popover = $(element).getPopover({
            type: 'async',
            placement: 'right',
            width: 700,
            trigger: 'click',
            style: 'icons',
            url: getAjaxURL('icons') + getAjaxSuffix()
        }).on('shown.popover', function(p) {
            p.displayContent();
        });*/

        //popover.show();

        console.log(getAjaxURL('icons'));
        /*particles.popover({
            type: 'async',
            placement: 'left-bottom',
            width: '200',
            style: 'particles, inverse, fixed, nooverflow',
            url: particles.attribute('href') + getAjaxSuffix()
        }).on('shown.popover', function(popover) {
            if (popover.$target.particleFilter) { return false; }

            var search = popover.$target.find('input[type=text]'),
                list = popover.$target.search('[data-lm-blocktype]');
            if (!search) { return false; }

            popover.$target.particleFilter = true;
            search.on('input', function(e) {
                list.style({ display: 'none' }).forEach(function(blocktype) {
                    var value = this.value().toLowerCase();
                    blocktype = $(blocktype);
                    if (blocktype.data('lm-blocktype').toLowerCase().match(value) || blocktype.text().toLowerCase().match(value)) {
                        blocktype.style({ display: 'block' });
                    }
                }, this);
            });
        });*/
    });
});

module.exports = {};