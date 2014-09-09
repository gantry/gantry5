var Blocks   = require('./blocks');

var DragDrop = require('./ui/dragdrop'),
    $        = require('./utils/elements.moofx'),
    zen      = require('elements/zen'),
    ready    = require('elements/domready');

var guid   = require('mout/random/guid'),
    set   = require('mout/object/set'),
    get   = require('mout/object/get'),
    unset = require('mout/object/unset');

require('elements/attributes');
require('elements/insertion');

var Builder = require('./builder');

var b,
    map = {},
    Log = {};


var drag = function(event, element){
    var type = $(element).data('lm-blocktype'), block, size;
    this.placeholder = zen('div.block.placeholder[data-lm-placeholder]').text('Drop Here');
    this.dirty = false;

    b.enableLonely();

    if (!element.data('lm-id')){
        block = new Blocks[type]();
        block.isNew = true;
        set(map, block.getId(), block);
    } else {
        size = {width: element[0].offsetWidth, height: element[0].offsetHeight};
        element.style({position: 'absolute', width: size.width, height: size.height});
        /*if (type == 'position' && !element.siblings()){
            var parentBlock = element.parent('[data-lm-id]').remove();
        }*/
        this.placeholder.after(element);
    }

    block = block || get(map, element.data('lm-id'));
    this.block = block;
};

var match = function(event, element){
    console.log('match', this.matched);
};

var location = function(event, location, target, element){
    target = $(target);
    this.placeholder.style({display: 'block'});

    if (this.dirty){
        var dirty = this.dirty.parent('.grid');
        this.dirty.before(dirty);
        dirty.remove();
        this.dirty = false;
    }

    var position,
        data = {
            target: {
                id:   target.data('lm-id'),
                type: target.data('lm-blocktype')
            },
            element: {
                id:   element.data('lm-id'),
                type: element.data('lm-blocktype')
            }
        };

    if (data.target.type == 'block'){
        position = (location.x == 'other') ? (location.y == 'above' ? 'top' : 'bottom') : location.x;
        this.placeholder[position](target);
        this.placementPosition = position;
    } else if (data.target.type == 'position' || data.target.type == 'spacer'){
        position = (location.x == 'other') ? (location.y == 'above' ? 'before' : 'after') : (location.x == 'before' ? 'left' : 'right');
        if (['left', 'right'].indexOf(position) == -1) this.placeholder[position](target);
        else {
            if (target.parent('.block').data('lm-id')){
                var grid  = zen('div.grid[data-lm-id="' + guid() + '"][data-lm-blocktype="grid"]').before(target),
                    block = zen('div.block[data-lm-id="' + guid() + '"][data-lm-dropzone][data-lm-blocktype="block"]').insert(grid);
                target.insert(block);

                this.placeholder[position == 'left' ? 'before' : 'after'](block);
                this.dirty = target;
            } else {
                this.placeholder[position == 'left' ? 'before' : 'after'](target.parent('.block'));
            }
        }
        this.placementPosition = position;
    }

    this.lastLocationTarget = target;

    //console.log(data, location);
    /*if (target.data('lm-blocktype') == 'section'){
        this.placeholder[location.y == 'above' ? 'before' : 'after'](target);
    } else {
        if (location.x != 'inside'){
        //    console.log('local', location, target);
            this.placeholder[location.x](target);
        }
    }*/
};

var leave = function(event, target, element){
    this.placeholder.style({display: 'none'});
};

var drop = function(event, target, element){
    if ((!this.block || !this.matched) && this.placeholder) this.placeholder.remove();
    if (!this.block) return;
    if (!this.matched) {
        if (this.block.isNew) unset(map, this.block.getId());
        return;
    }

    //if (this.dirty) element.remove();

    target = $(target);
    if (target[0] !== $(this.lastLocationTarget)[0]) target = this.lastLocationTarget;

    var direction  = this.direction,
        location   = this.lastLocation,
        id         = $(element).data('lm-id'),
        type       = $(element).data('lm-blocktype'),
        targetType = target.data('lm-blocktype');

   /* if (!target.data('lm-id') && this.block.getType() == 'position'){
        var section = new Blocks.section();
        set(map, section.getId(), section);
        section.adopt(this.block.block);
        this.block = section;
    }
*/
    /*this.block.block.animate({

    }, {
        callback: function(){
            this.style({position: 'relative', width: 'auto', height: 'auto'});
        }
    });*/

    /*if (target.data('lm-id')) this.block.block.after(this.placeholder);
    else this.block.block.insert(target);*/
    if ((targetType == 'block' || targetType == 'section') && ['before', 'after', 'left', 'right'].indexOf(this.placementPosition) !== -1){
        this.block.buildWrapper();
    } else if ((targetType == 'position' || targetType == 'spacer') && ['left', 'right'].indexOf(this.placementPosition) !== -1){
        this.block.buildWrapper();
    }

    this.block.insert(this.placeholder);
    this.placeholder.remove();

    //console.log('New Serial: ', new Builder().serialize());
    set(Log, +(new Date()), new Builder().serialize());
    //console.log('Log:', Log);
    this.block = null;
    var ghosts = b.cleanupLonely();
    //console.log('ghosts', ghosts);
    console.log(b._recursiveLoad(new Builder().serialize(), function(){}));
    b.disableLonely();
};

ready(function(){
    var dd = new DragDrop('#main', {delegate: '[data-lm-blocktype]:not([data-lm-nodrag])', droppables: '[data-lm-dropzone]'});

    //dd.on('dragdrop:start', function(event, element){ console.log('dragdrop:start'); });
    //dd.on('dragdrop:move',  function(event, element){ console.log('dragdrop:move'); });
    //dd.on('dragdrop:stop',  drop);
    //dd.on('dragdrop:match',    function(event, target, element){ console.log('dragdrop:match', target); });
    //dd.on('dragdrop:location', function(event, location, target, element){ console.log('dragdrop:location', location); });
    //dd.on('dragdrop:enter',    function(event, target, element){ console.log('dragdrop:enter', target); });
    //dd.on('dragdrop:leave',    function(event, target, element){ console.log('dragdrop:leave', target); });
    //dd.on('dragdrop:beforestart', beforeDrag);
    dd.on('dragdrop:start', drag);
    dd.on('dragdrop:location', location);
    /*dd.on('dragdrop:match', match);
    dd.on('dragdrop:enter', enter);*/
    dd.on('dragdrop:leave', leave);
    dd.on('dragdrop:stop', drop);


    var json = {
    "2330cbf9-25f2-4416-a0de-446fdce1ad0c": {
        "type": "section",
        "attributes": {
            "name": "Section 1",
            "key": "section-1"
        },
        "children": [{
            "ac1962c1-5587-4a24-8983-be8bfd9b1d2f": {
                "type": "grid",
                "attributes": {},
                "children": [{
                    "359b6022-55ce-45b9-880c-8974876a873d": {
                        "type": "block",
                        "attributes": {},
                        "children": [{
                            "fb585b76-4db5-4611-a96c-08c09bd8d90f": {
                                "type": "position",
                                "attributes": {
                                    "name": "Position 1",
                                    "key": "position-1"
                                },
                                "children": false
                            }
                        }]
                    }
                }, {
                    "388f9ef2-68ad-40bb-ba5e-8d534b24bdfc": {
                        "type": "block",
                        "attributes": {},
                        "children": [{
                            "6e85d292-e08b-448c-a46a-f5b2693db83e": {
                                "type": "position",
                                "attributes": {
                                    "name": "Position 2",
                                    "key": "position-2"
                                },
                                "children": false
                            }
                        }, {
                            "486fbfa4-b2a2-419e-972e-9386ec5f9448": {
                                "type": "grid",
                                "attributes": {},
                                "children": [{
                                    "f410832c-9a40-4af1-ad1e-89bdf5614455": {
                                        "type": "block",
                                        "attributes": {},
                                        "children": [{
                                            "a3079234-4d28-4a81-a87d-f6a13655f3b8": {
                                                "type": "position",
                                                "attributes": {
                                                    "name": "Position 3",
                                                    "key": "position-3"
                                                },
                                                "children": false
                                            }
                                        }]
                                    }
                                }, {
                                    "b0b1352e-8be7-46fb-bf00-fa2e8a9032b4": {
                                        "type": "block",
                                        "attributes": {},
                                        "children": [{
                                            "d35a7f80-0e4b-4ec8-b087-6da430159242": {
                                                "type": "spacer",
                                                "attributes": {},
                                                "children": false
                                            }
                                        }]
                                    }
                                }]
                            }
                        }]
                    }
                }]
            }
        }]
    }
};

    //new Builder().load(JSON.parse(JSON.stringify(json)));
    b = new Builder(json);
    b.on('loading', function(block){
        if (block) set(map, block.getId(), block);
    });

    b.load();
    console.log('Serialized: ', b.serialize());
    set(Log, +(new Date()), b.serialize());

    b.on('loaded', function(data){
        console.log('Map:', map);

        b.disableLonely();
    });
});

module.exports = {};
