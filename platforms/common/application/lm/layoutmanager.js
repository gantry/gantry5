"use strict";
var prime    = require('prime'),
    $        = require('../utils/elements.moofx'),
    zen      = require('elements/zen'),
    Emitter  = require('prime/emitter'),
    Bound    = require('prime-util/prime/bound'),
    Options  = require('prime-util/prime/options'),
    Blocks   = require('./blocks'),
    DragDrop = require('../ui/drag.drop'),
    Resizer  = require('../ui/drag.resizer'),
    Eraser   = require('./eraser'),
    get      = require('mout/object/get'),

    every    = require('mout/array/every'),
    isArray  = require('mout/lang/isArray'),
    isObject = require('mout/lang/isObject'),
    equals   = require('mout/object/equals');

var deepEquals = function(a, b, callback) {
    //callback = callback || defaultCompare;

    function compare(a, b) {
        return deepEquals(a, b, callback);
    }

    if (isArray(a) && isArray(b)) {
        if (a.length !== b.length) { return false; }
        return every(a, function(obj, index/*, arr*/) {
            return equals(obj, b[index], compare);
        });

    }

    if (!isObject(a) || !isObject(b)) {
        return callback(a, b);
    }

    return equals(a, b, compare);
};

var singles = {
    disable: function() {
        var root = $('[data-lm-root]');
        if (!root) { return; }

        var mode = root.data('lm-root') || 'page',
            children = root.search('.grid .block [data-lm-id]:not([data-lm-blocktype="grid"]):not([data-lm-blocktype="block"])');

        if (mode === 'page') {
            var sectionChildren = root.search('.section *:not(.button)');
            if (sectionChildren) {
                sectionChildren.style({ 'pointer-events': 'none' });
            }
            return;
        }

        if (!children) { return; }

        children.attribute('style', null).forEach(function(element) {
            element = $(element);
            if (!element.siblings()) {
                element.style({ 'pointer-events': 'none' });
            }
        });
    },
    enable: function() {
        var root = $('[data-lm-root]'),
            mode = root.data('lm-root') || 'page',
            children = root.search('.grid .block [data-lm-id]:not([data-lm-blocktype="grid"]):not([data-lm-blocktype="block"])');
        if (!children || mode === 'page') { return; }

        children.forEach(function(element) {
            element = $(element);
            if (!element.siblings()) {
                element.attribute('style', null);
            }
        });
    },
    cleanup: function(builder) {
        var roots = $('[data-lm-root] .section > .grid'),
            grids = $('[data-lm-root] .section > .grid .grid'),
            sects = $('[data-lm-root="page"] .grid > .block:empty');
        if (!grids && !roots && !sects) {
            return;
        }

        var children, container;//, siblings;
        /*if (sects) sects.forEach(function(sect){
         sect     = $(sect);
         siblings = sect.siblings();

         if (siblings.length >= 1){
         builder.remove(sect.data('lm-id'));
         sect.remove();
         } else {
         // one sibling only, we don't need the parent grid and block anymore
         container = sect.parent('[data-lm-blocktype="grid"]');
         builder.remove(sect.data('lm-id'));
         siblings.children().before(container);
         builder.remove(container.data('lm-id'));
         builder.remove(siblings.data('lm-id'));
         sect.remove();
         siblings.remove();
         container.remove();
         }
         });*/

        if (grids) {
            grids.forEach(function(grid) {
                grid = $(grid);
                children = grid.children();
                console.log(children);
                if (children && children.length <= 1) {
                    container = grid.firstChild();
                    container.children().before(grid);
                    builder.remove(container.data('lm-id'));
                    builder.remove(grid.data('lm-id'));
                    container.remove();
                    grid.remove();
                }
            });
        }

        if (roots) { roots.data('lm-dropzone', null); }
    }
};

var LayoutManager = new prime({

    mixin: [Bound, Options],

    inherits: Emitter,

    constructor: function(element, options) {
        //if (!$('[data-lm-root]')) { return; }
        this.dragdrop = new DragDrop(element, options);
        this.resizer = new Resizer(element, options);
        this.eraser = new Eraser('[data-lm-eraseblock]', options);
        this.dragdrop
            .on('dragdrop:start', this.bound('start'))
            .on('dragdrop:location', this.bound('location'))
            .on('dragdrop:nolocation', this.bound('nolocation'))
            .on('dragdrop:resize', this.bound('resize'))
            .on('dragdrop:stop:erase', this.bound('removeElement'))
            .on('dragdrop:stop', this.bound('stop'))
            .on('dragdrop:stop:animation', this.bound('stopAnimation'));

        this.builder = options.builder;
        this.history = options.history;

        singles.disable();
    },

    singles: function(mode) {
        singles[mode]();
    }, /*

     delegate: function(elements){
     this.dragdrop.detach();
     this.setOptions({delegate: elements});
     this.dragdrop.attach();
     },*/

    start: function(event, element) {
        var root = $('[data-lm-root]');
        this.block = this.dirty = null;
        this.mode = root.data('lm-root') || 'page';

        root.addClass('moving');
        var type = $(element).data('lm-blocktype'),
            clone = element[0].cloneNode(true);

        this.placeholder = zen('div.block.placeholder[data-lm-placeholder]').style({ display: 'none' });
        this.original = $(clone).after(element).style({
            display: 'block',
            opacity: 0.5
        }).addClass('original-placeholder').data('lm-dropzone', null);
        this.originalType = type;
        this.block = get(this.builder.map, element.data('lm-id') || '') || new Blocks[type]();

        if (!this.block.isNew()) {
            element.style({
                position: 'absolute',
                zIndex: 1000,
                width: element[0].offsetWidth,
                height: element[0].offsetHeight
            });
            this.placeholder.after(element);
            this.eraser.show();
        } else {
            this.original.remove();
        }

        singles.enable();
    },

    location: function(event, location, target/*, element*/) {
        target = $(target);
        //this.original.style({display: 'none'});

        // cleanup for the dirty flag
        if (this.dirty) {
            var dirty = this.dirty.target.parent('.grid');
            this.dirty.target.before(dirty);
            dirty.remove();
            this.dirty = null;
        }

        var position,
            dataType = target.data('lm-blocktype');

        if (!dataType && target.data('lm-root')) { dataType = 'root'; }
        if (this.mode !== 'page' && dataType === 'section') { return; }

        // Check for adjacents and avoid inserting any placeholder since it would be the same position
        var exclude = ':not(.placeholder):not([data-lm-id="' + this.original.data('lm-id') + '"])',
            adjacents = {
                before: this.original.previousSiblings(exclude),
                after: this.original.nextSiblings(exclude)
            };

        if (adjacents.before) { adjacents.before = $(adjacents.before[0]); }
        if (adjacents.after) { adjacents.after = $(adjacents.after[0]); }
        if (dataType === 'block' && (adjacents.before === target && location.x === 'after') || (adjacents.after === target && location.x === 'before')) {
            return;
        }

        // handles the types cases and normalizes the locations (x and y)
        var grid, block;
        switch (dataType) {
            case 'root':
                /*if (location.x === 'other') {
                    position = (location.y === 'above' ? 'top' : 'bottom');

                    this.placeholder[position](target);
                }*/
                break;
            case 'section':
                /*position = (location.x === 'other') ? (location.y === 'above' ? 'before' : 'after') : (location.x === 'before' ? 'left' : 'right');
                if (['left', 'right'].indexOf(position) === -1) { this.placeholder[position](target); }
                else {
                    if (target.parent('.block').data('lm-id') || target.parent().data('lm-root')) {
                        grid = zen('div.grid[data-lm-id="' + this.block.guid() + '"][data-lm-dropzone][data-lm-blocktype="grid"]').before(target);
                        block = zen('div.block[data-lm-id="' + this.block.guid() + '"][data-lm-dropzone][data-lm-blocktype="block"]').insert(grid);
                        target.insert(block);

                        this.placeholder[position === 'left' ? 'before' : 'after'](block);
                        this.dirty = {
                            element: grid,
                            target: target
                        };
                    } else {
                        this.placeholder[position === 'left' ? 'before' : 'after'](target.parent('.block'));
                    }

                }*/

                break;
            case 'grid':
            case 'block':
                var method;
                if (dataType === 'section' || dataType === 'grid') { method = (location.y === 'above' ? 'before' : 'after'); }
                if (dataType === 'block') { method = (location.y === 'above' ? 'top' : 'bottom'); }

                position = (location.x === 'other') ? method : location.x;

                if (dataType === 'block' && position === method) { this.placeholder.removeClass('in-between'); }

                this.placeholder[position](target);
                break;

            case 'position':
            case 'spacer':
                position = (location.x === 'other') ? (location.y === 'above' ? 'before' : 'after') : (location.x === 'before' ? 'left' : 'right');
                if (['left', 'right'].indexOf(position) === -1) { this.placeholder[position](target); }
                else {
                    if (target.parent('.block').data('lm-id')) {
                        grid = zen('div.grid[data-lm-id="' + this.block.guid() + '"][data-lm-dropzone][data-lm-blocktype="grid"]').before(target);
                        block = zen('div.block[data-lm-id="' + this.block.guid() + '"][data-lm-dropzone][data-lm-blocktype="block"]').insert(grid);
                        target.insert(block);

                        this.placeholder[position === 'left' ? 'before' : 'after'](block);
                        this.dirty = {
                            element: grid,
                            target: target
                        };
                    } else {
                        this.placeholder[position === 'left' ? 'before' : 'after'](target.parent('.block'));
                    }

                }

                break;
        }

        // If it's not a block we don't want a small version of the placeholder
        this.placeholder.removeClass('in-between').removeClass('in-between-sections');
        this.placeholder.style({ display: 'block' })[dataType !== 'block' ? 'removeClass' : 'addClass']('in-between');
        if (this.placeholder.parent().data('lm-blocktype') === 'block') { this.placeholder.addClass('in-between-sections'); }
    },

    nolocation: function(event) {
        if (this.placeholder) { this.placeholder.style({ display: 'none' }); }

        //var siblings = this.placeholder.siblings();

        if (!this.block.isNew()) {

            if ($(event.target).matches(this.eraser.element.find('.trash-zone'))) {
                this.dragdrop.removeElement = true;
                this.eraser.over();
            } else {
                this.dragdrop.removeElement = false;
                this.eraser.out();
            }
        }
    },

    resize: function(event, element, siblings) {
        this.resizer.start(event, element, siblings);
    },

    removeElement: function(event, element) {
        this.dragdrop.removeElement = false;

        var transition = {
            opacity: 0
        };

        element.animate(transition, {
            duration: '150ms'
        });


        this.eraser.hide();

        $(document).off(this.dragdrop.EVENTS.MOVE, this.dragdrop.bound('move'));
        $(document).off(this.dragdrop.EVENTS.STOP, this.dragdrop.bound('stop'));

        this.builder.remove(this.block.getId());

        var children = this.block.block.search('[data-lm-id]');
        if (children && children.length) {
            children.forEach(function(child) {
                this.builder.remove($(child).data('lm-id'));
            }, this);
        }

        this.block.block.remove();

        if (this.placeholder) { this.placeholder.remove(); }
        if (this.original)    { this.original.remove(); }
        this.element = this.block = this.dirty = null;

        singles.disable();
        singles.cleanup(this.builder);

        this.history.push(this.builder.serialize());

    },

    stop: function(event, target/*, element*/) {
        $('[data-lm-root]').removeClass('moving');

        // we are removing the block
        var lastOvered = $(this.dragdrop.lastOvered);
        if (lastOvered && lastOvered.matches(this.eraser.element.find('.trash-zone'))) {
            return;
        }

        if (!this.block.isNew()) { this.eraser.hide(); }
        if (!this.dragdrop.matched) {
            if (this.placeholder) { this.placeholder.remove(); }
            if (this.original)    { this.original.remove(); }

            return;
        }

        target = $(target);

        var wrapper, insider,
            type = this.block.getType(),
            targetId = target.data('lm-id'),
            targetType = !targetId ? false : get(this.builder.map, targetId) ? get(this.builder.map, targetId).getType() : target.data('lm-blocktype'),
            parentId = this.placeholder.parent().data('lm-id'),
            parentType = get(this.builder.map, parentId || '') ? get(this.builder.map, parentId).getType() : false;
            //originalParent = this.original.parent('[data-lm-id]');

        var resizeCase = false;
            //originalSiblings = this.original.siblings(':not(.original-placeholder):not([data-lm-id="' + this.block.getId() + '"])') || [];

        this.original.remove();

        // case 1: it's a position/spacer and needs to be wrapped by a block (dropped at root or next to another block)
        if (type !== 'block' && ((this.dirty || targetType === 'section' || targetType === 'grid') || (!this.dirty && targetType === 'block' && parentType !== 'block'))) {
            wrapper = new Blocks.block({ attributes: { size: 50 } }).adopt(this.block.block);
            insider = new Blocks[this.block.block.data('lm-blocktype')]({ id: this.block.block.data('lm-id') }).setLayout(this.block.block);

            wrapper.setSize();
            this.block = wrapper;
            this.builder.add(wrapper);
            this.builder.add(insider);
            console.log('1. resize me and my siblings');
            resizeCase = { case: 1 };
            console.log(this.block);
        }

        // case 2: it's a block that turns into position/spacer, we need to kill the wrapper and unregister it
        var children = this.block.block.children();
        if (type === 'block' && this.placeholder.siblings('.position, .spacer, .grid') && (children && children.length === 1)) {
            var block = this.block;
            this.block = get(this.builder.map, this.block.block.firstChild().data('lm-id'));
            resizeCase = {
                case: 2,
                siblings: block.block.siblings()
            };
            block.block.remove();
            this.builder.remove(block);
            console.log('2. im leaving, resize my siblings');
        }

        // case 3: moving a block around, need to reset the sizes
        if (this.originalType === 'block' && this.block.getType() === 'block') {
            console.log('3. im a block and ive been moved, resize my new siblings and the ones where i come from');
            resizeCase = { case: 3 };
            var previous = this.block.block.parent.siblings(':not(.original-placeholder)');
            if (previous.length) { this.resizer.evenResize(previous); }
        }

        // case 4: it's a section in a grid that goes out and the grid needs to be removed
        // if (type == 'section' && originalSiblings && originalParent.parent().data('lm-blocktype') == 'grid'){
        //console.log(this.original, originalSiblings);
        //console.log('case4', $('[data-lm-root="page"] .grid > .block:empty'));
        //return;
        //}
        console.log(targetType);
        /*if (type == 'section' && !originalSiblings.length && originalParent.data('lm-blocktype') == 'block'){
         if (originalParent.siblings().length > 1){
         resizeCase = {case: 4, siblings: originalParent.siblings()};
         if (targetType == 'block') resizeCase.siblings = $(this.block.block, resizeCase.siblings);
         this.builder.remove(originalParent.data('lm-id'));
         originalParent.remove();
         console.log(resizeCase);
         } else {
         var container = originalParent.parent('[data-lm-blocktype="grid"]');
         this.builder.remove(originalParent.data('lm-id'));
         originalParent.siblings().children().before(container);
         this.builder.remove(container.data('lm-id'));
         this.builder.remove(originalParent.siblings().data('lm-id'));
         originalParent.remove();
         //originalParent.siblings().remove();
         container.remove();
         }
         }*/

        // it's dirty, let's register all the blocks that are missing.
        if (this.dirty) {
            var structure = $([this.dirty.element, this.dirty.element.search('[data-lm-id]')]);
            var dirtyId, dirtyType, dirtyMap, dirtyBlock;
            structure.forEach(function(element) {
                element = $(element);
                dirtyId = element.data('lm-id');
                dirtyType = element.data('lm-blocktype');
                dirtyMap = get(this.builder.map, dirtyId);

                if (!dirtyMap) {
                    dirtyBlock = new Blocks[dirtyType]({ id: dirtyId }).setLayout(element);
                    if (dirtyType === 'block') { dirtyBlock.setSize(50, true); }
                    this.builder.add(dirtyBlock);
                }
            }, this);
        }


        if (this.block.hasAttribute('size')) { this.block.setSize(this.placeholder.compute('flex')); }
        this.block.insert(this.placeholder);
        this.placeholder.remove();

        if (resizeCase.case === 1) {
            console.log(this.block.block);
        }
        if (resizeCase && resizeCase.case === 1 || resizeCase.case === 3) { this.resizer.evenResize($([this.block.block, this.block.block.siblings()]), !this.dirty); }
        if (resizeCase && resizeCase.case === 2 || resizeCase.case === 4) { this.resizer.evenResize(resizeCase.siblings); }

        singles.disable();
        singles.cleanup(this.builder);

        var serial = this.builder.serialize(),
            lastEntry = this.history.get().data,
            callback = function(a, b) { return a === b; };

        if (!deepEquals(lastEntry[0], serial[0], callback)) { this.history.push(serial); }
    },

    stopAnimation: function(element) {
        singles.disable();
        if (!this.block) { this.block = get(this.builder.map, element.data('lm-id')); }
        if (this.block && this.block.getType() === 'block') { this.block.setSize(); }
    }
});

module.exports = LayoutManager;
