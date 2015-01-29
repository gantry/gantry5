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
        var grids = $('[data-lm-root] [data-lm-blocktype="grid"]')/*,
            sections = $('[data-lm-root] [data-lm-blocktype="section"]')*/;

        if (grids) { grids.removeClass('no-hover'); }
        /*if (sections) {
            sections.forEach(function(section) {
                var subGrids = $(section).search('> [data-lm-blocktype="grid"]:not(:empty), > [data-lm-blocktype="container"] > [data-lm-blocktype="grid"]:not(:empty)');
                if (subGrids) {
                    if (subGrids.length === 1) { subGrids.addClass('no-move').data('lm-nodrag', 'true'); }
                    else { subGrids.removeClass('no-move').data('lm-nodrag', null); }
                }
            }, this);
        }*/
    },
    enable: function() {
        var grids = $('[data-lm-root] [data-lm-blocktype="grid"]')/*,
            sections = $('[data-lm-root] [data-lm-blocktype="section"]')*/;

        if (grids) { grids.addClass('no-hover'); }
        /*if (sections) {
            sections.forEach(function(section) {
                var subGrids = $(section).search('> [data-lm-blocktype="grid"]:not(:empty), > [data-lm-blocktype="container"] > [data-lm-blocktype="grid"]:not(:empty)');
                if (subGrids) {
                    if (subGrids.length === 1) { subGrids.addClass('no-move').data('lm-nodrag', 'true'); }
                    else { subGrids.removeClass('no-move').data('lm-nodrag', null); }
                }
            }, this);
        }*/
    },
    cleanup: function(builder) {}

};

var LayoutManager = new prime({

    mixin: [Bound, Options],

    inherits: Emitter,

    constructor: function(element, options) {
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
        var root = $('[data-lm-root]'),
            size = $(element).position();

        this.block = null;
        this.mode = root.data('lm-root') || 'page';

        root.addClass('moving');
        var type = $(element).data('lm-blocktype'),
            clone = element[0].cloneNode(true);

        if (!this.placeholder) { this.placeholder = zen('div.block.placeholder[data-lm-placeholder]'); }
        this.placeholder.style({ display: 'none' });
        this.original = $(clone).after(element).style({
            display: 'block',
            opacity: 0.5
        }).addClass('original-placeholder').data('lm-dropzone', null);
        if (type === 'grid') { this.original.style({ display: 'flex' }); }
        this.originalType = type;
        this.block = get(this.builder.map, element.data('lm-id') || '') || new Blocks[type]({ builder: this.builder, subtype: element.data('lm-subtype'), attributes: { title: element.text() } });

        if (!this.block.isNew()) {
            //var margins = $(element).find('[data-lm-blocktype]').compute('margin');
            element.style({
                position: 'absolute',
                zIndex: 1000,
                width: Math.ceil(size.width),
                height: Math.ceil(size.height)
            }).find('[data-lm-blocktype]');//.style({ margin: margins });

            if (this.block.getType() === 'grid') {
                var siblings = this.block.block.siblings(':not(.original-placeholder):not(.section-header):not(:empty)');
                if (siblings) {
                    siblings.search('[data-lm-id]').style({ 'pointer-events': 'none' });
                }
            }

            this.placeholder.before(element);
            this.eraser.show();
        } else {
            var position = element.position(),
                parentOffset = {top: element.parent()[0].scrollTop, left: element.parent()[0].scrollLeft};
            this.original.style({position: 'absolute'}).style({left: element[0].offsetLeft - parentOffset.left, top: element[0].offsetTop - parentOffset.top, width: position.width, height: position.height});
            this.element = this.dragdrop.element;
            this.dragdrop.element = this.original;
        }

        singles.enable();
    },

    location: function(event, location, target/*, element*/) {
        target = $(target);
        if (!this.placeholder) { this.placeholder = zen('div.block.placeholder[data-lm-placeholder]').style({ display: 'none' }); }
        //this.original.style({display: 'none'});

        var position,
            dataType = target.data('lm-blocktype'),
            originalType = this.block.getType();

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
        if (dataType === 'block' && ((adjacents.before === target && location.x === 'after') || (adjacents.after === target && location.x === 'before'))) {
            return;
        }
        if (dataType === 'grid' && ((adjacents.before === target && location.y === 'below') || (adjacents.after === target && location.y === 'above'))) {
            return;
        }

        // handles the types cases and normalizes the locations (x and y)
        var grid, block, method;

        switch (dataType) {
            case 'root':
            case 'section':
                break;
            case 'grid':
                var empty = !target.children(':not(.placeholder)');
                // new particles cannot be dropped in existing grids, only empty ones
                if (originalType !== 'grid' && !empty) { return; }

                // grids cannot be dropped inside grids
                if (originalType === 'grid' && empty) { return; }

                // we are dropping a new particle into an empty grid, placeholder goes inside
                if (empty) { this.placeholder.bottom(target); }
                else {
                    // we are sorting grids ordering, placeholder goes above/below
                    method = (location.y === 'above' ? 'before' : 'after');
                    this.placeholder[method](target);
                }

                break;
            case 'block':
                method = (location.y === 'above' ? 'top' : 'bottom');
                position = (location.x === 'other') ? method : location.x;
                this.placeholder[position](target);

                break;
        }

        // If it's not a block we don't want a small version of the placeholder
        this.placeholder.removeClass('in-between').removeClass('in-between-grids').removeClass('in-between-grids-first').removeClass('in-between-grids-last');
        this.placeholder.style({ display: 'block' })[dataType !== 'block' ? 'removeClass' : 'addClass']('in-between');
        if (originalType === 'grid' && dataType === 'grid') {
            var next = this.placeholder.nextSibling(),
                previous = this.placeholder.previousSibling();

            this.placeholder.addClass('in-between-grids');
            if (previous && !previous.data('lm-blocktype')) { this.placeholder.addClass('in-between-grids-first'); }
            if (!next || !next.data('lm-blocktype')) { this.placeholder.addClass('in-between-grids-last'); }
        }
    },

    nolocation: function(event) {
        if (this.placeholder) { this.placeholder.remove(); }

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

    resize: function(event, element, siblings, offset) {
        this.resizer.start(event, element, siblings, offset);
    },

    removeElement: function(event, element) {
        this.dragdrop.removeElement = false;

        var transition = {
            opacity: 0
        };

        element.animate(transition, {
            duration: '150ms'
        });

        var siblings = this.block.block.siblings(':not(.original-placeholder)');

        if (siblings && this.block.getType() == 'block') {
            var size = this.block.getSize(),
                diff = size / siblings.length, block;
            siblings.forEach(function(sibling) {
                sibling = $(sibling);
                block = get(this.builder.map, sibling.data('lm-id'));
                block.setSize(block.getSize() + diff, true);
            }, this);
        }

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
        if (this.original) { this.original.remove(); }
        this.element = this.block =  null;

        singles.disable();
        singles.cleanup(this.builder);

        this.history.push(this.builder.serialize());
        $('[data-lm-root]').removeClass('moving');

    },

    stop: function(event, target, element) {
        // we are removing the block
        var lastOvered = $(this.dragdrop.lastOvered);
        if (lastOvered && lastOvered.matches(this.eraser.element.find('.trash-zone'))) {
            this.eraser.hide();
            return;
        }

        if (this.block.getType() === 'grid') {
            var siblings = this.block.block.siblings(':not(.original-placeholder):not(.section-header):not(:empty)');
            if (siblings) {
                siblings.search('[data-lm-id]').style({ 'pointer-events': 'inherit' });
            }
        }

        if (!this.block.isNew()) { this.eraser.hide(); }
        if (!this.dragdrop.matched) {
            if (this.placeholder) { this.placeholder.remove(); }
            //if (this.original) { this.original.remove(); }

            return;
        }

        target = $(target);

        var wrapper, insider,
            blockWasNew = this.block.isNew(),
            type = this.block.getType(),
            targetId = target.data('lm-id'),
            targetType = !targetId ? false : get(this.builder.map, targetId) ? get(this.builder.map, targetId).getType() : target.data('lm-blocktype'),
            placeholderParent = this.placeholder.parent();

        if (!placeholderParent) { return; }

        var parentId = placeholderParent.data('lm-id'),
            parentType = get(this.builder.map, parentId || '') ? get(this.builder.map, parentId).getType() : false;
        //originalParent = this.original.parent('[data-lm-id]');

        var resizeCase = false;
        //originalSiblings = this.original.siblings(':not(.original-placeholder):not([data-lm-id="' + this.block.getId() + '"])') || [];

        this.original.remove();

        // case 1: it's a position/spacer and needs to be wrapped by a block (dropped at root or next to another block)
        if (type !== 'block' && type !== 'grid' && ((targetType === 'section' || targetType === 'grid') || (targetType === 'block' && parentType !== 'block'))) {
            wrapper = new Blocks.block({
                attributes: { size: 50 },
                builder: this.builder
            }).adopt(this.block.block);
            insider = new Blocks[this.block.block.data('lm-blocktype')]({
                id: this.block.block.data('lm-id'),
                builder: this.builder
            }).setLayout(this.block.block);

            wrapper.setSize();
            this.block = wrapper;
            this.builder.add(wrapper);
            this.builder.add(insider);
            insider.emit('rendered', insider, wrapper);
            wrapper.emit('rendered', wrapper, null);
            //console.log('1. resize me and my siblings');
            resizeCase = { case: 1 };
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
            //console.log('2. im leaving, resize my siblings');
        }

        // case 3: moving a block around, need to reset the sizes
        if (this.originalType === 'block' && this.block.getType() === 'block') {
            //console.log('3. im a block and ive been moved, resize my new siblings and the ones where i come from');
            resizeCase = { case: 3 };
            var previous = this.block.block.parent('[data-lm-blocktype="grid"]');
            if (previous.find('!> [data-lm-blocktype="container"]')) { previous = previous.parent(); }
            previous = previous.siblings(':not(.original-placeholder)');
            if (!this.block.isNew() && previous.length) { this.resizer.evenResize(previous); }

            this.block.block.attribute('style', null);
            this.block.setSize();
        }


        if (this.block.hasAttribute('size')) { this.block.setSize(this.placeholder.compute('flex')); }
        this.block.insert(this.placeholder);
        this.placeholder.remove();

        if (blockWasNew) {
            if (resizeCase && resizeCase.case === 1 || resizeCase.case === 3) { this.resizer.evenResize($([this.block.block, this.block.block.siblings()])); }
            if (resizeCase && resizeCase.case === 2 || resizeCase.case === 4) { this.resizer.evenResize(resizeCase.siblings); }

            this.element.attribute('style', null);
        }

        singles.disable();
        singles.cleanup(this.builder);

        var serial = this.builder.serialize(),
            lastEntry = this.history.get().data,
            callback = function(a, b) { return a === b; };

        if (!deepEquals(lastEntry[0], serial[0], callback)) { this.history.push(serial); }
    },

    stopAnimation: function(element) {
        $('[data-lm-root]').removeClass('moving');
        if (this.original) { this.original.remove(); }
        singles.disable();
        if (!this.block) { this.block = get(this.builder.map, element.data('lm-id')); }
        if (this.block && this.block.getType() === 'block') { this.block.setSize(); }
        if (this.block && this.block.isNew()) { this.element.attribute('style', null); }
    }
});

module.exports = LayoutManager;
