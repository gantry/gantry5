"use strict";
var $             = require('../../utils/elements.moofx'),
    domready      = require('elements/domready'),
    modal         = require('../../ui').modal,
    getAjaxSuffix = require('../../utils/get-ajax-suffix'),
    getAjaxURL    = require('../../utils/get-ajax-url'),

    trim          = require('mout/string/trim'),
    contains      = require('mout/array/contains');

domready(function() {
    $('body').delegate('click', '[data-g5-iconpicker]', function(event, element) {
        element = $(element);
        var field = $(element.data('g5-iconpicker')),
            value = trim(field.value()).replace(/\s{2,}/g, ' ').split(' ');

        modal.open({
            content: 'Loading',
            className: 'g5-dialog-theme-default g5-modal-icons',
            remote: getAjaxURL('icons') + getAjaxSuffix(),
            afterClose: function(){
                var popovers = $('.g5-popover');
                if (popovers) { popovers.remove(); }
            },
            remoteLoaded: function(response, content) {
                var html, large, iconData = [],
                    container = content.elements.content,
                    icons = container.search('[data-icon]');

                if (!icons || !response.body.success) {
                    container.html(response.body.html || response.body);
                    return false;
                }

                var updatePreview = function(data){
                    container.find('.icon-preview').html('<i class="fa ' + data + '"></i>');
                };

                container.delegate('click', '[data-icon]', function(event, element){
                    element = $(element);
                    iconData = [element.data('icon')];

                    var active = container.find('[data-icon].active'),
                        options = container.search('.g-particles-header .float-right input:checked, .g-particles-header .float-right select');
                    if (active) { active.removeClass('active'); }
                    if (options) {
                        options.forEach(function(option){
                            var v = $(option).value();
                            if (v && v !== 'fa-') { iconData.push(v); }
                        });
                    }

                    element.addClass('active');

                    updatePreview(iconData.join(' '));
                });

                icons.forEach(function(icon) {
                    icon = $(icon);
                    html = '';
                    for (var i = 5, l = 0; i > l; i--) {
                        large = (!i) ? 'lg' : i + 'x';
                        html += '<i class="fa ' + icon.data('icon') + ' fa-' + large + '"></i> ';
                    }

                    icon.popover({
                        content: html,
                        placement: 'auto',
                        trigger: 'mouse',
                        style: 'above-modal, icons-preview',
                        width: 'auto',
                        targetEvents: false,
                        delay: 1
                    }).on('hidden.popover', function(instance) {
                        if (instance.$target) { instance.$target.remove(); }
                    });

                    if (contains(value, icon.data('icon'))) {
                        // set active icon
                        icon.addClass('active');
                        iconData.push(icon.data('icon'));

                        // toggle options
                        value.forEach(function(name){
                            var field = container.find('[name="' + name + '"]');
                            if (field) {
                                iconData.push(name);
                                field.checked(true);
                            }
                            else {
                                field = container.find('option[value="' + name + '"]');
                               if (field) {
                                   iconData.push(name);
                                   field.parent().value(name);
                               }
                            }
                        });

                        // scroll into place of active icon
                        var wrap = icon.parent('.icons-wrapper'),
                            wrapHeight = wrap[0].offsetHeight;
                        wrap[0].scrollTop = icon[0].offsetTop - (wrapHeight / 2);

                        // update preview
                        updatePreview(iconData.join(' '));
                    }
                });
            }
        });
    });
});

module.exports = {};