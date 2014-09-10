var getSupportedEvent = function(events){
    events = events.split(' ');

    var element = document.createElement('div'), event;
    var isSupported = false;

    for (var i = events.length - 1; i >= 0; i--) {
        event       = 'on' + events[i];
        isSupported = (event in element);

        if (!isSupported){
            element.setAttribute(event, 'return;');
            isSupported = typeof element[event] == 'function';
        }

        if (isSupported){
            isSupported = events[i];
            break;
        }
    }

    element = null;
    return isSupported;
};

var EVENT = {
    START: getSupportedEvent('mousedown touchstart MSPointerDown pointerdown'),
    MOVE:  getSupportedEvent('mousemove touchmove MSPointerMove pointermove'),
    STOP:  getSupportedEvent('mouseup touchend MSPointerUp pointerup'),
};


module.exports = EVENT;
