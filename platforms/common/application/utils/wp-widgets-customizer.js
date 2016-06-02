var $ = require('elements');

module.exports = function(field) {
    if (!field) { return false; }

    if ($('body').hasClass('wp-customizer') && jQuery) {
        var widgetContainer = field.parent('.widget-content'),
            title = field.siblings('.g-instancepicker-title');

        if (widgetContainer) {
            var jQueryEvent = jQuery.Event('change');
            jQueryEvent.target = field[0];
            jQuery(widgetContainer[0]).trigger(jQueryEvent);
        }

        if (title) {
            setTimeout(function(){
                title.hideIndicator();
            }, 5);
        }
    }
};