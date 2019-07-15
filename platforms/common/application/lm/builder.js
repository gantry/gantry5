"use strict";
var prime   = require('prime'),
    $       = require('elements'),
    Emitter = require('prime/emitter');

var Blocks = require('./blocks/');

var forOwn     = require('mout/object/forOwn'),
    forEach    = require('mout/collection/forEach'),
    size       = require('mout/collection/size'),
    isArray    = require('mout/lang/isArray'),
    flatten    = require('mout/array/flatten'),
    ID         = require('./id'),

    set        = require('mout/object/set'),
    unset      = require('mout/object/unset'),
    get        = require('mout/object/get'),
    deepFillIn = require('mout/object/deepFillIn'),
    omit       = require('mout/object/omit');

require('elements/attributes');
require('elements/traversal');


// start Debug
var DEBUG  = false,
    rpad   = require('mout/string/rpad'),
    repeat = require('mout/string/repeat');
// end   Debug

$.implement({
    empty: function() {
        return this.forEach(function(node) {
            var first;
            while ((first = node.firstChild)) {
                node.removeChild(first);
            }
        });
    }
});

var Builder = new prime({

    inherits: Emitter,

    constructor: function(structure) {
        if (structure) {
            this.setStructure(structure);
        }
        this.map = {};

        return this;
    },

    setStructure: function(structure) {
        try {
            this.structure = (typeof structure === 'object') ? structure : JSON.parse(structure);
        }
        catch (e) {
            console.error("Parsing error:", e);
        }
    },

    add: function(block) {
        var id = typeof block === 'string' ? block : block.id;
        set(this.map, id, block);
        block.isNew(false);
    },

    remove: function(block) {
        block = typeof block === 'string' ? block : block.id;
        unset(this.map, block);
    },

    get: function(block) {
        var id = typeof block === 'string' ? block : block.id;
        return get(this.map, id, block);
    },

    load: function(data) {
        this.recursiveLoad(data);
        this.emit('loaded', data);

        return this;
    },

    serialize: function(root, flat) {
        var serieChildren = [];
        root = root || $('[data-lm-root]');

        if (!root) { return; }

        var blocks = root.search((!flat ? '> ' : '') + '[data-lm-id]'),
            id, type, subtype, serial, hasChildren, children;

        forEach(blocks, function(element) {
            element = $(element);
            id = element.data('lm-id');
            type = element.data('lm-blocktype');
            subtype = element.data('lm-blocksubtype') || false;
            hasChildren = element.search('> [data-lm-id]');

            if (flat) {
                children = hasChildren ? hasChildren.map(function(element){ return $(element).data('lm-id'); }) : false;
            } else {
                children = hasChildren ? this.serialize(element) : [];
            }

            serial = {
                id: id,
                type: type,
                subtype: subtype,
                title: get(this.map, id) ? get(this.map, id).getTitle() : 'Untitled',
                attributes: get(this.map, id) ? get(this.map, id).getAttributes() : {},
                inherit: get(this.map, id) ? get(this.map, id).getInheritance() : {},
                children: children
            };

            if (flat) {
                var obj = {}; obj[id] = serial;
                serial = obj;
            }

            serieChildren.push(serial);
        }, this);

        return serieChildren;
    },

    insert: function(key, value, parent/*, object*/) {
        var root = $('[data-lm-root]');
        if (!root) {
            return;
        }

        if (!Blocks[value.type]) {
            console[console.error ? 'error' : 'log'](value.type + ' does not exist');
        }

        var Element = new (Blocks[value.type] || Blocks['section'])(deepFillIn({
            id: key,
            attributes: {},
            inherit: {},
            subtype: value.subtype || false,
            builder: this
        }, omit(value, 'children')));

        if (!parent) {
            Element.block.insert(root);
        }
        else {
            Element.block.insert($('[data-lm-id="' + parent + '"]'));
        }

        if (Element.getType() === 'block') {
            Element.setSize();
        }

        this.add(Element);
        Element.emit('rendered', Element, parent ? get(this.map, parent) : null);

        return Element;
    },

    reset: function(data) {
        this.map = {};
        this.setStructure(data || {});
        $('[data-lm-root]').empty();
        this.load();
    },

    cleanupLonely: function() {
        var ghosts = [],
            parent, children = $('[data-lm-root] > .g-section > .g-grid > .g-block .g-grid > .g-block, [data-lm-root] > .g-section > .g-grid > .g-block > .g-block');

        if (!children) {
            return;
        }


        var isGrid;
        children.forEach(function(child) {
            child = $(child);
            parent = null;
            isGrid = child.parent().hasClass('g-grid');

            if (isGrid && child.siblings()) {
                return false;
            }

            if (isGrid) {
                ghosts.push(child.data('lm-id'));
                parent = child.parent();
            }

            ghosts.push(child.data('lm-id'));
            child.children().before(parent ? parent : child);
            (parent ? parent : child).remove();
        });

        return ghosts;
    },

    recursiveLoad: function(data, callback, depth, parent) {
        data = data || this.structure;
        depth = depth || 0;
        parent = parent || false;
        callback = callback || this.insert;

        forEach(data, function(value/*, key, object*/) {

            if (!value.id) {
                value.id = ID({ builder: { map: this.map }, type: value.type, subtype: value.subtype });
            }

            // debug (flat view of the structure)
            if (console && console.log && DEBUG) {
                console.log(rpad(repeat('    ', depth) + '' + value.type, 35) + ' (' + rpad(value.id, 36) + ') parent: ' + parent);
            }

            this.emit('loading', callback.call(this, value.id, value, parent, depth));
            if (value.children && size(value.children)) {
                depth++;

                forEach(value.children, function(childValue/*, childKey, array*/) {
                    this.recursiveLoad([childValue], callback, depth, value.id);
                }, this);
            }

            this.get(value.id).emit('done', this.get(value.id));

            depth--;
        }, this);
    }
});

module.exports = Builder;
