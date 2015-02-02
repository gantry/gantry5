"use strict";
var $             = require('../../utils/elements.moofx'),
    domready      = require('elements/domready'),
    modal         = require('../../ui').modal,
    getAjaxSuffix = require('../../utils/get-ajax-suffix');

domready(function() {
    $('body').delegate('click', '[data-g5-content] .g-main-nav .g-toplevel [data-g5-ajaxify]', function(event, element) {
        var items = $('[data-g5-content] .g-main-nav .g-toplevel [data-g5-ajaxify] !> li');
        if (items) { items.removeClass('active'); }
        element.parent('li').addClass('active');
    });

    $('body').delegate('click', '#menu-editor .config-cog, #menu-editor .global-menu-settings', function(event, element) {
        event.preventDefault();
        
        modal.open({
            content: 'Loading',
            remote: $(element).attribute('href') + getAjaxSuffix()
        });
    });
});

module.exports = {};