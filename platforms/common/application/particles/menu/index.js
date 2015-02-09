"use strict";
var $             = require('../../utils/elements.moofx'),
    domready      = require('elements/domready'),
    modal         = require('../../ui').modal,
    toastr        = require('../../ui').toastr,
    request       = require('agent'),
    getAjaxSuffix = require('../../utils/get-ajax-suffix');

domready(function() {
    var body = $('body');
    body.delegate('click', '[data-g5-content] .g-main-nav .g-toplevel [data-g5-ajaxify]', function(event, element) {
        var items = $('[data-g5-content] .g-main-nav .g-toplevel [data-g5-ajaxify] !> li');
        if (items) { items.removeClass('active'); }
        element.parent('li').addClass('active');
    });

    body.delegate('click', '#menu-editor .config-cog, #menu-editor .global-menu-settings', function(event, element) {
        event.preventDefault();

        modal.open({
            content: 'Loading',
            //method: 'post',
            //data: data,
            remote: $(element).attribute('href') + getAjaxSuffix(),
            remoteLoaded: function(response, content) {
                var form = content.elements.content.find('form'),
                    submit = content.elements.content.find('input[type="submit"], button[type="submit"]'),
                    dataString = [];

                if (!form || !submit) { return true; }

                // Particle Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();
                    dataString = [];

                    submit.showSpinner();

                    $(form[0].elements).forEach(function(input) {
                        input = $(input);
                        var name = input.attribute('name'),
                            value = input.value();

                        if (!name) { return; }
                        dataString.push(name + '=' + value);
                    });

                    request(form.attribute('method'), form.attribute('action') + getAjaxSuffix(), dataString.join('&'), function(error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function(container) {
                                    if (!response.body.html) { container.style({ width: '90%' }); }
                                }
                            });
                        } else {
                            /*var particle = builder.get(ID),
                                block = builder.get(parentID);

                            // particle attributes
                            particle.setAttributes(response.body.data.options);
                            particle.setTitle(response.body.data.title || 'Untitled');
                            particle.updateTitle(particle.getTitle());

                            // parent block attributes
                            if (response.body.data.block && size(response.body.data.block)) {
                                var sibling = block.block.nextSibling() || block.block.previousSibling(),
                                    currentSize = block.getSize(),
                                    diffSize;

                                block.setAttributes(response.body.data.block);

                                diffSize = currentSize - block.getSize();

                                block.setAnimatedSize(block.getSize());

                                if (sibling) {
                                    sibling = builder.get(sibling.data('lm-id'));
                                    sibling.setAnimatedSize(parseFloat(sibling.getSize()) + diffSize, true);
                                }
                            }

                            lmhistory.push(builder.serialize());*/

                            modal.close();
                            toastr.success('The Menu Item settings have been applied to the Main Menu. <br />Remember to click the Save button to store them.', 'Settings Applied');
                        }

                        submit.hideSpinner();
                    });
                });
            }
        });
    });
});

module.exports = {};