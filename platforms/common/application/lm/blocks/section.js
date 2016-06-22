"use strict";
var prime              = require('prime'),
    Base               = require('./base'),
    Bound              = require('prime-util/prime/bound'),
    Grid               = require('./grid'),
    $                  = require('elements'),
    zen                = require('elements/zen'),

    bind               = require('mout/function/bind'),
    forOwn             = require('mout/object/forOwn'),
    getAjaxURL         = require('../../utils/get-ajax-url').config,
    getOutlineNameById = require('../../utils/get-outline').getOutlineNameById;

require('elements/insertion');

var UID = 0;

var Section = new prime({
    inherits: Base,
    options: {},

    constructor: function(options) {
        ++UID;
        this.grid = new Grid();
        Base.call(this, options);

        this.on('done', this.bound('onDone'));
        this.on('changed', this.hasChanged);
    },

    layout: function() {
        var settings_uri = getAjaxURL(this.getPageId() + '/layout/' + this.getType() + '/' + this.getId()),
            inheritanceLabel = '',
            klass = '';

        if (this.hasInheritance()) {
            var outline = getOutlineNameById(this.inherit.outline);
            inheritanceLabel = this.renderInheritanceLabel(outline);
            klass = ' g-inheriting';

            if (this.inherit.include.length) {
                klass += ' g-inheriting-' + this.inherit.include.join(' g-inheriting-');
            }
        }

        return '<div class="section' + klass + '" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '" data-lm-blocksubtype="' + this.getSubType() + '"><div class="section-header clearfix"><h4 class="float-left">' + (this.getTitle()) + '</h4><div class="section-actions float-right"><span class="section-addrow" data-tip="Adds a new row in the section" data-tip-place="top-right"><i aria-label="Add a new row" class="fa fa-plus"></i></span> <span class="section-settings" data-tip="Section settings" data-tip-place="top-right"><i aria-label="Configure Section Settings" class="fa fa-cog" data-lm-settings="' + settings_uri + '"></i></span></div></div>' + inheritanceLabel + '</div>';
    },

    adopt: function(child) {
        $(child).insert(this.block.find('.g-grid'));
    },
    
    renderInheritanceLabel: function(outline) {
        var content = 'Inheriting from <strong>' + outline + '</strong>';

        if (this.block && this.getParent()) {
            content = '';
        }

        return '<div class="g-inherit g-section-inherit"><div class="g-inherit-content" ' + this.addInheritanceTip(true) + '><i class="fa fa-lock"></i> ' + content + '</div></div>';
    },
 
    enableInheritance: function() {
        if (this.hasInheritance()) {
            this.block.attribute('class', this.cleanKlass(this.block.attribute('class')));
            this.block.addClass('g-inheriting');
            if (this.inherit.include.length) {
                this.block.addClass('g-inheriting-' + this.inherit.include.join(' g-inheriting-'));
            }

            if (!this.block.find('> .g-inherit')) {
                var inherit = zen('div'),
                    outline = getOutlineNameById(this.inherit.outline),
                    html = this.renderInheritanceLabel(outline);

                this.block.appendChild(inherit.html(html).children());
            }
        }
    },

    disableInheritance: function() {
        if (this.block.find('> .g-inherit')) {
            var inherit = this.block.find('> .g-inherit.g-section-inherit');
            if (inherit) {
                inherit.remove();
            }
        }

        this.block.attribute('class', this.cleanKlass(this.block.attribute('class')));
        this.block.removeClass('g-inheriting');
    },

    refreshInheritance: function() {
        this.block.attribute('class', this.cleanKlass(this.block.attribute('class')));
        if (this.hasInheritance()) {
            this.enableInheritance();
            var overlay = this.block.find('> .g-inherit');
            if (overlay) {
                var outline = getOutlineNameById(this.inherit.outline),
                    content = zen('div').html(this.renderInheritanceLabel(outline));

                if (overlay && content) { overlay.html(content.children().html()); }
            }
        }
    },

    addInheritanceTip: function(html) {
        var tooltip = this.getInheritanceTip();

        if (html) {
            var tooltipHTML = '';
            forOwn(tooltip, function(value, key) {
                tooltipHTML += 'data-' + key + '="' + value + '" ';
            });

            tooltip = tooltipHTML;
        }

        return this.hasInheritance() ? tooltip : '';
    },

    getInheritanceTip: function() {
        var outline = this.inherit ? this.inherit.outline : null,
            name = getOutlineNameById(outline),
            include = (this.inherit.include || []).join(', ');

        return {
            'tip': 'Inheriting from <strong>' + name + '</strong><br />Outline ID: ' + outline + '<br />Replace: ' + include,
            'tip-offset': -2,
            'tip-place': 'top-right'
        };
    },

    cleanKlass: function(klass) {
        klass = (klass || '').split(' ');

        return klass.filter(function(item) { return !item.match(/^g-inheriting-/); }).join(' ');
    },

    hasChanged: function(state, child) {
        var icon = this.block.find('h4 > i:first-child');

        // if the the event is triggered from a grid we need to be cautious not to override the proper state
        if (icon && child && !child.changeState) { return; }

        this.block[state ? 'addClass' : 'removeClass']('block-has-changes');

        if (!state && icon) { icon.remove(); }
        if (state && !icon) { zen('i.fa.fa-circle-o.changes-indicator').top(this.block.find('h4')); }
    },

    onDone: function(event) {
        if (!this.block.search('[data-lm-id]')) {
            this.grid.insert(this.block, 'bottom');
            this.options.builder.add(this.grid);
        }

        var plus = this.block.find('.fa-plus');
        if (plus) {
            plus.on('click', bind(function(e) {
                if (e) { e.preventDefault(); }

                if (this.block.find('.g-grid:last-child:empty')) { return false; }

                this.grid = new Grid();
                this.grid.insert(this.block.find('[data-lm-blocktype="container"]') ? this.block.find('[data-lm-blocktype="container"]') : this.block, 'bottom');
                this.options.builder.add(this.grid);
            }, this));
        }

        this.refreshInheritance();
    },

    getParent: function() {
        var parent = this.block.parent('[data-lm-id]');

        return parent ? this.options.builder.get(parent.data('lm-id')) : null;
    },

    getLimits: function(parent) {
        if (!parent) { return false; }

        var sibling = parent.block.nextSibling() || parent.block.previousSibling() || false;

        if (!sibling) { return [100, 100]; }

        var siblingBlock = this.options.builder.get(sibling.data('lm-id'));
        if (siblingBlock.getType() !== 'block') { return false; }

        var sizes = {
            current: this.getParent().getSize(),
            sibling: siblingBlock.getSize()
        };

        return [5, (sizes.current + sizes.sibling) - 5];
    }
});

module.exports = Section;
