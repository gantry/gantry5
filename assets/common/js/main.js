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
        'use strict';
        module.exports = { menu: require('1') };
    },
    '1': function (require, module, exports, global) {
        'use strict';
        var r = require('2'), $ = require('3'), zen = require('4');
        var MBP = 768;
        var resetSelectedActive = function () {
            var body = $('body'), mainNav = $('.g-main-nav'), selected, actives, levels;
            body.removeClass('g-nav-overlay-active');
            selected = mainNav.search('.g-selected');
            actives = mainNav.search('.g-active');
            levels = mainNav.search('.g-toplevel, .g-sublevel');
            if (selected) {
                selected.removeClass('g-selected');
            }
            if (actives) {
                actives.removeClass('g-active').addClass('g-inactive');
            }
            if (levels) {
                levels.removeClass('g-slide-out');
            }
        };
        var adjustOnViewportChange = function () {
            var body = $('body'), topLevel = $('.g-toplevel'), pageSurround = $('#g-page-surround'), mainNav = pageSurround.search('.g-main-nav'), mobileNav = pageSurround.nextSibling('.g-mobile-nav');
            if (window.innerWidth < MBP) {
                resetSelectedActive();
                if (mobileNav) {
                    mobileNav.appendChild(topLevel);
                }
            } else {
                resetSelectedActive();
                if (mainNav) {
                    mainNav.appendChild(topLevel);
                }
                if (body.hasClass('g-mobile-nav-active')) {
                    body.removeClass('g-mobile-nav-active');
                }
            }
        };
        $(window).on('load', adjustOnViewportChange);
        $(window).on('resize', adjustOnViewportChange);
        r(function () {
            var body = $('body'), pageSurround = $('#g-page-surround'), navOverlay = zen('div.g-nav-overlay'), mobileNav = zen('nav.g-main-nav.g-mobile-nav'), mobileNavToggle = zen('div.g-mobile-nav-toggle');
            body.delegate('click', '.g-menu-item [data-g-menuparent]', function (e, el) {
                el = $(el);
                var dropdown = el.nextSibling('.g-dropdown'), parent = el.parent('.g-menu-item');
                if (!dropdown) {
                    return;
                }
                if (dropdown.hasClass('g-inactive')) {
                    el.addClass('g-selected');
                    dropdown.removeClass('g-inactive').addClass('g-active');
                    el.parent('ul').addClass('g-slide-out');
                    var lists = parent.search('~~ .g-menu-item ul');
                    if (lists) {
                        lists.removeClass('g-active').addClass('g-inactive');
                    }
                    var children = el.children('.g-menu-item-content');
                    if (children) {
                        children.removeClass('g-selected');
                    }
                    if (window.innerWidth > MBP) {
                        body.addClass('g-nav-overlay-active');
                    }
                } else {
                    resetSelectedActive();
                }
            });
            body.delegate('click', '.g-menu-item .g-level-1', function (e, el) {
                el = $(el);
                var dropdown = el.parent('.g-dropdown'), toplevel = el.parent('.g-toplevel');
                if (dropdown || toplevel) {
                    if (dropdown) {
                        dropdown.removeClass('g-active').addClass('g-inactive');
                    }
                    if (toplevel) {
                        toplevel.removeClass('g-slide-out');
                    }
                }
            });
            body.delegate('click', '.g-menu-item .g-go-back', function (e, el) {
                el = $(el);
                var dropdown = el.parent('.g-dropdown'), parent = el.parent('.g-menu-item');
                if (dropdown) {
                    var parentSublevel = dropdown.parent('.g-sublevel');
                    dropdown.removeClass('g-active').addClass('g-inactive');
                    if (parentSublevel) {
                        parentSublevel.removeClass('g-slide-out');
                    }
                }
                if (parent) {
                    parent.search('> .g-menu-item-content').removeClass('g-selected');
                }
            });
            body.delegate('click', '.g-nav-overlay', function () {
                if (window.innerWidth < MBP) {
                    body.toggleClass('g-mobile-nav-active');
                } else {
                    body.toggleClass('g-nav-overlay-active');
                    resetSelectedActive();
                }
            });
            body.delegate('click', '.g-mobile-nav-toggle', function () {
                body.toggleClass('g-mobile-nav-active');
            });
            body.appendChild(mobileNav);
            navOverlay.bottom(pageSurround);
            mobileNavToggle.bottom(pageSurround);
            adjustOnViewportChange();
        });
        module.exports = {};
    },
    '2': function (require, module, exports, global) {
        !function (name, definition) {
            if (typeof module != 'undefined')
                module.exports = definition();
            else if (typeof define == 'function' && typeof define.amd == 'object')
                define(definition);
            else
                this[name] = definition();
        }('domready', function () {
            var fns = [], listener, doc = document, hack = doc.documentElement.doScroll, domContentLoaded = 'DOMContentLoaded', loaded = (hack ? /^loaded|^c/ : /^loaded|^i|^c/).test(doc.readyState);
            if (!loaded)
                doc.addEventListener(domContentLoaded, listener = function () {
                    doc.removeEventListener(domContentLoaded, listener);
                    loaded = 1;
                    while (listener = fns.shift())
                        listener();
                });
            return function (fn) {
                loaded ? fn() : fns.push(fn);
            };
        });
    },
    '3': function (require, module, exports, global) {
        'use strict';
        var $ = require('5');
        require('6');
        require('7');
        require('8');
        require('9');
        require('a');
        module.exports = $;
    },
    '4': function (require, module, exports, global) {
        'use strict';
        var forEach = require('b'), map = require('c');
        var parse = require('d');
        var $ = require('5');
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
                            node.setAttribute(attribute.name, attribute.value || '');
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
    '5': function (require, module, exports, global) {
        'use strict';
        var prime = require('e');
        var forEach = require('b'), map = require('c'), filter = require('f'), every = require('g'), some = require('h');
        var index = 0, __dc = document.__counter, counter = document.__counter = (__dc ? parseInt(__dc, 36) + 1 : 0).toString(36), key = 'uid:' + counter;
        var uniqueID = function (n) {
            if (n === window)
                return 'window';
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
                        if (n.nodeType || n === window) {
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
    '6': function (require, module, exports, global) {
        'use strict';
        var $ = require('5');
        var trim = require('j'), forEach = require('b'), filter = require('f'), indexOf = require('k');
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
            },
            toggleClass: function (className, force) {
                var add = force !== undefined ? force : !this.hasClass(className);
                if (add)
                    this.addClass(className);
                else
                    this.removeClass(className);
                return !!add;
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
    '7': function (require, module, exports, global) {
        'use strict';
        var Emitter = require('i');
        var $ = require('5');
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
    '8': function (require, module, exports, global) {
        'use strict';
        var $ = require('5');
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
    '9': function (require, module, exports, global) {
        'use strict';
        var map = require('c');
        var slick = require('l');
        var $ = require('5');
        var gen = function (combinator, expression) {
            return map(slick.parse(expression || '*'), function (part) {
                return combinator + ' ' + part;
            }).join(', ');
        };
        var push_ = Array.prototype.push;
        $.implement({
            search: function (expression) {
                if (this.length === 1)
                    return $(slick.search(expression, this[0], new $()));
                var buffer = [];
                for (var i = 0, node; node = this[i]; i++)
                    push_.apply(buffer, slick.search(expression, node));
                buffer = $(buffer);
                return buffer && buffer.sort();
            },
            find: function (expression) {
                if (this.length === 1)
                    return $(slick.find(expression, this[0]));
                for (var i = 0, node; node = this[i]; i++) {
                    var found = slick.find(expression, node);
                    if (found)
                        return $(found);
                }
                return null;
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
            nextSiblings: function (expression) {
                return this.search(gen('~', expression));
            },
            nextSibling: function (expression) {
                return this.find(gen('+', expression));
            },
            previousSiblings: function (expression) {
                return this.search(gen('!~', expression));
            },
            previousSibling: function (expression) {
                return this.find(gen('!+', expression));
            },
            children: function (expression) {
                return this.search(gen('>', expression));
            },
            firstChild: function (expression) {
                return this.find(gen('^', expression));
            },
            lastChild: function (expression) {
                return this.find(gen('!^', expression));
            },
            parent: function (expression) {
                var buffer = [];
                loop:
                    for (var i = 0, node; node = this[i]; i++)
                        while ((node = node.parentNode) && node !== document) {
                            if (!expression || slick.matches(node, expression)) {
                                buffer.push(node);
                                break loop;
                                break;
                            }
                        }
                return $(buffer);
            },
            parents: function (expression) {
                var buffer = [];
                for (var i = 0, node; node = this[i]; i++)
                    while ((node = node.parentNode) && node !== document) {
                        if (!expression || slick.matches(node, expression))
                            buffer.push(node);
                    }
                return $(buffer);
            }
        });
        module.exports = $;
    },
    'a': function (require, module, exports, global) {
        'use strict';
        var Map = require('m');
        var $ = require('7');
        require('9');
        $.implement({
            delegate: function (event, selector, handle) {
                return this.forEach(function (node) {
                    var self = $(node);
                    var delegation = self._delegation || (self._delegation = {}), events = delegation[event] || (delegation[event] = {}), map = events[selector] || (events[selector] = new Map());
                    if (map.get(handle))
                        return;
                    var action = function (e) {
                        var target = $(e.target || e.srcElement), match = target.matches(selector) ? target : target.parent(selector);
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
                        map.remove(action);
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
    'b': function (require, module, exports, global) {
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
    'c': function (require, module, exports, global) {
        var makeIterator = require('n');
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
    'd': function (require, module, exports, global) {
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
            var original = expression, replaced;
            while (expression) {
                replaced = expression.replace(slickRe, function () {
                    return replacer.apply(self, arguments);
                });
                if (replaced === expression)
                    throw new Error(original + ' is an invalid expression');
                expression = replaced;
            }
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
    'e': function (require, module, exports, global) {
        'use strict';
        var hasOwn = require('o'), mixIn = require('p'), create = require('q'), kindOf = require('r');
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
    'f': function (require, module, exports, global) {
        var makeIterator = require('n');
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
    'g': function (require, module, exports, global) {
        var makeIterator = require('n');
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
    'h': function (require, module, exports, global) {
        var makeIterator = require('n');
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
    'i': function (require, module, exports, global) {
        'use strict';
        var indexOf = require('k'), forEach = require('b');
        var prime = require('e'), defer = require('s');
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
    'j': function (require, module, exports, global) {
        var toString = require('t');
        var WHITE_SPACES = require('u');
        var ltrim = require('v');
        var rtrim = require('w');
        function trim(str, chars) {
            str = toString(str);
            chars = chars || WHITE_SPACES;
            return ltrim(rtrim(str, chars), chars);
        }
        module.exports = trim;
    },
    'k': function (require, module, exports, global) {
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
    'l': function (require, module, exports, global) {
        'use strict';
        module.exports = 'document' in global ? require('x') : { parse: require('d') };
    },
    'm': function (require, module, exports, global) {
        'use strict';
        var indexOf = require('k');
        var prime = require('e');
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
    'n': function (require, module, exports, global) {
        var identity = require('y');
        var prop = require('z');
        var deepMatches = require('10');
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
    'o': function (require, module, exports, global) {
        function hasOwn(obj, prop) {
            return Object.prototype.hasOwnProperty.call(obj, prop);
        }
        module.exports = hasOwn;
    },
    'p': function (require, module, exports, global) {
        var forOwn = require('11');
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
    'q': function (require, module, exports, global) {
        var mixIn = require('p');
        function createObject(parent, props) {
            function F() {
            }
            F.prototype = parent;
            return mixIn(new F(), props);
        }
        module.exports = createObject;
    },
    'r': function (require, module, exports, global) {
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
    's': function (require, module, exports, global) {
        'use strict';
        var kindOf = require('r'), now = require('12'), forEach = require('b'), indexOf = require('k');
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
    't': function (require, module, exports, global) {
        function toString(val) {
            return val == null ? '' : val.toString();
        }
        module.exports = toString;
    },
    'u': function (require, module, exports, global) {
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
    'v': function (require, module, exports, global) {
        var toString = require('t');
        var WHITE_SPACES = require('u');
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
    'w': function (require, module, exports, global) {
        var toString = require('t');
        var WHITE_SPACES = require('u');
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
    'x': function (require, module, exports, global) {
        'use strict';
        var parse = require('d');
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
                            cs = f;
                        }
                        if (i === 0)
                            found = f;
                        else
                            for (var l = 0; l < f.length; l++)
                                push(f[l]);
                    }
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
    'y': function (require, module, exports, global) {
        function identity(val) {
            return val;
        }
        module.exports = identity;
    },
    'z': function (require, module, exports, global) {
        function prop(name) {
            return function (obj) {
                return obj[name];
            };
        }
        module.exports = prop;
    },
    '10': function (require, module, exports, global) {
        var forOwn = require('11');
        var isArray = require('13');
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
    '11': function (require, module, exports, global) {
        var hasOwn = require('o');
        var forIn = require('14');
        function forOwn(obj, fn, thisObj) {
            forIn(obj, function (val, key) {
                if (hasOwn(obj, key)) {
                    return fn.call(thisObj, obj[key], key, obj);
                }
            });
        }
        module.exports = forOwn;
    },
    '12': function (require, module, exports, global) {
        function now() {
            return now.get();
        }
        now.get = typeof Date.now === 'function' ? Date.now : function () {
            return +new Date();
        };
        module.exports = now;
    },
    '13': function (require, module, exports, global) {
        var isKind = require('15');
        var isArray = Array.isArray || function (val) {
                return isKind(val, 'Array');
            };
        module.exports = isArray;
    },
    '14': function (require, module, exports, global) {
        var hasOwn = require('o');
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
    '15': function (require, module, exports, global) {
        var kindOf = require('r');
        function isKind(val, kind) {
            return kindOf(val) === kind;
        }
        module.exports = isKind;
    }
}, this));
//# sourceMappingURL=main.js.map
