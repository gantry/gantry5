var prime = require('prime'),
    Base  = require('./base'),
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
        Base.call(this, options);
    },

    layout: function(){
        return '<div class="section" data-lm-id="' + this.getId() + '" ' + this.dropZone() +' data-lm-blocktype="' + this.getType() +'"><div class="section-header clearfix"><h4 class="float-left">'+(this.getAttribute('name'))+'</h4><a href="#" class="button float-right"><i class="fa fa-pencil-square-o"></i> Edit</a></div></div>';
        //return '<div class="section" data-lm-id="' + this.getId() + '" data-lm-dropzone data-lm-blocktype="' + this.getType() +'"></div>';
    },

    adopt: function(child){
        $(child).insert(this.block.find('.grid'));
    }
});

module.exports = Section;
