"use strict";
// deprecated (5.2.0)
var prime   = require('prime'),
    $       = require('elements'),
    zen     = require('elements/zen'),
    bind    = require('mout/function/bind'),
    Section = require('./section');

var Atoms = new prime({
    inherits: Section,
    options: {
        type: 'atoms',
        attributes: {
            name: "Atoms Section"
        }
    },

    layout: function() {
        this.deprecated = '<div class="atoms-notice">Looking for Atoms? To make it easier we moved them in the <a href="#"><i class="fa fa-fw fa-list-alt" aria-hidden="true"></i> Page Settings</a>.</div>';

        return '<div class="atoms-section" style="display: none;" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '"><div class="section-header clearfix"><h4 class="float-left">' + (this.getAttribute('name')) + '</h4></div></div>';
    },

    getId: function() {
        return this.id || (this.id = this.options.type);
    },

    onDone: function(event) {
        // Gantry 5.2.0: Remove atoms section if empty to keep the layout clear
        if (!this.block.search('[data-lm-blocktype="atom"]')) {
            var ids = [this.getId()], segments = this.block.search('[data-lm-id]');
            if (segments) {
                segments.forEach(function(element) {
                    ids.push($(element).data('lm-id'));
                });
            }

            ids.reverse().forEach(bind(function(id) {
                this.options.builder.remove(id);
            }, this));

            this.block.empty()[0].outerHTML = this.deprecated;
            this._attachRedirect();

            return;
        }

        if (!this.block.search('[data-lm-id]')) {
            this.grid.insert(this.block, 'bottom');
            this.options.builder.add(this.grid);
        }

        zen('div').html(this.deprecated).firstChild().after(this.block);

        this._attachRedirect();
    },

    _attachRedirect: function() {
        var item = $('[data-g5-nav="page"]');
        if (!item) { return; }

        $('.atoms-notice a').on('click', function(event) {
            event.preventDefault();
            $('body').emit('click', { target: item });
        });
    }
});

module.exports = Atoms;
