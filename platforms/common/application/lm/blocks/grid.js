"use strict";
var prime = require('prime'),
    Base  = require('./base');

var Grid = new prime({
    inherits: Base,
    options: {
        type: 'grid'
    },

    constructor: function(options) {
        Base.call(this, options);
    },

    layout: function() {
        return '<div class="grid" data-lm-id="' + this.getId() + '" ' + this.dropzone() + ' data-lm-blocktype="grid"></div>';
    },

    onRendered: function(){
        /*if (this.block.find('[data-lm-id]')){
            this.block.data('lm-dropzone', null);
        }*/
    }
});

module.exports = Grid;
