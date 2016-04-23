"use strict";

var prime      = require('prime'),
    Emitter    = require('prime/emitter'),
    Bound      = require('prime-util/prime/bound'),
    Options    = require('prime-util/prime/options'),
    $          = require('elements'),
    ready      = require('elements/domready'),
    zen        = require('elements/zen'),

    DragEvents = require('../../ui/drag.events'),

    forEach    = require('mout/collection/forEach'),
    bind       = require('mout/function/bind'),
    clamp      = require('mout/math/clamp');

var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;

var MOUSEDOWN = DragEvents.EVENTS.START,
    MOUSEMOVE = DragEvents.EVENTS.MOVE,
    MOUSEUP   = DragEvents.EVENTS.STOP,
    FOCUSIN   = isFirefox ? 'focus' : 'focusin';

var ColorPicker = new prime({
    mixin: [Options, Bound],
    inherits: Emitter,
    options: {},
    constructor: function(options) {
        this.setOptions(options);
        this.built = false;
        this.attach();
    },

    attach: function() {
        var body = $('body');

        MOUSEDOWN.forEach(bind(function(mousedown) {
            body.delegate(mousedown, '.g-colorpicker i', this.bound('iconClick'));
        }, this));

        body.delegate(FOCUSIN, '.g-colorpicker input', this.bound('show'), true);


        body.delegate('keydown', '.g-colorpicker input', bind(function(event, element) {
            switch (event.keyCode) {
                case 9: // tab
                    this.hide();
                    break;
                case 13: // enter
                case 27: // esc
                    this.hide();
                    element[0].blur();
                    break;
            }
            return true;
        }, this));

        // Update on keyup
        body.delegate('keyup', '.g-colorpicker input', bind(function(event, element) {
            this.updateFromInput(true, element);
            return true;
        }, this));

        // Update on paste
        body.delegate('paste', '.g-colorpicker input', bind(function(event, element) {
            setTimeout(bind(function() {
                this.updateFromInput(true, element);
            }, this), 1);
        }, this));
    },

    show: function(event, element) {
        var body = $('body');

        if (!this.built) {
            this.build();
        }

        this.element = element;
        this.reposition();
        this.wrapper.addClass('cp-visible');
        this.updateFromInput();

        MOUSEMOVE.forEach(bind(function(mousemove) {
            body.on(mousemove, this.bound('bodyMove'));
        }, this));

        MOUSEDOWN.forEach(bind(function(mousedown) {
            this.wrapper.delegate(mousedown, '.cp-grid, .cp-slider, .cp-opacity-slider', this.bound('bodyDown'));
            body.on(mousedown, this.bound('bodyClick'));
        }, this));

        MOUSEUP.forEach(bind(function(mouseup) {
            body.on(mouseup, this.bound('targetReset'));
        }, this));
    },

    hide: function() {
        var body = $('body');

        if (!this.built) { return; }
        this.wrapper.removeClass('cp-visible');

        MOUSEMOVE.forEach(bind(function(mousemove) {
            body.off(mousemove, this.bound('bodyMove'));
        }, this));

        MOUSEDOWN.forEach(bind(function(mousedown) {
            this.wrapper.undelegate(mousedown, '.cp-grid, .cp-slider, .cp-opacity-slider', this.bound('bodyDown'));
            body.off(mousedown, this.bound('bodyClick'));
        }, this));

        MOUSEUP.forEach(bind(function(mouseup) {
            body.off(mouseup, this.bound('targetReset'));
        }, this));
    },

    iconClick: function(event, element) {
        event.preventDefault();

        var input = $(element).sibling('input');
        input[0].focus();

        this.show(event, input);
    },

    bodyMove: function(event) {
        event.preventDefault();

        if (this.target) { this.move(this.target, event); }
    },

    bodyClick: function(event) {
        var target = $(event.target);
        if (!target.parent('.cp-wrapper') && !target.parent('.g-colorpicker')) {
            this.hide();
        }
    },

    bodyDown: function(event, element) {
        event.preventDefault();

        this.target = element;
        this.move(this.target, event, true);
    },

    targetReset: function(event) {
        event.preventDefault();

        this.target = null;
    },

    move: function(target, event) {
        var input = this.element,
            picker = target.find('.cp-picker'),
            clientRect = target[0].getBoundingClientRect(),
            offsetX = clientRect.left + window.scrollX,
            offsetY = clientRect.top + window.scrollY,
            x = Math.round((event ? event.pageX : 0) - offsetX),
            y = Math.round((event ? event.pageY : 0) - offsetY),
            wx, wy, r, phi;

        // Touch support
        if (event && event.changedTouches) {
            x = (event.changedTouches ? event.changedTouches[0].pageX : 0) - offsetX;
            y = (event.changedTouches ? event.changedTouches[0].pageY : 0) - offsetY;
        }

        if (event && event.manualOpacity) {
            y = clientRect.height;
        }

        // Constrain picker to its container
        if (x < 0) x = 0;
        if (y < 0) y = 0;
        if (x > clientRect.width) x = clientRect.width;
        if (y > clientRect.height) y = clientRect.height;

        // Constrain color wheel values to the wheel
        if (target.parent('.cp-mode-wheel') && picker.parent('.cp-grid')) {
            wx = 75 - x;
            wy = 75 - y;
            r = Math.sqrt(wx * wx + wy * wy);
            phi = Math.atan2(wy, wx);

            if (phi < 0) phi += Math.PI * 2;
            if (r > 75) {
                x = 75 - (75 * Math.cos(phi));
                y = 75 - (75 * Math.sin(phi));
            }

            x = Math.round(x);
            y = Math.round(y);
        }

        // Move the picker
        if (target.hasClass('cp-grid')) {
            picker.style({
                top: y,
                left: x
            });

            this.updateFromPicker(input, target);
        } else {
            picker.style({
                top: y
            });
            this.updateFromPicker(input, target);
        }
    },

    build: function() {
        this.wrapper = zen('div.cp-wrapper.cp-with-opacity.cp-mode-hue');
        this.slider = zen('div.cp-slider.cp-sprite').bottom(this.wrapper).appendChild(zen('div.cp-picker'));
        this.opacitySlider = zen('div.cp-opacity-slider.cp-sprite').bottom(this.wrapper).appendChild(zen('div.cp-picker'));
        this.grid = zen('div.cp-grid.cp-sprite').bottom(this.wrapper).appendChild(zen('div.cp-grid-inner')).appendChild(zen('div.cp-picker'));

        zen('div').bottom(this.grid.find('.cp-picker'));

        var tabs = zen('div.cp-tabs').bottom(this.wrapper);

        this.tabs = {
            hue: zen('div.cp-tab-hue.active').text('HUE').bottom(tabs),
            brightness: zen('div.cp-tab-brightness').text('BRI').bottom(tabs),
            saturation: zen('div.cp-tab-saturation').text('SAT').bottom(tabs),
            wheel: zen('div.cp-tab-wheel').text('WHEEL').bottom(tabs),
            transparent: zen('div.cp-tab-transp').text('TRANSPARENT').bottom(tabs)
        };

        MOUSEDOWN.forEach(bind(function(mousedown) {
            tabs.delegate(mousedown, '> div', bind(function(event, element) {
                if (element == this.tabs.transparent) {
                    this.opacity = 0;
                    var sliderHeight = this.opacitySlider.position().height;
                    this.opacitySlider.find('.cp-picker').style({ 'top': clamp(sliderHeight - (sliderHeight * this.opacity), 0, sliderHeight) });
                    this.move(this.opacitySlider, { manualOpacity: true });
                    return;
                }

                var active = tabs.find('.active'),
                    mode = active.attribute('class').replace(/\s|active|cp-tab-/g, ''),
                    newMode = element.attribute('class').replace(/\s|active|cp-tab-/g, '');

                this.wrapper.removeClass('cp-mode-' + mode).addClass('cp-mode-' + newMode);
                active.removeClass('active');
                element.addClass('active');

                this.mode = newMode;
                this.updateFromInput();
            }, this));
        }, this));

        this.wrapper.bottom('#g5-container');

        this.built = true;
        this.mode = 'hue';
    },

    updateFromInput: function(dontFireEvent, element) {
        element = $(element) || this.element;
        var value = element.value(),
            opacity = value.replace(/\s/g, '').match(/^rgba?\([0-9]{1,3},[0-9]{1,3},[0-9]{1,3},(.+)\)/),
            hex, hsb;

        value = rgbstr2hex(value) || value;
        opacity = opacity ? clamp(opacity[1], 0, 1) : 1;

        if (!(hex = parseHex(value))) { hex = '#ffffff'; }
        hsb = hex2hsb(hex);

        if (this.built) {
            // opacity
            this.opacity = Math.max(opacity, 0);
            var sliderHeight = this.opacitySlider.position().height;
            this.opacitySlider.find('.cp-picker').style({ 'top': clamp(sliderHeight - (sliderHeight * this.opacity), 0, sliderHeight) });

            // bg color
            var gridHeight = this.grid.position().height,
                gridWidth = this.grid.position().width,
                r, phi, x, y;

            sliderHeight = this.slider.position().height;

            switch (this.mode) {
                case 'wheel':
                    // Set grid position
                    r = clamp(Math.ceil(hsb.s * 0.75), 0, gridHeight / 2);
                    phi = hsb.h * Math.PI / 180;
                    x = clamp(75 - Math.cos(phi) * r, 0, gridWidth);
                    y = clamp(75 - Math.sin(phi) * r, 0, gridHeight);
                    this.grid.style({ backgroundColor: 'transparent' }).find('.cp-picker').style({
                        top: y,
                        left: x
                    });

                    // Set slider position
                    y = 150 - (hsb.b / (100 / gridHeight));
                    if (hex === '') y = 0;
                    this.slider.find('.cp-picker').style({ top: y });

                    // Update panel color
                    this.slider.style({
                        backgroundColor: hsb2hex({
                            h: hsb.h,
                            s: hsb.s,
                            b: 100
                        })
                    });
                    break;

                case 'saturation':
                    // Set grid position
                    x = clamp((5 * hsb.h) / 12, 0, 150);
                    y = clamp(gridHeight - Math.ceil(hsb.b / (100 / gridHeight)), 0, gridHeight);
                    this.grid.find('.cp-picker').style({
                        top: y,
                        left: x
                    });

                    // Set slider position
                    y = clamp(sliderHeight - (hsb.s * (sliderHeight / 100)), 0, sliderHeight);
                    this.slider.find('.cp-picker').style({ top: y });

                    // Update UI
                    this.slider.style({
                        backgroundColor: hsb2hex({
                            h: hsb.h,
                            s: 100,
                            b: hsb.b
                        })
                    });
                    this.grid.find('.cp-grid-inner').style({ opacity: hsb.s / 100 });
                    break;

                case 'brightness':
                    // Set grid position
                    x = clamp((5 * hsb.h) / 12, 0, 150);
                    y = clamp(gridHeight - Math.ceil(hsb.s / (100 / gridHeight)), 0, gridHeight);
                    this.grid.find('.cp-picker').style({
                        top: y,
                        left: x
                    });

                    // Set slider position
                    y = clamp(sliderHeight - (hsb.b * (sliderHeight / 100)), 0, sliderHeight);
                    this.slider.find('.cp-picker').style({ top: y });

                    // Update UI
                    this.slider.style({
                        backgroundColor: hsb2hex({
                            h: hsb.h,
                            s: hsb.s,
                            b: 100
                        })
                    });
                    this.grid.find('.cp-grid-inner').style({ opacity: 1 - (hsb.b / 100) });
                    break;
                case 'hue':
                default:
                    // Set grid position
                    x = clamp(Math.ceil(hsb.s / (100 / gridWidth)), 0, gridWidth);
                    y = clamp(gridHeight - Math.ceil(hsb.b / (100 / gridHeight)), 0, gridHeight);
                    this.grid.find('.cp-picker').style({
                        top: y,
                        left: x
                    });

                    // Set slider position
                    y = clamp(sliderHeight - (hsb.h / (360 / sliderHeight)), 0, sliderHeight);
                    this.slider.find('.cp-picker').style({ top: y });

                    // Update panel color
                    this.grid.style({
                        backgroundColor: hsb2hex({
                            h: hsb.h,
                            s: 100,
                            b: 100
                        })
                    });
                    break;
            }
        }

        if (!dontFireEvent) { element.value(this.getValue(hex)); }

        this.emit('change', element, hex, opacity);

    },

    updateFromPicker: function(input, target) {
        var getCoords = function(picker, container) {

            var left, top;
            if (!picker.length || !container) return null;
            left = picker[0].getBoundingClientRect().left;
            top = picker[0].getBoundingClientRect().top;

            return {
                x: left - container[0].getBoundingClientRect().left + (picker[0].offsetWidth / 2),
                y: top - container[0].getBoundingClientRect().top + (picker[0].offsetHeight / 2)
            };

        };

        var hex, hue, saturation, brightness, x, y, r, phi,

            // Panel objects
            grid = this.wrapper.find('.cp-grid'),
            slider = this.wrapper.find('.cp-slider'),
            opacitySlider = this.wrapper.find('.cp-opacity-slider'),

            // Picker objects
            gridPicker = grid.find('.cp-picker'),
            sliderPicker = slider.find('.cp-picker'),
            opacityPicker = opacitySlider.find('.cp-picker'),

            // Picker positions
            gridPos = getCoords(gridPicker, grid),
            sliderPos = getCoords(sliderPicker, slider),
            opacityPos = getCoords(opacityPicker, opacitySlider),

            // Sizes
            gridWidth = grid[0].getBoundingClientRect().width,
            gridHeight = grid[0].getBoundingClientRect().height,
            sliderHeight = slider[0].getBoundingClientRect().height,
            opacitySliderHeight = opacitySlider[0].getBoundingClientRect().height;

        var value = this.element.value();
        value = rgbstr2hex(value) || value;
        if (!(hex = parseHex(value))) { hex = '#ffffff'; }

        // Handle colors
        if (target.hasClass('cp-grid') || target.hasClass('cp-slider')) {

            // Determine HSB values
            switch (this.mode) {
                case 'wheel':
                    // Calculate hue, saturation, and brightness
                    x = (gridWidth / 2) - gridPos.x;
                    y = (gridHeight / 2) - gridPos.y;
                    r = Math.sqrt(x * x + y * y);
                    phi = Math.atan2(y, x);
                    if (phi < 0) phi += Math.PI * 2;
                    if (r > 75) {
                        r = 75;
                        gridPos.x = 69 - (75 * Math.cos(phi));
                        gridPos.y = 69 - (75 * Math.sin(phi));
                    }
                    saturation = clamp(r / 0.75, 0, 100);
                    hue = clamp(phi * 180 / Math.PI, 0, 360);
                    brightness = clamp(100 - Math.floor(sliderPos.y * (100 / sliderHeight)), 0, 100);
                    hex = hsb2hex({
                        h: hue,
                        s: saturation,
                        b: brightness
                    });

                    // Update UI
                    slider.style({
                        backgroundColor: hsb2hex({
                            h: hue,
                            s: saturation,
                            b: 100
                        })
                    });
                    break;

                case 'saturation':
                    // Calculate hue, saturation, and brightness
                    hue = clamp(parseInt(gridPos.x * (360 / gridWidth), 10), 0, 360);
                    saturation = clamp(100 - Math.floor(sliderPos.y * (100 / sliderHeight)), 0, 100);
                    brightness = clamp(100 - Math.floor(gridPos.y * (100 / gridHeight)), 0, 100);
                    hex = hsb2hex({
                        h: hue,
                        s: saturation,
                        b: brightness
                    });

                    // Update UI
                    slider.style({
                        backgroundColor: hsb2hex({
                            h: hue,
                            s: 100,
                            b: brightness
                        })
                    });
                    grid.find('.cp-grid-inner').style({ opacity: saturation / 100 });
                    break;

                case 'brightness':
                    // Calculate hue, saturation, and brightness
                    hue = clamp(parseInt(gridPos.x * (360 / gridWidth), 10), 0, 360);
                    saturation = clamp(100 - Math.floor(gridPos.y * (100 / gridHeight)), 0, 100);
                    brightness = clamp(100 - Math.floor(sliderPos.y * (100 / sliderHeight)), 0, 100);
                    hex = hsb2hex({
                        h: hue,
                        s: saturation,
                        b: brightness
                    });

                    // Update UI
                    slider.style({
                        backgroundColor: hsb2hex({
                            h: hue,
                            s: saturation,
                            b: 100
                        })
                    });
                    grid.find('.cp-grid-inner').style({ opacity: 1 - (brightness / 100) });
                    break;

                default:
                    // Calculate hue, saturation, and brightness
                    hue = clamp(360 - parseInt(sliderPos.y * (360 / sliderHeight), 10), 0, 360);
                    saturation = clamp(Math.floor(gridPos.x * (100 / gridWidth)), 0, 100);
                    brightness = clamp(100 - Math.floor(gridPos.y * (100 / gridHeight)), 0, 100);
                    hex = hsb2hex({
                        h: hue,
                        s: saturation,
                        b: brightness
                    });

                    // Update UI
                    grid.style({
                        backgroundColor: hsb2hex({
                            h: hue,
                            s: 100,
                            b: 100
                        })
                    });
                    break;

            }
        }

        // Handle opacity
        if (target.hasClass('cp-opacity-slider')) {
            this.opacity = Math.max(parseFloat(1 - (opacityPos.y / opacitySliderHeight)).toFixed(2), 0);
        }

        // Adjust case
        input.value(this.getValue(hex));

        // Handle change event
        this.emit('change', this.element, hex, this.opacity);

    },

    reposition: function() {
        var offset = this.element[0].getBoundingClientRect(),
            ct = $('#g5-container')[0].getBoundingClientRect();
        this.wrapper.style({
            top: offset.top + offset.height - ct.top,
            left: offset.left - ct.left
        });
    },

    getValue: function(hex) {
        if (this.opacity == 1) { return hex; }
        var rgb = hex2rgb(hex);
        return 'rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + this.opacity + ')';
    }
});

// Parses a string and returns a valid hex string when possible
var parseHex = function(string) {
    string = string.replace(/[^A-F0-9]/ig, '');
    if (string.length !== 3 && string.length !== 6) return '';
    if (string.length === 3) {
        string = string[0] + string[0] + string[1] + string[1] + string[2] + string[2];
    }

    return '#' + string.toLowerCase();
};

// Converts an HSB object to an RGB object
var hsb2rgb = function(hsb) {
    var rgb = {};
    var h = Math.round(hsb.h);
    var s = Math.round(hsb.s * 255 / 100);
    var v = Math.round(hsb.b * 255 / 100);
    if (s === 0) {
        rgb.r = rgb.g = rgb.b = v;
    } else {
        var t1 = v;
        var t2 = (255 - s) * v / 255;
        var t3 = (t1 - t2) * (h % 60) / 60;
        if (h === 360) h = 0;
        if (h < 60) {
            rgb.r = t1;
            rgb.b = t2;
            rgb.g = t2 + t3;
        }
        else if (h < 120) {
            rgb.g = t1;
            rgb.b = t2;
            rgb.r = t1 - t3;
        }
        else if (h < 180) {
            rgb.g = t1;
            rgb.r = t2;
            rgb.b = t2 + t3;
        }
        else if (h < 240) {
            rgb.b = t1;
            rgb.r = t2;
            rgb.g = t1 - t3;
        }
        else if (h < 300) {
            rgb.b = t1;
            rgb.g = t2;
            rgb.r = t2 + t3;
        }
        else if (h < 360) {
            rgb.r = t1;
            rgb.g = t2;
            rgb.b = t1 - t3;
        }
        else {
            rgb.r = 0;
            rgb.g = 0;
            rgb.b = 0;
        }
    }
    return {
        r: Math.round(rgb.r),
        g: Math.round(rgb.g),
        b: Math.round(rgb.b)
    };
};

// Converts an RGB object to a hex string
var rgb2hex = function(rgb) {
    var hex = [
        rgb.r.toString(16),
        rgb.g.toString(16),
        rgb.b.toString(16)
    ];

    forEach(hex, function(val, nr) {
        if (val.length === 1) hex[nr] = '0' + val;
    });

    return '#' + hex.join('');
};

var rgbstr2hex = function(rgb) {
    rgb = rgb.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i);
    return (rgb && rgb.length === 4) ? "#" +
    ("0" + parseInt(rgb[1], 10).toString(16)).slice(-2) +
    ("0" + parseInt(rgb[2], 10).toString(16)).slice(-2) +
    ("0" + parseInt(rgb[3], 10).toString(16)).slice(-2) : '';
};

// Converts an HSB object to a hex string
var hsb2hex = function(hsb) {
    return rgb2hex(hsb2rgb(hsb));
};

// Converts a hex string to an HSB object
var hex2hsb = function(hex) {
    var hsb = rgb2hsb(hex2rgb(hex));
    if (hsb.s === 0) hsb.h = 360;
    return hsb;
};

// Converts an RGB object to an HSB object
var rgb2hsb = function(rgb) {
    var hsb = {
        h: 0,
        s: 0,
        b: 0
    };
    var min = Math.min(rgb.r, rgb.g, rgb.b);
    var max = Math.max(rgb.r, rgb.g, rgb.b);
    var delta = max - min;
    hsb.b = max;
    hsb.s = max !== 0 ? 255 * delta / max : 0;
    if (hsb.s !== 0) {
        if (rgb.r === max) {
            hsb.h = (rgb.g - rgb.b) / delta;
        } else if (rgb.g === max) {
            hsb.h = 2 + (rgb.b - rgb.r) / delta;
        } else {
            hsb.h = 4 + (rgb.r - rgb.g) / delta;
        }
    } else {
        hsb.h = -1;
    }
    hsb.h *= 60;
    if (hsb.h < 0) {
        hsb.h += 360;
    }
    hsb.s *= 100 / 255;
    hsb.b *= 100 / 255;
    return hsb;
};

// Converts a hex string to an RGB object
var hex2rgb = function(hex) {
    hex = parseInt(((hex.indexOf('#') > -1) ? hex.substring(1) : hex), 16);
    return {
        /* jshint ignore:start */
        r: hex >> 16,
        g: (hex & 0x00FF00) >> 8,
        b: (hex & 0x0000FF)
        /* jshint ignore:end */
    };
};


ready(function() {
    var x = new ColorPicker(), body = $('body');
    x.on('change', function(element, hex, opacity) {
        clearTimeout(this.timer);
        var rgb = hex2rgb(hex),
            yiq = (((rgb.r * 299) + (rgb.g * 587) + (rgb.b * 114)) / 1000) >= 128 ? 'dark' : 'light',
            check = yiq == 'dark' || (!opacity || opacity < 0.35);

        if (opacity < 1) {
            var str = 'rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + opacity + ')';
            element.style({ backgroundColor: str });
        } else {
            element.style({ backgroundColor: hex });
        }

        element.parent('.g-colorpicker')[!check ? 'addClass' : 'removeClass']('light-text');

        this.timer = setTimeout(function() {
            element.emit('input');
            body.emit('input', { target: element });
        }, 150);

    });
});

module.exports = ColorPicker;
