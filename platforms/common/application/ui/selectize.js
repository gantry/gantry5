"use strict";
// selectize (v0.12.1) (commit: 4dae761)

var prime      = require('prime'),
    ready      = require('elements/domready'),
    zen        = require('elements/zen'),

    sifter     = require('sifter'),

    Emitter    = require('prime/emitter'),
    Bound      = require('prime-util/prime/bound'),
    Options    = require('prime-util/prime/options'),

    $          = require('../utils/elements.utils'),
    moofx      = require('moofx'),

    bind       = require('mout/function/bind'),
    forEach    = require('mout/collection/forEach'),
    indexOf    = require('mout/array/indexOf'),
    last       = require('mout/array/last'),
    debounce   = require('mout/function/debounce'),
    isArray    = require('mout/lang/isArray'),
    isBoolean  = require('mout/lang/isBoolean'),
    merge      = require('mout/object/merge'),
    unset      = require('mout/object/unset'),
    size       = require('mout/object/size'),
    values     = require('mout/object/values'),
    escapeHTML = require('mout/string/escapeHtml'),
    trim       = require('mout/string/trim'),
    slugify    = require('mout/string/slugify');


var IS_MAC                = /Mac/.test(navigator.userAgent),
    IS_IE                 = /MSIE 9/i.test(navigator.userAgent) || /MSIE 10/i.test(navigator.userAgent) || /rv:11.0/i.test(navigator.userAgent),
    COUNT                 = 0,

    KEY_A                 = 65,
    KEY_COMMA             = 188,
    KEY_RETURN            = 13,
    KEY_ESC               = 27,
    KEY_LEFT              = 37,
    KEY_UP                = 38,
    KEY_P                 = 80,
    KEY_RIGHT             = 39,
    KEY_DOWN              = 40,
    KEY_N                 = 78,
    KEY_BACKSPACE         = 8,
    KEY_DELETE            = 46,
    KEY_SHIFT             = 16,
    KEY_CMD               = IS_MAC ? 91 : 17,
    KEY_CTRL              = IS_MAC ? 18 : 17,
    KEY_TAB               = 9,

    TAG_SELECT            = 1,
    TAG_INPUT             = 2,

    // for now, android support in general is too spotty to support validity
    SUPPORTS_VALIDITY_API = !/android/i.test(window.navigator.userAgent) && !!document.createElement('form').validity;

var hash_key = function(value) {
    if (typeof value === 'undefined' || value === null) return null;
    if (typeof value === 'boolean') return value ? '1' : '0';
    return value + '';
};

var isset = function(object) {
    return typeof object !== 'undefined';
};

var escape_replace = function(str) {
    return (str + '').replace(/\$/g, '$$$$');
};

var once = function(fn) {
    var called = false;
    return function() {
        if (called) return;
        called = true;
        fn.apply(this, arguments);
    };
};

var debounce_events = function(self, types, fn) {
    var type;
    var trigger = self.emit;
    var event_args = {};

    // override trigger method
    self.emit = function() {
        var type = arguments[0];
        if (types.indexOf(type) !== -1) {
            event_args[type] = arguments;
        } else {
            return trigger.apply(self, arguments);
        }
    };

    // invoke provided function
    fn.apply(self, []);
    self.emit = trigger;

    // trigger queued events
    for (type in event_args) {
        if (event_args.hasOwnProperty(type)) {
            trigger.apply(self, event_args[type]);
        }
    }
};

var build_hash_table = function(key, objects) {
    if (!isArray(objects)) return objects;
    var i, n, table = {};
    for (i = 0, n = objects.length; i < n; i++) {
        if (objects[i].hasOwnProperty(key)) {
            table[objects[i][key]] = objects[i];
        }
    }
    return table;
};

var domToString = function(d) {
    var tmp = document.createElement('div');
    tmp.appendChild(d.cloneNode(true));
    return tmp.innerHTML;
};

var getSelection = function(input) {
    var result = {};
    if ('selectionStart' in input) {
        result.start = input.selectionStart;
        result.length = input.selectionEnd - result.start;
    } else if (document.selection) {
        input.focus();
        var sel = document.selection.createRange();
        var selLen = document.selection.createRange().text.length;
        sel.moveStart('character', -input.value.length);
        result.start = sel.text.length - selLen;
        result.length = selLen;
    }
    return result;
};

var transferStyles = function($from, $to, properties) {
    var i, n, styles = {};
    if (properties) {
        for (i = 0, n = properties.length; i < n; i++) {
            styles[properties[i]] = $from.compute(properties[i]);
        }
    } else {
        styles = $from.compute();
    }
    $to.style(styles);
};

var measured = null;
var measureString = function(str, $parent) {
    if (!str) {
        return 0;
    }

    var $test;
    if (!measured) {
        $test = zen('test').style({
            position: 'absolute',
            top: -99999,
            left: -99999,
            width: 'auto',
            padding: 0,
            whiteSpace: 'pre'
        }).text(str).bottom('body');

        transferStyles($parent, $test, [
            'letterSpacing',
            'fontSize',
            'fontFamily',
            'fontWeight',
            'textTransform'
        ]);

        measured = $test;
    } else {
        $test = measured;
        $test.text(str);
    }

    //var width = $test[0].offsetWidth;
    //$test.remove();

    return $test[0].offsetWidth;
};

var highlight = function($element, pattern) {
    if (typeof pattern === 'string' && !pattern.length) return;
    var regex = (typeof pattern === 'string') ? new RegExp(pattern, 'i') : pattern;

    var highlight = function(node) {
        var skip = 0;
        if (node.nodeType === 3) {
            var pos = node.data.search(regex);
            if (pos >= 0 && node.data.length > 0) {
                var match = node.data.match(regex);
                var spannode = document.createElement('span');
                spannode.className = 'g-highlight';
                var middlebit = node.splitText(pos);
                var endbit = middlebit.splitText(match[0].length);
                var middleclone = middlebit.cloneNode(true);
                spannode.appendChild(middleclone);
                middlebit.parentNode.replaceChild(spannode, middlebit);
                skip = 1;
            }
        } else if (node.nodeType === 1 && node.childNodes && !/(script|style)/i.test(node.tagName)) {
            for (var i = 0; i < node.childNodes.length; ++i) {
                i += highlight(node.childNodes[i]);
            }
        }
        return skip;
    };

    return forEach($element, function(el) {
        highlight(el);
    });
};

var autoGrow = function(input) {
    input = $(input);
    var currentWidth = null;

    var update = function(options, e) {
        var value, keyCode, printable, placeholder, width;
        var shift, character, selection;
        e = e || window.event || {};
        options = options || {};

        if (e.metaKey || e.altKey) return;
        if (!options.force && input.selectizeGrow === false) return;

        value = input.value();
        if (e.type && e.type.toLowerCase() === 'keydown') {
            keyCode = e.keyCode;
            printable = (
                (keyCode >= 97 && keyCode <= 122) || // a-z
                (keyCode >= 65 && keyCode <= 90) || // A-Z
                (keyCode >= 48 && keyCode <= 57) || // 0-9
                keyCode === 32 // space
            );

            if (keyCode === KEY_DELETE || keyCode === KEY_BACKSPACE) {
                selection = getSelection(input[0]);
                if (selection.length) {
                    value = value.substring(0, selection.start) + value.substring(selection.start + selection.length);
                } else if (keyCode === KEY_BACKSPACE && selection.start) {
                    value = value.substring(0, selection.start - 1) + value.substring(selection.start + 1);
                } else if (keyCode === KEY_DELETE && typeof selection.start !== 'undefined') {
                    value = value.substring(0, selection.start) + value.substring(selection.start + 1);
                }
            } else if (printable) {
                shift = e.shiftKey;
                character = String.fromCharCode(e.keyCode);
                if (shift) character = character.toUpperCase();
                else character = character.toLowerCase();
                value += character;
            }
        }

        placeholder = input.attribute('placeholder');
        if (!value && placeholder) {
            value = placeholder;
        }

        width = measureString(value, input) + 4;
        if (width !== currentWidth) {
            currentWidth = width;
            input[0].style.width = width + 'px';
            input.emit('resize');
        }
    };

    input.on('keydown', update);
    input.on('keyup', update);
    input.on('update', update);
    input.on('blur', update);

    update();
};

var Selectize = new prime({

    mixin: [Bound, Options],

    inherits: Emitter,

    options: {
        delimiter: ' ',
        splitOn: null, // regexp or string for splitting up values from a paste command
        persist: true,
        diacritics: true,
        create: false,
        createOnBlur: true,
        createFilter: null,
        highlight: true,
        openOnFocus: true,
        maxOptions: 1000,
        maxItems: null,
        hideSelected: null,
        addPrecedence: false,
        selectOnTab: false,
        preload: false,
        allowEmptyOption: false,
        closeAfterSelect: false,
        searchOnKeypress: true,

        scrollDuration: 60,
        loadThrottle: 300,
        loadingClass: 'g-loading',

        dataAttr: 'data-data',
        optgroupField: 'optgroup',
        valueField: 'value',
        labelField: 'text',
        optgroupLabelField: 'label',
        optgroupValueField: 'value',
        lockOptgroupOrder: false,

        sortField: '$order',
        searchField: ['text'],
        searchConjunction: 'and',

        mode: null,
        wrapperClass: 'g-selectize-control',
        inputClass: 'g-selectize-input',
        dropdownClass: 'g-selectize-dropdown',
        dropdownContentClass: 'g-selectize-dropdown-content',

        dropdownParent: null,

        copyClassesToDropdown: true,

        /*
         load            : null, // function(query, callback) { ... }
         score           : null, // function(search) { ... }
         onInitialize    : null, // function() { ... }
         onChange        : null, // function(value) { ... }
         onItemAdd       : null, // function(value, $item) { ... }
         onItemRemove    : null, // function(value) { ... }
         onClear         : null, // function() { ... }
         onOptionAdd     : null, // function(value, data) { ... }
         onOptionRemove  : null, // function(value) { ... }
         onOptionClear   : null, // function() { ... }
         onOptionGroupAdd     : null, // function(id, data) { ... }
         onOptionGroupRemove  : null, // function(id) { ... }
         onOptionGroupClear   : null, // function() { ... }
         onDropdownOpen  : null, // function($dropdown) { ... }
         onDropdownClose : null, // function($dropdown) { ... }
         onType          : null, // function(str) { ... }
         onDelete        : null, // function(values) { ... }
         */

        render: {
            /*
             item: null,
             optgroup: null,
             optgroup_header: null,
             option: null,
             option_create: null
             */
        }
    },

    constructor: function(input, options) {
        input = $(input);
        this.setOptions(options);

        // detect rtl environment
        var computedStyle = window.getComputedStyle && window.getComputedStyle(input[0], null);
        var dir = computedStyle ? computedStyle.getPropertyValue('direction') : input[0].currentStyle && input[0].currentStyle.direction;
        dir = dir || input.parents('[dir]:first').attr('dir') || '';

        this.rand = 'selectize-id-' + (Math.random() + 1).toString(36).substring(5);
        this.input = input;
        this.input.selectizeInstance = this;

        this.order = 0;
        this.tabIndex = input.attribute('tabindex') || '';
        this.tagType = input.tag() == 'select' ? TAG_SELECT : TAG_INPUT;
        this.rtl = /rtl/i.test(dir);
        this.highlightedValue = null;
        this.isRequired = input.attribute('required');
        forEach(['isOpen', 'isDisabled',  'isInvalid', 'isLocked', 'isFocused', 'isInputHidden', 'isSetup', 'isShiftDown', 'isCmdDown', 'isCtrlDown', 'ignoreFocus', 'ignoreBlur', 'ignoreHover', 'hasOptions'], function(option) {
            this[option] = false;
        }, this);
        this.currentResults = null;
        this.lastValue = '';
        this.caretPos = 0;
        this.loading = 0;
        this.loadedSearches = {};

        this.$activeOption = null;
        this.$activeItems = [];

        this.Optgroups = {};
        this.Options = {};
        this.UserOptions = {};
        this.items = [];
        this.renderCache = {};
        this.onSearchChange = this.options.loadThrottle === null ? this.onSearchChange : debounce(this.onSearchChange, this.options.loadThrottle);

        // search system
        this.sifter = new sifter(this.Options, { diacritics: this.options.diacritics });

        var i, n;

        // build options table
        if (this.options.Options) {
            for (i = 0, n = this.options.Options.length; i < n; i++) {
                this.registerOption(this.options.Options[i]);
            }
            delete this.options.Options;
        }

        // build optgroup table
        if (this.options.Optgroups) {
            for (i = 0, n = this.options.Optgroups.length; i < n; i++) {
                this.registerOptionGroup(this.options.Optgroups[i]);
            }
            delete this.options.Optgroups;
        }

        // option-demand defaults
        this.options.mode = this.options.mode || (this.options.maxItems === 1 ? 'single' : 'multi');
        if (!isBoolean(this.options.hideSelected)) { this.options.hideSelected = (this.options.mode === 'multi'); }

        this.setupCallbacks();
        this.setupTemplates();
        this.setup();

    },

    setup: function() {
        var $input = this.input,
            $wrapper,
            $control,
            $control_input,
            $dropdown,
            $dropdown_content,
            $dropdown_parent,
            inputMode,
            timeout_blur,
            timeout_focus,
            classes;

        inputMode = this.options.mode;
        classes = $input.attribute('class') || '';

        $wrapper = zen('div').addClass(this.options.wrapperClass).addClass(classes).addClass('g-' + inputMode).after(this.input);
        $control = zen('div').addClass(this.options.inputClass).addClass('g-items').bottom($wrapper);
        $control_input = zen('input[type="text"][autocomplete="off"][role="textbox"]').bottom($control).attribute('tabindex', $input.disabled() ? '-1' : this.tabIndex);
        $dropdown_parent = $(this.options.dropdownParent || $wrapper);
        $dropdown = zen('div').addClass(this.options.dropdownClass).addClass('g-' + inputMode).hide().bottom($dropdown_parent);
        $dropdown_content = zen('div[id="' + this.rand + '"]').addClass(this.options.dropdownContentClass).bottom($dropdown);

        if (this.options.copyClassesToDropdown) {
            $dropdown.addClass(classes);
        }

        if (inputMode == 'single') {
            $wrapper.style('width', parseInt($input[0].offsetWidth) + 12 + 24); // padding compensation
        }

        if ((this.options.maxItems === null || this.options.maxItems > 1) && this.tagType === TAG_SELECT) {
            $input.attribute('multiple', 'multiple');
        }

        if (this.options.placeholder) {
            $control_input.attribute('placeholder', this.options.placeholder);
        }

        // if splitOn was not passed in, construct it from the delimiter to allow pasting universally
        if (!this.options.splitOn && this.options.delimiter) {
            var delimiterEscaped = this.options.delimiter.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
            this.options.splitOn = new RegExp('\\s*' + delimiterEscaped + '+\\s*');
        }

        if ($input.attribute('autocorrect')) {
            $control_input.attribute('autocorrect', $input.attribute('autocorrect'));
        }

        if ($input.attribute('autocapitalize')) {
            $control_input.attribute('autocapitalize', $input.attribute('autocapitalize'));
        }

        this.$wrapper = $wrapper;
        this.$control = $control;
        this.$control_input = $control_input;
        this.$dropdown = $dropdown;
        this.$dropdown_content = $dropdown_content;

        $dropdown.delegate('mouseover', '[data-selectable]', bind(function() { return this.onOptionHover.apply(this, arguments); }, this));
        $dropdown.delegate('mousedown', '[data-selectable]', bind(function() { return this.onOptionSelect.apply(this, arguments); }, this));
        $dropdown.delegate('click', '[data-selectable]', bind(function() { return this.onOptionSelect.apply(this, arguments); }, this));

        autoGrow($control_input);

        $control.delegate('mousedown', '*:not(input)', bind(function(event, element) {
            if (element == $control) { return true; }
            return this.onItemSelect.apply(this, arguments);
        }, this));

        $control.on('mousedown', bind(function() { return this.onMouseDown.apply(this, arguments); }, this));
        $control.on('click', bind(function() { return this.onClick.apply(this, arguments); }, this));
        $control.delegate('click', '.g-remove-single-item', bind(function() { return this.onItemRemoveViaX.apply(this, arguments); }, this));

        $control_input.on('mousedown', function(e) { e.stopPropagation(); });
        $control_input.on('keydown', bind(function() { return this.onKeyDown.apply(this, arguments); }, this));
        $control_input.on('keyup', bind(function() { return this.onKeyUp.apply(this, arguments); }, this));
        $control_input.on('keypress', bind(function() { return this.onKeyPress.apply(this, arguments); }, this));
        $control_input.on('resize', bind(function() { this.positionDropdown.apply(this, []); }, this));
        $control_input.on('blur', bind(function() { return this.onBlur.apply(this, arguments); }, this));
        $control_input.on('focus', bind(function() {
            this.ignoreBlur = false;
            return this.onFocus.apply(this, arguments);
        }, this));
        $control_input.on('paste', bind(function() { return this.onPaste.apply(this, arguments); }, this));

        $(document).on('keydown', bind(function(e) {
            this.isCmdDown = e[IS_MAC ? 'metaKey' : 'ctrlKey'];
            this.isCtrlDown = e[IS_MAC ? 'altKey' : 'ctrlKey'];
            this.isShiftDown = e.shiftKey;
        }, this));

        $(document).on('keyup', bind(function(e) {
            if (e.keyCode === KEY_CTRL) this.isCtrlDown = false;
            if (e.keyCode === KEY_SHIFT) this.isShiftDown = false;
            if (e.keyCode === KEY_CMD) this.isCmdDown = false;
        }, this));

        $(document).on('mousedown', bind(function(e) {
            if (this.isFocused) {
                // prevent events on the dropdown scrollbar from causing the control to blur
                if (e.target === this.$dropdown[0] || e.target.parentNode === this.$dropdown[0]) {
                    e.preventDefault();
                    return false;
                }
                // blur on click outside
                if (!this.$control.find($(e.target)) && e.target !== this.$control[0]) {
                    this.blur(e.target);
                }
            }
        }, this));

        $(window).on('scroll', bind(function() {
            if (this.isOpen) {
                this.positionDropdown.apply(this, arguments);
            }
        }, this));
        $(window).on('resize', bind(function() {
            if (this.isOpen) {
                this.positionDropdown.apply(this, arguments);
            }
        }, this));
        $(window).on('mousemove', bind(function() {
            this.ignoreHover = false;
        }, this));

        // store original children and tab index so that they can be
        // restored when the destroy() method is called.
        this.revertSettings = {
            $children: this.input.children(),//.detach(),
            tabindex: this.input.attribute('tabindex')
        };

        this.input.attribute('tabindex', -1).attribute('aria-hidden', true).hide().after($wrapper);

        if (isArray(this.options.items)) {
            this.setValue(this.options.items);
            delete this.options.items;
        }

        // feature detect for the validation API
        if (SUPPORTS_VALIDITY_API) {
            this.input.on('invalid', bind(function(e) {
                e.preventDefault();
                this.isInvalid = true;
                this.refreshState();
            }, this));
        }

        this.updateOriginalInput();
        this.refreshItems();
        this.refreshState();
        this.updatePlaceholder();
        this.isSetup = true;

        if (this.input.disabled()) {
            this.disable();
        }

        this.on('change', this.onChange);

        this.input.selectizeInstance = this;
        this.input.addClass('selectized');
        this.emit('initialize');

        // preload options
        if (this.options.preload === true) {
            this.onSearchChange('');
        }
        // ARIA
        $wrapper
            .attribute('role', 'combobox')
            .attribute('aria-autocomplete', 'list')
            .attribute('aria-haspopup', true)
            .attribute('aria-expanded', false)
            .attribute('aria-labelledby', this.rand + '-' + slugify(this.getValue()));

        $dropdown_content
            .attribute('role', 'tree')
            .attribute('aria-expanded', false)
            .attribute('aria-hidden', true);


    },

    setupTemplates: function() {
        var field_label    = this.options.labelField,
            field_value    = this.options.valueField,
            field_optgroup = this.options.optgroupLabelField,
            mode           = this.options.mode;

        var templates = {
            'optgroup': function(data) {
                return '<div class="g-optgroup">' + data.html + '</div>';
            },
            'optgroup_header': function(data, escape) {
                return '<div class="g-optgroup-header">' + escape(data[field_optgroup]) + '</div>';
            },
            'option': function(data, escape) {
                var label = '<div class="g-option">' + escape(data[field_label]) + '</div>';
                if (this.options.Subtitles) {
                    label = '<div class="g-option"><span>' + escape(data[field_label]) + '</span> <div class="g-option-subtitle"><small>' + escape(data[field_value]) + '</small></div></div>';
                }
                return label;
            },
            'item': function(data, escape) {
                var removeButton = '',
                    title = escape(data[field_value]);

                if (mode !== 'single') {
                    removeButton = '<span  class="g-remove-single-item" tabindex="-1" title="Remove">&times;</span></div>';
                }

                if (this.options.Subtitles) {
                    title = 'class name: ' + title;
                }

                return '<div class="g-item" title="' + title + '">' + escape(data[field_label]) + removeButton;
            },
            'option_create': function(data, escape) {
                return '<div class="g-create">Add <strong>' + escape(data.input) + '</strong>&hellip;</div>';
            }
        };

        this.options.render = merge({}, templates, this.options.render);
    },

    setupCallbacks: function() {
        var key, fn, callbacks = {
            'initialize': 'onInitialize',
            'change': 'onChange',
            'item_add': 'onItemAdd',
            'item_remove': 'onItemRemove',
            'clear': 'onClear',
            'option_add': 'onOptionAdd',
            'option_remove': 'onOptionRemove',
            'option_clear': 'onOptionClear',
            'optgroup_add': 'onOptionGroupAdd',
            'optgroup_remove': 'onOptionGroupRemove',
            'optgroup_clear': 'onOptionGroupClear',
            'dropdown_open': 'onDropdownOpen',
            'dropdown_close': 'onDropdownClose',
            'type': 'onType',
            'load': 'onLoad',
            'focus': 'onFocus',
            'blur': 'onBlur'
        };

        for (key in callbacks) {
            if (callbacks.hasOwnProperty(key)) {
                fn = this.options[callbacks[key]];
                if (fn) { this.on(key, fn); }
            }
        }
    },



    onClick: function(e) {
        // necessary for mobile webkit devices (manual focus triggering
        // is ignored unless invoked within a click event)
        if (!this.isFocused) {
            this.focus();
            e.preventDefault();
        }
    },

    onMouseDown: function(e) {
        var defaultPrevented = e.defaultPrevented || (typeof e.defaultPrevented === 'undefined');
        var $target = $(e.target);

        if (this.isFocused) {
            // retain focus by preventing native handling. if the
            // event target is the input it should not be modified.
            // otherwise, text selection within the input won't work.
            if (e.target !== this.$control_input[0]) {
                if (this.options.mode === 'single') {
                    // toggle dropdown
                    this.isOpen ? this.close() : this.open();
                } else if (!defaultPrevented) {
                    this.setActiveItem(null);
                }

                /*e.preventDefault();
                 e.stopPropagation();*/
                return false;
            }
        } else {
            // give control focus
            if (!defaultPrevented) {
                window.setTimeout(bind(function() {
                    this.focus();
                }, this), 0);
            }
        }
    },

    onChange: function() {
        this.input.emit('change', this.input.value(), this);
        $('body').emit('change', { target: this.input });
    },

    onPaste: function(e) {
        if (this.isFull() || this.isInputHidden || this.isLocked) {
            e.preventDefault();
        } else {
            // If a regex or string is included, this will split the pasted
            // input and create Items for each separate value
            if (this.options.splitOn) {
                setTimeout(bind(function() {
                    var splitInput = trim(this.$control_input.value() || '').split(this.options.splitOn);
                    for (var i = 0, n = splitInput.length; i < n; i++) {
                        this.createItem(splitInput[i]);
                    }
                }, this), 0);
            }
        }
    },

    onKeyPress: function(e) {
        if (this.isLocked) return e && e.preventDefault();
        var character = String.fromCharCode(e.keyCode || e.which);
        if (this.options.create && this.options.mode === 'multi' && character === this.options.delimiter) {
            this.createItem();
            e.preventDefault();
            return false;
        }
    },

    onKeyDown: function(e) {
        var isInput = e.target === this.$control_input[0];

        if (this.isLocked) {
            if (e.keyCode !== KEY_TAB) {
                e.preventDefault();
            }
            return;
        }

        switch (e.keyCode) {
            case KEY_A:
                if (this.isCmdDown) {
                    this.selectAll();
                    return;
                }
                break;
            case KEY_ESC:
                if (this.isOpen) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.close();
                    //this.blur();
                }
                return;
            case KEY_N:
                if (!e.ctrlKey || e.altKey) break;
            case KEY_DOWN:
                if (!this.isOpen && this.hasOptions) {
                    this.open();
                } else if (this.$activeOption) {
                    this.ignoreHover = true;
                    var $next = this.getAdjacentOption(this.$activeOption, 1);
                    if ($next) { this.setActiveOption($next, true, true); }
                }
                e.preventDefault();
                return;
            case KEY_P:
                if (!e.ctrlKey || e.altKey) break;
            case KEY_UP:
                if (this.$activeOption) {
                    this.ignoreHover = true;
                    var $prev = this.getAdjacentOption(this.$activeOption, -1);
                    if ($prev) { this.setActiveOption($prev, true, true); }
                }
                e.preventDefault();
                return;
            case KEY_RETURN:
                if (this.isOpen && this.$activeOption) {
                    this.onOptionSelect({ currentTarget: this.$activeOption });
                    e.preventDefault();
                }
                return;
            case KEY_LEFT:
                this.advanceSelection(-1, e);
                return;
            case KEY_RIGHT:
                this.advanceSelection(1, e);
                return;
            case KEY_TAB:
                if (this.options.selectOnTab && this.isOpen && this.$activeOption) {
                    this.onOptionSelect({ currentTarget: this.$activeOption });

                    // Default behaviour is to jump to the next field, we only want this
                    // if the current field doesn't accept any more entries
                    if (!self.isFull()) {
                        e.preventDefault();
                    }
                }
                if (this.options.create && this.createItem()) {
                    e.preventDefault();
                }
                return;
            case KEY_BACKSPACE:
            case KEY_DELETE:
                this.deleteSelection(e);
                return;
        }

        if ((this.isFull() || this.isInputHidden) && !(IS_MAC ? e.metaKey : e.ctrlKey)) {
            e.preventDefault();
            return;
        }
    },

    onKeyUp: function(e) {

        if (this.isLocked) return e && e.preventDefault();
        var value = this.$control_input.value() || '';
        if (this.lastValue !== value) {
            this.lastValue = value;
            this.onSearchChange(value);
            this.refreshOptions();
            this.emit('type', value);
        }
    },

    onSearchChange: function(value) {
        var fn = this.options.load;
        if (!fn) return;
        if (this.loadedSearches.hasOwnProperty(value)) return;
        this.loadedSearches[value] = true;
        this.load(bind(function(callback) {
            fn.apply(this, [value, callback]);
        }, this));
    },

    onFocus: function(e) {
        var wasFocused = this.isFocused;

        if (this.isDisabled) {
            this.blur();
            e && e.preventDefault();
            return false;
        }

        if (this.ignoreFocus) return;
        this.isFocused = true;
        if (this.options.preload === 'focus') this.onSearchChange('');

        if (!wasFocused) this.emit('focus');

        if (!this.$activeItems.length) {
            this.showInput();
            this.setActiveItem(null);
            this.refreshOptions(!!this.options.openOnFocus);
        }

        this.refreshState();
    },

    onBlur: function(e, dest) {
        if (!this.isFocused) return;
        this.isFocused = false;

        if (this.ignoreFocus) {
            return;
        } else if (!this.ignoreBlur && (document.activeElement === this.$dropdown_content[0])) {
            // ^- g5 custom [before: no e.target && ..]
            // necessary to prevent IE closing the dropdown when the scrollbar is clicked
            this.ignoreBlur = true;
            this.onFocus(e);
            return;
        }

        var deactivate = bind(function() {
            this.close();
            this.setTextboxValue('');
            this.setActiveItem(null);
            this.setActiveOption(null);
            this.setCaret(this.items.length);
            this.refreshState();

            dest && dest.focus();

            this.ignoreFocus = false;
            this.emit('blur');

        }, this);

        this.ignoreFocus = true;
        if (this.options.create && this.options.createOnBlur) {
            this.createItem(null, false, deactivate);
        } else {
            deactivate();
        }
    },

    onOptionHover: function(e, element) {
        element = $(element);
        if (this.ignoreHover) return;
        this.setActiveOption(element || e.currentTarget, false);
    },

    onOptionSelect: function(e, element) {
        var value, $target, $option, self = this;

        if (e.preventDefault) {
            e.preventDefault();
            e.stopPropagation();
        }

        $target = $(element || e.currentTarget);
        if ($target.hasClass('g-create')) {
            this.createItem(null, bind(function() {
                if (this.options.closeAfterSelect) {
                    this.close();
                }
            }, this));
        } else {
            value = $target.attribute('data-value');
            if (typeof value !== 'undefined') {
                this.lastQuery = null;
                this.setTextboxValue('');
                this.addItem(value);
                if (this.options.closeAfterSelect) {
                    this.close();
                } else if (!this.options.hideSelected && e.type && /mouse/.test(e.type)) {
                    this.setActiveOption(this.getOption(value));
                }
            }
        }
    },

    onItemSelect: function(e, element) {
        if (this.isLocked) return;
        if (this.options.mode === 'multi') {
            e.preventDefault();
            this.setActiveItem(element || e.currentTarget, e);
        }
    },

    onItemRemoveViaX: function(e, element) {
        e.preventDefault();
        if (this.isLocked || this.options.mode == 'single') return;

        var $item = element.parent();
        this.setActiveItem($item);
        if (this.deleteSelection()) {
            this.setCaret(this.items.length);
        }
    },

    load: function(fn) {
        var $wrapper = this.$wrapper.addClass(this.options.loadingClass);

        this.loading++;
        fn.apply(this, [bind(function(results) {
            this.loading = Math.max(this.loading - 1, 0);
            if (results && results.length) {
                this.addOption(results);
                this.refreshOptions(this.isFocused && !this.isInputHidden);
            }
            if (!this.loading) {
                $wrapper.removeClass(this.options.loadingClass);
            }
            this.emit('load', results);
        }, this)]);
    },

    setTextboxValue: function(value) {
        var $input = this.$control_input;
        var changed = $input.value() !== value;
        if (changed) {
            $input.value(value).emit('update');
            this.lastValue = value;
        }
    },

    getValue: function(value) {
        // g5 custom
        if (this.tagType === TAG_SELECT && this.input.attribute('multiple')) {
            return value || this.items;
        } else {
            return (value || this.items).join(this.options.delimiter);
        }
    },

    setValue: function(value, silent) {
        var events = silent ? [] : ['change'];

        debounce_events(this, events, function() {
            this.clear(silent);
            this.previousValue = this.getValue() || value;
            this.addItems(value, silent);
        });
    },

    setActiveItem: function(item, e) {
        var eventName, idx, begin, end, $item, swap, $last;

        if (this.options.mode === 'single') { return; }
        item = $(item);

        // clear the active selection
        if (!item) {
            if (this.$activeItems.length) { $(this.$activeItems).removeClass('g-active'); }
            this.$activeItems = [];
            if (this.isFocused) {
                this.showInput();
            }
            return;
        }

        // modify selection
        eventName = e && e.type.toLowerCase();

        if (eventName === 'mousedown' && this.isShiftDown && this.$activeItems.length) {
            $last = $(last(this.$control.children('.g-active')));
            begin = Array.prototype.indexOf.apply(this.$control[0].childNodes, [$last[0]]);
            end = Array.prototype.indexOf.apply(this.$control[0].childNodes, [item[0]]);
            if (begin > end) {
                swap = begin;
                begin = end;
                end = swap;
            }
            for (var i = begin; i <= end; i++) {
                $item = this.$control[0].childNodes[i];
                if (this.$activeItems.indexOf($item) === -1) {
                    $($item).addClass('g-active');
                    this.$wrapper.attribute('aria-activedescendant', slugify(this.rand + '-' + $($item).attribute('data-value')));
                    this.$activeItems.push($item);
                }
            }
            e.preventDefault();
        } else if ((eventName === 'mousedown' && this.isCtrlDown) || (eventName === 'keydown' && this.isShiftDown)) {
            if (item.hasClass('g-active')) {
                idx = this.$activeItems.indexOf(item[0]);
                this.$activeItems.splice(idx, 1);
                item.removeClass('g-active');
            } else {
                this.$activeItems.push(item.addClass('g-active')[0]);
                this.$wrapper.attribute('aria-activedescendant', slugify(this.rand + '-' + item.attribute('data-value')));
            }
        } else {
            if ($(this.$activeItems)) $(this.$activeItems).removeClass('g-active');
            this.$activeItems = [item.addClass('g-active')[0]];
            this.$wrapper.attribute('aria-activedescendant', slugify(this.rand + '-' + item.attribute('data-value')));
        }

        // ensure control has focus
        this.hideInput();
        if (!this.isFocused) {
            this.focus();
        }
    },

    setActiveOption: function($option, scroll, animate) {
        var height_menu, height_item, y;
        var scroll_top, scroll_bottom;

        if (this.$activeOption) this.$activeOption.removeClass('g-active');
        this.$activeOption = null;

        $option = $($option);
        if (!$option) return;

        this.$activeOption = $option.addClass('g-active');
        this.$wrapper.attribute('aria-activedescendant', slugify(this.rand + '-' + $option.attribute('data-value')));

        if (scroll || !isset(scroll)) {

            height_menu = this.$dropdown_content[0].offsetHeight;
            height_item = this.$activeOption[0].offsetHeight;
            scroll = this.$dropdown_content[0].scrollTop || 0;
            y = this.$activeOption.position().top - this.$dropdown_content.position().top + scroll;
            scroll_top = y;
            scroll_bottom = y - height_menu + height_item;

            if (y + height_item > height_menu + scroll) {
                this.$dropdown_content[0].scrollTop = scroll_bottom;
                /*moofx(bind(function(value){
                 this.$dropdown_content[0].scrollTop = value;
                 }, this), {
                 duration: animate ? this.options.scrollDuration : 0,
                 equation: 'linear'
                 }).start(scroll, scroll_bottom);*/
            } else if (y < scroll) {
                this.$dropdown_content[0].scrollTop = scroll_top;
                /*moofx(bind(function(value){
                 this.$dropdown_content[0].scrollTop = value;
                 }, this), {
                 duration: animate ? this.options.scrollDuration : 0,
                 equation: 'linear'
                 }).start(scroll, scroll_top);*/
            }

        }
    },

    selectAll: function() {
        if (this.options.mode === 'single') return;

        var items = this.$control.children(':not(input)');
        if (items) {
            items.addClass('g-active');
            this.$wrapper.attribute('aria-activedescendant', slugify(this.rand + '-' + items.attribute('data-value')));
        }

        this.$activeItems = Array.prototype.slice.apply(items || []);
        if (this.$activeItems.length) {
            this.hideInput();
            this.close();
        }
        this.focus();
    },

    hideInput: function() {

        this.setTextboxValue('');
        this.$control_input.style({
            opacity: 0,
            position: 'absolute',
            left: this.rtl ? 10000 : -10000
        });
        this.isInputHidden = true;
    },

    showInput: function() {
        this.$control_input.style({
            opacity: 1,
            position: 'relative',
            left: 0
        });
        this.isInputHidden = false;
    },

    focus: function() {
        if (this.isDisabled) { return; }

        this.ignoreFocus = true;
        this.$control_input[0].focus();
        setTimeout(bind(function() {
            this.ignoreFocus = false;
            this.onFocus();
        }, this), 0);
    },

    blur: function(dest) {
        this.$control_input[0].blur();
        this.onBlur(null, dest);
        // g5 custom
        //this.$control_input[0].blur();
    },

    getScoreFunction: function(query) {
        return this.sifter.getScoreFunction(query, this.getSearchOptions());
    },

    getSearchOptions: function() {
        var sort = this.options.sortField;
        if (typeof sort === 'string') {
            sort = [{ field: sort }];
        }

        return {
            fields: this.options.searchField,
            conjunction: this.options.searchConjunction,
            sort: sort
        };
    },

    search: function(query) {
        var i, value, score, result, calculateScore;
        var options = this.getSearchOptions();

        // validate user-provided result scoring function
        if (this.options.score) {
            calculateScore = this.options.score.apply(this, [query]);
            if (typeof calculateScore !== 'function') {
                throw new Error('Selectize "score" setting must be a function that returns a function');
            }
        }

        // perform search
        if (query !== this.lastQuery) {
            this.lastQuery = query;
            result = this.sifter.search(query, merge(options, { score: calculateScore }));
            this.currentResults = result;
        } else {
            result = merge({}, this.currentResults);
        }

        // filter out selected items
        if (this.options.hideSelected) {
            for (i = result.items.length - 1; i >= 0; i--) {
                if (this.items.indexOf(hash_key(result.items[i].id)) !== -1) {
                    result.items.splice(i, 1);
                }
            }
        }

        return result;
    },

    refreshOptions: function(triggerDropdown) {
        var i, j, k, n, groups, groups_order, option, option_html, optgroup, optgroups, html, html_children, has_create_option;
        var $active, $active_before, $create;

        if (typeof triggerDropdown === 'undefined') {
            triggerDropdown = true;
        }

        var query = trim(this.$control_input.value());
        var results = this.search(query);
        var $dropdown_content = this.$dropdown_content;
        var active_before = this.$activeOption && hash_key(this.$activeOption.attribute('data-value'));

        // build markup
        n = results.items.length;
        if (typeof this.options.maxOptions === 'number') {
            n = Math.min(n, this.options.maxOptions);
        }

        // render and group available options individually
        groups = {};
        groups_order = [];

        // g5 custom
        //if (this.options.optgroupOrder) {
        //    groups_order = this.options.optgroupOrder;
        //    for (i = 0; i < groups_order.length; i++) {
        //        groups[groups_order[i]] = [];
        //    }
        //} else {
        //    groups_order = [];
        //}

        for (i = 0; i < n; i++) {
            option = this.Options[results.items[i].id];
            option_html = this.render('option', option);
            optgroup = option[this.options.optgroupField] || '';
            optgroups = isArray(optgroup) ? optgroup : [optgroup];

            for (j = 0, k = optgroups && optgroups.length; j < k; j++) {
                optgroup = optgroups[j];
                if (!this.Optgroups.hasOwnProperty(optgroup)) {
                    optgroup = '';
                }
                if (!groups.hasOwnProperty(optgroup)) {
                    groups[optgroup] = document.createDocumentFragment();
                    groups_order.push(optgroup);
                }
                groups[optgroup].appendChild(option_html);
            }
        }

        // sort optgroups
        if (this.options.lockOptgroupOrder) {
            groups_order.sort(function(a, b) {
                var a_order = this.Optgroups[a].$order || 0;
                var b_order = this.Optgroups[b].$order || 0;
                return a_order - b_order;
            });
        }

        // render optgroup headers & join groups
        html = document.createDocumentFragment();
        for (i = 0, n = groups_order.length; i < n; i++) {
            optgroup = groups_order[i];
            if (this.Optgroups.hasOwnProperty(optgroup) && groups[optgroup].childNodes.length) {
                // render the optgroup header and options within it,
                // then pass it to the wrapper template
                html_children = document.createDocumentFragment();
                html_children.appendChild(this.render('optgroup_header', this.Optgroups[optgroup]));
                html_children.appendChild(groups[optgroup]);
                html.appendChild(this.render('optgroup', merge({}, this.Optgroups[optgroup], {
                    html: domToString(html_children),
                    dom: html_children
                })));
            } else {
                html.appendChild(groups[optgroup]);
            }
        }

        $dropdown_content.html(domToString(html));

        // highlight matching terms inline
        if (this.options.highlight && results.query.length && results.tokens.length) {
            for (i = 0, n = results.tokens.length; i < n; i++) {
                highlight($dropdown_content, results.tokens[i].regex);
            }
        }

        // add "selected" class to selected options
        if (!this.options.hideSelected) {
            for (i = 0, n = this.items.length; i < n; i++) {
                this.getOption(this.items[i]).addClass('g-selected').attribute('aria-selected', true);
            }
        }

        // add create option
        has_create_option = this.canCreate(query);
        if (has_create_option) {
            //$dropdown_content.prepend(this.render('option_create', { input: query }));
            $(this.render('option_create', { input: query })).top($dropdown_content);
            $create = $($dropdown_content[0].childNodes[0]);
        }

        // activate
        this.hasOptions = results.items.length > 0 || has_create_option;
        if (this.hasOptions) {
            if (results.items.length > 0) {
                $active_before = active_before && this.getOption(active_before);
                if ($active_before && $active_before.length) {
                    $active = $active_before;
                } else if (this.options.mode === 'single' && this.items.length) {
                    $active = this.getOption(this.items[0]);
                }
                if (!$active || !$active.length) {
                    if ($create && !this.options.addPrecedence) {
                        $active = this.getAdjacentOption($create, 1);
                    } else {
                        $active = $dropdown_content.find('[data-selectable]:first-child');
                    }
                }
            } else {
                $active = $create;
            }
            this.setActiveOption($active);
            if (triggerDropdown && !this.isOpen) { this.open(); }
        } else {
            this.setActiveOption(null);
            if (triggerDropdown && this.isOpen) { this.close(); }
        }
    },

    addOption: function(data) {
        var value;

        if (isArray(data)) {
            for (var i = 0, n = data.length; i < n; i++) {
                this.addOption(data[i]);
            }
            return;
        }

        if (value = this.registerOption(data)) {
            this.UserOptions[value] = true;
            this.lastQuery = null;
            this.emit('option_add', value, data);
        }

        // g5 custom
        /*value = hash_key(data[this.options.valueField]);
         if (typeof value !== 'string' || this.Options.hasOwnProperty(value)) return;

         this.UserOptions[value] = true;
         this.Options[value] = data;
         this.lastQuery = null;
         this.emit('option_add', value, data);*/
    },

    registerOption: function(data) {
        var key = hash_key(data[this.options.valueField]);
        if ((!key && !this.options.allowEmptyOption) || this.options.hasOwnProperty(key)) return false;
        data.$order = data.$order || ++this.order;
        this.Options[key] = data;

        return key;
    },

    registerOptionGroup: function(data) {
        var key = hash_key(data[this.options.optgroupValueField]);
        if (!key) return false;

        data.$order = data.$order || ++this.order;
        this.Optgroups[key] = data;

        return key;
    },


    addOptionGroup: function(id, data) {
        data[this.options.optgroupValueField] = id;
        if (id = this.registerOptionGroup(data)) {
            this.emit('optgroup_add', id, data);
        }
    },

    removeOptionGroup: function(id) {
        if (this.Optgroups.hasOwnProperty(id)) {
            delete this.Optgroups[id];
            this.renderCache = {};
            this.emit('optgroup_remove', id);
        }
    },

    clearOptionGroups: function() {
        this.Optgroups = {};
        this.renderCache = {};
        this.emit('optgroup_clear');
    },

    updateOption: function(value, data) {
        var self = this;
        var $item, $item_new, dummy;
        var value_new, index_item, cache_items, cache_options, order_old;

        value = hash_key(value);
        value_new = hash_key(data[this.options.valueField]);

        // sanity checks
        if (value === null) return;
        if (!this.Options.hasOwnProperty(value)) return;
        if (typeof value_new !== 'string') throw new Error('Value must be set in option data');

        order_old = this.Options[value].$order;

        // update references
        if (value_new !== value) {
            delete this.Options[value];
            index_item = this.items.indexOf(value);
            if (index_item !== -1) {
                this.items.splice(index_item, 1, value_new);
            }
        }
        data.$order = data.$order || order_old;
        this.Options[value_new] = data;

        // invalidate render cache
        cache_items = this.renderCache['item'];
        cache_options = this.renderCache['option'];

        if (cache_items) {
            delete cache_items[value];
            delete cache_items[value_new];
        }
        if (cache_options) {
            delete cache_options[value];
            delete cache_options[value_new];
        }

        // update the item if it's selected
        if (this.items.indexOf(value_new) !== -1) {
            $item = this.getItem(value);
            $item_new = $(this.render('item', data));
            if ($item.hasClass('g-active')) {
                $item_new.addClass('g-active');
                this.$wrapper.attribute('aria-activedescendant', slugify(this.rand + '-' + $item_new.attribute('data-value')));
            }

            $item_new.after($item);
            $item.remove();
        }

        // invalidate last query because we might have updated the sortField
        this.lastQuery = null;

        // update dropdown contents
        if (this.isOpen) {
            this.refreshOptions(false);
        }
    },

    removeOption: function(value, silent) {
        value = hash_key(value);

        var cache_items = this.renderCache['item'];
        var cache_options = this.renderCache['option'];
        if (cache_items) delete cache_items[value];
        if (cache_options) delete cache_options[value];

        delete this.UserOptions[value];
        delete this.Options[value];
        this.lastQuery = null;
        this.emit('option_remove', value);
        this.removeItem(value, silent);
    },

    clearOptions: function() {
        this.loadedSearches = {};
        this.UserOptions = {};
        this.renderCache = {};
        this.Options = this.sifter.items = {};
        this.lastQuery = null;
        this.emit('option_clear');
        this.clear();
    },

    getOption: function(value) {
        return this.getElementWithValue(value, this.$dropdown_content.search('[data-selectable]'));
    },

    getAdjacentOption: function($option, direction) {
        var $options = this.$dropdown.search('[data-selectable]');
        var index = indexOf($options, ($option ? $option[0] : null)) + direction;

        return index >= 0 && index < ($options ? $options.length : 0) ? $($options[index]) : $();
    },

    getElementWithValue: function(value, $els) {
        value = hash_key(value);

        if (typeof value !== 'undefined' && value !== null) {
            for (var i = 0, n = ($els ? $els.length : 0); i < n; i++) {
                if ($els[i].getAttribute('data-value') === value) {
                    return $($els[i]);
                }
            }
        }

        return $();
    },

    getItem: function(value) {
        return this.getElementWithValue(value, this.$control.children());
    },

    addItems: function(values, silent) {
        var items = isArray(values) ? values : [values];
        for (var i = 0, n = items.length; i < n; i++) {
            this.isPending = (i < n - 1);
            this.addItem(items[i], silent);
        }
    },

    addItem: function(value, silent) {
        var events = silent ? [] : ['change'];

        debounce_events(this, events, function() {
            var $item, $option, $options;
            var inputMode = this.options.mode;
            var i, active, value_next, wasFull;
            value = hash_key(value);

            if (this.items.indexOf(value) !== -1) {
                // g5 custom [before: && this.isOpen]
                if (inputMode === 'single') this.close();
                return;
            }

            if (!this.Options.hasOwnProperty(value)) return;
            if (inputMode === 'single') this.clear(silent);
            if (inputMode === 'multi' && this.isFull()) return;

            $item = $(this.render('item', this.Options[value]));
            // if (inputMode !== 'multi') $item.find('.g-remove-single-item').remove();

            // ARIA
            $item.attribute('id', this.rand + '-' + slugify($item.attribute('data-value')));
            if (inputMode === 'multi') $item.attribute('aria-selected', true);

            wasFull = this.isFull();
            this.items.splice(this.caretPos, 0, value);
            this.insertAtCaret($item);
            if (!this.isPending || (!wasFull && this.isFull())) {
                this.refreshState();
            }

            if (this.isSetup) {
                $options = this.$dropdown_content.search('[data-selectable]');

                // update menu / remove the option (if this is not one item being added as part of series)
                if (!this.isPending) {
                    $option = this.getOption(value);
                    var adj = this.getAdjacentOption($option, 1);
                    value_next = (adj) ? adj.attribute('data-value') : null;
                    this.refreshOptions(this.isFocused && inputMode !== 'single');
                    if (value_next) {
                        this.setActiveOption(this.getOption(value_next));
                    }
                }

                // hide the menu if the maximum number of items have been selected or no options are left
                if (!$options || this.isFull()) {
                    this.close();
                } else {
                    this.positionDropdown();
                }

                this.updatePlaceholder();
                this.emit('item_add', value, $item);
                this.updateOriginalInput({ silent: silent });
            }
        });
    },

    removeItem: function(value, silent) {
        var $item, i, idx;

        $item = (value instanceof $) ? value : this.getItem(value);
        value = hash_key($item.attribute('data-value'));
        i = this.items.indexOf(value);

        if (i !== -1) {
            $item.remove();
            if ($item.hasClass('g-active')) {
                idx = this.$activeItems.indexOf($item[0]);
                this.$activeItems.splice(idx, 1);
            }

            this.items.splice(i, 1);
            this.lastQuery = null;
            if (!this.options.persist && this.UserOptions.hasOwnProperty(value)) {
                this.removeOption(value, silent);
            }

            if (i < this.caretPos) {
                this.setCaret(this.caretPos - 1);
            }

            this.refreshState();
            this.updatePlaceholder();
            this.updateOriginalInput({ silent: silent });
            this.positionDropdown();
            this.emit('item_remove', value, $item);
        }
    },

    createItem: function(input, triggerDropdown) {
        var caret = this.caretPos;
        input = input || trim(this.$control_input.value() || '');

        var callback = arguments[arguments.length - 1];
        if (typeof callback !== 'function') callback = function() {};

        if (!isBoolean(triggerDropdown)) {
            triggerDropdown = true;
        }

        if (!this.canCreate(input)) {
            callback();
            return false;
        }

        this.lock();

        var setup = (typeof this.options.create === 'function') ? this.options.create : bind(function(input) {
            var data = {};
            data[this.options.labelField] = input;
            data[this.options.valueField] = input;
            return data;
        }, this);

        var create = once(bind(function(data) {
            this.unlock();

            if (!data || typeof data !== 'object') return callback();
            var value = hash_key(data[this.options.valueField]);
            if (typeof value !== 'string') return callback();

            this.setTextboxValue('');
            this.addOption(data);
            this.setCaret(caret);
            this.addItem(value);
            this.refreshOptions(triggerDropdown && this.options.mode !== 'single');
            callback(data);
        }, this));

        var output = setup.apply(this, [input, create]);
        if (typeof output !== 'undefined') {
            create(output);
        }

        return true;
    },

    refreshItems: function() {
        this.lastQuery = null;

        if (this.isSetup) {
            this.addItem(this.items);
        }

        this.refreshState();
        this.updateOriginalInput();
    },

    refreshState: function() {
        var invalid;
        if (this.isRequired) {
            if (this.items.length) this.isInvalid = false;
            this.$control_input.attribute('required', this.isInvalid || null);
        }
        this.refreshClasses();
    },

    refreshClasses: function() {
        var isFull   = this.isFull(),
            isLocked = this.isLocked;

        this.$wrapper.toggleClass('g-rtl', this.rtl);

        this.$control.toggleClass('g-focus', this.isFocused);
        this.$control.toggleClass('g-disabled', this.isDisabled);
        this.$control.toggleClass('g-required', this.isRequired);
        this.$control.toggleClass('g-invalid', this.isInvalid);
        this.$control.toggleClass('g-locked', isLocked);
        this.$control.toggleClass('g-full', isFull);
        this.$control.toggleClass('g-not-full', !isFull);
        this.$control.toggleClass('g-input-active', this.isFocused && !this.isInputHidden);
        this.$control.toggleClass('g-dropdown-active', this.isOpen);
        this.$control.toggleClass('g-has-options', !size(this.options.Options));
        this.$control.toggleClass('g-has-items', this.items.length > 0);

        // ARIA
        if (this.isOpen) {
            this.$wrapper
                .attribute('aria-owns', this.rand)
                .attribute('aria-activedescendant', slugify(this.rand + '-' + this.getValue()))
                .attribute('aria-expanded', true);

            this.$dropdown_content
                .attribute('aria-expanded', true)
                .attribute('aria-hidden', false);
        } else {
            this.$wrapper
                .attribute('aria-owns', null)
                .attribute('aria-activedescendant', null)
                .attribute('aria-expanded', false);

            this.$dropdown_content
                .attribute('aria-expanded', false)
                .attribute('aria-hidden', true);
        }

        this.$control_input.selectizeGrow = !isFull && !isLocked;
    },

    isFull: function() {
        return this.options.maxItems !== null && this.items.length >= this.options.maxItems;
    },

    updateOriginalInput: function(opts) {
        var options, label;
        opts = opts || {};

        if (this.tagType === TAG_SELECT) {
            options = [];
            for (var i = 0, n = this.items.length; i < n; i++) {
                label = this.Options[this.items[i]][this.options.labelField] || '';
                options.push('<option value="' + escapeHTML(this.items[i]) + '" selected="selected">' + escapeHTML(label) + '</option>');
            }
            if (!options.length && !this.input.attribute('multiple')) {
                options.push('<option value="" selected="selected"></option>');
            }
            this.input.html(options.join(''));
        } else {
            this.input.value(this.getValue());
            this.input.attribute('value', this.input.value());
        }

        if (this.isSetup && !opts.silent) {
            this.emit('change', this.input.value());
        }
    },

    updatePlaceholder: function() {
        if (!this.options.placeholder) return;
        var control_input = this.$control_input;

        if (this.items.length) {
            control_input.attribute('placeholder', null);
        } else {
            control_input.attribute('placeholder', this.options.placeholder);
        }
        control_input.emit('update', { force: true });
    },

    open: function() {
        if (this.isLocked || this.isOpen || (this.options.mode === 'multi' && this.isFull())) return;
        this.focus();
        this.isOpen = true;
        this.refreshState();
        this.$dropdown.style({
            visibility: 'hidden',
            display: 'block'
        });
        this.positionDropdown();
        this.$dropdown.style({ visibility: 'visible' });
        this.emit('dropdown_open', this.$dropdown);
    },

    close: function() {
        var trigger = this.isOpen;

        if (this.options.mode === 'single' && this.items.length) {
            this.hideInput();
        }

        this.isOpen = false;
        this.$dropdown.hide();
        this.setActiveOption(null);
        this.refreshState();

        if (trigger) this.emit('dropdown_close', this.$dropdown);
    },

    positionDropdown: function() {
        var control = this.$control,
            offset  = control.position();//this.options.dropdownParent === 'body' ? control.offset() : control.position();
        offset.top += control[0].offsetHeight;

        this.$dropdown.style({
            width: control[0].offsetWidth,
            top: control[0].offsetTop + control[0].offsetHeight,
            left: control[0].offsetLeft
        });
    },

    clear: function(silent) {
        if (!this.items.length) return;

        var non_input = this.$control.children(':not(input)');
        if (non_input) non_input.remove();

        this.items = [];
        this.lastQuery = null;
        this.setCaret(0);
        this.setActiveItem(null);
        this.updatePlaceholder();
        this.updateOriginalInput({ silent: silent });
        this.refreshState();
        this.showInput();
        this.emit('clear');
    },

    insertAtCaret: function($el) {
        var caret = Math.min(this.caretPos, this.items.length);
        if (caret === 0) {
            $el.top(this.$control);//.prepend($el);
        } else {
            //$(this.$control[0].childNodes[caret]).before($el);
            $el.after(this.$control.find(':nth-child(' + caret + ')'));
            //this.$control.find(':nth-child(' + caret + ')').before($el);
        }
        this.setCaret(caret + 1);
    },

    deleteSelection: function(e) {
        var i, n, direction, selection, values, caret, option_select, $option_select, $tail;

        direction = (e && e.keyCode === KEY_BACKSPACE) ? -1 : 1;
        selection = getSelection(this.$control_input[0]);

        if (this.$activeOption && !this.options.hideSelected) {
            option_select = this.getAdjacentOption(this.$activeOption, -1);
            if (option_select) { option_select = option_select.attribute('data-value'); }
        }

        // determine items that will be removed
        values = [];

        if (this.$activeItems.length) {
            var children = this.$control.children(':not(input)');
            $tail = this.$control.children('.g-active');
            if ($tail) { $tail = $(direction > 0 ? last($tail) : $tail[0]); }
            caret = (!children ? -1 : indexOf(children, $tail[0]));
            if (direction > 0) { caret++; }

            for (i = 0, n = this.$activeItems.length; i < n; i++) {
                values.push($(this.$activeItems[i]).attribute('data-value'));
            }
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
        } else if ((this.isFocused || this.options.mode === 'single') && this.items.length) {
            if (direction < 0 && selection.start === 0 && selection.length === 0) {
                values.push(this.items[this.caretPos - 1]);
            } else if (direction > 0 && selection.start === this.$control_input.value().length) {
                values.push(this.items[this.caretPos]);
            }
        }

        // allow the callback to abort
        if (!values.length || (typeof this.options.onDelete === 'function' && this.options.onDelete.apply(this, [values]) === false)) {
            return false;
        }

        // perform removal
        if (typeof caret !== 'undefined') {
            this.setCaret(caret);
        }
        while (values.length) {
            this.removeItem(values.pop());
        }

        this.showInput();
        this.positionDropdown();
        this.refreshOptions(true);

        // select previous option
        if (option_select) {
            $option_select = this.getOption(option_select);
            if ($option_select.length) {
                this.setActiveOption($option_select);
            }
        }

        return true;
    },

    advanceSelection: function(direction, e) {
        var tail, selection, idx, valueLength, cursorAtEdge, $tail;

        if (direction === 0) return;
        if (this.rtl) direction *= -1;

        tail = direction > 0 ? 'last-child' : 'first-child';
        selection = getSelection(this.$control_input[0]);

        if (this.isFocused && !this.isInputHidden) {
            valueLength = this.$control_input.value().length;
            cursorAtEdge = direction < 0
                ? selection.start === 0 && selection.length === 0
                : selection.start === valueLength;

            if (cursorAtEdge && !valueLength) {
                this.advanceCaret(direction, e);
            }
        } else {
            $tail = this.$control.children('.g-active:' + tail);
            if ($tail) {
                idx = indexOf(this.$control.children(':not(input)'), $tail);
                this.setActiveItem(null);
                this.setCaret(direction > 0 ? idx + 1 : idx);
            }
        }
    },

    advanceCaret: function(direction, e) {
        var fn, $adj;

        if (direction === 0) return;

        fn = direction > 0 ? 'nextSibling' : 'previousSibling';
        if (this.isShiftDown) {
            $adj = this.$control_input[fn]();
            if ($adj) {
                this.hideInput();
                this.setActiveItem($adj);
                e && e.preventDefault();
            }
        } else {
            this.setCaret(this.caretPos + direction);
        }
    },

    setCaret: function(i) {

        if (this.options.mode === 'single') {
            i = this.items.length;
        } else {
            i = Math.max(0, Math.min(this.items.length, i));
        }

        if (!this.isPending) {
            // the input must be moved by leaving it in place and moving the
            // siblings, due to the fact that focus cannot be restored once lost
            // on mobile webkit devices
            var j, n, fn, $children, $child;
            $children = this.$control.children(':not(input)');
            for (j = 0, n = ($children ? $children.length : 0); j < n; j++) {
                $child = $($children[j]);//.detach();
                if (j < i) {
                    $child.before(this.$control_input);
                } else {
                    this.$control.appendChild($child);
                }
            }
        }

        this.caretPos = i;
    },

    lock: function() {
        this.close();
        this.isLocked = true;
        this.refreshState();
    },

    unlock: function() {
        this.isLocked = false;
        this.refreshState();
    },

    disable: function() {
        this.input.disabled(true);
        this.$control_input.attribute('disabled', true).attribute('tabindex', -1);
        this.isDisabled = true;
        this.lock();
    },

    enable: function() {
        this.input.attribute('disabled', null);
        this.$control_input.attribute('disabled', null).attribute('tabindex', this.tabIndex);
        this.isDisabled = false;
        this.unlock();
    },

    destroy: function() {
        var revertSettings = this.revertSettings;

        this.emit('destroy');
        this.off();
        this.$wrapper.remove();
        this.$dropdown.remove();

        this.input
            .html('')
            .appendChild(revertSettings.$children)
            .attribute('tabindex', null)
            .removeClass('selectized')
            .attribute({ tabindex: revertSettings.tabindex })
            .show();

        /*$(window).off(eventNS);
         $(document).off(eventNS);
         $(document.body).off(eventNS);*/

        delete this.$control_input.selectizeGrow;
        delete this.input.selectizeInstance;
        delete this.input[0].selectize;
    },

    render: function(templateName, data) {
        var value, id, label;
        var name = '';
        var cache = false;
        var regex_tag = /^[\t \r\n]*<([a-z][a-z0-9\-_]*(?:\:[a-z][a-z0-9\-_]*)?)/i;

        if (templateName === 'option' || templateName === 'item') {
            value = hash_key(data[this.options.valueField]);
            cache = !!value;
        }

        // pull markup from cache if it exists
        if (cache) {
            if (!isset(this.renderCache[templateName])) {
                this.renderCache[templateName] = {};
            }
            if (this.renderCache[templateName].hasOwnProperty(value)) {
                return this.renderCache[templateName][value];
            }
        }

        // render markup
        var html = zen('div').html(this.options.render[templateName].apply(this, [data, escapeHTML]));
        html = html.firstChild();

        // add mandatory attributes
        if (templateName === 'option' || templateName === 'option_create') {
            html = html.data('selectable', '');
        }
        if (templateName === 'optgroup') {
            id = data[this.options.optgroupValueField] || '';
            name = escape_replace(escapeHTML(id));
            html = html.data('group', name).attribute('role', 'group').attribute('aria-label', name);
        }
        if (templateName === 'option' || templateName === 'item') {
            name = escape_replace(escapeHTML(value || ''));
            html = html.data('value', name).attribute('id', slugify(this.rand + '-' + name)).attribute('role', 'treeitem').attribute('aria-label', trim(data.text)).attribute('aria-selected', 'false');
        }

        // update cache
        if (cache) {
            this.renderCache[templateName][value] = html[0];
        }

        return html[0];
    },

    clearCache: function(templateName) {
        if (typeof templateName === 'undefined') {
            this.renderCache = {};
        } else {
            delete this.renderCache[templateName];
        }
    },

    canCreate: function(input) {
        if (!this.options.create) return false;
        var filter = this.options.createFilter;
        return input.length
            && (typeof filter !== 'function' || filter.apply(self, [input]))
            && (typeof filter !== 'string' || new RegExp(filter).test(input))
            && (!(filter instanceof RegExp) || filter.test(input));
    },

    getPreviousValue: function() {
        return this.previousValue;
    }
});

$.implement({
    selectize: function(settings_user) {
        settings_user = settings_user || {};
        var defaults             = Selectize.prototype.options,
            settings             = merge({}, defaults, settings_user),
            attr_data            = settings.dataAttr,
            field_label          = settings.labelField,
            field_value          = settings.valueField,
            field_optgroup       = settings.optgroupField,
            field_optgroup_label = settings.optgroupLabelField,
            field_optgroup_value = settings.optgroupValueField;

        var init_textbox = function(input, settings_element) {
            input = $(input);
            var i, n, values, option;

            var data_raw = input.attribute(attr_data);

            if (!data_raw) {
                var value = trim(input.value() || '');
                if (!settings.allowEmptyOption && !value.length) return;

                values = value.split(settings.delimiter);
                for (i = 0, n = values.length; i < n; i++) {
                    option = {};
                    option[field_label] = values[i];
                    option[field_value] = values[i];

                    settings_element.Options.push(option);
                }

                settings_element.items = values;
            } else {
                settings_element.Options = JSON.parse(data_raw);
                for (i = 0, n = settings_element.Options.length; i < n; i++) {
                    settings_element.items.push(settings_element.Options[i][field_value]);
                }
            }
        };

        var init_select = function(input, settings_element) {
            var i, n, tagName, children, order = 0;
            var options = settings_element.Options;
            var optionsMap = {};

            var readData = function(el) {
                var data = attr_data && el.attribute(attr_data);
                if (typeof data === 'string' && data.length) {
                    return JSON.parse(data);
                }
                return null;
            };

            var addOption = function(option, group) {
                var value, opt;

                option = $(option);

                value = hash_key(option.value());
                if (!value.length && !settings.allowEmptyOption) return;

                // if the option already exists, it's probably been
                // duplicated in another optgroup. in this case, push
                // the current group to the "optgroup" property on the
                // existing option so that it's rendered in both places.
                if (optionsMap.hasOwnProperty(value)) {
                    if (group) {
                        var arr = optionsMap[value][field_optgroup];
                        if (!arr) {
                            optionsMap[value][field_optgroup] = group;
                        } else if (!isArray(arr)) {
                            optionsMap[value][field_optgroup] = [arr, group];
                        } else {
                            arr.push(group);
                        }
                    }
                    return;
                }

                opt = readData(option) || {};
                opt[field_label] = opt[field_label] || option.text();
                opt[field_value] = opt[field_value] || value;
                opt[field_optgroup] = opt[field_optgroup] || group;

                optionsMap[value] = opt;
                options.push(opt);

                if (option.matches(':selected')) {
                    settings_element.items.push(value);
                }
            };

            var addGroup = function(optgroup) {
                var i, n, id, optgrp, options;

                optgroup = $(optgroup);
                id = optgroup.attribute('label');

                if (id) {
                    optgrp = readData(optgroup) || {};
                    optgrp[field_optgroup_label] = id;
                    optgrp[field_optgroup_value] = id;
                    settings_element.Optgroups.push(optgrp);
                }

                options = optgroup.search('option');
                for (i = 0, n = options.length; i < n; i++) {
                    addOption(options[i], id);
                }
            };

            settings_element.maxItems = input.attribute('multiple') ? null : 1;

            children = input.children() || 0;
            for (i = 0, n = children.length; i < n; i++) {
                tagName = children[i].tagName.toLowerCase();
                if (tagName === 'optgroup') {
                    addGroup(children[i]);
                } else if (tagName === 'option') {
                    addOption(children[i]);
                }
            }
        };

        return this.forEach(function($input, i) {
            settings = merge({}, defaults, settings_user),
            $input = $($input);
            if ($input.selectizeInstance) return;

            var instance,
                dataOptions = $input.data('selectize'),
                tag_name    = $input.tag().toLowerCase(),
                placeholder = $input.attribute('placeholder') || $input.attribute('data-placeholder');

            // g5 custom
            if (dataOptions) { dataOptions = JSON.parse(dataOptions); }
            settings = merge({}, settings, dataOptions);
            // end g5 custom

            if (!placeholder && !settings.allowEmptyOption) {
                var chlds = $input.children('option[value=""]');
                placeholder = chlds ? $input.children('option[value=""]').text() : '';
            }

            var settings_element = {
                'placeholder': placeholder,
                'Options': [],
                'Optgroups': [],
                'items': []
            };

            if (tag_name === 'select') {
                init_select($input, settings_element);
            } else {
                init_textbox($input, settings_element);
            }

            instance = new Selectize($input, merge({}, defaults, settings_element, settings_user, dataOptions));
            $input.selectizeInstance = instance;
        });
    }
});

ready(function() {
    var selects = $('[data-selectize]');
    if (!selects) { return; }

    selects.selectize();
});


module.exports = Selectize;
