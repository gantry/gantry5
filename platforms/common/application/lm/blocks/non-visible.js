"use strict";
var prime   = require('prime'),
    Section = require('./section');

var NonVisible = new prime({
    inherits: Section,
    options: {
        type: 'non-visible',
        attributes: {
            name: "Non-Visible Section"
        }
    },

    layout: function() {
        return '<div class="non-visible-section" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '"><div class="section-header clearfix"><h4 class="float-left">' + (this.getAttribute('name')) + '</h4></div></div>';
    },

    getId: function() {
        return this.id || (this.id = this.options.type);
    },

    onDone: function(event) {
        if (!this.block.search('[data-lm-id]')) {
            this.grid.insert(this.block, 'bottom');
            this.options.builder.add(this.grid);
        }
    }
});

module.exports = NonVisible;
