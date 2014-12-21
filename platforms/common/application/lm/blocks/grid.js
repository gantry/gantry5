"use strict";
var prime      = require('prime'),
    Base       = require('./base'),
    getAjaxURL = require('../../utils/get-ajax-url');

var Grid = new prime({
    inherits: Base,
    options: {
        type: 'grid'
    },

    constructor: function(options) {
        Base.call(this, options);
    },

    layout: function() {
        var settings_uri = getAjaxURL('particles/' + this.getId() + '/edit');

        return '<div class="g-grid nowrap" data-lm-id="' + this.getId() + '" ' + this.dropzone() + '  data-lm-settings="' + settings_uri + '" data-lm-blocktype="grid"></div>';
    },

    onRendered: function() {
        var parent = this.block.parent();
        if (parent && parent.data('lm-root')) {
            this.removeDropzone();
        }

        this.block.on('click', function(e){
            var clientX = event.clientX || (event.touches && event.touches[0].clientX) || 0,
                boundings = this[0].getBoundingClientRect();

            if (clientX + 4 - boundings.left >= boundings.width) {
                return true;
            }

            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    }
});

module.exports = Grid;
