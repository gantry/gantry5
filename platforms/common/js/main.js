var G5;
(function (modules, global) {
    var cache = {}, require = function (id) {
            var module = cache[id];
            if (!module) {
                module = cache[id] = {};
                var exports = module.exports = {};
                modules[id].call(exports, require, module, exports, global);
            }
            return module.exports;
        };
    G5 = require('0');
}({
    '0': function (require, module, exports, global) {
        module.exports = {
            ui: require('1'),
            lm: require('2')
        };
    },
    '1': function (require, module, exports, global) {
        var ready = require('3'), $ = require('9');
        ready(function () {
            var toggle = $('[data-sidebar-toggle]'), mode = $('[data-mode-toggle] > span');
            if (!toggle && !mode)
                return;
            if (toggle)
                toggle.on('click', function () {
                    var sidebar = $('.block.sidebar-block'), elements = $([
                            toggle,
                            sidebar
                        ]);
                    if (sidebar.hasClass('sidebar-closed'))
                        elements.removeClass('sidebar-closed').removeClass('sidebar-icons');
                    else if (sidebar.hasClass('sidebar-icons'))
                        elements.removeClass('sidebar-icons').addClass('sidebar-closed');
                    else
                        elements.addClass('sidebar-icons');
                });
            if (mode)
                mode.on('click', function () {
                    var current = this.parent().hasClass('production') ? 'production' : 'development', opposite = current == 'production' ? 'development' : 'production';
                    this.parent().removeClass(current).addClass(opposite);
                    $('[data-mode-indicator]').data('mode-indicator', opposite);
                });
        });
        ready(function () {
            var input, hiddens, radios;
            $(document).delegate('click', '.enabler .toggle', function (e, element) {
                element = $(element);
                hiddens = element.find('~~ [type=hidden]');
                if (!hiddens)
                    return true;
                if (hiddens)
                    hiddens.value(hiddens.value() == '0' ? '1' : '0');
            });
        });
        module.exports = {};
    },
    '2': function (require, module, exports, global) {
        var ready = require('3'), json = require('4'), $ = require('5'), Builder = require('6'), DropManager = require('7'), History = require('8');
        var builder, dropmanager, history;
        ready(function () {
            builder = new Builder(json).load();
            history = new History(builder.serialize());
            ready(function () {
                dropmanager = new DropManager('#main', {
                    delegate: '[data-lm-root="page"] .section, [data-lm-root="section"] .section > .grid [data-lm-blocktype]:not([data-lm-nodrag]), .lm-newblocks [data-lm-blocktype]',
                    droppables: '[data-lm-dropzone]',
                    exclude: '.section-header .button, .lm-newblocks .float-right .button',
                    resize_handles: '[data-lm-root="section"] .grid > .block:not(:last-child)',
                    builder: builder,
                    history: history
                });
            });
        });
        ready(function () {
            var HM = {
                    back: $('[data-lm-back]'),
                    forward: $('[data-lm-forward]')
                };
            if (!HM.back && !HM.forward)
                return;
            HM.back.on('click', function () {
                if ($(this).hasClass('disabled'))
                    return false;
                history.undo();
            });
            HM.forward.on('click', function () {
                if ($(this).hasClass('disabled'))
                    return false;
                history.redo();
            });
            history.on('push', function (session, index, reset) {
                if (index && HM.back.hasClass('disabled'))
                    HM.back.removeClass('disabled');
                if (reset && !HM.forward.hasClass('disabled'))
                    HM.forward.addClass('disabled');
            });
            history.on('undo', function (session, index) {
                builder.reset(session.data);
                HM.forward.removeClass('disabled');
                if (!index)
                    HM.back.addClass('disabled');
                dropmanager.singles('disable');
            });
            history.on('redo', function (session, index) {
                builder.reset(session.data);
                HM.back.removeClass('disabled');
                if (index == this.session.length - 1)
                    HM.forward.addClass('disabled');
                dropmanager.singles('disable');
            });
        });
        module.exports = {
            builder: builder,
            dropmanager: dropmanager,
            history: history,
            $: $
        };
    },
    '3': function (require, module, exports, global) {
        'use strict';
        var $ = require('a');
        var readystatechange = 'onreadystatechange' in document, shouldPoll = false, loaded = false, readys = [], checks = [], ready = null, timer = null, test = document.createElement('div'), doc = $(document), win = $(window);
        var domready = function () {
            if (timer)
                timer = clearTimeout(timer);
            if (!loaded) {
                if (readystatechange)
                    doc.off('readystatechange', check);
                doc.off('DOMContentLoaded', domready);
                win.off('load', domready);
                loaded = true;
                for (var i = 0; ready = readys[i++];)
                    ready();
            }
            return loaded;
        };
        var check = function () {
            for (var i = checks.length; i--;)
                if (checks[i]())
                    return domready();
            return false;
        };
        var poll = function () {
            clearTimeout(timer);
            if (!check())
                timer = setTimeout(poll, 1000 / 60);
        };
        if (document.readyState) {
            var complete = function () {
                return !!/loaded|complete/.test(document.readyState);
            };
            checks.push(complete);
            if (!complete()) {
                if (readystatechange)
                    doc.on('readystatechange', check);
                else
                    shouldPoll = true;
            } else {
                domready();
            }
        }
        if (test.doScroll) {
            var scrolls = function () {
                try {
                    test.doScroll();
                    return true;
                } catch (e) {
                }
                return false;
            };
            if (!scrolls()) {
                checks.push(scrolls);
                shouldPoll = true;
            }
        }
        if (shouldPoll)
            poll();
        doc.on('DOMContentLoaded', domready);
        win.on('load', domready);
        module.exports = function (ready) {
            loaded ? ready() : readys.push(ready);
            return null;
        };
    },
    '4': function (require, module, exports, global) {
        module.exports = [
            {
                'id': '2330cbf9-25f2-4416-a0de-446fdce1ad0c',
                'type': 'section',
                'attributes': {
                    'name': 'Header',
                    'key': 'section-header',
                    'type': 'header',
                    'id': 'header'
                },
                'children': [{
                        'id': 'ac1962c1-5587-4a24-8983-be8bfd9b1d2f',
                        'type': 'grid',
                        'attributes': {},
                        'children': [
                            {
                                'id': '359b6022-55ce-45b9-880c-8974876a873d',
                                'type': 'block',
                                'attributes': { 'size': 20 },
                                'children': [{
                                        'id': 'fb585b76-4db5-4611-a96c-08c09bd8d90f',
                                        'type': 'position',
                                        'attributes': {
                                            'name': 'Position 1',
                                            'key': 'position-1'
                                        },
                                        'children': []
                                    }]
                            },
                            {
                                'id': '388f9ef2-68ad-40bb-ba5e-8d534b24bdfc',
                                'type': 'block',
                                'attributes': { 'size': 80 },
                                'children': [{
                                        'id': '6e85d292-e08b-448c-a46a-f5b2693db83e',
                                        'type': 'position',
                                        'attributes': {
                                            'name': 'Position 2',
                                            'key': 'position-2'
                                        },
                                        'children': []
                                    }]
                            }
                        ]
                    }]
            },
            {
                'id': '43b618ab-5066-410f-9a9d-0601adeeea02',
                'type': 'grid',
                'attributes': {},
                'children': [
                    {
                        'id': '0cc9d063-1447-4335-963b-aee05fa28398',
                        'type': 'block',
                        'attributes': { 'size': 75 },
                        'children': [
                            {
                                'id': 'f29bf0b7-7348-48de-b979-e10008de799e',
                                'type': 'section',
                                'attributes': {
                                    'name': 'Showcase',
                                    'key': 'section-showcase',
                                    'type': 'section',
                                    'id': 'showcase'
                                },
                                'children': [{
                                        'id': 'e5f91d76-81c8-4e46-bb24-86164ca6f52c',
                                        'type': 'grid',
                                        'attributes': {},
                                        'children': [
                                            {
                                                'id': '2f2622ca-01ba-44fb-845f-6028285a0176',
                                                'type': 'block',
                                                'attributes': { 'size': 50 },
                                                'children': [{
                                                        'id': '8462e204-f196-498f-a71b-c1c8799e2e80',
                                                        'type': 'position',
                                                        'attributes': {
                                                            'name': 'Position Showcase 1',
                                                            'key': 'position-showcase-1'
                                                        },
                                                        'children': []
                                                    }]
                                            },
                                            {
                                                'id': 'dbcafc00-a3c9-42ea-bac0-8d9a85d3284d',
                                                'type': 'block',
                                                'attributes': { 'size': 50 },
                                                'children': [{
                                                        'id': 'f568d0bb-0964-4091-9971-b49cb8fd01e3',
                                                        'type': 'position',
                                                        'attributes': {
                                                            'name': 'Position Showcase 2',
                                                            'key': 'position-showcase-2'
                                                        },
                                                        'children': []
                                                    }]
                                            }
                                        ]
                                    }]
                            },
                            {
                                'id': '5672f5b7-01c3-4f6a-a826-f2537e881b6d',
                                'type': 'section',
                                'attributes': {
                                    'name': 'Feature',
                                    'key': 'section-feature',
                                    'type': 'section',
                                    'id': 'feature'
                                },
                                'children': [{
                                        'id': '5df8ef8d-8dfa-46a8-b8c1-b4520f8d1ebc',
                                        'type': 'grid',
                                        'attributes': {},
                                        'children': [
                                            {
                                                'id': '837e1ebe-c18e-49e3-ada6-1efbeb62c76f',
                                                'type': 'block',
                                                'attributes': { 'size': 50 },
                                                'children': [{
                                                        'id': '1875d46e-639f-4a36-b553-ef6b561bccdc',
                                                        'type': 'position',
                                                        'attributes': {
                                                            'name': 'Position Feature 1',
                                                            'key': 'position-feature-1'
                                                        },
                                                        'children': []
                                                    }]
                                            },
                                            {
                                                'id': 'd872554e-da74-4452-87f0-b8d96fe6b711',
                                                'type': 'block',
                                                'attributes': { 'size': 50 },
                                                'children': [{
                                                        'id': '51d44206-6a78-45b6-9da0-ffee380a1d16',
                                                        'type': 'position',
                                                        'attributes': {
                                                            'name': 'Position Feature 2',
                                                            'key': 'position-feature-2'
                                                        },
                                                        'children': []
                                                    }]
                                            }
                                        ]
                                    }]
                            },
                            {
                                'id': 'b40f50f7-1950-461e-8307-d277d30fc54f',
                                'type': 'section',
                                'attributes': {
                                    'name': 'Main',
                                    'key': 'section-main',
                                    'type': 'main',
                                    'id': 'main'
                                },
                                'children': [{
                                        'id': '6d51de8b-e954-4135-8ce3-7665bb6b5d07',
                                        'type': 'grid',
                                        'attributes': {},
                                        'children': [{
                                                'id': '51673446-865a-4999-b789-6a864d64e115',
                                                'type': 'block',
                                                'attributes': { 'size': 100 },
                                                'children': [{
                                                        'id': 'f03448da-fb1b-49ec-8d33-b58152ac8b0d',
                                                        'type': 'mainbody',
                                                        'attributes': {
                                                            'name': 'Mainbody',
                                                            'key': 'mainbody'
                                                        },
                                                        'children': []
                                                    }]
                                            }]
                                    }]
                            },
                            {
                                'id': 'c8315bcd-37c2-4691-8c14-32d44980a173',
                                'type': 'section',
                                'attributes': {
                                    'name': 'Bottom',
                                    'key': 'section-bottom',
                                    'type': 'section',
                                    'id': 'bottom'
                                },
                                'children': [{
                                        'id': 'd59c63b3-6bce-4912-a30a-3b777e3c3bc7',
                                        'type': 'grid',
                                        'attributes': {},
                                        'children': [{
                                                'id': 'a76cfe58-e9f3-4ab0-a741-489084e347be',
                                                'type': 'block',
                                                'attributes': { 'size': 100 },
                                                'children': [{
                                                        'id': 'ec9e89e5-5043-4e3e-a853-fe470247a875',
                                                        'type': 'position',
                                                        'attributes': {
                                                            'name': 'Position Bottom',
                                                            'key': 'position-bottom'
                                                        },
                                                        'children': []
                                                    }]
                                            }]
                                    }]
                            }
                        ]
                    },
                    {
                        'id': '6b28344b-7ce3-431c-907f-204b851f7218',
                        'type': 'block',
                        'attributes': { 'size': 25 },
                        'children': [{
                                'id': '303ea0a4-5279-436f-b808-b37d99c5de90',
                                'type': 'section',
                                'attributes': {
                                    'name': 'Sidebar',
                                    'key': 'section-sidebar',
                                    'type': 'section',
                                    'id': 'sidebar'
                                },
                                'children': [{
                                        'id': '89230c44-26a3-4f4f-b135-a2194f05b3b6',
                                        'type': 'grid',
                                        'attributes': {},
                                        'children': [{
                                                'id': '6002f826-16c6-4920-b550-abdb93a9398b',
                                                'type': 'block',
                                                'attributes': { 'size': 100 },
                                                'children': [{
                                                        'id': 'fb585b76-4db5-4611-a96c-08c09bd8d90f',
                                                        'type': 'position',
                                                        'attributes': {
                                                            'name': 'Position Sidebar',
                                                            'key': 'position-sidebar'
                                                        },
                                                        'children': []
                                                    }]
                                            }]
                                    }]
                            }]
                    }
                ]
            },
            {
                'id': 'ddeb26e3-caeb-4a5c-82c4-9f72a556f107',
                'type': 'section',
                'attributes': {
                    'name': 'Section Footer',
                    'key': 'section-footer',
                    'type': 'footer',
                    'id': 'footer'
                },
                'children': [{
                        'id': '9550d178-71b5-4e04-a566-137e6350ea70',
                        'type': 'grid',
                        'attributes': {},
                        'children': [
                            {
                                'id': '54bb2fd9-fc27-462b-ada8-de41912bd8fa',
                                'type': 'block',
                                'attributes': { 'size': 16.6667 },
                                'children': [{
                                        'id': '825a6498-c19b-42df-bffc-86dae90b64d8',
                                        'type': 'spacer',
                                        'attributes': {
                                            'name': 'Position Footer',
                                            'key': 'position-footer'
                                        },
                                        'children': []
                                    }]
                            },
                            {
                                'id': 'c3390225-81c8-4403-91d4-14a2b01fb1bd',
                                'type': 'block',
                                'attributes': { 'size': 66.6667 },
                                'children': [{
                                        'id': '0f7653c0-909b-4430-afdd-75bc5df8105a',
                                        'type': 'position',
                                        'attributes': {
                                            'name': 'Position Footer',
                                            'key': 'position-footer'
                                        },
                                        'children': []
                                    }]
                            },
                            {
                                'id': 'b853fa65-3ccb-4994-b266-01b398961063',
                                'type': 'block',
                                'attributes': { 'size': 16.6667 },
                                'children': [{
                                        'id': '491e8d6c-c762-401f-9ebf-018d83d4a0fe',
                                        'type': 'spacer',
                                        'attributes': {
                                            'name': 'Position Footer',
                                            'key': 'position-footer'
                                        },
                                        'children': []
                                    }]
                            }
                        ]
                    }]
            }
        ];
    },
    '5': function (require, module, exports, global) {
        'use strict';
        var $ = require('b');
        var trim = require('c'), forEach = require('d'), filter = require('e'), indexOf = require('f');
        $.implement({
            setAttribute: function (name, value) {
                return this.forEach(function (node) {
                    node.setAttribute(name, value);
                });
            },
            getAttribute: function (name) {
                var attr = this[0].getAttributeNode(name);
                return attr && attr.specified ? attr.value : null;
            },
            hasAttribute: function (name) {
                var node = this[0];
                if (node.hasAttribute)
                    return node.hasAttribute(name);
                var attr = node.getAttributeNode(name);
                return !!(attr && attr.specified);
            },
            removeAttribute: function (name) {
                return this.forEach(function (node) {
                    var attr = node.getAttributeNode(name);
                    if (attr)
                        node.removeAttributeNode(attr);
                });
            }
        });
        var accessors = {};
        forEach([
            'type',
            'value',
            'name',
            'href',
            'title',
            'id'
        ], function (name) {
            accessors[name] = function (value) {
                return value !== undefined ? this.forEach(function (node) {
                    node[name] = value;
                }) : this[0][name];
            };
        });
        forEach([
            'checked',
            'disabled',
            'selected'
        ], function (name) {
            accessors[name] = function (value) {
                return value !== undefined ? this.forEach(function (node) {
                    node[name] = !!value;
                }) : !!this[0][name];
            };
        });
        var classes = function (className) {
            var classNames = trim(className).replace(/\s+/g, ' ').split(' '), uniques = {};
            return filter(classNames, function (className) {
                if (className !== '' && !uniques[className])
                    return uniques[className] = className;
            }).sort();
        };
        accessors.className = function (className) {
            return className !== undefined ? this.forEach(function (node) {
                node.className = classes(className).join(' ');
            }) : classes(this[0].className).join(' ');
        };
        $.implement({
            attribute: function (name, value) {
                var accessor = accessors[name];
                if (accessor)
                    return accessor.call(this, value);
                if (value != null)
                    return this.setAttribute(name, value);
                if (value === null)
                    return this.removeAttribute(name);
                if (value === undefined)
                    return this.getAttribute(name);
            }
        });
        $.implement(accessors);
        $.implement({
            check: function () {
                return this.checked(true);
            },
            uncheck: function () {
                return this.checked(false);
            },
            disable: function () {
                return this.disabled(true);
            },
            enable: function () {
                return this.disabled(false);
            },
            select: function () {
                return this.selected(true);
            },
            deselect: function () {
                return this.selected(false);
            }
        });
        $.implement({
            classNames: function () {
                return classes(this[0].className);
            },
            hasClass: function (className) {
                return indexOf(this.classNames(), className) > -1;
            },
            addClass: function (className) {
                return this.forEach(function (node) {
                    var nodeClassName = node.className;
                    var classNames = classes(nodeClassName + ' ' + className).join(' ');
                    if (nodeClassName !== classNames)
                        node.className = classNames;
                });
            },
            removeClass: function (className) {
                return this.forEach(function (node) {
                    var classNames = classes(node.className);
                    forEach(classes(className), function (className) {
                        var index = indexOf(classNames, className);
                        if (index > -1)
                            classNames.splice(index, 1);
                    });
                    node.className = classNames.join(' ');
                });
            }
        });
        $.prototype.toString = function () {
            var tag = this.tag(), id = this.id(), classes = this.classNames();
            var str = tag;
            if (id)
                str += '#' + id;
            if (classes.length)
                str += '.' + classes.join('.');
            return str;
        };
        var textProperty = document.createElement('div').textContent == null ? 'innerText' : 'textContent';
        $.implement({
            tag: function () {
                return this[0].tagName.toLowerCase();
            },
            html: function (html) {
                return html !== undefined ? this.forEach(function (node) {
                    node.innerHTML = html;
                }) : this[0].innerHTML;
            },
            text: function (text) {
                return text !== undefined ? this.forEach(function (node) {
                    node[textProperty] = text;
                }) : this[0][textProperty];
            },
            data: function (key, value) {
                switch (value) {
                case undefined:
                    return this.getAttribute('data-' + key);
                case null:
                    return this.removeAttribute('data-' + key);
                default:
                    return this.setAttribute('data-' + key, value);
                }
            }
        });
        module.exports = $;
    },
    '6': function (require, module, exports, global) {
        var prime = require('g'), $ = require('9'), Emitter = require('h'), Bound = null, Options = null;
        var Blocks = require('i');
        var forOwn = require('j'), forEach = require('k'), size = require('l'), isArray = require('m'), flatten = require('n'), set = require('o'), unset = require('p'), get = require('q');
        require('5');
        require('r');
        var rpad = require('s'), repeat = require('t');
        $.implement({
            empty: function () {
                return this.forEach(function (node) {
                    var first;
                    while (first = node.firstChild)
                        node.removeChild(first);
                });
            }
        });
        var Builder = new prime({
                inherits: Emitter,
                constructor: function (structure) {
                    if (structure)
                        this.setStructure(structure);
                    this.map = {};
                    return this;
                },
                setStructure: function (structure) {
                    try {
                        this.structure = typeof structure == 'object' ? structure : JSON.parse(structure);
                    } catch (e) {
                        console.error('Parsing error:', e);
                    }
                },
                add: function (block) {
                    var type, id = typeof block == 'string' ? block : block.id;
                    set(this.map, id, block);
                    block.isNew(false);
                },
                remove: function (block) {
                    block = typeof block == 'string' ? block : block.id;
                    unset(this.map, block);
                },
                load: function (data) {
                    this.recursiveLoad(data);
                    this.emit('loaded', data);
                    return this;
                },
                serialize: function (root) {
                    var serie = {}, obj = {}, serieChildren = [];
                    root = root || $('[data-lm-root]');
                    if (!root)
                        return;
                    var blocks = root.search('> [data-lm-id]'), id, type, serial, hasChildren, children;
                    forEach(blocks, function (element) {
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
                        serieChildren.push(serial);
                    }, this);
                    return serieChildren;
                },
                __serialize: function (root) {
                    var serie = {}, obj = {}, serieChildren = {};
                    root = root || $('[data-lm-root]');
                    var blocks = root.search('> [data-lm-id]'), id, type, serial, hasChildren, children;
                    forEach(blocks, function (element) {
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
                        if (blocks.length <= 1)
                            set(serie, id, serial);
                        else {
                            keysSort.push(id);
                            set(serieChildren, id, serial);
                        }
                    }, this);
                    return size(serieChildren) ? serieChildren : serie;
                },
                insert: function (key, value, parent, object) {
                    if (!$('[data-lm-root]'))
                        return;
                    var Element = new Blocks[value.type]({
                            id: key,
                            attributes: value.attributes || {}
                        });
                    if (!parent)
                        Element.block.insert($('[data-lm-root]'));
                    else
                        Element.block.insert($('[data-lm-id="' + parent + '"]'));
                    if (value.type == 'grid' && (parent && get(this.map, parent).getType() == 'section')) {
                        Element.block.data('lm-dropzone', null);
                    }
                    if (Element.getType() == 'block') {
                        Element.setSize();
                    }
                    this.add(Element);
                    return Element;
                },
                reset: function (data) {
                    this.map = {};
                    this.setStructure(data || {});
                    $('[data-lm-root]').empty();
                    this.load();
                },
                cleanupLonely: function () {
                    var ghosts = [], parent, siblings, children = $('[data-lm-root] > .section > .grid > .block .grid > .block, [data-lm-root] > .section > .grid > .block > .block');
                    if (!children)
                        return;
                    var isGrid;
                    children.forEach(function (child) {
                        child = $(child);
                        parent = null;
                        isGrid = child.parent().hasClass('grid');
                        if (isGrid && child.siblings())
                            return false;
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
                recursiveLoad: function (data, callback, depth, parent) {
                    data = data || this.structure;
                    depth = depth || 0;
                    parent = parent || false;
                    callback = callback || this.insert;
                    forEach(data, function (value, key, object) {
                        this.emit('loading', callback.call(this, value.id, value, parent, depth));
                        if (value.children && size(value.children)) {
                            depth++;
                            forEach(value.children, function (childValue, childKey, array) {
                                this.recursiveLoad([childValue], callback, depth, value.id);
                            }, this);
                        }
                    }, this);
                },
                __recursiveLoad: function (data, callback, depth, parent) {
                    data = data || this.structure;
                    depth = depth || 0;
                    parent = parent || false;
                    callback = callback || this.insert;
                    forEach(data, function (value, key, object) {
                        this.emit('loading', callback.call(this, key, value, parent, depth, object));
                        if (value.children && size(value.children)) {
                            this.recursiveLoad(value.children, callback, ++depth, key);
                        }
                        depth--;
                    }, this);
                }
            });
        module.exports = Builder;
    },
    '7': function (require, module, exports, global) {
        var prime = require('g'), $ = require('u'), zen = require('v'), Emitter = require('h'), Bound = require('w'), Options = require('x'), Blocks = require('i'), DragDrop = require('y'), Resizer = require('z'), Eraser = require('10'), get = require('q'), every = require('11'), isArray = require('m'), isObject = require('12'), equals = require('13');
        var deepEquals = function (a, b, callback) {
            callback = callback || defaultCompare;
            function compare(a, b) {
                return deepEquals(a, b, callback);
            }
            if (isArray(a) && isArray(b)) {
                if (a.length != b.length)
                    return false;
                return every(a, function (obj, index, arr) {
                    return equals(obj, b[index], compare);
                });
            }
            if (!isObject(a) || !isObject(b)) {
                return callback(a, b);
            }
            return equals(a, b, compare);
        };
        var singles = {
                disable: function () {
                    var root = $('[data-lm-root]'), mode = root.data('lm-root') || 'page', children = root.search('.grid .block [data-lm-id]:not([data-lm-blocktype="grid"]):not([data-lm-blocktype="block"])');
                    if (mode == 'page') {
                        var sectionChildren = root.search('.section *:not(.button)');
                        if (sectionChildren)
                            sectionChildren.style({ 'pointer-events': 'none' });
                        return;
                    }
                    if (!children)
                        return;
                    children.attribute('style', null).forEach(function (element) {
                        element = $(element);
                        if (!element.siblings())
                            element.style({ 'pointer-events': 'none' });
                    });
                },
                enable: function () {
                    var root = $('[data-lm-root]'), mode = root.data('lm-root') || 'page', children = root.search('.grid .block [data-lm-id]:not([data-lm-blocktype="grid"]):not([data-lm-blocktype="block"])');
                    if (!children || mode == 'page')
                        return;
                    children.forEach(function (element) {
                        element = $(element);
                        if (!element.siblings())
                            element.attribute('style', null);
                    });
                },
                cleanup: function (builder) {
                    var roots = $('[data-lm-root] .section > .grid'), grids = $('[data-lm-root] .section > .grid .grid'), sects = $('[data-lm-root="page"] .grid > .block:empty');
                    if (!grids && !roots && !sects)
                        return;
                    var children, siblings, container;
                    if (grids)
                        grids.forEach(function (grid) {
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
                    if (roots)
                        roots.data('lm-dropzone', null);
                }
            };
        var DropManager = new prime({
                mixin: [
                    Bound,
                    Options
                ],
                inherits: Emitter,
                constructor: function (element, options) {
                    if (!$('[data-lm-root]'))
                        return;
                    this.dragdrop = new DragDrop(element, options);
                    this.resizer = new Resizer(element, options);
                    this.eraser = new Eraser('[data-lm-eraseblock]', options);
                    this.dragdrop.on('dragdrop:start', this.bound('start')).on('dragdrop:location', this.bound('location')).on('dragdrop:nolocation', this.bound('nolocation')).on('dragdrop:resize', this.bound('resize')).on('dragdrop:stop:erase', this.bound('removeElement')).on('dragdrop:stop', this.bound('stop')).on('dragdrop:stop:animation', this.bound('stopAnimation'));
                    this.builder = options.builder;
                    this.history = options.history;
                    singles.disable();
                },
                singles: function (mode) {
                    singles[mode]();
                },
                start: function (event, element) {
                    this.block = this.dirty = null;
                    this.mode = $('[data-lm-root]').data('lm-root') || 'page';
                    $('[data-lm-root]').addClass('moving');
                    var type = $(element).data('lm-blocktype'), clone = element[0].cloneNode(true);
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
                location: function (event, location, target, element) {
                    target = $(target);
                    if (this.dirty) {
                        var dirty = this.dirty.target.parent('.grid');
                        this.dirty.target.before(dirty);
                        dirty.remove();
                        this.dirty = null;
                    }
                    var position, dataType = target.data('lm-blocktype');
                    if (!dataType && target.data('lm-root'))
                        dataType = 'root';
                    if (this.mode != 'page' && dataType == 'section')
                        return;
                    var exclude = ':not(.placeholder):not([data-lm-id="' + this.original.data('lm-id') + '"])', adjacents = {
                            before: this.original.previousSiblings(exclude),
                            after: this.original.nextSiblings(exclude)
                        };
                    if (adjacents.before)
                        adjacents.before = $(adjacents.before[0]);
                    if (adjacents.after)
                        adjacents.after = $(adjacents.after[0]);
                    if (dataType == 'block' && (adjacents.before == target && location.x == 'after') || adjacents.after == target && location.x == 'before') {
                        return;
                    }
                    switch (dataType) {
                    case 'root':
                        if (location.x == 'other') {
                            position = location.y == 'above' ? 'top' : 'bottom';
                            this.placeholder[position](target);
                        }
                        break;
                    case 'section':
                        position = location.x == 'other' ? location.y == 'above' ? 'before' : 'after' : location.x == 'before' ? 'left' : 'right';
                        if ([
                                'left',
                                'right'
                            ].indexOf(position) == -1)
                            this.placeholder[position](target);
                        else {
                            if (target.parent('.block').data('lm-id') || target.parent().data('lm-root')) {
                                var grid = zen('div.grid[data-lm-id="' + this.block.guid() + '"][data-lm-dropzone][data-lm-blocktype="grid"]').before(target), block = zen('div.block[data-lm-id="' + this.block.guid() + '"][data-lm-dropzone][data-lm-blocktype="block"]').insert(grid);
                                target.insert(block);
                                this.placeholder[position == 'left' ? 'before' : 'after'](block);
                                this.dirty = {
                                    element: grid,
                                    target: target
                                };
                            } else {
                                this.placeholder[position == 'left' ? 'before' : 'after'](target.parent('.block'));
                            }
                        }
                        break;
                    case 'grid':
                    case 'block':
                        var method;
                        if (dataType == 'section' || dataType == 'grid')
                            method = location.y == 'above' ? 'before' : 'after';
                        if (dataType == 'block')
                            method = location.y == 'above' ? 'top' : 'bottom';
                        position = location.x == 'other' ? method : location.x;
                        if (dataType == 'block' && position == method)
                            this.placeholder.removeClass('in-between');
                        this.placeholder[position](target);
                        break;
                    case 'position':
                    case 'spacer':
                        position = location.x == 'other' ? location.y == 'above' ? 'before' : 'after' : location.x == 'before' ? 'left' : 'right';
                        if ([
                                'left',
                                'right'
                            ].indexOf(position) == -1)
                            this.placeholder[position](target);
                        else {
                            if (target.parent('.block').data('lm-id')) {
                                var grid = zen('div.grid[data-lm-id="' + this.block.guid() + '"][data-lm-dropzone][data-lm-blocktype="grid"]').before(target), block = zen('div.block[data-lm-id="' + this.block.guid() + '"][data-lm-dropzone][data-lm-blocktype="block"]').insert(grid);
                                target.insert(block);
                                this.placeholder[position == 'left' ? 'before' : 'after'](block);
                                this.dirty = {
                                    element: grid,
                                    target: target
                                };
                            } else {
                                this.placeholder[position == 'left' ? 'before' : 'after'](target.parent('.block'));
                            }
                        }
                        break;
                    }
                    this.placeholder.removeClass('in-between').removeClass('in-between-sections');
                    this.placeholder.style({ display: 'block' })[dataType != 'block' ? 'removeClass' : 'addClass']('in-between');
                    if (this.placeholder.parent().data('lm-blocktype') == 'block')
                        this.placeholder.addClass('in-between-sections');
                },
                nolocation: function (event) {
                    if (this.placeholder)
                        this.placeholder.style({ display: 'none' });
                    var siblings = this.placeholder.siblings();
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
                resize: function (event, element, siblings) {
                    this.resizer.start(event, element, siblings);
                },
                removeElement: function (event, element) {
                    this.dragdrop.removeElement = false;
                    var transition = { opacity: 0 };
                    element.animate(transition, { duration: '150ms' });
                    this.eraser.hide();
                    $(document).off(this.dragdrop.EVENTS.MOVE, this.dragdrop.bound('move'));
                    $(document).off(this.dragdrop.EVENTS.STOP, this.dragdrop.bound('stop'));
                    this.builder.remove(this.block.getId());
                    var children = this.block.block.search('[data-lm-id]');
                    if (children && children.length)
                        children.forEach(function (child) {
                            this.builder.remove($(child).data('lm-id'));
                        }, this);
                    this.block.block.remove();
                    if (this.placeholder)
                        this.placeholder.remove();
                    if (this.original)
                        this.original.remove();
                    this.element = this.block = this.dirty = null;
                    singles.disable();
                    singles.cleanup(this.builder);
                    this.history.push(this.builder.serialize());
                },
                stop: function (event, target, element) {
                    $('[data-lm-root]').removeClass('moving');
                    var lastOvered = $(this.dragdrop.lastOvered);
                    if (lastOvered && lastOvered.matches(this.eraser.element.find('.trash-zone'))) {
                        return;
                    }
                    if (!this.block.isNew())
                        this.eraser.hide();
                    if (!this.dragdrop.matched) {
                        if (this.placeholder)
                            this.placeholder.remove();
                        if (this.original)
                            this.original.remove();
                        return;
                    }
                    target = $(target);
                    var wrapper, insider, type = this.block.getType(), targetId = target.data('lm-id'), targetType = !targetId ? false : get(this.builder.map, targetId) ? get(this.builder.map, targetId).getType() : target.data('lm-blocktype'), parentId = this.placeholder.parent().data('lm-id'), parentType = get(this.builder.map, parentId || '') ? get(this.builder.map, parentId).getType() : false, originalParent = this.original.parent('[data-lm-id]');
                    var resizeCase = false, originalSiblings = this.original.siblings(':not(.original-placeholder):not([data-lm-id="' + this.block.getId() + '"])') || [];
                    this.original.remove();
                    if (type != 'block' && (this.dirty || targetType == 'section' || targetType == 'grid' || !this.dirty && targetType == 'block' && parentType != 'block')) {
                        wrapper = new Blocks.block({ attributes: { size: 50 } }).adopt(this.block.block);
                        insider = new Blocks[(this.block.block.data('lm-blocktype'))]({ id: this.block.block.data('lm-id') }).setLayout(this.block.block);
                        wrapper.setSize();
                        this.block = wrapper;
                        this.builder.add(wrapper);
                        this.builder.add(insider);
                        console.log('1. resize me and my siblings');
                        resizeCase = { case: 1 };
                        console.log(this.block);
                    }
                    var children = this.block.block.children();
                    if (type == 'block' && this.placeholder.siblings('.position, .spacer, .grid') && (children && children.length == 1)) {
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
                    if (this.originalType == 'block' && this.block.getType() == 'block') {
                        console.log('3. im a block and ive been moved, resize my new siblings and the ones where i come from');
                        resizeCase = { case: 3 };
                        var previous = this.block.block.parent.siblings(':not(.original-placeholder)');
                        if (previous.length)
                            this.resizer.evenResize(previous);
                    }
                    console.log(targetType);
                    if (this.dirty) {
                        var structure = $([
                                this.dirty.element,
                                this.dirty.element.search('[data-lm-id]')
                            ]);
                        var dirtyId, dirtyType, dirtyMap, dirtyBlock;
                        structure.forEach(function (element) {
                            element = $(element);
                            dirtyId = element.data('lm-id');
                            dirtyType = element.data('lm-blocktype');
                            dirtyMap = get(this.builder.map, dirtyId);
                            if (!dirtyMap) {
                                dirtyBlock = new Blocks[dirtyType]({ id: dirtyId }).setLayout(element);
                                if (dirtyType == 'block')
                                    dirtyBlock.setSize(50, true);
                                this.builder.add(dirtyBlock);
                            }
                        }, this);
                    }
                    if (this.block.hasAttribute('size'))
                        this.block.setSize(this.placeholder.compute('flex'));
                    this.block.insert(this.placeholder);
                    this.placeholder.remove();
                    if (resizeCase.case == 1) {
                        console.log(this.block.block);
                    }
                    if (resizeCase && resizeCase.case == 1 || resizeCase.case == 3)
                        this.resizer.evenResize($([
                            this.block.block,
                            this.block.block.siblings()
                        ]), !this.dirty);
                    if (resizeCase && resizeCase.case == 2 || resizeCase.case == 4)
                        this.resizer.evenResize(resizeCase.siblings);
                    singles.disable();
                    singles.cleanup(this.builder);
                    var serial = this.builder.serialize(), lastEntry = this.history.get().data, callback = function (a, b) {
                            return a == b;
                        };
                    if (!deepEquals(lastEntry[0], serial[0], callback))
                        this.history.push(serial);
                },
                stopAnimation: function (element) {
                    singles.disable();
                    if (!this.block)
                        this.block = get(this.builder.map, element.data('lm-id'));
                    if (this.block && this.block.getType() == 'block')
                        this.block.setSize();
                }
            });
        module.exports = DropManager;
    },
    '8': function (require, module, exports, global) {
        var prime = require('g'), Emitter = require('h'), slice = require('14'), merge = require('15');
        var History = new prime({
                inherits: Emitter,
                constructor: function (session) {
                    this.index = 0;
                    session = merge({}, session);
                    this.setSession(session);
                },
                undo: function () {
                    if (!this.index)
                        return;
                    this.index--;
                    var session = this.get();
                    this.emit('undo', session, this.index);
                    return session;
                },
                redo: function () {
                    if (this.index == this.session.length - 1)
                        return;
                    this.index++;
                    var session = this.get();
                    this.emit('redo', session, this.index);
                    return session;
                },
                reset: function () {
                    this.index = 0;
                    var session = this.get();
                    this.emit('reset', session, this.index);
                    return session;
                },
                push: function (session) {
                    session = merge({}, session);
                    var sliced = this.index < this.session.length - 1;
                    if (this.index < this.session.length - 1)
                        this.session = slice(this.session, 0, -(this.session.length - 1 - this.index));
                    session = {
                        time: +new Date(),
                        data: session
                    };
                    this.session.push(session);
                    this.index = this.session.length - 1;
                    this.emit('push', session, this.index, sliced);
                    return session;
                },
                get: function (index) {
                    return this.session[index || this.index] || false;
                },
                setSession: function (session) {
                    session = !session ? [] : [{
                            time: +new Date(),
                            data: merge({}, session)
                        }];
                    this.session = session;
                    this.index = 0;
                    return this.session;
                },
                import: function () {
                },
                export: function () {
                }
            });
        module.exports = History;
    },
    '9': function (require, module, exports, global) {
        'use strict';
        var $ = require('b');
        require('5');
        require('a');
        require('16');
        require('r');
        require('17');
        module.exports = $;
    },
    'a': function (require, module, exports, global) {
        'use strict';
        var Emitter = require('h');
        var $ = require('b');
        var html = document.documentElement;
        var addEventListener = html.addEventListener ? function (node, event, handle, useCapture) {
                node.addEventListener(event, handle, useCapture || false);
                return handle;
            } : function (node, event, handle) {
                node.attachEvent('on' + event, handle);
                return handle;
            };
        var removeEventListener = html.removeEventListener ? function (node, event, handle, useCapture) {
                node.removeEventListener(event, handle, useCapture || false);
            } : function (node, event, handle) {
                node.detachEvent('on' + event, handle);
            };
        $.implement({
            on: function (event, handle, useCapture) {
                return this.forEach(function (node) {
                    var self = $(node);
                    var internalEvent = event + (useCapture ? ':capture' : '');
                    Emitter.prototype.on.call(self, internalEvent, handle);
                    var domListeners = self._domListeners || (self._domListeners = {});
                    if (!domListeners[internalEvent])
                        domListeners[internalEvent] = addEventListener(node, event, function (e) {
                            Emitter.prototype.emit.call(self, internalEvent, e || window.event, Emitter.EMIT_SYNC);
                        }, useCapture);
                });
            },
            off: function (event, handle, useCapture) {
                return this.forEach(function (node) {
                    var self = $(node);
                    var internalEvent = event + (useCapture ? ':capture' : '');
                    var domListeners = self._domListeners, domEvent, listeners = self._listeners, events;
                    if (domListeners && (domEvent = domListeners[internalEvent]) && listeners && (events = listeners[internalEvent])) {
                        Emitter.prototype.off.call(self, internalEvent, handle);
                        if (!self._listeners || !self._listeners[event]) {
                            removeEventListener(node, event, domEvent);
                            delete domListeners[event];
                            for (var l in domListeners)
                                return;
                            delete self._domListeners;
                        }
                    }
                });
            },
            emit: function () {
                var args = arguments;
                return this.forEach(function (node) {
                    Emitter.prototype.emit.apply($(node), args);
                });
            }
        });
        module.exports = $;
    },
    'b': function (require, module, exports, global) {
        'use strict';
        var prime = require('g');
        var forEach = require('d'), map = require('1c'), filter = require('e'), every = require('11'), some = require('1d');
        var index = 0, __dc = document.__counter, counter = document.__counter = (__dc ? parseInt(__dc, 36) + 1 : 0).toString(36), key = 'uid:' + counter;
        var uniqueID = function (n) {
            if (n === global)
                return 'global';
            if (n === document)
                return 'document';
            if (n === document.documentElement)
                return 'html';
            return n[key] || (n[key] = (index++).toString(36));
        };
        var instances = {};
        var $ = prime({
                constructor: function $(n, context) {
                    if (n == null)
                        return this && this.constructor === $ ? new Elements() : null;
                    var self, uid;
                    if (n.constructor !== Elements) {
                        self = new Elements();
                        if (typeof n === 'string') {
                            if (!self.search)
                                return null;
                            self[self.length++] = context || document;
                            return self.search(n);
                        }
                        if (n.nodeType || n === global) {
                            self[self.length++] = n;
                        } else if (n.length) {
                            var uniques = {};
                            for (var i = 0, l = n.length; i < l; i++) {
                                var nodes = $(n[i], context);
                                if (nodes && nodes.length)
                                    for (var j = 0, k = nodes.length; j < k; j++) {
                                        var node = nodes[j];
                                        uid = uniqueID(node);
                                        if (!uniques[uid]) {
                                            self[self.length++] = node;
                                            uniques[uid] = true;
                                        }
                                    }
                            }
                        }
                    } else {
                        self = n;
                    }
                    if (!self.length)
                        return null;
                    if (self.length === 1) {
                        uid = uniqueID(self[0]);
                        return instances[uid] || (instances[uid] = self);
                    }
                    return self;
                }
            });
        var Elements = prime({
                inherits: $,
                constructor: function Elements() {
                    this.length = 0;
                },
                unlink: function () {
                    return this.map(function (node) {
                        delete instances[uniqueID(node)];
                        return node;
                    });
                },
                forEach: function (method, context) {
                    forEach(this, method, context);
                    return this;
                },
                map: function (method, context) {
                    return map(this, method, context);
                },
                filter: function (method, context) {
                    return filter(this, method, context);
                },
                every: function (method, context) {
                    return every(this, method, context);
                },
                some: function (method, context) {
                    return some(this, method, context);
                }
            });
        module.exports = $;
    },
    'c': function (require, module, exports, global) {
        var toString = require('18');
        var WHITE_SPACES = require('19');
        var ltrim = require('1a');
        var rtrim = require('1b');
        function trim(str, chars) {
            str = toString(str);
            chars = chars || WHITE_SPACES;
            return ltrim(rtrim(str, chars), chars);
        }
        module.exports = trim;
    },
    'd': function (require, module, exports, global) {
        function forEach(arr, callback, thisObj) {
            if (arr == null) {
                return;
            }
            var i = -1, len = arr.length;
            while (++i < len) {
                if (callback.call(thisObj, arr[i], i, arr) === false) {
                    break;
                }
            }
        }
        module.exports = forEach;
    },
    'e': function (require, module, exports, global) {
        var makeIterator = require('1u');
        function filter(arr, callback, thisObj) {
            callback = makeIterator(callback, thisObj);
            var results = [];
            if (arr == null) {
                return results;
            }
            var i = -1, len = arr.length, value;
            while (++i < len) {
                value = arr[i];
                if (callback(value, i, arr)) {
                    results.push(value);
                }
            }
            return results;
        }
        module.exports = filter;
    },
    'f': function (require, module, exports, global) {
        function indexOf(arr, item, fromIndex) {
            fromIndex = fromIndex || 0;
            if (arr == null) {
                return -1;
            }
            var len = arr.length, i = fromIndex < 0 ? len + fromIndex : fromIndex;
            while (i < len) {
                if (arr[i] === item) {
                    return i;
                }
                i++;
            }
            return -1;
        }
        module.exports = indexOf;
    },
    'g': function (require, module, exports, global) {
        'use strict';
        var hasOwn = require('1e'), mixIn = require('1f'), create = require('1g'), kindOf = require('1h');
        var hasDescriptors = true;
        try {
            Object.defineProperty({}, '~', {});
            Object.getOwnPropertyDescriptor({}, '~');
        } catch (e) {
            hasDescriptors = false;
        }
        var hasEnumBug = !{ valueOf: 0 }.propertyIsEnumerable('valueOf'), buggy = [
                'toString',
                'valueOf'
            ];
        var verbs = /^constructor|inherits|mixin$/;
        var implement = function (proto) {
            var prototype = this.prototype;
            for (var key in proto) {
                if (key.match(verbs))
                    continue;
                if (hasDescriptors) {
                    var descriptor = Object.getOwnPropertyDescriptor(proto, key);
                    if (descriptor) {
                        Object.defineProperty(prototype, key, descriptor);
                        continue;
                    }
                }
                prototype[key] = proto[key];
            }
            if (hasEnumBug)
                for (var i = 0; key = buggy[i]; i++) {
                    var value = proto[key];
                    if (value !== Object.prototype[key])
                        prototype[key] = value;
                }
            return this;
        };
        var prime = function (proto) {
            if (kindOf(proto) === 'Function')
                proto = { constructor: proto };
            var superprime = proto.inherits;
            var constructor = hasOwn(proto, 'constructor') ? proto.constructor : superprime ? function () {
                    return superprime.apply(this, arguments);
                } : function () {
                };
            if (superprime) {
                mixIn(constructor, superprime);
                var superproto = superprime.prototype;
                var cproto = constructor.prototype = create(superproto);
                constructor.parent = superproto;
                cproto.constructor = constructor;
            }
            if (!constructor.implement)
                constructor.implement = implement;
            var mixins = proto.mixin;
            if (mixins) {
                if (kindOf(mixins) !== 'Array')
                    mixins = [mixins];
                for (var i = 0; i < mixins.length; i++)
                    constructor.implement(create(mixins[i].prototype));
            }
            return constructor.implement(proto);
        };
        module.exports = prime;
    },
    'h': function (require, module, exports, global) {
        'use strict';
        var indexOf = require('f'), forEach = require('d');
        var prime = require('g'), defer = require('1i');
        var slice = Array.prototype.slice;
        var Emitter = prime({
                on: function (event, fn) {
                    var listeners = this._listeners || (this._listeners = {}), events = listeners[event] || (listeners[event] = []);
                    if (indexOf(events, fn) === -1)
                        events.push(fn);
                    return this;
                },
                off: function (event, fn) {
                    var listeners = this._listeners, events, key, length = 0;
                    if (listeners && (events = listeners[event])) {
                        var io = indexOf(events, fn);
                        if (io > -1)
                            events.splice(io, 1);
                        if (!events.length)
                            delete listeners[event];
                        for (var l in listeners)
                            return this;
                        delete this._listeners;
                    }
                    return this;
                },
                emit: function (event) {
                    var self = this, args = slice.call(arguments, 1);
                    var emit = function () {
                        var listeners = self._listeners, events;
                        if (listeners && (events = listeners[event])) {
                            forEach(events.slice(0), function (event) {
                                return event.apply(self, args);
                            });
                        }
                    };
                    if (args[args.length - 1] === Emitter.EMIT_SYNC) {
                        args.pop();
                        emit();
                    } else {
                        defer(emit);
                    }
                    return this;
                }
            });
        Emitter.EMIT_SYNC = {};
        module.exports = Emitter;
    },
    'i': function (require, module, exports, global) {
        module.exports = {
            base: require('1j'),
            section: require('1k'),
            grid: require('1l'),
            block: require('1m'),
            position: require('1n'),
            mainbody: require('1o'),
            spacer: require('1p')
        };
    },
    'j': function (require, module, exports, global) {
        var hasOwn = require('1e');
        var forIn = require('1q');
        function forOwn(obj, fn, thisObj) {
            forIn(obj, function (val, key) {
                if (hasOwn(obj, key)) {
                    return fn.call(thisObj, obj[key], key, obj);
                }
            });
        }
        module.exports = forOwn;
    },
    'k': function (require, module, exports, global) {
        var make = require('1r');
        var arrForEach = require('d');
        var objForEach = require('j');
        module.exports = make(arrForEach, objForEach);
    },
    'l': function (require, module, exports, global) {
        var isArray = require('m');
        var objSize = require('1s');
        function size(list) {
            if (!list) {
                return 0;
            }
            if (isArray(list)) {
                return list.length;
            }
            return objSize(list);
        }
        module.exports = size;
    },
    'm': function (require, module, exports, global) {
        var isKind = require('1t');
        var isArray = Array.isArray || function (val) {
                return isKind(val, 'Array');
            };
        module.exports = isArray;
    },
    'n': function (require, module, exports, global) {
        var isArray = require('m');
        var append = require('1v');
        function flattenTo(arr, result, level) {
            if (arr == null) {
                return result;
            } else if (level === 0) {
                append(result, arr);
                return result;
            }
            var value, i = -1, len = arr.length;
            while (++i < len) {
                value = arr[i];
                if (isArray(value)) {
                    flattenTo(value, result, level - 1);
                } else {
                    result.push(value);
                }
            }
            return result;
        }
        function flatten(arr, level) {
            level = level == null ? -1 : level;
            return flattenTo(arr, [], level);
        }
        module.exports = flatten;
    },
    'o': function (require, module, exports, global) {
        var namespace = require('1w');
        function set(obj, prop, val) {
            var parts = /^(.+)\.(.+)$/.exec(prop);
            if (parts) {
                namespace(obj, parts[1])[parts[2]] = val;
            } else {
                obj[prop] = val;
            }
        }
        module.exports = set;
    },
    'p': function (require, module, exports, global) {
        var has = require('1x');
        function unset(obj, prop) {
            if (has(obj, prop)) {
                var parts = prop.split('.'), last = parts.pop();
                while (prop = parts.shift()) {
                    obj = obj[prop];
                }
                return delete obj[last];
            } else {
                return true;
            }
        }
        module.exports = unset;
    },
    'q': function (require, module, exports, global) {
        function get(obj, prop) {
            var parts = prop.split('.'), last = parts.pop();
            while (prop = parts.shift()) {
                obj = obj[prop];
                if (typeof obj !== 'object' || !obj)
                    return;
            }
            return obj[last];
        }
        module.exports = get;
    },
    'r': function (require, module, exports, global) {
        'use strict';
        var map = require('1c');
        var slick = require('1y');
        var $ = require('b');
        var walk = function (combinator, method) {
            return function (expression) {
                var parts = slick.parse(expression || '*');
                expression = map(parts, function (part) {
                    return combinator + ' ' + part;
                }).join(', ');
                return this[method](expression);
            };
        };
        $.implement({
            search: function (expression) {
                if (this.length === 1)
                    return $(slick.search(expression, this[0], new $()));
                var buffer = [];
                for (var i = 0, node; node = this[i]; i++)
                    buffer.push.apply(buffer, slick.search(expression, node));
                return $(buffer).sort();
            },
            find: function (expression) {
                if (this.length === 1)
                    return $(slick.find(expression, this[0]));
                var buffer = [];
                for (var i = 0, node; node = this[i]; i++)
                    buffer.push(slick.find(expression, node));
                return $(buffer);
            },
            sort: function () {
                return slick.sort(this);
            },
            matches: function (expression) {
                return slick.matches(this[0], expression);
            },
            contains: function (node) {
                return slick.contains(this[0], node);
            },
            nextSiblings: walk('~', 'search'),
            nextSibling: walk('+', 'find'),
            previousSiblings: walk('!~', 'search'),
            previousSibling: walk('!+', 'find'),
            children: walk('>', 'search'),
            firstChild: walk('^', 'find'),
            lastChild: walk('!^', 'find'),
            parent: walk('!', 'find'),
            parents: walk('!', 'search')
        });
        module.exports = $;
    },
    's': function (require, module, exports, global) {
        var toString = require('18');
        var repeat = require('t');
        function rpad(str, minLen, ch) {
            str = toString(str);
            ch = ch || ' ';
            return str.length < minLen ? str + repeat(ch, minLen - str.length) : str;
        }
        module.exports = rpad;
    },
    't': function (require, module, exports, global) {
        var toString = require('18');
        var toInt = require('1z');
        function repeat(str, n) {
            var result = '';
            str = toString(str);
            n = toInt(n);
            if (n < 1) {
                return '';
            }
            while (n > 0) {
                if (n % 2) {
                    result += str;
                }
                n = Math.floor(n / 2);
                str += str;
            }
            return result;
        }
        module.exports = repeat;
    },
    'u': function (require, module, exports, global) {
        var $ = require('9'), moofx = require('20'), map = require('1c'), slick = require('1y');
        var walk = function (combinator, method) {
            return function (expression) {
                var parts = slick.parse(expression || '*');
                expression = map(parts, function (part) {
                    return combinator + ' ' + part;
                }).join(', ');
                return this[method](expression);
            };
        };
        $.implement({
            style: function () {
                var moo = moofx(this);
                moo.style.apply(moo, arguments);
                return this;
            },
            animate: function () {
                var moo = moofx(this);
                moo.animate.apply(moo, arguments);
                return this;
            },
            compute: function () {
                var moo = moofx(this);
                return moo.compute.apply(moo, arguments);
            },
            sibling: walk('++', 'find'),
            siblings: walk('~~', 'search')
        });
        module.exports = $;
    },
    'v': function (require, module, exports, global) {
        'use strict';
        var forEach = require('d'), map = require('1c');
        var parse = require('21');
        var $ = require('b');
        module.exports = function (expression, doc) {
            return $(map(parse(expression), function (expression) {
                var previous, result;
                forEach(expression, function (part, i) {
                    var node = (doc || document).createElement(part.tag);
                    if (part.id)
                        node.id = part.id;
                    if (part.classList)
                        node.className = part.classList.join(' ');
                    if (part.attributes)
                        forEach(part.attributes, function (attribute) {
                            node.setAttribute(attribute.name, attribute.value);
                        });
                    if (part.pseudos)
                        forEach(part.pseudos, function (pseudo) {
                            var n = $(node), method = n[pseudo.name];
                            if (method)
                                method.call(n, pseudo.value);
                        });
                    if (i === 0) {
                        result = node;
                    } else if (part.combinator === ' ') {
                        previous.appendChild(node);
                    } else if (part.combinator === '+') {
                        var parentNode = previous.parentNode;
                        if (parentNode)
                            parentNode.appendChild(node);
                    }
                    previous = node;
                });
                return result;
            }));
        };
    },
    'w': function (require, module, exports, global) {
        'use strict';
        var prime = require('g');
        var bind = require('22');
        var bound = prime({
                bound: function (name) {
                    var bound = this._bound || (this._bound = {});
                    return bound[name] || (bound[name] = bind(this[name], this));
                }
            });
        module.exports = bound;
    },
    'x': function (require, module, exports, global) {
        'use strict';
        var prime = require('g');
        var merge = require('15');
        var Options = prime({
                setOptions: function (options) {
                    var args = [
                            {},
                            this.options
                        ];
                    args.push.apply(args, arguments);
                    this.options = merge.apply(null, args);
                    return this;
                }
            });
        module.exports = Options;
    },
    'y': function (require, module, exports, global) {
        var prime = require('g'), Emitter = require('h'), Bound = require('w'), Options = require('x'), bind = require('22'), contains = require('23'), DragEvents = require('24'), $ = require('u');
        require('a');
        require('17');
        var isIE = navigator.appName == 'Microsoft Internet Explorer';
        var DragDrop = new prime({
                mixin: [
                    Bound,
                    Options
                ],
                inherits: Emitter,
                options: {
                    delegate: null,
                    droppables: false
                },
                EVENTS: DragEvents,
                constructor: function (container, options) {
                    this.container = $(container);
                    if (!this.container)
                        return;
                    this.setOptions(options);
                    this.element = null;
                    this.origin = {
                        x: 0,
                        y: 0,
                        transform: null,
                        offset: {
                            x: 0,
                            y: 0
                        }
                    };
                    this.matched = false;
                    this.lastMatched = false;
                    this.lastOvered = null;
                    this.attach();
                },
                attach: function () {
                    this.container.delegate(this.EVENTS.START, this.options.delegate, this.bound('start'));
                },
                detach: function () {
                    this.container.undelegate(this.EVENTS.START, this.options.delegate, this.bound('start'));
                },
                start: function (event, element) {
                    if (event.which && event.which != 1 || $(event.target).matches(this.options.exclude))
                        return true;
                    this.element = $(element);
                    this.matched = false;
                    this.emit('dragdrop:beforestart', event, this.element);
                    if (isIE)
                        this.element.style({
                            '-ms-touch-action': 'none',
                            'touch-action': 'none'
                        });
                    event.preventDefault();
                    this.origin = {
                        x: event.changedTouches ? event.changedTouches[0].pageX : event.pageX,
                        y: event.changedTouches ? event.changedTouches[0].pageY : event.pageY,
                        transform: this.element.compute('transform')
                    };
                    var clientRect = this.element[0].getBoundingClientRect();
                    this.origin.offset = {
                        clientRect: clientRect,
                        x: this.origin.x - clientRect.right,
                        y: clientRect.top - this.origin.y
                    };
                    if (this.origin.offset.x > 0) {
                        this.emit('dragdrop:resize', event, this.element, this.element.siblings());
                        return false;
                    }
                    this.element.style({
                        'pointer-events': 'none',
                        opacity: 0.5,
                        zIndex: 100
                    });
                    $(document).on(this.EVENTS.MOVE, this.bound('move'));
                    $(document).on(this.EVENTS.STOP, this.bound('stop'));
                    this.emit('dragdrop:start', event, this.element);
                    return this.element;
                },
                stop: function (event) {
                    var settings = { duration: '250ms' };
                    if (this.removeElement)
                        return this.emit('dragdrop:stop:erase', event, this.element);
                    if (this.element) {
                        this.emit('dragdrop:stop', event, this.matched, this.element);
                        this.element.style({
                            position: 'relative',
                            width: 'auto',
                            height: 'auto'
                        });
                        if (this.matched)
                            this.element.style({
                                opacity: 0,
                                transform: 'translate(0, 0)'
                            });
                        settings.callback = bind(function (element) {
                            this._removeStyleAttribute(element);
                            this.emit('dragdrop:stop:animation', element);
                        }, this, this.element);
                        this.element.animate({
                            transform: this.origin.transform || 'translate(0, 0)',
                            opacity: 1
                        }, settings);
                    }
                    $(document).off(this.EVENTS.MOVE, this.bound('move'));
                    $(document).off(this.EVENTS.STOP, this.bound('stop'));
                    this.element = null;
                },
                move: function (event) {
                    var clientX = event.clientX || event.touches && event.touches[0].clientX || 0, clientY = event.clientY || event.touches && event.touches[0].clientY || 0, overing = document.elementFromPoint(clientX, clientY);
                    if (!overing)
                        return false;
                    this.matched = $(overing).matches(this.options.droppables) ? overing : ($(overing).parent(this.options.droppables) || [false])[0];
                    this.isPlaceHolder = $(overing).matches('[data-lm-placeholder]') ? true : $(overing).parent('[data-lm-placeholder]') ? true : false;
                    var deltaX = this.lastX - clientX, deltaY = this.lastY - clientY, direction = Math.abs(deltaX) > Math.abs(deltaY) && deltaX > 0 && 'left' || Math.abs(deltaX) > Math.abs(deltaY) && deltaX < 0 && 'right' || Math.abs(deltaY) > Math.abs(deltaX) && deltaY > 0 && 'up' || 'down';
                    deltaX = (event.changedTouches ? event.changedTouches[0].pageX : event.pageX) - this.origin.x;
                    deltaY = (event.changedTouches ? event.changedTouches[0].pageY : event.pageY) - this.origin.y;
                    this.direction = direction;
                    this.element.style({ transform: 'translate(' + deltaX + 'px, ' + deltaY + 'px)' });
                    if (!this.isPlaceHolder) {
                        if (this.lastMatched && this.matched !== this.lastMatched) {
                            this.emit('dragdrop:leave', event, this.lastMatched, this.element);
                            this.lastMatched = false;
                        }
                        if (this.matched && this.matched !== this.lastMatched && overing !== this.lastOvered) {
                            this.emit('dragdrop:enter', event, this.matched, this.element);
                            this.lastMatched = this.matched;
                        }
                        if (this.matched && this.lastMatched) {
                            var rect = this.matched.getBoundingClientRect();
                            var x = clientX - rect.left, y = clientY - rect.top;
                            var location = {
                                    x: Math.abs(clientX - rect.left) < rect.width / 3 && 'before' || Math.abs(clientX - rect.left) >= rect.width - rect.width / 3 && 'after' || 'other',
                                    y: Math.abs(clientY - rect.top) < rect.height / 2 && 'above' || Math.abs(clientY - rect.top) >= rect.height / 2 && 'below' || 'other'
                                };
                            this.emit('dragdrop:location', event, location, this.matched, this.element);
                            this.lastLocation = location;
                        } else {
                            this.emit('dragdrop:nolocation', event);
                        }
                    }
                    this.lastOvered = overing;
                    this.lastDirection = direction;
                    this.lastX = clientX;
                    this.lastY = clientY;
                    this.emit('dragdrop:move', event, this.element);
                },
                _removeStyleAttribute: function (element) {
                    $(element || this.element).attribute('style', null);
                }
            });
        module.exports = DragDrop;
    },
    'z': function (require, module, exports, global) {
        var DragEvents = require('24'), prime = require('g'), Emitter = require('h'), Bound = require('w'), Options = require('x'), bind = require('22'), isString = require('25'), nMap = require('26'), clamp = require('27'), precision = require('28'), get = require('q'), $ = require('u');
        require('a');
        require('17');
        var Resizer = new prime({
                mixin: [
                    Bound,
                    Options
                ],
                EVENTS: DragEvents,
                options: {},
                constructor: function (container, options) {
                    this.setOptions(options);
                    this.history = this.options.history;
                    this.builder = this.options.builder;
                    this.map = this.builder.map;
                    this.origin = {
                        x: 0,
                        y: 0,
                        transform: null,
                        offset: {
                            x: 0,
                            y: 0
                        }
                    };
                },
                getBlock: function (element) {
                    return get(this.map, isString(element) ? element : $(element).data('lm-id') || '');
                },
                getAttribute: function (element, prop) {
                    return this.getBlock(element).getAttribute(prop);
                },
                getSize: function (element) {
                    return this.getAttribute($(element), 'size');
                },
                start: function (event, element, siblings) {
                    this.map = this.builder.map;
                    if (event.which && event.which != 1)
                        return true;
                    event.preventDefault();
                    this.element = $(element);
                    this.siblings = {
                        occupied: 0,
                        elements: this.element.siblings(),
                        next: this.element.nextSibling(),
                        prevs: this.element.previousSiblings(),
                        sizeBefore: 0
                    };
                    if (this.siblings.elements.length > 1) {
                        this.siblings.occupied -= this.getSize(this.siblings.next);
                        this.siblings.elements.forEach(function (sibling) {
                            this.siblings.occupied += this.getSize(sibling);
                        }, this);
                    }
                    if (this.siblings.prevs)
                        this.siblings.prevs.forEach(function (sibling) {
                            this.siblings.sizeBefore += this.getSize(sibling);
                        }, this);
                    this.origin = {
                        size: this.getSize(this.element),
                        x: event.changedTouches ? event.changedTouches[0].pageX : event.pageX,
                        y: event.changedTouches ? event.changedTouches[0].pageY : event.pageY
                    };
                    var clientRect = this.element[0].getBoundingClientRect(), parentRect = this.element.parent()[0].getBoundingClientRect();
                    this.origin.offset = {
                        clientRect: clientRect,
                        parentRect: parentRect,
                        x: this.origin.x - clientRect.right,
                        y: clientRect.top - this.origin.y
                    };
                    $(document).on(this.EVENTS.MOVE, this.bound('move'));
                    $(document).on(this.EVENTS.STOP, this.bound('stop'));
                },
                move: function (event) {
                    var clientX = event.clientX || event.touches[0].clientX || 0, clientY = event.clientY || event.touches[0].clientY || 0, clientRect = this.origin.offset.clientRect, parentRect = this.origin.offset.parentRect;
                    var deltaX = this.lastX - clientX, deltaY = this.lastY - clientY, direction = Math.abs(deltaX) > Math.abs(deltaY) && deltaX > 0 && 'left' || Math.abs(deltaX) > Math.abs(deltaY) && deltaX < 0 && 'right' || Math.abs(deltaY) > Math.abs(deltaX) && deltaY > 0 && 'up' || 'down';
                    deltaX = (event.changedTouches ? event.changedTouches[0].pageX : event.pageX) - this.origin.x;
                    deltaY = (event.changedTouches ? event.changedTouches[0].pageY : event.pageY) - this.origin.y;
                    this.direction = direction;
                    if (clientX >= parentRect.left && clientX <= parentRect.right) {
                        var size = this.getSize(this.element) - deltaX, diff = 100 - this.siblings.occupied;
                        size = nMap(clientX - 8 + (!this.siblings.prevs ? this.origin.offset.x : this.siblings.prevs.length * 8), parentRect.left, parentRect.right, 0, 100);
                        size = size - this.siblings.sizeBefore;
                        size = precision(clamp(size, 0, 100), 4);
                        diff = precision(diff - size, 4);
                        this.getBlock(this.element).setSize(size, true);
                        this.getBlock(this.siblings.next).setSize(diff, true);
                    }
                    this.lastDirection = direction;
                    this.lastX = clientX;
                    this.lastY = clientY;
                },
                stop: function (event) {
                    $(document).off(this.EVENTS.MOVE, this.bound('move'));
                    $(document).off(this.EVENTS.STOP, this.bound('stop'));
                    if (this.origin.size !== this.getSize(this.element))
                        this.history.push(this.builder.serialize());
                },
                evenResize: function (elements, animated) {
                    var total = elements.length, size = precision(100 / total, 4), block;
                    if (typeof animated == 'undefined')
                        animated = true;
                    elements.forEach(function (element) {
                        element = $(element);
                        block = this.getBlock(element);
                        if (block && block.hasAttribute('size')) {
                            block[animated ? 'setAnimatedSize' : 'setSize'](size, size != block.getSize() ? true : false);
                        } else {
                            if (element)
                                element[animated ? 'animate' : 'style']({ flex: '0 1 ' + size + '%' });
                        }
                    }, this);
                }
            });
        module.exports = Resizer;
    },
    '10': function (require, module, exports, global) {
        var prime = require('g'), $ = require('u'), Emitter = require('h'), Bound = require('w'), Options = require('x');
        var Eraser = new prime({
                mixin: [
                    Options,
                    Bound
                ],
                inherits: Emitter,
                constructor: function (element, options) {
                    this.setOptions(options);
                    this.element = $(element);
                    if (!this.element)
                        return;
                    this.hide(true);
                },
                show: function (fast) {
                    this.out();
                    this.element[fast ? 'style' : 'animate']({ top: 0 }, { duration: '150ms' });
                },
                hide: function (fast) {
                    var top = { top: -this.element[0].offsetHeight };
                    this.out();
                    this.element[fast ? 'style' : 'animate'](top, { duration: '150ms' });
                },
                over: function () {
                    this.element.find('.trash-zone').animate({ transform: 'scale(1.2)' }, {
                        duration: '150ms',
                        equation: 'cubic-bezier(0.5,0,0.5,1)'
                    });
                },
                out: function () {
                    this.element.find('.trash-zone').animate({ transform: 'scale(1)' }, {
                        duration: '150ms',
                        equation: 'cubic-bezier(0.5,0,0.5,1)'
                    });
                }
            });
        module.exports = Eraser;
    },
    '11': function (require, module, exports, global) {
        var makeIterator = require('1u');
        function every(arr, callback, thisObj) {
            callback = makeIterator(callback, thisObj);
            var result = true;
            if (arr == null) {
                return result;
            }
            var i = -1, len = arr.length;
            while (++i < len) {
                if (!callback(arr[i], i, arr)) {
                    result = false;
                    break;
                }
            }
            return result;
        }
        module.exports = every;
    },
    '12': function (require, module, exports, global) {
        var isKind = require('1t');
        function isObject(val) {
            return isKind(val, 'Object');
        }
        module.exports = isObject;
    },
    '13': function (require, module, exports, global) {
        var hasOwn = require('1e');
        var every = require('29');
        var isObject = require('12');
        function defaultCompare(a, b) {
            return a === b;
        }
        function makeCompare(callback) {
            return function (value, key) {
                return hasOwn(this, key) && callback(value, this[key]);
            };
        }
        function checkProperties(value, key) {
            return hasOwn(this, key);
        }
        function equals(a, b, callback) {
            callback = callback || defaultCompare;
            if (!isObject(a) || !isObject(b)) {
                return callback(a, b);
            }
            return every(a, makeCompare(callback), b) && every(b, checkProperties, a);
        }
        module.exports = equals;
    },
    '14': function (require, module, exports, global) {
        function slice(arr, start, end) {
            var len = arr.length;
            if (start == null) {
                start = 0;
            } else if (start < 0) {
                start = Math.max(len + start, 0);
            } else {
                start = Math.min(start, len);
            }
            if (end == null) {
                end = len;
            } else if (end < 0) {
                end = Math.max(len + end, 0);
            } else {
                end = Math.min(end, len);
            }
            var result = [];
            while (start < end) {
                result.push(arr[start++]);
            }
            return result;
        }
        module.exports = slice;
    },
    '15': function (require, module, exports, global) {
        var hasOwn = require('1e');
        var deepClone = require('2a');
        var isObject = require('12');
        function merge() {
            var i = 1, key, val, obj, target;
            target = deepClone(arguments[0]);
            while (obj = arguments[i++]) {
                for (key in obj) {
                    if (!hasOwn(obj, key)) {
                        continue;
                    }
                    val = obj[key];
                    if (isObject(val) && isObject(target[key])) {
                        target[key] = merge(target[key], val);
                    } else {
                        target[key] = deepClone(val);
                    }
                }
            }
            return target;
        }
        module.exports = merge;
    },
    '16': function (require, module, exports, global) {
        'use strict';
        var $ = require('b');
        $.implement({
            appendChild: function (child) {
                this[0].appendChild($(child)[0]);
                return this;
            },
            insertBefore: function (child, ref) {
                this[0].insertBefore($(child)[0], $(ref)[0]);
                return this;
            },
            removeChild: function (child) {
                this[0].removeChild($(child)[0]);
                return this;
            },
            replaceChild: function (child, ref) {
                this[0].replaceChild($(child)[0], $(ref)[0]);
                return this;
            }
        });
        $.implement({
            before: function (element) {
                element = $(element)[0];
                var parent = element.parentNode;
                if (parent)
                    this.forEach(function (node) {
                        parent.insertBefore(node, element);
                    });
                return this;
            },
            after: function (element) {
                element = $(element)[0];
                var parent = element.parentNode;
                if (parent)
                    this.forEach(function (node) {
                        parent.insertBefore(node, element.nextSibling);
                    });
                return this;
            },
            bottom: function (element) {
                element = $(element)[0];
                return this.forEach(function (node) {
                    element.appendChild(node);
                });
            },
            top: function (element) {
                element = $(element)[0];
                return this.forEach(function (node) {
                    element.insertBefore(node, element.firstChild);
                });
            }
        });
        $.implement({
            insert: $.prototype.bottom,
            remove: function () {
                return this.forEach(function (node) {
                    var parent = node.parentNode;
                    if (parent)
                        parent.removeChild(node);
                });
            },
            replace: function (element) {
                element = $(element)[0];
                element.parentNode.replaceChild(this[0], element);
                return this;
            }
        });
        module.exports = $;
    },
    '17': function (require, module, exports, global) {
        'use strict';
        var Map = require('2b');
        var $ = require('a');
        require('r');
        $.implement({
            delegate: function (event, selector, handle) {
                return this.forEach(function (node) {
                    var self = $(node);
                    var delegation = self._delegation || (self._delegation = {}), events = delegation[event] || (delegation[event] = {}), map = events[selector] || (events[selector] = new Map());
                    if (map.get(handle))
                        return;
                    var action = function (e) {
                        var target = $(e.target), match = target.matches(selector) ? target : target.parent(selector);
                        var res;
                        if (match)
                            res = handle.call(self, e, match);
                        return res;
                    };
                    map.set(handle, action);
                    self.on(event, action);
                });
            },
            undelegate: function (event, selector, handle) {
                return this.forEach(function (node) {
                    var self = $(node), delegation, events, map;
                    if (!(delegation = self._delegation) || !(events = delegation[event]) || !(map = events[selector]))
                        return;
                    var action = map.get(handle);
                    if (action) {
                        self.off(event, action);
                        map.remove(handle);
                        if (!map.count())
                            delete events[selector];
                        var e1 = true, e2 = true, x;
                        for (x in events) {
                            e1 = false;
                            break;
                        }
                        if (e1)
                            delete delegation[event];
                        for (x in delegation) {
                            e2 = false;
                            break;
                        }
                        if (e2)
                            delete self._delegation;
                    }
                });
            }
        });
        module.exports = $;
    },
    '18': function (require, module, exports, global) {
        function toString(val) {
            return val == null ? '' : val.toString();
        }
        module.exports = toString;
    },
    '19': function (require, module, exports, global) {
        module.exports = [
            ' ',
            '\n',
            '\r',
            '\t',
            '\f',
            '\x0B',
            '\xa0',
            '\u1680',
            '\u180e',
            '\u2000',
            '\u2001',
            '\u2002',
            '\u2003',
            '\u2004',
            '\u2005',
            '\u2006',
            '\u2007',
            '\u2008',
            '\u2009',
            '\u200a',
            '\u2028',
            '\u2029',
            '\u202f',
            '\u205f',
            '\u3000'
        ];
    },
    '1a': function (require, module, exports, global) {
        var toString = require('18');
        var WHITE_SPACES = require('19');
        function ltrim(str, chars) {
            str = toString(str);
            chars = chars || WHITE_SPACES;
            var start = 0, len = str.length, charLen = chars.length, found = true, i, c;
            while (found && start < len) {
                found = false;
                i = -1;
                c = str.charAt(start);
                while (++i < charLen) {
                    if (c === chars[i]) {
                        found = true;
                        start++;
                        break;
                    }
                }
            }
            return start >= len ? '' : str.substr(start, len);
        }
        module.exports = ltrim;
    },
    '1b': function (require, module, exports, global) {
        var toString = require('18');
        var WHITE_SPACES = require('19');
        function rtrim(str, chars) {
            str = toString(str);
            chars = chars || WHITE_SPACES;
            var end = str.length - 1, charLen = chars.length, found = true, i, c;
            while (found && end >= 0) {
                found = false;
                i = -1;
                c = str.charAt(end);
                while (++i < charLen) {
                    if (c === chars[i]) {
                        found = true;
                        end--;
                        break;
                    }
                }
            }
            return end >= 0 ? str.substring(0, end + 1) : '';
        }
        module.exports = rtrim;
    },
    '1c': function (require, module, exports, global) {
        var makeIterator = require('1u');
        function map(arr, callback, thisObj) {
            callback = makeIterator(callback, thisObj);
            var results = [];
            if (arr == null) {
                return results;
            }
            var i = -1, len = arr.length;
            while (++i < len) {
                results[i] = callback(arr[i], i, arr);
            }
            return results;
        }
        module.exports = map;
    },
    '1d': function (require, module, exports, global) {
        var makeIterator = require('1u');
        function some(arr, callback, thisObj) {
            callback = makeIterator(callback, thisObj);
            var result = false;
            if (arr == null) {
                return result;
            }
            var i = -1, len = arr.length;
            while (++i < len) {
                if (callback(arr[i], i, arr)) {
                    result = true;
                    break;
                }
            }
            return result;
        }
        module.exports = some;
    },
    '1e': function (require, module, exports, global) {
        function hasOwn(obj, prop) {
            return Object.prototype.hasOwnProperty.call(obj, prop);
        }
        module.exports = hasOwn;
    },
    '1f': function (require, module, exports, global) {
        var forOwn = require('j');
        function mixIn(target, objects) {
            var i = 0, n = arguments.length, obj;
            while (++i < n) {
                obj = arguments[i];
                if (obj != null) {
                    forOwn(obj, copyProp, target);
                }
            }
            return target;
        }
        function copyProp(val, key) {
            this[key] = val;
        }
        module.exports = mixIn;
    },
    '1g': function (require, module, exports, global) {
        var mixIn = require('1f');
        function createObject(parent, props) {
            function F() {
            }
            F.prototype = parent;
            return mixIn(new F(), props);
        }
        module.exports = createObject;
    },
    '1h': function (require, module, exports, global) {
        var _rKind = /^\[object (.*)\]$/, _toString = Object.prototype.toString, UNDEF;
        function kindOf(val) {
            if (val === null) {
                return 'Null';
            } else if (val === UNDEF) {
                return 'Undefined';
            } else {
                return _rKind.exec(_toString.call(val))[1];
            }
        }
        module.exports = kindOf;
    },
    '1i': function (require, module, exports, global) {
        'use strict';
        var kindOf = require('1h'), now = require('2c'), forEach = require('d'), indexOf = require('f');
        var callbacks = {
                timeout: {},
                frame: [],
                immediate: []
            };
        var push = function (collection, callback, context, defer) {
            var iterator = function () {
                iterate(collection);
            };
            if (!collection.length)
                defer(iterator);
            var entry = {
                    callback: callback,
                    context: context
                };
            collection.push(entry);
            return function () {
                var io = indexOf(collection, entry);
                if (io > -1)
                    collection.splice(io, 1);
            };
        };
        var iterate = function (collection) {
            var time = now();
            forEach(collection.splice(0), function (entry) {
                entry.callback.call(entry.context, time);
            });
        };
        var defer = function (callback, argument, context) {
            return kindOf(argument) === 'Number' ? defer.timeout(callback, argument, context) : defer.immediate(callback, argument);
        };
        if (global.process && process.nextTick) {
            defer.immediate = function (callback, context) {
                return push(callbacks.immediate, callback, context, process.nextTick);
            };
        } else if (global.setImmediate) {
            defer.immediate = function (callback, context) {
                return push(callbacks.immediate, callback, context, setImmediate);
            };
        } else if (global.postMessage && global.addEventListener) {
            addEventListener('message', function (event) {
                if (event.source === global && event.data === '@deferred') {
                    event.stopPropagation();
                    iterate(callbacks.immediate);
                }
            }, true);
            defer.immediate = function (callback, context) {
                return push(callbacks.immediate, callback, context, function () {
                    postMessage('@deferred', '*');
                });
            };
        } else {
            defer.immediate = function (callback, context) {
                return push(callbacks.immediate, callback, context, function (iterator) {
                    setTimeout(iterator, 0);
                });
            };
        }
        var requestAnimationFrame = global.requestAnimationFrame || global.webkitRequestAnimationFrame || global.mozRequestAnimationFrame || global.oRequestAnimationFrame || global.msRequestAnimationFrame || function (callback) {
                setTimeout(callback, 1000 / 60);
            };
        defer.frame = function (callback, context) {
            return push(callbacks.frame, callback, context, requestAnimationFrame);
        };
        var clear;
        defer.timeout = function (callback, ms, context) {
            var ct = callbacks.timeout;
            if (!clear)
                clear = defer.immediate(function () {
                    clear = null;
                    callbacks.timeout = {};
                });
            return push(ct[ms] || (ct[ms] = []), callback, context, function (iterator) {
                setTimeout(iterator, ms);
            });
        };
        module.exports = defer;
    },
    '1j': function (require, module, exports, global) {
        var prime = require('g'), Options = require('x'), guid = require('2d'), zen = require('v'), $ = require('9'), get = require('q'), has = require('1x'), set = require('o');
        require('r');
        var Base = new prime({
                mixin: Options,
                options: { attributes: {} },
                constructor: function (options) {
                    this.setOptions(options);
                    this.fresh = !this.options.id;
                    this.id = this.options.id || this.guid();
                    this.attributes = this.options.attributes || {};
                    this.block = zen('div').html(this.layout()).firstChild();
                    return this;
                },
                guid: function () {
                    return guid();
                },
                getId: function () {
                    return this.id || (this.id = this.guid());
                },
                getType: function () {
                    return this.options.type || '';
                },
                getTitle: function () {
                    return '';
                },
                getAttribute: function (key) {
                    return get(this.attributes, key);
                },
                getAttributes: function () {
                    return this.attributes || {};
                },
                setAttribute: function (key, value) {
                    set(this.attributes, key, value);
                    return this;
                },
                hasAttribute: function (key) {
                    return has(this.attributes, key);
                },
                insert: function (target, location) {
                    this.block[location || 'after'](target);
                    return this;
                },
                adopt: function (element) {
                    element.insert(this.block);
                    return this;
                },
                isNew: function (fresh) {
                    if (typeof fresh !== 'undefined')
                        this.fresh = !!fresh;
                    return this.fresh;
                },
                dropZone: function () {
                    var root = $('[data-lm-root]'), mode = root.data('lm-root'), type = this.getType();
                    if (mode == 'page' && type != 'section' && type != 'grid' && type != 'block')
                        return '';
                    return 'data-lm-dropzone';
                },
                layout: function () {
                },
                setLayout: function (layout) {
                    this.block = layout;
                    return this;
                }
            });
        module.exports = Base;
    },
    '1k': function (require, module, exports, global) {
        var prime = require('g'), Base = require('1j'), $ = require('9'), zen = require('v');
        null;
        require('16');
        var UID = 0;
        var Section = new prime({
                inherits: Base,
                options: { type: 'section' },
                constructor: function (options) {
                    ++UID;
                    Base.call(this, options);
                },
                layout: function () {
                    return '<div class="section" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="' + this.getType() + '"><div class="section-header clearfix"><h4 class="float-left">' + this.getAttribute('name') + '</h4><a href="#" class="button float-right"><i class="fa fa-pencil-square-o"></i> Edit</a></div></div>';
                },
                adopt: function (child) {
                    $(child).insert(this.block.find('.grid'));
                }
            });
        module.exports = Section;
    },
    '1l': function (require, module, exports, global) {
        var prime = require('g'), Base = require('1j');
        var Grid = new prime({
                inherits: Base,
                options: { type: 'grid' },
                constructor: function (options) {
                    Base.call(this, options);
                },
                layout: function () {
                    return '<div class="grid" data-lm-id="' + this.getId() + '" data-lm-blocktype="grid" ' + this.dropZone() + '></div>';
                }
            });
        module.exports = Grid;
    },
    '1m': function (require, module, exports, global) {
        var prime = require('g'), Base = require('1j');
        var Block = new prime({
                inherits: Base,
                options: {
                    type: 'block',
                    attributes: { size: 50 }
                },
                constructor: function (options) {
                    Base.call(this, options);
                },
                getSize: function () {
                    return this.getAttribute('size');
                },
                setSize: function (size, store) {
                    size = typeof size == 'undefined' ? this.getSize() : Math.max(0, Math.min(100, parseFloat(size)));
                    if (store)
                        this.setAttribute('size', size);
                    this.block.style('flex', '0 1 ' + size + '%');
                },
                setAnimatedSize: function (size, store) {
                    size = typeof size == 'undefined' ? this.getSize() : Math.max(0, Math.min(100, parseFloat(size)));
                    if (store)
                        this.setAttribute('size', size);
                    this.block.animate({ flex: '0 1 ' + size + '%' });
                },
                layout: function () {
                    return '<div class="block" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="block"></div>';
                }
            });
        module.exports = Block;
    },
    '1n': function (require, module, exports, global) {
        var prime = require('g'), Base = require('1j'), $ = require('9'), zen = require('v');
        null;
        require('16');
        var UID = 0;
        var Position = new prime({
                inherits: Base,
                options: { type: 'position' },
                constructor: function (options) {
                    ++UID;
                    Base.call(this, options);
                    this.setAttribute('name', this.getTitle());
                },
                getTitle: function () {
                    return this.getAttribute('name') || 'Position ' + UID;
                },
                layout: function () {
                    return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="' + this.getType() + '">' + this.getTitle() + '</div>';
                }
            });
        module.exports = Position;
    },
    '1o': function (require, module, exports, global) {
        var prime = require('g'), Base = require('1j'), $ = require('9'), zen = require('v');
        null;
        require('16');
        var UID = 0;
        var Mainbody = new prime({
                inherits: Base,
                options: { type: 'mainbody' },
                constructor: function (options) {
                    ++UID;
                    Base.call(this, options);
                },
                getTitle: function () {
                    return 'Mainbody ' + UID;
                },
                layout: function () {
                    return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="' + this.getType() + '">' + this.getTitle() + '</div>';
                }
            });
        module.exports = Mainbody;
    },
    '1p': function (require, module, exports, global) {
        var prime = require('g'), Position = require('1n');
        var UID = 0;
        var Spacer = new prime({
                inherits: Position,
                options: { type: 'spacer' },
                constructor: function (options) {
                    ++UID;
                    Position.call(this, options);
                },
                getTitle: function () {
                    return 'Spacer';
                }
            });
        module.exports = Spacer;
    },
    '1q': function (require, module, exports, global) {
        var hasOwn = require('1e');
        var _hasDontEnumBug, _dontEnums;
        function checkDontEnum() {
            _dontEnums = [
                'toString',
                'toLocaleString',
                'valueOf',
                'hasOwnProperty',
                'isPrototypeOf',
                'propertyIsEnumerable',
                'constructor'
            ];
            _hasDontEnumBug = true;
            for (var key in { 'toString': null }) {
                _hasDontEnumBug = false;
            }
        }
        function forIn(obj, fn, thisObj) {
            var key, i = 0;
            if (_hasDontEnumBug == null)
                checkDontEnum();
            for (key in obj) {
                if (exec(fn, obj, key, thisObj) === false) {
                    break;
                }
            }
            if (_hasDontEnumBug) {
                var ctor = obj.constructor, isProto = !!ctor && obj === ctor.prototype;
                while (key = _dontEnums[i++]) {
                    if ((key !== 'constructor' || !isProto && hasOwn(obj, key)) && obj[key] !== Object.prototype[key]) {
                        if (exec(fn, obj, key, thisObj) === false) {
                            break;
                        }
                    }
                }
            }
        }
        function exec(fn, obj, key, thisObj) {
            return fn.call(thisObj, obj[key], key, obj);
        }
        module.exports = forIn;
    },
    '1r': function (require, module, exports, global) {
        var slice = require('14');
        function makeCollectionMethod(arrMethod, objMethod, defaultReturn) {
            return function () {
                var args = slice(arguments);
                if (args[0] == null) {
                    return defaultReturn;
                }
                return typeof args[0].length === 'number' ? arrMethod.apply(null, args) : objMethod.apply(null, args);
            };
        }
        module.exports = makeCollectionMethod;
    },
    '1s': function (require, module, exports, global) {
        var forOwn = require('j');
        function size(obj) {
            var count = 0;
            forOwn(obj, function () {
                count++;
            });
            return count;
        }
        module.exports = size;
    },
    '1t': function (require, module, exports, global) {
        var kindOf = require('1h');
        function isKind(val, kind) {
            return kindOf(val) === kind;
        }
        module.exports = isKind;
    },
    '1u': function (require, module, exports, global) {
        var identity = require('2e');
        var prop = require('2f');
        var deepMatches = require('2g');
        function makeIterator(src, thisObj) {
            if (src == null) {
                return identity;
            }
            switch (typeof src) {
            case 'function':
                return typeof thisObj !== 'undefined' ? function (val, i, arr) {
                    return src.call(thisObj, val, i, arr);
                } : src;
            case 'object':
                return function (val) {
                    return deepMatches(val, src);
                };
            case 'string':
            case 'number':
                return prop(src);
            }
        }
        module.exports = makeIterator;
    },
    '1v': function (require, module, exports, global) {
        function append(arr1, arr2) {
            if (arr2 == null) {
                return arr1;
            }
            var pad = arr1.length, i = -1, len = arr2.length;
            while (++i < len) {
                arr1[pad + i] = arr2[i];
            }
            return arr1;
        }
        module.exports = append;
    },
    '1w': function (require, module, exports, global) {
        var forEach = require('d');
        function namespace(obj, path) {
            if (!path)
                return obj;
            forEach(path.split('.'), function (key) {
                if (!obj[key]) {
                    obj[key] = {};
                }
                obj = obj[key];
            });
            return obj;
        }
        module.exports = namespace;
    },
    '1x': function (require, module, exports, global) {
        var get = require('q');
        var UNDEF;
        function has(obj, prop) {
            return get(obj, prop) !== UNDEF;
        }
        module.exports = has;
    },
    '1y': function (require, module, exports, global) {
        'use strict';
        module.exports = 'document' in global ? require('2h') : { parse: require('21') };
    },
    '1z': function (require, module, exports, global) {
        function toInt(val) {
            return ~~val;
        }
        module.exports = toInt;
    },
    '20': function (require, module, exports, global) {
        'use strict';
        var color = require('2i'), frame = require('2j');
        var moofx = typeof document !== 'undefined' ? require('2k') : require('2l');
        moofx.requestFrame = function (callback) {
            frame.request(callback);
            return this;
        };
        moofx.cancelFrame = function (callback) {
            frame.cancel(callback);
            return this;
        };
        moofx.color = color;
        module.exports = moofx;
    },
    '21': function (require, module, exports, global) {
        'use strict';
        var escapeRe = /([-.*+?^${}()|[\]\/\\])/g, unescapeRe = /\\/g;
        var escape = function (string) {
            return (string + '').replace(escapeRe, '\\$1');
        };
        var unescape = function (string) {
            return (string + '').replace(unescapeRe, '');
        };
        var slickRe = RegExp('^(?:\\s*(,)\\s*|\\s*(<combinator>+)\\s*|(\\s+)|(<unicode>+|\\*)|\\#(<unicode>+)|\\.(<unicode>+)|\\[\\s*(<unicode1>+)(?:\\s*([*^$!~|]?=)(?:\\s*(?:(["\']?)(.*?)\\9)))?\\s*\\](?!\\])|(:+)(<unicode>+)(?:\\((?:(?:(["\'])([^\\13]*)\\13)|((?:\\([^)]+\\)|[^()]*)+))\\))?)'.replace(/<combinator>/, '[' + escape('>+~`!@$%^&={}\\;</') + ']').replace(/<unicode>/g, '(?:[\\w\\u00a1-\\uFFFF-]|\\\\[^\\s0-9a-f])').replace(/<unicode1>/g, '(?:[:\\w\\u00a1-\\uFFFF-]|\\\\[^\\s0-9a-f])'));
        var Part = function Part(combinator) {
            this.combinator = combinator || ' ';
            this.tag = '*';
        };
        Part.prototype.toString = function () {
            if (!this.raw) {
                var xpr = '', k, part;
                xpr += this.tag || '*';
                if (this.id)
                    xpr += '#' + this.id;
                if (this.classes)
                    xpr += '.' + this.classList.join('.');
                if (this.attributes)
                    for (k = 0; part = this.attributes[k++];) {
                        xpr += '[' + part.name + (part.operator ? part.operator + '"' + part.value + '"' : '') + ']';
                    }
                if (this.pseudos)
                    for (k = 0; part = this.pseudos[k++];) {
                        xpr += ':' + part.name;
                        if (part.value)
                            xpr += '(' + part.value + ')';
                    }
                this.raw = xpr;
            }
            return this.raw;
        };
        var Expression = function Expression() {
            this.length = 0;
        };
        Expression.prototype.toString = function () {
            if (!this.raw) {
                var xpr = '';
                for (var j = 0, bit; bit = this[j++];) {
                    if (j !== 1)
                        xpr += ' ';
                    if (bit.combinator !== ' ')
                        xpr += bit.combinator + ' ';
                    xpr += bit;
                }
                this.raw = xpr;
            }
            return this.raw;
        };
        var replacer = function (rawMatch, separator, combinator, combinatorChildren, tagName, id, className, attributeKey, attributeOperator, attributeQuote, attributeValue, pseudoMarker, pseudoClass, pseudoQuote, pseudoClassQuotedValue, pseudoClassValue) {
            var expression, current;
            if (separator || !this.length) {
                expression = this[this.length++] = new Expression();
                if (separator)
                    return '';
            }
            if (!expression)
                expression = this[this.length - 1];
            if (combinator || combinatorChildren || !expression.length) {
                current = expression[expression.length++] = new Part(combinator);
            }
            if (!current)
                current = expression[expression.length - 1];
            if (tagName) {
                current.tag = unescape(tagName);
            } else if (id) {
                current.id = unescape(id);
            } else if (className) {
                var unescaped = unescape(className);
                var classes = current.classes || (current.classes = {});
                if (!classes[unescaped]) {
                    classes[unescaped] = escape(className);
                    var classList = current.classList || (current.classList = []);
                    classList.push(unescaped);
                    classList.sort();
                }
            } else if (pseudoClass) {
                pseudoClassValue = pseudoClassValue || pseudoClassQuotedValue;
                ;
                (current.pseudos || (current.pseudos = [])).push({
                    type: pseudoMarker.length == 1 ? 'class' : 'element',
                    name: unescape(pseudoClass),
                    escapedName: escape(pseudoClass),
                    value: pseudoClassValue ? unescape(pseudoClassValue) : null,
                    escapedValue: pseudoClassValue ? escape(pseudoClassValue) : null
                });
            } else if (attributeKey) {
                attributeValue = attributeValue ? escape(attributeValue) : null;
                ;
                (current.attributes || (current.attributes = [])).push({
                    operator: attributeOperator,
                    name: unescape(attributeKey),
                    escapedName: escape(attributeKey),
                    value: attributeValue ? unescape(attributeValue) : null,
                    escapedValue: attributeValue ? escape(attributeValue) : null
                });
            }
            return '';
        };
        var Expressions = function Expressions(expression) {
            this.length = 0;
            var self = this;
            while (expression)
                expression = expression.replace(slickRe, function () {
                    return replacer.apply(self, arguments);
                });
        };
        Expressions.prototype.toString = function () {
            if (!this.raw) {
                var expressions = [];
                for (var i = 0, expression; expression = this[i++];)
                    expressions.push(expression);
                this.raw = expressions.join(', ');
            }
            return this.raw;
        };
        var cache = {};
        var parse = function (expression) {
            if (expression == null)
                return null;
            expression = ('' + expression).replace(/^\s+|\s+$/g, '');
            return cache[expression] || (cache[expression] = new Expressions(expression));
        };
        module.exports = parse;
    },
    '22': function (require, module, exports, global) {
        var slice = require('14');
        function bind(fn, context, args) {
            var argsArr = slice(arguments, 2);
            return function () {
                return fn.apply(context, argsArr.concat(slice(arguments)));
            };
        }
        module.exports = bind;
    },
    '23': function (require, module, exports, global) {
        var indexOf = require('f');
        function contains(arr, val) {
            return indexOf(arr, val) !== -1;
        }
        module.exports = contains;
    },
    '24': function (require, module, exports, global) {
        var getSupportedEvent = function (events) {
            events = events.split(' ');
            var element = document.createElement('div'), event;
            var isSupported = false;
            for (var i = events.length - 1; i >= 0; i--) {
                event = 'on' + events[i];
                isSupported = event in element;
                if (!isSupported) {
                    element.setAttribute(event, 'return;');
                    isSupported = typeof element[event] == 'function';
                }
                if (isSupported) {
                    isSupported = events[i];
                    break;
                }
            }
            element = null;
            return isSupported;
        };
        var EVENT = {
                START: getSupportedEvent('mousedown touchstart MSPointerDown pointerdown'),
                MOVE: getSupportedEvent('mousemove touchmove MSPointerMove pointermove'),
                STOP: getSupportedEvent('mouseup touchend MSPointerUp pointerup')
            };
        module.exports = EVENT;
    },
    '25': function (require, module, exports, global) {
        var isKind = require('1t');
        function isString(val) {
            return isKind(val, 'String');
        }
        module.exports = isString;
    },
    '26': function (require, module, exports, global) {
        var lerp = require('2m');
        var norm = require('2n');
        function map(val, min1, max1, min2, max2) {
            return lerp(norm(val, min1, max1), min2, max2);
        }
        module.exports = map;
    },
    '27': function (require, module, exports, global) {
        function clamp(val, min, max) {
            return val < min ? min : val > max ? max : val;
        }
        module.exports = clamp;
    },
    '28': function (require, module, exports, global) {
        var toNumber = require('2o');
        function enforcePrecision(val, nDecimalDigits) {
            val = toNumber(val);
            var pow = Math.pow(10, nDecimalDigits);
            return +(Math.round(val * pow) / pow).toFixed(nDecimalDigits);
        }
        module.exports = enforcePrecision;
    },
    '29': function (require, module, exports, global) {
        var forOwn = require('j');
        var makeIterator = require('1u');
        function every(obj, callback, thisObj) {
            callback = makeIterator(callback, thisObj);
            var result = true;
            forOwn(obj, function (val, key) {
                if (!callback(val, key, obj)) {
                    result = false;
                    return false;
                }
            });
            return result;
        }
        module.exports = every;
    },
    '2a': function (require, module, exports, global) {
        var clone = require('2p');
        var forOwn = require('j');
        var kindOf = require('1h');
        var isPlainObject = require('2q');
        function deepClone(val, instanceClone) {
            switch (kindOf(val)) {
            case 'Object':
                return cloneObject(val, instanceClone);
            case 'Array':
                return cloneArray(val, instanceClone);
            default:
                return clone(val);
            }
        }
        function cloneObject(source, instanceClone) {
            if (isPlainObject(source)) {
                var out = {};
                forOwn(source, function (val, key) {
                    this[key] = deepClone(val, instanceClone);
                }, out);
                return out;
            } else if (instanceClone) {
                return instanceClone(source);
            } else {
                return source;
            }
        }
        function cloneArray(arr, instanceClone) {
            var out = [], i = -1, n = arr.length, val;
            while (++i < n) {
                out[i] = deepClone(arr[i], instanceClone);
            }
            return out;
        }
        module.exports = deepClone;
    },
    '2b': function (require, module, exports, global) {
        'use strict';
        var indexOf = require('f');
        var prime = require('g');
        var Map = prime({
                constructor: function Map() {
                    this.length = 0;
                    this._values = [];
                    this._keys = [];
                },
                set: function (key, value) {
                    var index = indexOf(this._keys, key);
                    if (index === -1) {
                        this._keys.push(key);
                        this._values.push(value);
                        this.length++;
                    } else {
                        this._values[index] = value;
                    }
                    return this;
                },
                get: function (key) {
                    var index = indexOf(this._keys, key);
                    return index === -1 ? null : this._values[index];
                },
                count: function () {
                    return this.length;
                },
                forEach: function (method, context) {
                    for (var i = 0, l = this.length; i < l; i++) {
                        if (method.call(context, this._values[i], this._keys[i], this) === false)
                            break;
                    }
                    return this;
                },
                map: function (method, context) {
                    var results = new Map();
                    this.forEach(function (value, key) {
                        results.set(key, method.call(context, value, key, this));
                    }, this);
                    return results;
                },
                filter: function (method, context) {
                    var results = new Map();
                    this.forEach(function (value, key) {
                        if (method.call(context, value, key, this))
                            results.set(key, value);
                    }, this);
                    return results;
                },
                every: function (method, context) {
                    var every = true;
                    this.forEach(function (value, key) {
                        if (!method.call(context, value, key, this))
                            return every = false;
                    }, this);
                    return every;
                },
                some: function (method, context) {
                    var some = false;
                    this.forEach(function (value, key) {
                        if (method.call(context, value, key, this))
                            return !(some = true);
                    }, this);
                    return some;
                },
                indexOf: function (value) {
                    var index = indexOf(this._values, value);
                    return index > -1 ? this._keys[index] : null;
                },
                remove: function (value) {
                    var index = indexOf(this._values, value);
                    if (index !== -1) {
                        this._values.splice(index, 1);
                        this.length--;
                        return this._keys.splice(index, 1)[0];
                    }
                    return null;
                },
                unset: function (key) {
                    var index = indexOf(this._keys, key);
                    if (index !== -1) {
                        this._keys.splice(index, 1);
                        this.length--;
                        return this._values.splice(index, 1)[0];
                    }
                    return null;
                },
                keys: function () {
                    return this._keys.slice();
                },
                values: function () {
                    return this._values.slice();
                }
            });
        var map = function () {
            return new Map();
        };
        map.prototype = Map.prototype;
        module.exports = map;
    },
    '2c': function (require, module, exports, global) {
        function now() {
            return now.get();
        }
        now.get = typeof Date.now === 'function' ? Date.now : function () {
            return +new Date();
        };
        module.exports = now;
    },
    '2d': function (require, module, exports, global) {
        var randHex = require('2r');
        var choice = require('2s');
        function guid() {
            return randHex(8) + '-' + randHex(4) + '-' + '4' + randHex(3) + '-' + choice(8, 9, 'a', 'b') + randHex(3) + '-' + randHex(12);
        }
        module.exports = guid;
    },
    '2e': function (require, module, exports, global) {
        function identity(val) {
            return val;
        }
        module.exports = identity;
    },
    '2f': function (require, module, exports, global) {
        function prop(name) {
            return function (obj) {
                return obj[name];
            };
        }
        module.exports = prop;
    },
    '2g': function (require, module, exports, global) {
        var forOwn = require('j');
        var isArray = require('m');
        function containsMatch(array, pattern) {
            var i = -1, length = array.length;
            while (++i < length) {
                if (deepMatches(array[i], pattern)) {
                    return true;
                }
            }
            return false;
        }
        function matchArray(target, pattern) {
            var i = -1, patternLength = pattern.length;
            while (++i < patternLength) {
                if (!containsMatch(target, pattern[i])) {
                    return false;
                }
            }
            return true;
        }
        function matchObject(target, pattern) {
            var result = true;
            forOwn(pattern, function (val, key) {
                if (!deepMatches(target[key], val)) {
                    return result = false;
                }
            });
            return result;
        }
        function deepMatches(target, pattern) {
            if (target && typeof target === 'object') {
                if (isArray(target) && isArray(pattern)) {
                    return matchArray(target, pattern);
                } else {
                    return matchObject(target, pattern);
                }
            } else {
                return target === pattern;
            }
        }
        module.exports = deepMatches;
    },
    '2h': function (require, module, exports, global) {
        'use strict';
        var parse = require('21');
        var index = 0, counter = document.__counter = (parseInt(document.__counter || -1, 36) + 1).toString(36), key = 'uid:' + counter;
        var uniqueID = function (n, xml) {
            if (n === window)
                return 'window';
            if (n === document)
                return 'document';
            if (n === document.documentElement)
                return 'html';
            if (xml) {
                var uid = n.getAttribute(key);
                if (!uid) {
                    uid = (index++).toString(36);
                    n.setAttribute(key, uid);
                }
                return uid;
            } else {
                return n[key] || (n[key] = (index++).toString(36));
            }
        };
        var uniqueIDXML = function (n) {
            return uniqueID(n, true);
        };
        var isArray = Array.isArray || function (object) {
                return Object.prototype.toString.call(object) === '[object Array]';
            };
        var uniqueIndex = 0;
        var HAS = {
                GET_ELEMENT_BY_ID: function (test, id) {
                    id = 'slick_' + uniqueIndex++;
                    test.innerHTML = '<a id="' + id + '"></a>';
                    return !!this.getElementById(id);
                },
                QUERY_SELECTOR: function (test) {
                    test.innerHTML = '_<style>:nth-child(2){}</style>';
                    test.innerHTML = '<a class="MiX"></a>';
                    return test.querySelectorAll('.MiX').length === 1;
                },
                EXPANDOS: function (test, id) {
                    id = 'slick_' + uniqueIndex++;
                    test._custom_property_ = id;
                    return test._custom_property_ === id;
                },
                MATCHES_SELECTOR: function (test) {
                    test.className = 'MiX';
                    var matches = test.matchesSelector || test.mozMatchesSelector || test.webkitMatchesSelector;
                    if (matches)
                        try {
                            matches.call(test, ':slick');
                        } catch (e) {
                            return matches.call(test, '.MiX') ? matches : false;
                        }
                    return false;
                },
                GET_ELEMENTS_BY_CLASS_NAME: function (test) {
                    test.innerHTML = '<a class="f"></a><a class="b"></a>';
                    if (test.getElementsByClassName('b').length !== 1)
                        return false;
                    test.firstChild.className = 'b';
                    if (test.getElementsByClassName('b').length !== 2)
                        return false;
                    test.innerHTML = '<a class="a"></a><a class="f b a"></a>';
                    if (test.getElementsByClassName('a').length !== 2)
                        return false;
                    return true;
                },
                GET_ATTRIBUTE: function (test) {
                    var shout = 'fus ro dah';
                    test.innerHTML = '<a class="' + shout + '"></a>';
                    return test.firstChild.getAttribute('class') === shout;
                }
            };
        var Finder = function Finder(document) {
            this.document = document;
            var root = this.root = document.documentElement;
            this.tested = {};
            this.uniqueID = this.has('EXPANDOS') ? uniqueID : uniqueIDXML;
            this.getAttribute = this.has('GET_ATTRIBUTE') ? function (node, name) {
                return node.getAttribute(name);
            } : function (node, name) {
                node = node.getAttributeNode(name);
                return node && node.specified ? node.value : null;
            };
            this.hasAttribute = root.hasAttribute ? function (node, attribute) {
                return node.hasAttribute(attribute);
            } : function (node, attribute) {
                node = node.getAttributeNode(attribute);
                return !!(node && node.specified);
            };
            this.contains = document.contains && root.contains ? function (context, node) {
                return context.contains(node);
            } : root.compareDocumentPosition ? function (context, node) {
                return context === node || !!(context.compareDocumentPosition(node) & 16);
            } : function (context, node) {
                do {
                    if (node === context)
                        return true;
                } while (node = node.parentNode);
                return false;
            };
            this.sorter = root.compareDocumentPosition ? function (a, b) {
                if (!a.compareDocumentPosition || !b.compareDocumentPosition)
                    return 0;
                return a.compareDocumentPosition(b) & 4 ? -1 : a === b ? 0 : 1;
            } : 'sourceIndex' in root ? function (a, b) {
                if (!a.sourceIndex || !b.sourceIndex)
                    return 0;
                return a.sourceIndex - b.sourceIndex;
            } : document.createRange ? function (a, b) {
                if (!a.ownerDocument || !b.ownerDocument)
                    return 0;
                var aRange = a.ownerDocument.createRange(), bRange = b.ownerDocument.createRange();
                aRange.setStart(a, 0);
                aRange.setEnd(a, 0);
                bRange.setStart(b, 0);
                bRange.setEnd(b, 0);
                return aRange.compareBoundaryPoints(Range.START_TO_END, bRange);
            } : null;
            this.failed = {};
            var nativeMatches = this.has('MATCHES_SELECTOR');
            if (nativeMatches)
                this.matchesSelector = function (node, expression) {
                    if (this.failed[expression])
                        return null;
                    try {
                        return nativeMatches.call(node, expression);
                    } catch (e) {
                        if (slick.debug)
                            console.warn('matchesSelector failed on ' + expression);
                        this.failed[expression] = true;
                        return null;
                    }
                };
            if (this.has('QUERY_SELECTOR')) {
                this.querySelectorAll = function (node, expression) {
                    if (this.failed[expression])
                        return true;
                    var result, _id, _expression, _combinator, _node;
                    if (node !== this.document) {
                        _combinator = expression[0].combinator;
                        _id = node.getAttribute('id');
                        _expression = expression;
                        if (!_id) {
                            _node = node;
                            _id = '__slick__';
                            _node.setAttribute('id', _id);
                        }
                        expression = '#' + _id + ' ' + _expression;
                        if (_combinator.indexOf('~') > -1 || _combinator.indexOf('+') > -1) {
                            node = node.parentNode;
                            if (!node)
                                result = true;
                        }
                    }
                    if (!result)
                        try {
                            result = node.querySelectorAll(expression.toString());
                        } catch (e) {
                            if (slick.debug)
                                console.warn('querySelectorAll failed on ' + (_expression || expression));
                            result = this.failed[_expression || expression] = true;
                        }
                    if (_node)
                        _node.removeAttribute('id');
                    return result;
                };
            }
        };
        Finder.prototype.has = function (FEATURE) {
            var tested = this.tested, testedFEATURE = tested[FEATURE];
            if (testedFEATURE != null)
                return testedFEATURE;
            var root = this.root, document = this.document, testNode = document.createElement('div');
            testNode.setAttribute('style', 'display: none;');
            root.appendChild(testNode);
            var TEST = HAS[FEATURE], result = false;
            if (TEST)
                try {
                    result = TEST.call(document, testNode);
                } catch (e) {
                }
            if (slick.debug && !result)
                console.warn('document has no ' + FEATURE);
            root.removeChild(testNode);
            return tested[FEATURE] = result;
        };
        var combinators = {
                ' ': function (node, part, push) {
                    var item, items;
                    var noId = !part.id, noTag = !part.tag, noClass = !part.classes;
                    if (part.id && node.getElementById && this.has('GET_ELEMENT_BY_ID')) {
                        item = node.getElementById(part.id);
                        if (item && item.getAttribute('id') === part.id) {
                            items = [item];
                            noId = true;
                            if (part.tag === '*')
                                noTag = true;
                        }
                    }
                    if (!items) {
                        if (part.classes && node.getElementsByClassName && this.has('GET_ELEMENTS_BY_CLASS_NAME')) {
                            items = node.getElementsByClassName(part.classList);
                            noClass = true;
                            if (part.tag === '*')
                                noTag = true;
                        } else {
                            items = node.getElementsByTagName(part.tag);
                            if (part.tag !== '*')
                                noTag = true;
                        }
                        if (!items || !items.length)
                            return false;
                    }
                    for (var i = 0; item = items[i++];)
                        if (noTag && noId && noClass && !part.attributes && !part.pseudos || this.match(item, part, noTag, noId, noClass))
                            push(item);
                    return true;
                },
                '>': function (node, part, push) {
                    if (node = node.firstChild)
                        do {
                            if (node.nodeType == 1 && this.match(node, part))
                                push(node);
                        } while (node = node.nextSibling);
                },
                '+': function (node, part, push) {
                    while (node = node.nextSibling)
                        if (node.nodeType == 1) {
                            if (this.match(node, part))
                                push(node);
                            break;
                        }
                },
                '^': function (node, part, push) {
                    node = node.firstChild;
                    if (node) {
                        if (node.nodeType === 1) {
                            if (this.match(node, part))
                                push(node);
                        } else {
                            combinators['+'].call(this, node, part, push);
                        }
                    }
                },
                '~': function (node, part, push) {
                    while (node = node.nextSibling) {
                        if (node.nodeType === 1 && this.match(node, part))
                            push(node);
                    }
                },
                '++': function (node, part, push) {
                    combinators['+'].call(this, node, part, push);
                    combinators['!+'].call(this, node, part, push);
                },
                '~~': function (node, part, push) {
                    combinators['~'].call(this, node, part, push);
                    combinators['!~'].call(this, node, part, push);
                },
                '!': function (node, part, push) {
                    while (node = node.parentNode)
                        if (node !== this.document && this.match(node, part))
                            push(node);
                },
                '!>': function (node, part, push) {
                    node = node.parentNode;
                    if (node !== this.document && this.match(node, part))
                        push(node);
                },
                '!+': function (node, part, push) {
                    while (node = node.previousSibling)
                        if (node.nodeType == 1) {
                            if (this.match(node, part))
                                push(node);
                            break;
                        }
                },
                '!^': function (node, part, push) {
                    node = node.lastChild;
                    if (node) {
                        if (node.nodeType == 1) {
                            if (this.match(node, part))
                                push(node);
                        } else {
                            combinators['!+'].call(this, node, part, push);
                        }
                    }
                },
                '!~': function (node, part, push) {
                    while (node = node.previousSibling) {
                        if (node.nodeType === 1 && this.match(node, part))
                            push(node);
                    }
                }
            };
        Finder.prototype.search = function (context, expression, found) {
            if (!context)
                context = this.document;
            else if (!context.nodeType && context.document)
                context = context.document;
            var expressions = parse(expression);
            if (!expressions || !expressions.length)
                throw new Error('invalid expression');
            if (!found)
                found = [];
            var uniques, push = isArray(found) ? function (node) {
                    found[found.length] = node;
                } : function (node) {
                    found[found.length++] = node;
                };
            if (expressions.length > 1) {
                uniques = {};
                var plush = push;
                push = function (node) {
                    var uid = uniqueID(node);
                    if (!uniques[uid]) {
                        uniques[uid] = true;
                        plush(node);
                    }
                };
            }
            var node, nodes, part;
            main:
                for (var i = 0; expression = expressions[i++];) {
                    if (!slick.noQSA && this.querySelectorAll) {
                        nodes = this.querySelectorAll(context, expression);
                        if (nodes !== true) {
                            if (nodes && nodes.length)
                                for (var j = 0; node = nodes[j++];)
                                    if (node.nodeName > '@') {
                                        push(node);
                                    }
                            continue main;
                        }
                    }
                    if (expression.length === 1) {
                        part = expression[0];
                        combinators[part.combinator].call(this, context, part, push);
                    } else {
                        var cs = [context], c, f, u, p = function (node) {
                                var uid = uniqueID(node);
                                if (!u[uid]) {
                                    u[uid] = true;
                                    f[f.length] = node;
                                }
                            };
                        for (var j = 0; part = expression[j++];) {
                            f = [];
                            u = {};
                            for (var k = 0; c = cs[k++];)
                                combinators[part.combinator].call(this, c, part, p);
                            if (!f.length)
                                continue main;
                            if (j === expression.length)
                                found = f;
                            else
                                cs = f;
                        }
                    }
                    if (!found.length)
                        continue main;
                }
            if (uniques && found && found.length > 1)
                this.sort(found);
            return found;
        };
        Finder.prototype.sort = function (nodes) {
            return this.sorter ? Array.prototype.sort.call(nodes, this.sorter) : nodes;
        };
        var pseudos = {
                'empty': function () {
                    return !(this && this.nodeType === 1) && !(this.innerText || this.textContent || '').length;
                },
                'not': function (expression) {
                    return !slick.match(this, expression);
                },
                'contains': function (text) {
                    return (this.innerText || this.textContent || '').indexOf(text) > -1;
                },
                'first-child': function () {
                    var node = this;
                    while (node = node.previousSibling)
                        if (node.nodeType == 1)
                            return false;
                    return true;
                },
                'last-child': function () {
                    var node = this;
                    while (node = node.nextSibling)
                        if (node.nodeType == 1)
                            return false;
                    return true;
                },
                'only-child': function () {
                    var prev = this;
                    while (prev = prev.previousSibling)
                        if (prev.nodeType == 1)
                            return false;
                    var next = this;
                    while (next = next.nextSibling)
                        if (next.nodeType == 1)
                            return false;
                    return true;
                },
                'first-of-type': function () {
                    var node = this, nodeName = node.nodeName;
                    while (node = node.previousSibling)
                        if (node.nodeName == nodeName)
                            return false;
                    return true;
                },
                'last-of-type': function () {
                    var node = this, nodeName = node.nodeName;
                    while (node = node.nextSibling)
                        if (node.nodeName == nodeName)
                            return false;
                    return true;
                },
                'only-of-type': function () {
                    var prev = this, nodeName = this.nodeName;
                    while (prev = prev.previousSibling)
                        if (prev.nodeName == nodeName)
                            return false;
                    var next = this;
                    while (next = next.nextSibling)
                        if (next.nodeName == nodeName)
                            return false;
                    return true;
                },
                'enabled': function () {
                    return !this.disabled;
                },
                'disabled': function () {
                    return this.disabled;
                },
                'checked': function () {
                    return this.checked || this.selected;
                },
                'selected': function () {
                    return this.selected;
                },
                'focus': function () {
                    var doc = this.ownerDocument;
                    return doc.activeElement === this && (this.href || this.type || slick.hasAttribute(this, 'tabindex'));
                },
                'root': function () {
                    return this === this.ownerDocument.documentElement;
                }
            };
        Finder.prototype.match = function (node, bit, noTag, noId, noClass) {
            if (!slick.noQSA && this.matchesSelector) {
                var matches = this.matchesSelector(node, bit);
                if (matches !== null)
                    return matches;
            }
            if (!noTag && bit.tag) {
                var nodeName = node.nodeName.toLowerCase();
                if (bit.tag === '*') {
                    if (nodeName < '@')
                        return false;
                } else if (nodeName != bit.tag) {
                    return false;
                }
            }
            if (!noId && bit.id && node.getAttribute('id') !== bit.id)
                return false;
            var i, part;
            if (!noClass && bit.classes) {
                var className = this.getAttribute(node, 'class');
                if (!className)
                    return false;
                for (part in bit.classes)
                    if (!RegExp('(^|\\s)' + bit.classes[part] + '(\\s|$)').test(className))
                        return false;
            }
            var name, value;
            if (bit.attributes)
                for (i = 0; part = bit.attributes[i++];) {
                    var operator = part.operator, escaped = part.escapedValue;
                    name = part.name;
                    value = part.value;
                    if (!operator) {
                        if (!this.hasAttribute(node, name))
                            return false;
                    } else {
                        var actual = this.getAttribute(node, name);
                        if (actual == null)
                            return false;
                        switch (operator) {
                        case '^=':
                            if (!RegExp('^' + escaped).test(actual))
                                return false;
                            break;
                        case '$=':
                            if (!RegExp(escaped + '$').test(actual))
                                return false;
                            break;
                        case '~=':
                            if (!RegExp('(^|\\s)' + escaped + '(\\s|$)').test(actual))
                                return false;
                            break;
                        case '|=':
                            if (!RegExp('^' + escaped + '(-|$)').test(actual))
                                return false;
                            break;
                        case '=':
                            if (actual !== value)
                                return false;
                            break;
                        case '*=':
                            if (actual.indexOf(value) === -1)
                                return false;
                            break;
                        default:
                            return false;
                        }
                    }
                }
            if (bit.pseudos)
                for (i = 0; part = bit.pseudos[i++];) {
                    name = part.name;
                    value = part.value;
                    if (pseudos[name])
                        return pseudos[name].call(node, value);
                    if (value != null) {
                        if (this.getAttribute(node, name) !== value)
                            return false;
                    } else {
                        if (!this.hasAttribute(node, name))
                            return false;
                    }
                }
            return true;
        };
        Finder.prototype.matches = function (node, expression) {
            var expressions = parse(expression);
            if (expressions.length === 1 && expressions[0].length === 1) {
                return this.match(node, expressions[0][0]);
            }
            if (!slick.noQSA && this.matchesSelector) {
                var matches = this.matchesSelector(node, expressions);
                if (matches !== null)
                    return matches;
            }
            var nodes = this.search(this.document, expression, { length: 0 });
            for (var i = 0, res; res = nodes[i++];)
                if (node === res)
                    return true;
            return false;
        };
        var finders = {};
        var finder = function (context) {
            var doc = context || document;
            if (doc.ownerDocument)
                doc = doc.ownerDocument;
            else if (doc.document)
                doc = doc.document;
            if (doc.nodeType !== 9)
                throw new TypeError('invalid document');
            var uid = uniqueID(doc);
            return finders[uid] || (finders[uid] = new Finder(doc));
        };
        var slick = function (expression, context) {
            return slick.search(expression, context);
        };
        slick.search = function (expression, context, found) {
            return finder(context).search(context, expression, found);
        };
        slick.find = function (expression, context) {
            return finder(context).search(context, expression)[0] || null;
        };
        slick.getAttribute = function (node, name) {
            return finder(node).getAttribute(node, name);
        };
        slick.hasAttribute = function (node, name) {
            return finder(node).hasAttribute(node, name);
        };
        slick.contains = function (context, node) {
            return finder(context).contains(context, node);
        };
        slick.matches = function (node, expression) {
            return finder(node).matches(node, expression);
        };
        slick.sort = function (nodes) {
            if (nodes && nodes.length > 1)
                finder(nodes[0]).sort(nodes);
            return nodes;
        };
        slick.parse = parse;
        module.exports = slick;
    },
    '2i': function (require, module, exports, global) {
        'use strict';
        var colors = {
                maroon: '#800000',
                red: '#ff0000',
                orange: '#ffA500',
                yellow: '#ffff00',
                olive: '#808000',
                purple: '#800080',
                fuchsia: '#ff00ff',
                white: '#ffffff',
                lime: '#00ff00',
                green: '#008000',
                navy: '#000080',
                blue: '#0000ff',
                aqua: '#00ffff',
                teal: '#008080',
                black: '#000000',
                silver: '#c0c0c0',
                gray: '#808080',
                transparent: '#0000'
            };
        var RGBtoRGB = function (r, g, b, a) {
            if (a == null || a === '')
                a = 1;
            r = parseFloat(r);
            g = parseFloat(g);
            b = parseFloat(b);
            a = parseFloat(a);
            if (!(r <= 255 && r >= 0 && g <= 255 && g >= 0 && b <= 255 && b >= 0 && a <= 1 && a >= 0))
                return null;
            return [
                Math.round(r),
                Math.round(g),
                Math.round(b),
                a
            ];
        };
        var HEXtoRGB = function (hex) {
            if (hex.length === 3)
                hex += 'f';
            if (hex.length === 4) {
                var h0 = hex.charAt(0), h1 = hex.charAt(1), h2 = hex.charAt(2), h3 = hex.charAt(3);
                hex = h0 + h0 + h1 + h1 + h2 + h2 + h3 + h3;
            }
            if (hex.length === 6)
                hex += 'ff';
            var rgb = [];
            for (var i = 0, l = hex.length; i < l; i += 2)
                rgb.push(parseInt(hex.substr(i, 2), 16) / (i === 6 ? 255 : 1));
            return rgb;
        };
        var HUEtoRGB = function (p, q, t) {
            if (t < 0)
                t += 1;
            if (t > 1)
                t -= 1;
            if (t < 1 / 6)
                return p + (q - p) * 6 * t;
            if (t < 1 / 2)
                return q;
            if (t < 2 / 3)
                return p + (q - p) * (2 / 3 - t) * 6;
            return p;
        };
        var HSLtoRGB = function (h, s, l, a) {
            var r, b, g;
            if (a == null || a === '')
                a = 1;
            h = parseFloat(h) / 360;
            s = parseFloat(s) / 100;
            l = parseFloat(l) / 100;
            a = parseFloat(a) / 1;
            if (h > 1 || h < 0 || s > 1 || s < 0 || l > 1 || l < 0 || a > 1 || a < 0)
                return null;
            if (s === 0) {
                r = b = g = l;
            } else {
                var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
                var p = 2 * l - q;
                r = HUEtoRGB(p, q, h + 1 / 3);
                g = HUEtoRGB(p, q, h);
                b = HUEtoRGB(p, q, h - 1 / 3);
            }
            return [
                r * 255,
                g * 255,
                b * 255,
                a
            ];
        };
        var keys = [];
        for (var c in colors)
            keys.push(c);
        var shex = '(?:#([a-f0-9]{3,8}))', sval = '\\s*([.\\d%]+)\\s*', sop = '(?:,\\s*([.\\d]+)\\s*)?', slist = '\\(' + [
                sval,
                sval,
                sval
            ] + sop + '\\)', srgb = '(?:rgb)a?', shsl = '(?:hsl)a?', skeys = '(' + keys.join('|') + ')';
        var xhex = RegExp(shex, 'i'), xrgb = RegExp(srgb + slist, 'i'), xhsl = RegExp(shsl + slist, 'i');
        var color = function (input, array) {
            if (input == null)
                return null;
            input = (input + '').replace(/\s+/, '');
            var match = colors[input];
            if (match) {
                return color(match, array);
            } else if (match = input.match(xhex)) {
                input = HEXtoRGB(match[1]);
            } else if (match = input.match(xrgb)) {
                input = match.slice(1);
            } else if (match = input.match(xhsl)) {
                input = HSLtoRGB.apply(null, match.slice(1));
            } else
                return null;
            if (!(input && (input = RGBtoRGB.apply(null, input))))
                return null;
            if (array)
                return input;
            if (input[3] === 1)
                input.splice(3, 1);
            return 'rgb' + (input.length === 4 ? 'a' : '') + '(' + input + ')';
        };
        color.x = RegExp([
            skeys,
            shex,
            srgb + slist,
            shsl + slist
        ].join('|'), 'gi');
        module.exports = color;
    },
    '2j': function (require, module, exports, global) {
        'use strict';
        var indexOf = require('2t');
        var requestFrame = global.requestAnimationFrame || global.webkitRequestAnimationFrame || global.mozRequestAnimationFrame || global.oRequestAnimationFrame || global.msRequestAnimationFrame || function (callback) {
                return setTimeout(function () {
                    callback();
                }, 1000 / 60);
            };
        var callbacks = [];
        var iterator = function (time) {
            var split = callbacks.splice(0, callbacks.length);
            for (var i = 0, l = split.length; i < l; i++)
                split[i](time || (time = +new Date()));
        };
        var cancel = function (callback) {
            var io = indexOf(callbacks, callback);
            if (io > -1)
                callbacks.splice(io, 1);
        };
        var request = function (callback) {
            var i = callbacks.push(callback);
            if (i === 1)
                requestFrame(iterator);
            return function () {
                cancel(callback);
            };
        };
        exports.request = request;
        exports.cancel = cancel;
    },
    '2k': function (require, module, exports, global) {
        'use strict';
        var color = require('2i'), frame = require('2j');
        var cancelFrame = frame.cancel, requestFrame = frame.request;
        var prime = require('2u');
        var camelize = require('2x'), clean = require('2y'), capitalize = require('2z'), hyphenateString = require('30');
        var map = require('2w'), forEach = require('31'), indexOf = require('2t');
        var elements = require('32');
        var fx = require('2l');
        var matchString = function (s, r) {
            return String.prototype.match.call(s, r);
        };
        var hyphenated = {};
        var hyphenate = function (self) {
            return hyphenated[self] || (hyphenated[self] = hyphenateString(self));
        };
        var round = function (n) {
            return Math.round(n * 1000) / 1000;
        };
        var compute = global.getComputedStyle ? function (node) {
                var cts = getComputedStyle(node, null);
                return function (property) {
                    return cts ? cts.getPropertyValue(hyphenate(property)) : '';
                };
            } : function (node) {
                var cts = node.currentStyle;
                return function (property) {
                    return cts ? cts[camelize(property)] : '';
                };
            };
        var test = document.createElement('div');
        var cssText = 'border:none;margin:none;padding:none;visibility:hidden;position:absolute;height:0;';
        var pixelRatio = function (element, u) {
            var parent = element.parentNode, ratio = 1;
            if (parent) {
                test.style.cssText = cssText + ('width:100' + u + ';');
                parent.appendChild(test);
                ratio = test.offsetWidth / 100;
                parent.removeChild(test);
            }
            return ratio;
        };
        var mirror4 = function (values) {
            var length = values.length;
            if (length === 1)
                values.push(values[0], values[0], values[0]);
            else if (length === 2)
                values.push(values[0], values[1]);
            else if (length === 3)
                values.push(values[1]);
            return values;
        };
        var sLength = '([-.\\d]+)(%|cm|mm|in|px|pt|pc|em|ex|ch|rem|vw|vh|vm)', sLengthNum = sLength + '?', sBorderStyle = 'none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset|inherit';
        var rgLength = RegExp(sLength, 'g'), rLengthNum = RegExp(sLengthNum), rgLengthNum = RegExp(sLengthNum, 'g'), rBorderStyle = RegExp(sBorderStyle);
        var parseString = function (value) {
            return value == null ? '' : value + '';
        };
        var parseOpacity = function (value, normalize) {
            if (value == null || value === '')
                return normalize ? '1' : '';
            return isFinite(value = +value) ? value < 0 ? '0' : value + '' : '1';
        };
        try {
            test.style.color = 'rgba(0,0,0,0.5)';
        } catch (e) {
        }
        var rgba = /^rgba/.test(test.style.color);
        var parseColor = function (value, normalize) {
            var black = 'rgba(0,0,0,1)', c;
            if (!value || !(c = color(value, true)))
                return normalize ? black : '';
            if (normalize)
                return 'rgba(' + c + ')';
            var alpha = c[3];
            if (alpha === 0)
                return 'transparent';
            return !rgba || alpha === 1 ? 'rgb(' + c.slice(0, 3) + ')' : 'rgba(' + c + ')';
        };
        var parseLength = function (value, normalize) {
            if (value == null || value === '')
                return normalize ? '0px' : '';
            var match = matchString(value, rLengthNum);
            return match ? match[1] + (match[2] || 'px') : value;
        };
        var parseBorderStyle = function (value, normalize) {
            if (value == null || value === '')
                return normalize ? 'none' : '';
            var match = value.match(rBorderStyle);
            return match ? value : normalize ? 'none' : '';
        };
        var parseBorder = function (value, normalize) {
            var normalized = '0px none rgba(0,0,0,1)';
            if (value == null || value === '')
                return normalize ? normalized : '';
            if (value === 0 || value === 'none')
                return normalize ? normalized : value + '';
            var c;
            value = value.replace(color.x, function (match) {
                c = match;
                return '';
            });
            var s = value.match(rBorderStyle), l = value.match(rgLengthNum);
            return clean([
                parseLength(l ? l[0] : '', normalize),
                parseBorderStyle(s ? s[0] : '', normalize),
                parseColor(c, normalize)
            ].join(' '));
        };
        var parseShort4 = function (value, normalize) {
            if (value == null || value === '')
                return normalize ? '0px 0px 0px 0px' : '';
            return clean(mirror4(map(clean(value).split(' '), function (v) {
                return parseLength(v, normalize);
            })).join(' '));
        };
        var parseShadow = function (value, normalize, len) {
            var transparent = 'rgba(0,0,0,0)', normalized = len === 3 ? transparent + ' 0px 0px 0px' : transparent + ' 0px 0px 0px 0px';
            if (value == null || value === '')
                return normalize ? normalized : '';
            if (value === 'none')
                return normalize ? normalized : value;
            var colors = [], value = clean(value).replace(color.x, function (match) {
                    colors.push(match);
                    return '';
                });
            return map(value.split(','), function (shadow, i) {
                var c = parseColor(colors[i], normalize), inset = /inset/.test(shadow), lengths = shadow.match(rgLengthNum) || ['0px'];
                lengths = map(lengths, function (m) {
                    return parseLength(m, normalize);
                });
                while (lengths.length < len)
                    lengths.push('0px');
                var ret = inset ? [
                        'inset',
                        c
                    ] : [c];
                return ret.concat(lengths).join(' ');
            }).join(', ');
        };
        var parse = function (value, normalize) {
            if (value == null || value === '')
                return '';
            return value.replace(color.x, function (match) {
                return parseColor(match, normalize);
            }).replace(rgLength, function (match) {
                return parseLength(match, normalize);
            });
        };
        var getters = {}, setters = {}, parsers = {}, aliases = {};
        var getter = function (key) {
            return getters[key] || (getters[key] = function () {
                var alias = aliases[key] || key, parser = parsers[key] || parse;
                return function () {
                    return parser(compute(this)(alias), true);
                };
            }());
        };
        var setter = function (key) {
            return setters[key] || (setters[key] = function () {
                var alias = aliases[key] || key, parser = parsers[key] || parse;
                return function (value) {
                    this.style[alias] = parser(value, false);
                };
            }());
        };
        var trbl = [
                'Top',
                'Right',
                'Bottom',
                'Left'
            ], tlbl = [
                'TopLeft',
                'TopRight',
                'BottomRight',
                'BottomLeft'
            ];
        forEach(trbl, function (d) {
            var bd = 'border' + d;
            forEach([
                'margin' + d,
                'padding' + d,
                bd + 'Width',
                d.toLowerCase()
            ], function (n) {
                parsers[n] = parseLength;
            });
            parsers[bd + 'Color'] = parseColor;
            parsers[bd + 'Style'] = parseBorderStyle;
            parsers[bd] = parseBorder;
            getters[bd] = function () {
                return [
                    getter(bd + 'Width').call(this),
                    getter(bd + 'Style').call(this),
                    getter(bd + 'Color').call(this)
                ].join(' ');
            };
        });
        forEach(tlbl, function (d) {
            parsers['border' + d + 'Radius'] = parseLength;
        });
        parsers.color = parsers.backgroundColor = parseColor;
        parsers.width = parsers.height = parsers.minWidth = parsers.minHeight = parsers.maxWidth = parsers.maxHeight = parsers.fontSize = parsers.backgroundSize = parseLength;
        forEach([
            'margin',
            'padding'
        ], function (name) {
            parsers[name] = parseShort4;
            getters[name] = function () {
                return map(trbl, function (d) {
                    return getter(name + d).call(this);
                }, this).join(' ');
            };
        });
        parsers.borderWidth = parseShort4;
        parsers.borderStyle = function (value, normalize) {
            if (value == null || value === '')
                return normalize ? mirror4(['none']).join(' ') : '';
            value = clean(value).split(' ');
            return clean(mirror4(map(value, function (v) {
                parseBorderStyle(v, normalize);
            })).join(' '));
        };
        parsers.borderColor = function (value, normalize) {
            if (!value || !(value = matchString(value, color.x)))
                return normalize ? mirror4(['rgba(0,0,0,1)']).join(' ') : '';
            return clean(mirror4(map(value, function (v) {
                return parseColor(v, normalize);
            })).join(' '));
        };
        forEach([
            'Width',
            'Style',
            'Color'
        ], function (name) {
            getters['border' + name] = function () {
                return map(trbl, function (d) {
                    return getter('border' + d + name).call(this);
                }, this).join(' ');
            };
        });
        parsers.borderRadius = parseShort4;
        getters.borderRadius = function () {
            return map(tlbl, function (d) {
                return getter('border' + d + 'Radius').call(this);
            }, this).join(' ');
        };
        parsers.border = parseBorder;
        getters.border = function () {
            var pvalue;
            for (var i = 0; i < trbl.length; i++) {
                var value = getter('border' + trbl[i]).call(this);
                if (pvalue && value !== pvalue)
                    return null;
                pvalue = value;
            }
            return pvalue;
        };
        parsers.zIndex = parseString;
        parsers.opacity = parseOpacity;
        var filterName = test.style.MsFilter != null && 'MsFilter' || test.style.filter != null && 'filter';
        if (filterName && test.style.opacity == null) {
            var matchOp = /alpha\(opacity=([\d.]+)\)/i;
            setters.opacity = function (value) {
                value = (value = parseOpacity(value)) === '1' ? '' : 'alpha(opacity=' + Math.round(value * 100) + ')';
                var filter = compute(this)(filterName);
                return this.style[filterName] = matchOp.test(filter) ? filter.replace(matchOp, value) : filter + ' ' + value;
            };
            getters.opacity = function () {
                var match = compute(this)(filterName).match(matchOp);
                return (!match ? 1 : match[1] / 100) + '';
            };
        }
        var parseBoxShadow = parsers.boxShadow = function (value, normalize) {
                return parseShadow(value, normalize, 4);
            };
        var parseTextShadow = parsers.textShadow = function (value, normalize) {
                return parseShadow(value, normalize, 3);
            };
        forEach([
            'Webkit',
            'Moz',
            'ms',
            'O',
            null
        ], function (prefix) {
            forEach([
                'transition',
                'transform',
                'transformOrigin',
                'transformStyle',
                'perspective',
                'perspectiveOrigin',
                'backfaceVisibility'
            ], function (style) {
                var cc = prefix ? prefix + capitalize(style) : style;
                if (prefix === 'ms')
                    hyphenated[cc] = '-ms-' + hyphenate(style);
                if (test.style[cc] != null)
                    aliases[style] = cc;
            });
        });
        var transitionName = aliases.transition, transformName = aliases.transform;
        if (transitionName === 'OTransition')
            transitionName = null;
        var parseTransform2d, Transform2d;
        if (!transitionName && transformName)
            (function () {
                var unmatrix = require('33');
                var v = '\\s*([-\\d\\w.]+)\\s*';
                var rMatrix = RegExp('matrix\\(' + [
                        v,
                        v,
                        v,
                        v,
                        v,
                        v
                    ] + '\\)');
                var decomposeMatrix = function (matrix) {
                    var d = unmatrix.apply(null, matrix.match(rMatrix).slice(1)) || [
                            [
                                0,
                                0
                            ],
                            0,
                            0,
                            [
                                0,
                                0
                            ]
                        ];
                    return [
                        'translate(' + map(d[0], function (v) {
                            return round(v) + 'px';
                        }) + ')',
                        'rotate(' + round(d[1] * 180 / Math.PI) + 'deg)',
                        'skewX(' + round(d[2] * 180 / Math.PI) + 'deg)',
                        'scale(' + map(d[3], round) + ')'
                    ].join(' ');
                };
                var def0px = function (value) {
                        return value || '0px';
                    }, def1 = function (value) {
                        return value || '1';
                    }, def0deg = function (value) {
                        return value || '0deg';
                    };
                var transforms = {
                        translate: function (value) {
                            if (!value)
                                value = '0px,0px';
                            var values = value.split(',');
                            if (!values[1])
                                values[1] = '0px';
                            return map(values, clean) + '';
                        },
                        translateX: def0px,
                        translateY: def0px,
                        scale: function (value) {
                            if (!value)
                                value = '1,1';
                            var values = value.split(',');
                            if (!values[1])
                                values[1] = values[0];
                            return map(values, clean) + '';
                        },
                        scaleX: def1,
                        scaleY: def1,
                        rotate: def0deg,
                        skewX: def0deg,
                        skewY: def0deg
                    };
                Transform2d = prime({
                    constructor: function (transform) {
                        var names = this.names = [];
                        var values = this.values = [];
                        transform.replace(/(\w+)\(([-.\d\s\w,]+)\)/g, function (match, name, value) {
                            names.push(name);
                            values.push(value);
                        });
                    },
                    identity: function () {
                        var functions = [];
                        forEach(this.names, function (name) {
                            var fn = transforms[name];
                            if (fn)
                                functions.push(name + '(' + fn() + ')');
                        });
                        return functions.join(' ');
                    },
                    sameType: function (transformObject) {
                        return this.names.toString() === transformObject.names.toString();
                    },
                    decompose: function () {
                        var transform = this.toString();
                        test.style.cssText = cssText + hyphenate(transformName) + ':' + transform + ';';
                        document.body.appendChild(test);
                        var m = compute(test)(transformName);
                        if (!m || m === 'none')
                            m = 'matrix(1, 0, 0, 1, 0, 0)';
                        document.body.removeChild(test);
                        return decomposeMatrix(m);
                    }
                });
                Transform2d.prototype.toString = function (clean) {
                    var values = this.values, functions = [];
                    forEach(this.names, function (name, i) {
                        var fn = transforms[name];
                        if (!fn)
                            return;
                        var value = fn(values[i]);
                        if (!clean || value !== fn())
                            functions.push(name + '(' + value + ')');
                    });
                    return functions.length ? functions.join(' ') : 'none';
                };
                Transform2d.union = function (from, to) {
                    if (from === to)
                        return;
                    var fromMap, toMap;
                    if (from === 'none') {
                        toMap = new Transform2d(to);
                        to = toMap.toString();
                        from = toMap.identity();
                        fromMap = new Transform2d(from);
                    } else if (to === 'none') {
                        fromMap = new Transform2d(from);
                        from = fromMap.toString();
                        to = fromMap.identity();
                        toMap = new Transform2d(to);
                    } else {
                        fromMap = new Transform2d(from);
                        from = fromMap.toString();
                        toMap = new Transform2d(to);
                        to = toMap.toString();
                    }
                    if (from === to)
                        return;
                    if (!fromMap.sameType(toMap)) {
                        from = fromMap.decompose();
                        to = toMap.decompose();
                    }
                    if (from === to)
                        return;
                    return [
                        from,
                        to
                    ];
                };
                parseTransform2d = parsers.transform = function (transform) {
                    if (!transform || transform === 'none')
                        return 'none';
                    return new Transform2d(rMatrix.test(transform) ? decomposeMatrix(transform) : transform).toString(true);
                };
                getters.transform = function () {
                    var s = this.style;
                    return s[transformName] || (s[transformName] = parseTransform2d(compute(this)(transformName)));
                };
            }());
        var prepare = function (node, property, to) {
            var parser = parsers[property] || parse, from = getter(property).call(node), to = parser(to, true);
            if (from === to)
                return;
            if (parser === parseLength || parser === parseBorder || parser === parseShort4) {
                var toAll = to.match(rgLength), i = 0;
                if (toAll)
                    from = from.replace(rgLength, function (fromFull, fromValue, fromUnit) {
                        var toFull = toAll[i++], toMatched = toFull.match(rLengthNum), toUnit = toMatched[2];
                        if (fromUnit !== toUnit) {
                            var fromPixels = fromUnit === 'px' ? fromValue : pixelRatio(node, fromUnit) * fromValue;
                            return round(fromPixels / pixelRatio(node, toUnit)) + toUnit;
                        }
                        return fromFull;
                    });
                if (i > 0)
                    setter(property).call(node, from);
            } else if (parser === parseTransform2d) {
                return Transform2d.union(from, to);
            }
            return from !== to ? [
                from,
                to
            ] : null;
        };
        var BrowserAnimation = prime({
                inherits: fx,
                constructor: function BrowserAnimation(node, property) {
                    var _getter = getter(property), _setter = setter(property);
                    this.get = function () {
                        return _getter.call(node);
                    };
                    this.set = function (value) {
                        return _setter.call(node, value);
                    };
                    BrowserAnimation.parent.constructor.call(this, this.set);
                    this.node = node;
                    this.property = property;
                }
            });
        var JSAnimation;
        JSAnimation = prime({
            inherits: BrowserAnimation,
            constructor: function JSAnimation() {
                return JSAnimation.parent.constructor.apply(this, arguments);
            },
            start: function (to) {
                this.stop();
                if (this.duration === 0) {
                    this.cancel(to);
                    return this;
                }
                var fromTo = prepare(this.node, this.property, to);
                if (!fromTo) {
                    this.cancel(to);
                    return this;
                }
                JSAnimation.parent.start.apply(this, fromTo);
                if (!this.cancelStep)
                    return this;
                var parser = parsers[this.property] || parse;
                if ((parser === parseBoxShadow || parser === parseTextShadow || parser === parse) && this.templateFrom !== this.templateTo) {
                    this.cancelStep();
                    delete this.cancelStep;
                    this.cancel(to);
                }
                return this;
            },
            parseEquation: function (equation) {
                if (typeof equation === 'string')
                    return JSAnimation.parent.parseEquation.call(this, equation);
            }
        });
        var remove3 = function (value, a, b, c) {
            var index = indexOf(a, value);
            if (index !== -1) {
                a.splice(index, 1);
                b.splice(index, 1);
                c.splice(index, 1);
            }
        };
        var CSSAnimation = prime({
                inherits: BrowserAnimation,
                constructor: function CSSAnimation(node, property) {
                    CSSAnimation.parent.constructor.call(this, node, property);
                    this.hproperty = hyphenate(aliases[property] || property);
                    var self = this;
                    this.bSetTransitionCSS = function (time) {
                        self.setTransitionCSS(time);
                    };
                    this.bSetStyleCSS = function (time) {
                        self.setStyleCSS(time);
                    };
                    this.bComplete = function () {
                        self.complete();
                    };
                },
                start: function (to) {
                    this.stop();
                    if (this.duration === 0) {
                        this.cancel(to);
                        return this;
                    }
                    var fromTo = prepare(this.node, this.property, to);
                    if (!fromTo) {
                        this.cancel(to);
                        return this;
                    }
                    this.to = fromTo[1];
                    this.cancelSetTransitionCSS = requestFrame(this.bSetTransitionCSS);
                    return this;
                },
                setTransitionCSS: function (time) {
                    delete this.cancelSetTransitionCSS;
                    this.resetCSS(true);
                    this.cancelSetStyleCSS = requestFrame(this.bSetStyleCSS);
                },
                setStyleCSS: function (time) {
                    delete this.cancelSetStyleCSS;
                    var duration = this.duration;
                    this.cancelComplete = setTimeout(this.bComplete, duration);
                    this.endTime = time + duration;
                    this.set(this.to);
                },
                complete: function () {
                    delete this.cancelComplete;
                    this.resetCSS();
                    this.callback(this.endTime);
                },
                stop: function (hard) {
                    if (this.cancelExit) {
                        this.cancelExit();
                        delete this.cancelExit;
                    } else if (this.cancelSetTransitionCSS) {
                        this.cancelSetTransitionCSS();
                        delete this.cancelSetTransitionCSS;
                    } else if (this.cancelSetStyleCSS) {
                        this.cancelSetStyleCSS();
                        delete this.cancelSetStyleCSS;
                        if (hard)
                            this.resetCSS();
                    } else if (this.cancelComplete) {
                        clearTimeout(this.cancelComplete);
                        delete this.cancelComplete;
                        if (hard) {
                            this.resetCSS();
                            this.set(this.get());
                        }
                    }
                    return this;
                },
                resetCSS: function (inclusive) {
                    var rules = compute(this.node), properties = (rules(transitionName + 'Property').replace(/\s+/g, '') || 'all').split(','), durations = (rules(transitionName + 'Duration').replace(/\s+/g, '') || '0s').split(','), equations = (rules(transitionName + 'TimingFunction').replace(/\s+/g, '') || 'ease').match(/cubic-bezier\([\d-.,]+\)|([a-z-]+)/g);
                    remove3('all', properties, durations, equations);
                    remove3(this.hproperty, properties, durations, equations);
                    if (inclusive) {
                        properties.push(this.hproperty);
                        durations.push(this.duration + 'ms');
                        equations.push('cubic-bezier(' + this.equation + ')');
                    }
                    var nodeStyle = this.node.style;
                    nodeStyle[transitionName + 'Property'] = properties;
                    nodeStyle[transitionName + 'Duration'] = durations;
                    nodeStyle[transitionName + 'TimingFunction'] = equations;
                },
                parseEquation: function (equation) {
                    if (typeof equation === 'string')
                        return CSSAnimation.parent.parseEquation.call(this, equation, true);
                }
            });
        var BaseAnimation = transitionName ? CSSAnimation : JSAnimation;
        var moofx = function (x, y) {
            return typeof x === 'function' ? fx(x) : elements(x, y);
        };
        elements.implement({
            animate: function (A, B, C) {
                var styles = A, options = B;
                if (typeof A === 'string') {
                    styles = {};
                    styles[A] = B;
                    options = C;
                }
                if (options == null)
                    options = {};
                var type = typeof options;
                options = type === 'function' ? { callback: options } : type === 'string' || type === 'number' ? { duration: options } : options;
                var callback = options.callback || function () {
                    }, completed = 0, length = 0;
                options.callback = function (t) {
                    if (++completed === length)
                        callback(t);
                };
                for (var property in styles) {
                    var value = styles[property], property = camelize(property);
                    this.forEach(function (node) {
                        length++;
                        var self = elements(node), anims = self._animations || (self._animations = {});
                        var anim = anims[property] || (anims[property] = new BaseAnimation(node, property));
                        anim.setOptions(options).start(value);
                    });
                }
                return this;
            },
            style: function (A, B) {
                var styles = A;
                if (typeof A === 'string') {
                    styles = {};
                    styles[A] = B;
                }
                for (var property in styles) {
                    var value = styles[property], set = setter(property = camelize(property));
                    this.forEach(function (node) {
                        var self = elements(node), anims = self._animations, anim;
                        if (anims && (anim = anims[property]))
                            anim.stop(true);
                        set.call(node, value);
                    });
                }
                return this;
            },
            compute: function (property) {
                property = camelize(property);
                var node = this[0];
                if (property === 'transform' && parseTransform2d)
                    return compute(node)(transformName);
                var value = getter(property).call(node);
                return value != null ? value.replace(rgLength, function (match, value, unit) {
                    return unit === 'px' ? match : pixelRatio(node, unit) * value + 'px';
                }) : '';
            }
        });
        moofx.parse = function (property, value, normalize) {
            return (parsers[camelize(property)] || parse)(value, normalize);
        };
        module.exports = moofx;
    },
    '2l': function (require, module, exports, global) {
        'use strict';
        var prime = require('2u'), requestFrame = require('2j').request, bezier = require('2v');
        var map = require('2w');
        var sDuration = '([\\d.]+)(s|ms)?', sCubicBezier = 'cubic-bezier\\(([-.\\d]+),([-.\\d]+),([-.\\d]+),([-.\\d]+)\\)';
        var rDuration = RegExp(sDuration), rCubicBezier = RegExp(sCubicBezier), rgCubicBezier = RegExp(sCubicBezier, 'g');
        var equations = {
                'default': 'cubic-bezier(0.25, 0.1, 0.25, 1.0)',
                'linear': 'cubic-bezier(0, 0, 1, 1)',
                'ease-in': 'cubic-bezier(0.42, 0, 1.0, 1.0)',
                'ease-out': 'cubic-bezier(0, 0, 0.58, 1.0)',
                'ease-in-out': 'cubic-bezier(0.42, 0, 0.58, 1.0)'
            };
        equations.ease = equations['default'];
        var compute = function (from, to, delta) {
            return (to - from) * delta + from;
        };
        var divide = function (string) {
            var numbers = [];
            var template = (string + '').replace(/[-.\d]+/g, function (number) {
                    numbers.push(+number);
                    return '@';
                });
            return [
                numbers,
                template
            ];
        };
        var Fx = prime({
                constructor: function Fx(render, options) {
                    this.setOptions(options);
                    this.render = render || function () {
                    };
                    var self = this;
                    this.bStep = function (t) {
                        return self.step(t);
                    };
                    this.bExit = function (time) {
                        self.exit(time);
                    };
                },
                setOptions: function (options) {
                    if (options == null)
                        options = {};
                    if (!(this.duration = this.parseDuration(options.duration || '500ms')))
                        throw new Error('invalid duration');
                    if (!(this.equation = this.parseEquation(options.equation || 'default')))
                        throw new Error('invalid equation');
                    this.callback = options.callback || function () {
                    };
                    return this;
                },
                parseDuration: function (duration) {
                    if (duration = (duration + '').match(rDuration)) {
                        var time = +duration[1], unit = duration[2] || 'ms';
                        if (unit === 's')
                            return time * 1000;
                        if (unit === 'ms')
                            return time;
                    }
                },
                parseEquation: function (equation, array) {
                    var type = typeof equation;
                    if (type === 'function') {
                        return equation;
                    } else if (type === 'string') {
                        equation = equations[equation] || equation;
                        var match = equation.replace(/\s+/g, '').match(rCubicBezier);
                        if (match) {
                            equation = map(match.slice(1), function (v) {
                                return +v;
                            });
                            if (array)
                                return equation;
                            if (equation.toString() === '0,0,1,1')
                                return function (x) {
                                    return x;
                                };
                            type = 'object';
                        }
                    }
                    if (type === 'object') {
                        return bezier(equation[0], equation[1], equation[2], equation[3], 1000 / 60 / this.duration / 4);
                    }
                },
                cancel: function (to) {
                    this.to = to;
                    this.cancelExit = requestFrame(this.bExit);
                },
                exit: function (time) {
                    this.render(this.to);
                    delete this.cancelExit;
                    this.callback(time);
                },
                start: function (from, to) {
                    this.stop();
                    if (this.duration === 0) {
                        this.cancel(to);
                        return this;
                    }
                    this.isArray = false;
                    this.isNumber = false;
                    var fromType = typeof from, toType = typeof to;
                    if (fromType === 'object' && toType === 'object') {
                        this.isArray = true;
                    } else if (fromType === 'number' && toType === 'number') {
                        this.isNumber = true;
                    }
                    var from_ = divide(from), to_ = divide(to);
                    this.from = from_[0];
                    this.to = to_[0];
                    this.templateFrom = from_[1];
                    this.templateTo = to_[1];
                    if (this.from.length !== this.to.length || this.from.toString() === this.to.toString()) {
                        this.cancel(to);
                        return this;
                    }
                    delete this.time;
                    this.length = this.from.length;
                    this.cancelStep = requestFrame(this.bStep);
                    return this;
                },
                stop: function () {
                    if (this.cancelExit) {
                        this.cancelExit();
                        delete this.cancelExit;
                    } else if (this.cancelStep) {
                        this.cancelStep();
                        delete this.cancelStep;
                    }
                    return this;
                },
                step: function (now) {
                    this.time || (this.time = now);
                    var factor = (now - this.time) / this.duration;
                    if (factor > 1)
                        factor = 1;
                    var delta = this.equation(factor), from = this.from, to = this.to, tpl = this.templateTo;
                    for (var i = 0, l = this.length; i < l; i++) {
                        var f = from[i], t = to[i];
                        tpl = tpl.replace('@', t !== f ? compute(f, t, delta) : t);
                    }
                    this.render(this.isArray ? tpl.split(',') : this.isNumber ? +tpl : tpl, factor);
                    if (factor !== 1) {
                        this.cancelStep = requestFrame(this.bStep);
                    } else {
                        delete this.cancelStep;
                        this.callback(now);
                    }
                }
            });
        var fx = function (render) {
            var ffx = new Fx(render);
            return {
                start: function (from, to, options) {
                    var type = typeof options;
                    ffx.setOptions(type === 'function' ? { callback: options } : type === 'string' || type === 'number' ? { duration: options } : options).start(from, to);
                    return this;
                },
                stop: function () {
                    ffx.stop();
                    return this;
                }
            };
        };
        fx.prototype = Fx.prototype;
        module.exports = fx;
    },
    '2m': function (require, module, exports, global) {
        function lerp(ratio, start, end) {
            return start + (end - start) * ratio;
        }
        module.exports = lerp;
    },
    '2n': function (require, module, exports, global) {
        function norm(val, min, max) {
            return (val - min) / (max - min);
        }
        module.exports = norm;
    },
    '2o': function (require, module, exports, global) {
        var isArray = require('m');
        function toNumber(val) {
            if (typeof val === 'number')
                return val;
            if (!val)
                return 0;
            if (typeof val === 'string')
                return parseFloat(val);
            if (isArray(val))
                return NaN;
            return Number(val);
        }
        module.exports = toNumber;
    },
    '2p': function (require, module, exports, global) {
        var kindOf = require('1h');
        var isPlainObject = require('2q');
        var mixIn = require('1f');
        function clone(val) {
            switch (kindOf(val)) {
            case 'Object':
                return cloneObject(val);
            case 'Array':
                return cloneArray(val);
            case 'RegExp':
                return cloneRegExp(val);
            case 'Date':
                return cloneDate(val);
            default:
                return val;
            }
        }
        function cloneObject(source) {
            if (isPlainObject(source)) {
                return mixIn({}, source);
            } else {
                return source;
            }
        }
        function cloneRegExp(r) {
            var flags = '';
            flags += r.multiline ? 'm' : '';
            flags += r.global ? 'g' : '';
            flags += r.ignorecase ? 'i' : '';
            return new RegExp(r.source, flags);
        }
        function cloneDate(date) {
            return new Date(+date);
        }
        function cloneArray(arr) {
            return arr.slice();
        }
        module.exports = clone;
    },
    '2q': function (require, module, exports, global) {
        function isPlainObject(value) {
            return !!value && typeof value === 'object' && value.constructor === Object;
        }
        module.exports = isPlainObject;
    },
    '2r': function (require, module, exports, global) {
        var choice = require('2s');
        var _chars = '0123456789abcdef'.split('');
        function randHex(size) {
            size = size && size > 0 ? size : 6;
            var str = '';
            while (size--) {
                str += choice(_chars);
            }
            return str;
        }
        module.exports = randHex;
    },
    '2s': function (require, module, exports, global) {
        var randInt = require('34');
        var isArray = require('m');
        function choice(items) {
            var target = arguments.length === 1 && isArray(items) ? items : arguments;
            return target[randInt(0, target.length - 1)];
        }
        module.exports = choice;
    },
    '2t': function (require, module, exports, global) {
        'use strict';
        var indexOf = function (self, item, from) {
            for (var l = self.length >>> 0, i = from < 0 ? Math.max(0, l + from) : from || 0; i < l; i++) {
                if (self[i] === item)
                    return i;
            }
            return -1;
        };
        module.exports = indexOf;
    },
    '2u': function (require, module, exports, global) {
        'use strict';
        var hasOwn = require('35'), forIn = require('36'), mixIn = require('37'), filter = require('38'), create = require('39'), type = require('3a');
        var defineProperty = Object.defineProperty, getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;
        try {
            defineProperty({}, '~', {});
            getOwnPropertyDescriptor({}, '~');
        } catch (e) {
            defineProperty = null;
            getOwnPropertyDescriptor = null;
        }
        var define = function (value, key, from) {
            defineProperty(this, key, getOwnPropertyDescriptor(from, key) || {
                writable: true,
                enumerable: true,
                configurable: true,
                value: value
            });
        };
        var copy = function (value, key) {
            this[key] = value;
        };
        var implement = function (proto) {
            forIn(proto, defineProperty ? define : copy, this.prototype);
            return this;
        };
        var verbs = /^constructor|inherits|mixin$/;
        var prime = function (proto) {
            if (type(proto) === 'function')
                proto = { constructor: proto };
            var superprime = proto.inherits;
            var constructor = hasOwn(proto, 'constructor') ? proto.constructor : superprime ? function () {
                    return superprime.apply(this, arguments);
                } : function () {
                };
            if (superprime) {
                mixIn(constructor, superprime);
                var superproto = superprime.prototype;
                var cproto = constructor.prototype = create(superproto);
                constructor.parent = superproto;
                cproto.constructor = constructor;
            }
            if (!constructor.implement)
                constructor.implement = implement;
            var mixins = proto.mixin;
            if (mixins) {
                if (type(mixins) !== 'array')
                    mixins = [mixins];
                for (var i = 0; i < mixins.length; i++)
                    constructor.implement(create(mixins[i].prototype));
            }
            return constructor.implement(filter(proto, function (value, key) {
                return !key.match(verbs);
            }));
        };
        module.exports = prime;
    },
    '2v': function (require, module, exports, global) {
        module.exports = function (x1, y1, x2, y2, epsilon) {
            var curveX = function (t) {
                var v = 1 - t;
                return 3 * v * v * t * x1 + 3 * v * t * t * x2 + t * t * t;
            };
            var curveY = function (t) {
                var v = 1 - t;
                return 3 * v * v * t * y1 + 3 * v * t * t * y2 + t * t * t;
            };
            var derivativeCurveX = function (t) {
                var v = 1 - t;
                return 3 * (2 * (t - 1) * t + v * v) * x1 + 3 * (-t * t * t + 2 * v * t) * x2;
            };
            return function (t) {
                var x = t, t0, t1, t2, x2, d2, i;
                for (t2 = x, i = 0; i < 8; i++) {
                    x2 = curveX(t2) - x;
                    if (Math.abs(x2) < epsilon)
                        return curveY(t2);
                    d2 = derivativeCurveX(t2);
                    if (Math.abs(d2) < 0.000001)
                        break;
                    t2 = t2 - x2 / d2;
                }
                t0 = 0, t1 = 1, t2 = x;
                if (t2 < t0)
                    return curveY(t0);
                if (t2 > t1)
                    return curveY(t1);
                while (t0 < t1) {
                    x2 = curveX(t2);
                    if (Math.abs(x2 - x) < epsilon)
                        return curveY(t2);
                    if (x > x2)
                        t0 = t2;
                    else
                        t1 = t2;
                    t2 = (t1 - t0) * 0.5 + t0;
                }
                return curveY(t2);
            };
        };
    },
    '2w': function (require, module, exports, global) {
        'use strict';
        var map = function (self, method, context) {
            var length = self.length >>> 0, results = Array(length);
            for (var i = 0, l = length; i < l; i++) {
                results[i] = method.call(context, self[i], i, self);
            }
            return results;
        };
        module.exports = map;
    },
    '2x': function (require, module, exports, global) {
        'use strict';
        var camelize = function (self) {
            return (self + '').replace(/-\D/g, function (match) {
                return match.charAt(1).toUpperCase();
            });
        };
        module.exports = camelize;
    },
    '2y': function (require, module, exports, global) {
        'use strict';
        var trim = require('3b');
        var clean = function (self) {
            return trim((self + '').replace(/\s+/g, ' '));
        };
        module.exports = clean;
    },
    '2z': function (require, module, exports, global) {
        'use strict';
        var capitalize = function (self) {
            return (self + '').replace(/\b[a-z]/g, function (match) {
                return match.toUpperCase();
            });
        };
        module.exports = capitalize;
    },
    '30': function (require, module, exports, global) {
        'use strict';
        var hyphenate = function (self) {
            return (self + '').replace(/[A-Z]/g, function (match) {
                return '-' + match.toLowerCase();
            });
        };
        module.exports = hyphenate;
    },
    '31': function (require, module, exports, global) {
        'use strict';
        var forEach = function (self, method, context) {
            for (var i = 0, l = self.length >>> 0; i < l; i++) {
                if (method.call(context, self[i], i, self) === false)
                    break;
            }
            return self;
        };
        module.exports = forEach;
    },
    '32': function (require, module, exports, global) {
        'use strict';
        var prime = require('2u'), forEach = require('31'), map = require('2w'), filter = require('3c'), every = require('3d'), some = require('3e');
        var uniqueIndex = 0;
        var uniqueID = function (n) {
            return n === global ? 'global' : n.uniqueNumber || (n.uniqueNumber = 'n:' + (uniqueIndex++).toString(36));
        };
        var instances = {};
        var $ = prime({
                constructor: function $(n, context) {
                    if (n == null)
                        return this && this.constructor === $ ? new elements() : null;
                    var self = n;
                    if (n.constructor !== elements) {
                        self = new elements();
                        var uid;
                        if (typeof n === 'string') {
                            if (!self.search)
                                return null;
                            self[self.length++] = context || document;
                            return self.search(n);
                        }
                        if (n.nodeType || n === global) {
                            self[self.length++] = n;
                        } else if (n.length) {
                            var uniques = {};
                            for (var i = 0, l = n.length; i < l; i++) {
                                var nodes = $(n[i], context);
                                if (nodes && nodes.length)
                                    for (var j = 0, k = nodes.length; j < k; j++) {
                                        var node = nodes[j];
                                        uid = uniqueID(node);
                                        if (!uniques[uid]) {
                                            self[self.length++] = node;
                                            uniques[uid] = true;
                                        }
                                    }
                            }
                        }
                    }
                    if (!self.length)
                        return null;
                    if (self.length === 1) {
                        uid = uniqueID(self[0]);
                        return instances[uid] || (instances[uid] = self);
                    }
                    return self;
                }
            });
        var elements = prime({
                inherits: $,
                constructor: function elements() {
                    this.length = 0;
                },
                unlink: function () {
                    return this.map(function (node, i) {
                        delete instances[uniqueID(node)];
                        return node;
                    });
                },
                forEach: function (method, context) {
                    return forEach(this, method, context);
                },
                map: function (method, context) {
                    return map(this, method, context);
                },
                filter: function (method, context) {
                    return filter(this, method, context);
                },
                every: function (method, context) {
                    return every(this, method, context);
                },
                some: function (method, context) {
                    return some(this, method, context);
                }
            });
        module.exports = $;
    },
    '33': function (require, module, exports, global) {
        'use strict';
        var length = function (a) {
            return Math.sqrt(a[0] * a[0] + a[1] * a[1]);
        };
        var normalize = function (a) {
            var l = length(a);
            return l ? [
                a[0] / l,
                a[1] / l
            ] : [
                0,
                0
            ];
        };
        var dot = function (a, b) {
            return a[0] * b[0] + a[1] * b[1];
        };
        var atan2 = Math.atan2;
        var combine = function (a, b, ascl, bscl) {
            return [
                ascl * a[0] + bscl * b[0],
                ascl * a[1] + bscl * b[1]
            ];
        };
        module.exports = function (a, b, c, d, tx, ty) {
            if (a * d - b * c === 0)
                return false;
            var translate = [
                    tx,
                    ty
                ];
            var m = [
                    [
                        a,
                        b
                    ],
                    [
                        c,
                        d
                    ]
                ];
            var scale = [length(m[0])];
            m[0] = normalize(m[0]);
            var skew = dot(m[0], m[1]);
            m[1] = combine(m[1], m[0], 1, -skew);
            scale[1] = length(m[1]);
            skew /= scale[1];
            var rotate = atan2(m[0][1], m[0][0]);
            return [
                translate,
                rotate,
                skew,
                scale
            ];
        };
    },
    '34': function (require, module, exports, global) {
        var MIN_INT = require('3f');
        var MAX_INT = require('3g');
        var rand = require('3h');
        function randInt(min, max) {
            min = min == null ? MIN_INT : ~~min;
            max = max == null ? MAX_INT : ~~max;
            return Math.round(rand(min - 0.5, max + 0.499999999999));
        }
        module.exports = randInt;
    },
    '35': function (require, module, exports, global) {
        'use strict';
        var hasOwnProperty = Object.hasOwnProperty;
        var hasOwn = function (self, key) {
            return hasOwnProperty.call(self, key);
        };
        module.exports = hasOwn;
    },
    '36': function (require, module, exports, global) {
        'use strict';
        var has = require('35');
        var forIn = function (self, method, context) {
            for (var key in self)
                if (method.call(context, self[key], key, self) === false)
                    break;
            return self;
        };
        if (!{ valueOf: 0 }.propertyIsEnumerable('valueOf')) {
            var buggy = 'constructor,toString,valueOf,hasOwnProperty,isPrototypeOf,propertyIsEnumerable,toLocaleString'.split(',');
            var proto = Object.prototype;
            forIn = function (self, method, context) {
                for (var key in self)
                    if (method.call(context, self[key], key, self) === false)
                        return self;
                for (var i = 0; key = buggy[i]; i++) {
                    var value = self[key];
                    if ((value !== proto[key] || has(self, key)) && method.call(context, value, key, self) === false)
                        break;
                }
                return self;
            };
        }
        module.exports = forIn;
    },
    '37': function (require, module, exports, global) {
        'use strict';
        var forOwn = require('3i');
        var copy = function (value, key) {
            this[key] = value;
        };
        var mixIn = function (self) {
            for (var i = 1, l = arguments.length; i < l; i++)
                forOwn(arguments[i], copy, self);
            return self;
        };
        module.exports = mixIn;
    },
    '38': function (require, module, exports, global) {
        'use strict';
        var forIn = require('36');
        var filter = function (self, method, context) {
            var results = {};
            forIn(self, function (value, key) {
                if (method.call(context, value, key, self))
                    results[key] = value;
            });
            return results;
        };
        module.exports = filter;
    },
    '39': function (require, module, exports, global) {
        'use strict';
        var create = function (self) {
            var constructor = function () {
            };
            constructor.prototype = self;
            return new constructor();
        };
        module.exports = create;
    },
    '3a': function (require, module, exports, global) {
        'use strict';
        var toString = Object.prototype.toString, types = /number|object|array|string|function|date|regexp|boolean/;
        var type = function (object) {
            if (object == null)
                return 'null';
            var string = toString.call(object).slice(8, -1).toLowerCase();
            if (string === 'number' && isNaN(object))
                return 'null';
            if (types.test(string))
                return string;
            return 'object';
        };
        module.exports = type;
    },
    '3b': function (require, module, exports, global) {
        'use strict';
        var trim = function (self) {
            return (self + '').replace(/^\s+|\s+$/g, '');
        };
        module.exports = trim;
    },
    '3c': function (require, module, exports, global) {
        'use strict';
        var filter = function (self, method, context) {
            var results = [];
            for (var i = 0, l = self.length >>> 0; i < l; i++) {
                var value = self[i];
                if (method.call(context, value, i, self))
                    results.push(value);
            }
            return results;
        };
        module.exports = filter;
    },
    '3d': function (require, module, exports, global) {
        'use strict';
        var every = function (self, method, context) {
            for (var i = 0, l = self.length >>> 0; i < l; i++) {
                if (!method.call(context, self[i], i, self))
                    return false;
            }
            return true;
        };
        module.exports = every;
    },
    '3e': function (require, module, exports, global) {
        'use strict';
        var some = function (self, method, context) {
            for (var i = 0, l = self.length >>> 0; i < l; i++) {
                if (method.call(context, self[i], i, self))
                    return true;
            }
            return false;
        };
        module.exports = some;
    },
    '3f': function (require, module, exports, global) {
        module.exports = -2147483648;
    },
    '3g': function (require, module, exports, global) {
        module.exports = 2147483647;
    },
    '3h': function (require, module, exports, global) {
        var random = require('3j');
        var MIN_INT = require('3f');
        var MAX_INT = require('3g');
        function rand(min, max) {
            min = min == null ? MIN_INT : min;
            max = max == null ? MAX_INT : max;
            return min + (max - min) * random();
        }
        module.exports = rand;
    },
    '3i': function (require, module, exports, global) {
        'use strict';
        var forIn = require('36'), hasOwn = require('35');
        var forOwn = function (self, method, context) {
            forIn(self, function (value, key) {
                if (hasOwn(self, key))
                    return method.call(context, value, key, self);
            });
            return self;
        };
        module.exports = forOwn;
    },
    '3j': function (require, module, exports, global) {
        function random() {
            return random.get();
        }
        random.get = Math.random;
        module.exports = random;
    }
}, this));
//# sourceMappingURL=main.js.map
