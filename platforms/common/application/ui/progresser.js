"use strict";

var $        = require('elements'),
    prime    = require('prime'),
    Emitter  = require('prime/emitter'),
    Bound    = require('prime-util/prime/bound'),
    Options  = require('prime-util/prime/options'),
    zen      = require('elements/zen'),
    moofx    = require('moofx'),

    bind     = require('mout/function/bind'),
    isArray  = require('mout/lang/isArray'),
    isNumber = require('mout/lang/isNumber');

var Progresser = new prime({

    mixin: [Bound, Options],

    inherits: Emitter,

    options: {
        value: 0.0,
        size: 50.0,
        startAngle: -Math.PI / 2,
        thickness: 'auto',
        fill: { // { color: 'hex|rgba' } || { gradient: [from, to], gradientAngle: Math.PI / 4, gradientDirection: [x0, y0, x1, y1] }
            gradient: ['#9e38eb', '#4e68fc']//['#3aeabb', '#fdd250']
        },
        emptyFill: 'rgba(0, 0, 0, .1)',
        animation: {
            duration: 1200,
            equation: 'cubic-bezier(0.645, 0.045, 0.355, 1)'
        },
        animationStartValue: 0.0,
        reverse: false,
        lineCap: 'butt', // butt, round, square
        insertElement: null,
        insertLocation: 'before'
    },

    constructor: function(element, options) {
        this.setOptions(options);

        this.element = this.element || $(element);
        this.canvas = this.canvas || zen('canvas')[this.options.insertLocation || 'before'](this.options.insertElement || this.element)[0];
        this.radius = this.options.size / 2;
        this.arcFill = null;
        this.lastFrameValue = 0.0;

        this.canvas.width = this.options.size;
        this.canvas.height = this.options.size;
        this.ctx = this.canvas.getContext('2d');

        this.initFill();
        this.draw();
    },

    initFill: function() {
        var fill = this.options.fill,
            size = this.options.size,
            ctx  = this.ctx;

        if (!fill) { throw Error('The fill is not specified.'); }

        if (fill.color) {
            this.arcFill = fill.color;
        }

        if (fill.gradient) {
            var gr = fill.gradient;

            if (gr.length == 1) { this.arcFill = gr[0]; }
            else {
                var ga = fill.gradientAngle || 0,  // gradient direction angle; 0 by default
                    gd = fill.gradientDirection || [
                            size / 2 * (1 - Math.cos(ga)), // x0
                            size / 2 * (1 + Math.sin(ga)), // y0
                            size / 2 * (1 + Math.cos(ga)), // x1
                            size / 2 * (1 - Math.sin(ga))  // y1
                        ],
                    lg = ctx.createLinearGradient.apply(ctx, gd);

                for (var i = 0; i < gr.length; i++) {
                    var color = gr[i],
                        pos   = i / (gr.length - 1);

                    if (isArray(color)) {
                        pos = color[1];
                        color = color[0];
                    }

                    lg.addColorStop(pos, color);
                }

                this.arcFill = lg;
            }
        }
    },

    draw: function() {
        this[this.options.animation ? 'drawAnimated' : 'drawFrame'](this.options.value);
    },

    drawFrame: function(v) {
        this.lastFrameValue = v;
        this.ctx.clearRect(0, 0, this.options.size, this.options.size);
        this.drawEmptyArc(v);
        this.drawArc(v);
    },

    drawArc: function(v) {
        var ctx = this.ctx,
            r   = this.radius,
            t   = this.getThickness(),
            a   = this.options.startAngle;

        ctx.save();
        ctx.beginPath();

        if (!this.options.reverse) {
            ctx.arc(r, r, r - t / 2, a, a + Math.PI * 2 * v);
        } else {
            ctx.arc(r, r, r - t / 2, a - Math.PI * 2 * v, a);
        }

        ctx.lineWidth = t;
        ctx.lineCap = this.options.lineCap;
        ctx.strokeStyle = this.arcFill;
        ctx.stroke();
        ctx.restore();
    },

    drawEmptyArc: function(v) {
        var ctx = this.ctx,
            r   = this.radius,
            t   = this.getThickness(),
            a   = this.options.startAngle;

        if (v < 1) {
            ctx.save();
            ctx.beginPath();

            if (v <= 0) {
                ctx.arc(r, r, r - t / 2, 0, Math.PI * 2);
            } else {
                if (!this.reverse) {
                    ctx.arc(r, r, r - t / 2, a + Math.PI * 2 * v, a);
                } else {
                    ctx.arc(r, r, r - t / 2, a, a - Math.PI * 2 * v);
                }
            }

            ctx.lineWidth = t;
            ctx.strokeStyle = this.options.emptyFill;
            ctx.stroke();
            ctx.restore();
        }
    },

    drawAnimated: function(v) {
        this.element.emit('progress-animation-start');

        moofx(bind(function(now) {
            var stepValue = this.options.animationStartValue * (1 - now) + v * now;
            this.drawFrame(stepValue);
            this.element.emit('progress-animation-change', now, stepValue);
        }, this), {
            duration: this.options.animation.duration || '1200',
            equation: this.options.animation.equation || 'linear',
            callback: bind(function() {
                if (this.options.animation.callback) { this.options.animation.callback(); }
                this.element.emit('progress-animation-end');
            }, this)
        }).start(0, 1);
    },

    getThickness: function() {
        return isNumber(this.options.thickness) ? this.options.thickness : this.options.size / 14;
    }
});

module.exports = Progresser;
