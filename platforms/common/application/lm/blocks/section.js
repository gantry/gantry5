"use strict";
var prime = require('prime'),
    Base  = require('./base'),
    Bound = require('prime-util/prime/bound'),
    Grid  = require('./grid'),
    $     = require('elements'),
    zen   = require('elements/zen'),

    bind  = require('mout/function/bind'),
    getAjaxURL = require('../../utils/get-ajax-url');

require('elements/insertion');

var UID = 0;

var Section = new prime({
    inherits: Base,
    options: {
        type: 'section'
    },

    constructor: function(options) {
        ++UID;
        this.grid = new Grid();
        Base.call(this, options);

        this.on('done', this.bound('onDone'));
    },

    layout: function() {
        var settings_uri = getAjaxURL('layout/' + this.getPageId() +  '/' + this.getType() + '/' + this.getId());

        return '<div class="section" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '"><div class="section-header clearfix"><h4 class="float-left">' + (this.getTitle()) + '</h4><div class="section-actions float-right"><i class="fa fa-plus"></i> <i class="fa fa-cog" data-lm-settings="' + settings_uri + '"></i></div></div></div>';
    },

    adopt: function(child) {
        $(child).insert(this.block.find('.g-grid'));
    },

    onDone: function(event) {
        if (!this.block.search('[data-lm-id]')) {
            this.grid.insert(this.block, 'bottom');
            this.options.builder.add(this.grid);
        }

        var plus = this.block.find('.fa-plus');
        if (plus) {
            plus.on('click', bind(function(e) {
                e.preventDefault();

                if (this.block.find('.g-grid:last-child:empty')) { return false; }

                this.grid = new Grid();
                this.grid.insert(this.block.find('[data-lm-blocktype="container"]') ? this.block.find('[data-lm-blocktype="container"]') : this.block, 'bottom');
                this.options.builder.add(this.grid);
            }, this));
        }
    }
});

module.exports = Section;
