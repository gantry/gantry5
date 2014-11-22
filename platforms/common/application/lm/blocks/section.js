"use strict";
var prime = require('prime'),
    Base  = require('./base'),
    Grid  = require('./grid'),
    $     = require('elements'),
    zen   = require('elements/zen');

require('elements/insertion');

var UID = 0;

var Section = new prime({
    inherits: Base,
    options: {
        type: 'section'
    },

    constructor: function(options){
        ++UID;
        this.grid = new Grid();
        Base.call(this, options);
    },

    layout: function(){
        return '<div class="section" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() +'"><div class="section-header clearfix"><h4 class="float-left">'+(this.getAttribute('name'))+'</h4><div class="float-right"><i class="fa fa-plus"></i> <i class="fa fa-cog"></i></div></div>' + this.grid.layout() + '</div>';

        //return '<div class="section" data-lm-id="' + this.getId() + '" data-lm-dropzone data-lm-blocktype="' + this.getType() +'"></div>';
    },

    adopt: function(child){
        $(child).insert(this.block.find('.grid'));
    }
});

module.exports = Section;
