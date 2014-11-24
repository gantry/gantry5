"use strict";
var prime   = require('prime'),
    $       = require('elements'),
    Emitter = require('prime/emitter');

var Blocks = require('./blocks/');

var forOwn  = require('mout/object/forOwn'),
    forEach = require('mout/collection/forEach'),
    size    = require('mout/collection/size'),
    isArray = require('mout/lang/isArray'),
    flatten = require('mout/array/flatten'),
    guid    = require('mout/random/guid'),

    set     = require('mout/object/set'),
    unset   = require('mout/object/unset'),
    get     = require('mout/object/get');

require('elements/attributes');
require('elements/traversal');


// start Debug
var rpad   = require('mout/string/rpad'),
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
        var id = typeof block == 'string' ? block : block.id;
        return get(this.map, id, block);
    },

    load: function(data) {
        this.recursiveLoad(data);
        //console.log('---');
        this.emit('loaded', data);

        return this;
    },

    serialize: function(root) {
        var serieChildren = [];
        root = root || $('[data-lm-root]');

        if (!root) {
            return;
        }

        var blocks = root.search('> [data-lm-id]'),
            id, type, serial, hasChildren, children;

        forEach(blocks, function(element) {
            element = $(element);
            id = element.data('lm-id');
            type = element.data('lm-blocktype');
            hasChildren = element.search('> [data-lm-id]');

            children = hasChildren ? this.serialize(element) : [];

            serial = {
                id: id,
                type: type,
                attributes: get(this.map, id) ? get(this.map, id).getAttributes() : {},
                children: children
            };

            /*if (blocks.length <= 1) serie = serial;
             else {*/
            serieChildren.push(serial);
            //}
        }, this);

        return serieChildren;// size(serieChildren) ? serieChildren : serie;
    },

    __serialize: function(root) {
        var serie = {},
            serieChildren = {};
        root = root || $('[data-lm-root]');

        var blocks = root.search('> [data-lm-id]'),
            id, type, serial, hasChildren, children, keysSort;

        forEach(blocks, function(element) {
            element = $(element);
            id = element.data('lm-id');
            type = element.data('lm-blocktype');
            hasChildren = element.search('> [data-lm-id]');

            children = hasChildren ? this.serialize(element) : false;
            keysSort = [];

            serial = {
                type: type,
                attributes: get(this.map, id).getAttributes(),
                children: children
            };

            if (blocks.length <= 1) {
                set(serie, id, serial);
            }
            else {
                keysSort.push(id);
                set(serieChildren, id, serial);
            }
        }, this);

        return size(serieChildren) ? serieChildren : serie;
    },

    insert: function(key, value, parent/*, object*/) {
        var root = $('[data-lm-root]');
        if (!root) {
            return;
        }
        var Element = new Blocks[value.type]({
            id: key,
            attributes: value.attributes || {}
        });

        if (!parent) {
            Element.block.insert(root);
        }
        else {
            Element.block.insert($('[data-lm-id="' + parent + '"]'));
        }

        if (value.type === 'grid' && (parent && get(this.map, parent).getType() === 'section')) {
            Element.block.data('lm-dropzone', null);
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
            parent, children = $('[data-lm-root] > .section > .grid > .block .grid > .block, [data-lm-root] > .section > .grid > .block > .block');

        if (!children) {
            return;
        }


        var isGrid;
        children.forEach(function(child) {
            child = $(child);
            parent = null;
            isGrid = child.parent().hasClass('grid');

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
                value.id = guid();
            }
            console.log(rpad(repeat('    ', depth) + '' + value.type, 35) + ' (' + rpad(value.id, 36) + ') parent: ' + parent);
            this.emit('loading', callback.call(this, value.id, value, parent, depth));
            if (value.children && size(value.children)) {
                //this.recursiveLoad(value.children, callback, ++depth, value.id);
                depth++;
                forEach(value.children, function(childValue/*, childKey, array*/) {
                    this.recursiveLoad([childValue], callback, depth, value.id);
                }, this);
            }

            this.get(value.id).emit('done', this.get(value.id));

            depth--;
        }, this);
    },

    __recursiveLoad: function(data, callback, depth, parent) {
        data = data || this.structure;
        depth = depth || 0;
        parent = parent || false;
        callback = callback || this.insert;

        forEach(data, function(value, key, object) {
            //console.log(rpad(repeat('    ', depth) + '' + value.type, 35) + ' ('+key+') parent: ' + parent);
            this.emit('loading', callback.call(this, key, value, parent, depth, object));
            if (value.children && size(value.children)) {
                this.recursiveLoad(value.children, callback, ++depth, key);
                /*forEach(value.children, function(childValue, childKey, array){
                 this._recursiveLoad([childValue], callback, depth, key);
                 }, this);*/
            }

            depth--;
        }, this);
    }
});

module.exports = Builder;
