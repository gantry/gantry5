'use strict';

var rAF = (function() {
    return window.requestAnimationFrame ||
        window.webkitRequestAnimationFrame ||
        function(callback) { window.setTimeout(callback, 1000 / 60); };
}());

var decouple = function(element, event, callback) {
    var evt, tracking = false;
    element = element[0] || element;

    var capture = function(e) {
        evt = e;
        track();
    };

    var track = function() {
        if (!tracking) {
            rAF(update);
            tracking = true;
        }
    };

    var update = function() {
        callback.call(element, evt);
        tracking = false;
    };

    try {
        element.addEventListener(event, capture, false);
    } catch (e) {}

    return capture;
};

module.exports = decouple;