"use strict";
// fonts list: https://www.googleapis.com/webfonts/v1/webfonts?key=AIzaSyB2yJM8DBwt66u2MVRgb6M4t9CqkW7_IRY
var prime         = require('prime'),
    $             = require('../../utils/elements.utils'),
    zen           = require('elements/zen'),
    storage       = require('prime/map')(),
    Emitter       = require('prime/emitter'),
    Bound         = require('prime-util/prime/bound'),
    Options       = require('prime-util/prime/options'),
    domready      = require('elements/domready'),

    decouple      = require('../../utils/decouple'),

    bind          = require('mout/function/bind'),
    map           = require('mout/array/map'),
    forEach       = require('mout/array/forEach'),
    contains      = require('mout/array/contains'),
    last          = require('mout/array/last'),
    split         = require('mout/array/split'),
    removeAll     = require('mout/array/removeAll'),
    insert        = require('mout/array/insert'),
    append        = require('mout/array/append'),
    find          = require('mout/array/find'),
    combine       = require('mout/array/combine'),
    intersection  = require('mout/array/intersection'),
    merge         = require('mout/object/merge'),

    unhyphenate   = require('mout/string/unhyphenate'),
    properCase    = require('mout/string/properCase'),
    trim          = require('mout/string/trim'),
    getAjaxSuffix = require('../../utils/get-ajax-suffix'),
    parseAjaxURI  = require('../../utils/get-ajax-url').parse,
    getAjaxURL    = require('../../utils/get-ajax-url').global,

    modal         = require('../../ui').modal,
    asyncForEach  = require('../../utils/async-foreach'),
    translate     = require('../../utils/translate'),

    request       = require('agent'),

    wf            = require('webfontloader');

require('../../utils/elements.viewport');

var isIE = function() {
    var ua = window.navigator.userAgent;
    return ua.indexOf('MSIE ') > 0 || ua.indexOf('Trident/') > 0 || ua.indexOf('Edge/') > 0 || false;
};

var Fonts = new prime({

    mixin: Bound,

    inherits: Emitter,

    previewSentence: {
        'latin': 'Wizard boy Jack loves the grumpy Queen\'s fox.',
        'latin-ext': 'Wizard boy Jack loves the grumpy Queen\'s fox.',
        'arabic': 'نص حكيم له سر قاطع وذو شأن عظيم مكتوب على ثوب أخضر ومغلف بجلد أزرق',
        'cyrillic': 'В чащах юга жил бы цитрус? Да, но фальшивый экземпляр!',
        'cyrillic-ext': 'В чащах юга жил бы цитрус? Да, но фальшивый экземпляр!',
        'devanagari': 'एक पल का क्रोध आपका भविष्य बिगाड सकता है',
        'greek': 'Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός',
        'greek-ext': 'Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός',
        'hebrew': 'דג סקרן שט בים מאוכזב ולפתע מצא חברה',
        'khmer': 'ខ្ញុំអាចញ៉ាំកញ្ចក់បាន ដោយគ្មានបញ្ហា',
        'telugu': 'దేశ భాషలందు తెలుగు లెస్స',
        'vietnamese': 'Tôi có thể ăn thủy tinh mà không hại gì.'
    },

    constructor: function() {
        this.wf = wf;
        this.field = null;
        this.element = null;
        this.throttle = false;
        this.selected = null;
        this.loadedFonts = [];
        this.filters = {
            search: '',
            script: 'latin',
            categories: []
        };
    },

    open: function(event, element) {
        var data = element.data('g5-fontpicker');
        if (!data) {
            throw new Error('No fontpicker data found');
        }

        data = JSON.parse(data);
        this.field = $(data.field);

        modal.open({
            content: translate('GANTRY5_PLATFORM_JS_LOADING'),
            className: 'g5-dialog-theme-default g5-modal-fonts',
            remote: parseAjaxURI(getAjaxURL('fontpicker') + getAjaxSuffix()),
            remoteLoaded: bind(function(response, content) {
                var container = content.elements.content;

                this.attachEvents(container);
                this.updateCategories(container);

                this.search();

                this.scroll(container.find('ul.g-fonts-list'));
                this.updateTotal();
                this.selectFromValue();

                setTimeout(function() {
                    container.find('.particle-search-wrapper input')[0].focus();
                }, 5);
            }, this)
        });
    },

    scroll: function(container) {
        clearTimeout(this.throttle);
        this.throttle = setTimeout(bind(function() {
            if (!container) {
                clearTimeout(this.throttle);
                return;
            }

            // 550 = container height, 5 = pages
            var elements = (container.find('ul.g-fonts-list') || container).inviewport(' > li:not(.g-font-hide)', (550 * (isIE() ? 2 : 7))),
                list     = [];

            if (!elements) { return; }

            $(elements).forEach(function(element) {
                element = $(element);
                var dataFont = element.data('font'),
                    variant  = element.data('variant');

                if (!contains(this.loadedFonts, dataFont) && variant) {
                    list.push(dataFont + (variant != 'regular' ? ':' + variant : ''));
                }
                else {
                    if (variant) {
                        element.find('[data-variant="' + variant + '"] .preview').style({
                            fontFamily: dataFont,
                            fontWeight: variant == 'regular' ? 'normal' : variant
                        });
                    }
                }
            }, this);

            if (!list || !list.length) { return; }

            this.wf.load({
                classes: false,
                google: {
                    families: list
                },
                fontactive: bind(function(family, fvd) {
                    container.find('li[data-font="' + family + '"]:not(.g-variant-hide) > .preview').style({
                        fontFamily: family,
                        fontWeight: fvd
                    });
                    this.loadedFonts.push(family);
                }, this)
            });
        }, this), 100);
    },

    unselect: function(selected) {
        selected = selected || this.selected;
        if (!selected) { return false; }

        var baseVariant = selected.element.data('variant');
        selected.element.removeClass('selected');
        selected.element.search('input[type=checkbox]').checked(false);
        selected.element.search('[data-font]').addClass('g-variant-hide');
        selected.element.find('[data-variant="' + baseVariant + '"]').removeClass('g-variant-hide');
        selected.variants = [selected.baseVariant];
        selected.selected = [];
    },

    selectFromValue: function() {
        var value = this.field.value(), name, variants, subset, isLocal = false;

        if (!value.match('family=')) {
            var locals = $('[data-category="local-fonts"][data-font]') || [], intersect;
            locals = locals.map(function(l) { return $(l).data('font'); });
            value = value.replace(/(\s{1,})?,(\s{1,})?/gi, ',').split(',');
            intersect = intersection(locals, value);
            if (!intersect.length) { return false; }

            isLocal = true;
            name = intersect.shift();
        } else {
            var split  = value.split('&'),
                family = split[0],
                split2 = family.split(':');

            name = split2[0].replace('family=', '').replace(/\+/g, ' ');
            variants = split2[1] ? split2[1].split(',') : ['regular'];
            subset = split[1] ? split[1].replace('subset=', '').split(',') : ['latin'];
        }

        var noConflict = isLocal ? '[data-category="local-fonts"]' : 'not([data-category="local-fonts"])',
            element = $('ul.g-fonts-list > [data-font="' + name + '"]' + noConflict);
        variants = variants || element.data('variants').split(',') || ['regular'];

        if (contains(variants, '400')) {
            removeAll(variants, '400');
            insert(variants, 'regular');
        }

        if (contains(variants, '400italic')) {
            removeAll(variants, '400italic');
            insert(variants, 'italic');
        }

        this.selected = {
            font: name,
            baseVariant: element.data('variant'),
            element: element,
            variants: variants,
            selected: [],
            local: isLocal,
            charsets: subset,
            availableVariants: element.data('variants').split(','),
            expanded: isLocal,
            loaded: isLocal
        };

        (isLocal ? [name] : variants).forEach(function(variant) {
            this.select(element, variant);
            variant = element.find('> ul > [data-variant="' + variant + '"]');
            if (variant) { variant.removeClass('g-variant-hide'); }
        }, this);

        var charsetSelected = element.find('.font-charsets-selected');
        if (charsetSelected) {
            var subsetsLength = element.data('subsets').split(',').length;
            charsetSelected.html('(<i class="fa fa-fw fa-check-square-o"></i>  <span class="font-charsets-details">' + subset.length + ' of ' + subsetsLength + '</span> selected)');
        }

        if (!isLocal) { $('ul.g-fonts-list')[0].scrollTop = element[0].offsetTop; }

        this.toggleExpansion();
        setTimeout(bind(function() { this.toggleExpansion(); }, this), 50);
        if (!isLocal) { setTimeout(bind(function() { $('ul.g-fonts-list')[0].scrollTop = element[0].offsetTop; }, this, 250)); }
    },

    select: function(element, variant/*, target*/) {
        var baseVariant = element.data('variant'),
            isLocal     = !baseVariant;

        if (!this.selected || this.selected.element != element) {
            if (variant && this.selected) {
                var charsetSelected = this.selected.element.find('.font-charsets-selected');
                if (charsetSelected) {
                    var subsetsLength = element.data('subsets').split(',').length;
                    charsetSelected.html('(<i class="fa fa-fw fa-check-square-o"></i>  <span class="font-charsets-details">1 of ' + subsetsLength + '</span> selected)');
                }
            }
            this.selected = {
                font: element.data('font'),
                baseVariant: baseVariant,
                element: element,
                variants: [baseVariant],
                selected: [],
                local: isLocal,
                charsets: ['latin'],
                availableVariants: element.data('variants').split(','),
                expanded: isLocal,
                loaded: isLocal
            };
        }

        if (!variant) {
            this.toggleExpansion();
        }


        if (variant || isLocal) {
            var selected = ($('ul.g-fonts-list > [data-font]:not([data-font="' + this.selected.font + '"]) input[type="checkbox"]:checked'));
            if (selected) {
                selected.checked(false);
                selected.parent('[data-variants]').removeClass('font-selected');
            }
            var checkbox = this.selected.element.find('input[type="checkbox"][value="' + (isLocal ? this.selected.font : variant) + '"]'),
                checked  = checkbox.checked();
            if (checkbox) {
                checkbox.checked(!checked);
            }

            if (!checked) {
                insert(this.selected.variants, variant);
                insert(this.selected.selected, variant);
            } else {
                if (variant != this.selected.baseVariant) { removeAll(this.selected.variants, variant); }
                removeAll(this.selected.selected, variant);
            }

            this.updateSelection();
        }
    },

    toggleExpansion: function() {
        if (this.selected.availableVariants.length <= 1) { return; }
        if (this.selected.local) {
            this.selected.expanded = true;
            return;
        }

        if (!this.selected.expanded) {
            var variants = this.selected.element.data('variants'), variant;
            if (variants.split(',').length > 1) {
                this.manipulateLink(this.selected.font);
                this.selected.element.search('[data-font]').removeClass('g-variant-hide');

                if (!this.selected.loaded) {
                    this.wf.load({
                        classes: false,
                        google: {
                            families: [this.selected.font.replace(/\s/g, '+') + ':' + variants]
                        },
                        fontactive: bind(function(family, fvd) {
                            var style  = this.fvdToStyle(family, fvd),
                                search = style.fontWeight;

                            if (search == '400') {
                                search = style.fontStyle == 'normal' ? 'regular' : 'italic';
                            } else if (style.fontStyle == 'italic') {
                                search += 'italic';
                            }

                            this.selected.element.find('li[data-variant="' + search + '"] .preview').style(style);
                            this.selected.loaded = true;
                        }, this)
                    });
                }
            }
        } else {
            var exclude = ':not([data-variant="' + this.selected.variants.join('"]):not([data-variant="') + '"])';
            exclude = this.selected.element.search('[data-font]' + exclude);
            if (exclude) { exclude.addClass('g-variant-hide'); }
        }

        this.selected.expanded = !this.selected.expanded;
    },

    manipulateLink: function(family) {
        family = family.replace(/\s/g, '+');
        var link = $('head link[href*="' + family + '"]');
        if (!link) { return; }

        var parts = decodeURIComponent(link.href()).split('|');
        if (!parts || parts.length <= 1) { return; }

        removeAll(parts, family);

        link.attribute('href', encodeURI(parts.join('|')));
    },

    toggle: function(event, element) {
        element = $(element);
        var target = $(event.target);

        if (target.attribute('type') == 'checkbox') {
            target.checked(!target.checked());
        }

        this.select(element.parent('[data-font]') || element, element.parent('[data-font]') ? element.data('variant') : false, element);

        return false;
    },

    updateSelection: function() {
        var preview = $('.g-particles-footer .font-selected'), selected, variants;
        if (!preview) { return; }

        if (!this.selected.selected.length) {
            preview.empty();
            this.selected.element.removeClass('font-selected');
            return;
        }

        selected = this.selected.selected.sort();
        variants = this.selected.local ? '(<small>local</small>)' : '(<small>' + selected.join(', ').replace('regular', 'normal') + '</small>)';
        this.selected.element.addClass('font-selected');
        preview.html('<strong>' + this.selected.font + '</strong> ' + variants);
    },

    updateTotal: function() {
        var totals = $('.g-particles-header .particle-search-total'),
            count  = $('.g-fonts-list > [data-font]:not(.g-font-hide)');

        totals.text(count ? count.length : 0);
    },

    updateCategories: function(container) {
        var categories = container.find('[data-font-categories]');
        if (!categories) { return; }

        this.filters.categories = categories.data('font-categories').split(',');
    },

    attachEvents: function(container) {
        var header  = container.find('.g-particles-header'),
            list    = container.find('.g-fonts-list'),
            search  = header.find('input.font-search'),
            preview = header.find('input.font-preview');

        decouple(list, 'scroll', bind(this.scroll, this, list));
        container.delegate('click', '.g-fonts-list li[data-font]', bind(this.toggle, this));

        if (search) { search.on('keyup', bind(this.search, this, search)); }
        if (preview) { preview.on('keyup', bind(this.updatePreview, this, preview)); }

        this.attachCharsets(container);
        this.attachLocalVariants(container);
        this.attachFooter(container);
    },

    attachCharsets: function(container) {
        container.delegate('mouseover', '.font-charsets-selected', bind(function(event, element) {
            if (!element.PopoverDefined) {
                var popover = element.getPopover({
                    placement: 'auto',
                    width: '200',
                    trigger: 'mouse',
                    style: 'font-categories, above-modal'
                });

                element.on('beforeshow.popover', bind(function(popover) {
                    var subsets = element.parent('[data-subsets]').data('subsets').split(','),
                        content = popover.$target.find('.g5-popover-content'),
                        checked;

                    content.empty();

                    var div, current;
                    subsets.forEach(function(cs) {
                        current = contains(this.selected.charsets, cs) ? (cs == 'latin' ? 'checked disabled' : 'checked') : '';
                        zen('div').html('<label><input type="checkbox" ' + current + ' value="' + cs + '"/> ' + properCase(unhyphenate(cs.replace('ext', 'extended'))) + '</label>').bottom(content);
                    }, this);

                    content.delegate('click', 'input[type="checkbox"]', bind(function(event, input) {
                        input = $(input);
                        checked = content.search('input[type="checkbox"]:checked');
                        this.selected.charsets = checked ? checked.map('value') : [];

                        element.html('(<i class="fa fa-fw fa-check-square-o"></i>  <span class="font-charsets-details">' + this.selected.charsets.length + ' of ' + subsets.length + '</span> selected)');
                    }, this));

                    popover.displayContent();
                }, this));

                element.getPopover().show();
            }
        }, this));
    },

    attachLocalVariants: function(container) {
        container.delegate('mouseover', '.g-font-variants-list', bind(function(event, element) {
            if (!element.PopoverDefined) {
                var popover = element.getPopover({
                    placement: 'auto',
                    width: '200',
                    trigger: 'mouse',
                    style: 'font-categories, above-modal'
                });

                element.on('beforeshow.popover', bind(function(popover) {
                    var content  = popover.$target.find('.g5-popover-content'),
                        variants = element.parent('[data-variants]').data('variants').split(',');

                    content.empty();

                    asyncForEach(variants, bind(function(variant) {
                        variant = variant == '400' ? 'regular' : (variant == '400italic' ? 'italic' : variant + '');
                        zen('div').text(this.mapVariant(variant)).bottom(content);
                    }, this));

                    popover.displayContent();
                }, this));
            }
        }, this));
    },

    attachFooter: function(container) {
        var footer     = container.find('.g-particles-footer'),
            select     = footer.find('button.button-primary'),
            categories = footer.find('.font-category'),
            subsets    = footer.find('.font-subsets'),
            current;

        select.on('click', bind(function() {
            if (!$('ul.g-fonts-list > [data-font] input[type="checkbox"]:checked')) {
                this.field.value('');
                modal.close();
                return;
            }

            var name      = this.selected.font.replace(/\s/g, '+'),
                variation = this.selected.selected,
                charset   = this.selected.charsets;

            if (variation.length == 1 && variation[0] == 'regular') { variation = []; }
            if (charset.length == 1 && charset[0] == 'latin') { charset = []; }

            if (contains(variation, 'regular')) {
                removeAll(variation, 'regular');
                insert(variation, '400');
            }
            if (contains(variation, 'italic')) {
                removeAll(variation, 'italic');
                insert(variation, '400italic');
            }

            if (!this.selected.local) {
                this.field.value('family=' + name + (variation.length ? ':' + variation.join(',') : '') + (charset.length ? '&subset=' + charset.join(',') : ''));
            } else {
                this.field.value(name);
            }

            this.field.emit('input');
            $('body').emit('input', { target: this.field });

            modal.close();
        }, this));

        categories.popover({
            placement: 'top',
            width: '200',
            trigger: 'mouse',
            style: 'font-categories, above-modal'
        }).on('beforeshow.popover', bind(function(popover) {
            var cats    = categories.data('font-categories').split(','),
                content = popover.$target.find('.g5-popover-content'),
                checked;

            content.empty();

            cats.forEach(function(category) {
                if (category == 'local-fonts') { return; }
                current = contains(this.filters.categories, category) ? 'checked' : '';
                zen('div').html('<label><input type="checkbox" ' + current + ' value="' + category + '"/> ' + properCase(unhyphenate(category)) + '</label>').bottom(content);
            }, this);

            content.delegate('click', 'input[type="checkbox"]', bind(function(event, input) {
                input = $(input);
                checked = content.search('input[type="checkbox"]:checked');
                this.filters.categories = checked ? checked.map('value') : [];
                categories.find('small').text(this.filters.categories.length);
                this.search();
            }, this));

            popover.displayContent();
        }, this));

        subsets.popover({
            placement: 'top',
            width: '200',
            trigger: 'mouse',
            style: 'font-subsets, above-modal'
        }).on('beforeshow.popover', bind(function(popover) {
            var subs    = subsets.data('font-subsets').split(','),
                content = popover.$target.find('.g5-popover-content');

            content.empty();

            var div;
            subs.forEach(function(sub) {
                current = sub == this.filters.script ? 'checked' : '';
                zen('div').html('<label><input name="font-subset[]" type="radio" ' + current + ' value="' + sub + '"/> ' + properCase(unhyphenate(sub.replace('ext', 'extended'))) + '</label>').bottom(content);
            }, this);

            content.delegate('change', 'input[type="radio"]', bind(function(event, input) {
                input = $(input);
                this.filters.script = input.value();
                $('.g-particles-header input.font-preview').value(this.previewSentence[this.filters.script]);
                subsets.find('small').text(properCase(unhyphenate(input.value().replace('ext', 'extended'))));
                this.search();
                this.updatePreview();
            }, this));

            popover.displayContent();
        }, this));

        return container;
    },

    search: function(input) {
        input = input || $('.g-particles-header input.font-search');
        var list  = $('.g-fonts-list'),
            value = input.value(),
            name, subsets, category, data;

        list.search('> [data-font]').forEach(function(font) {
            font = $(font);
            name = font.data('font');
            subsets = font.data('subsets').split(',');
            category = font.data('category');
            font.removeClass('g-font-hide');

            // We dont want to hide selected fonts
            if (this.selected && this.selected.font == name && this.selected.selected.length) { return; }

            // Filter by Subset
            if (!contains(subsets, this.filters.script)) {
                font.addClass('g-font-hide');
                return;
            }

            // Filter by Category
            if (!contains(this.filters.categories, category)) {
                font.addClass('g-font-hide');
                return;
            }

            // Filter by Name
            if (!name.match(new RegExp("^" + value + '|\\s' + value, 'gi'))) {
                font.addClass('g-font-hide');
            } else {
                font.removeClass('g-font-hide');
            }
        }, this);

        this.updateTotal();

        clearTimeout(input.refreshTimer);

        input.refreshTimer = setTimeout(bind(function() {
            this.scroll($('ul.g-fonts-list'));
        }, this), 400);

        input.previousValue = value;
    },

    updatePreview: function(input) {
        input = input || $('.g-particles-header input.font-preview');

        clearTimeout(input.refreshTimer);

        var value = input.value(),
            list  = $('.g-fonts-list');

        value = trim(value) ? trim(value) : this.previewSentence[this.filters.script];

        if (input.previousValue == value) { return true; }

        list.search('[data-font] .preview').text(value);

        input.previousValue = value;
    },

    fvdToStyle: function(family, fvd) {
        var match = fvd.match(/([a-z])([0-9])/);
        if (!match) return '';

        var styleMap = {
            n: 'normal',
            i: 'italic',
            o: 'oblique'
        };
        return {
            fontFamily: family,
            fontStyle: styleMap[match[1]],
            fontWeight: (match[2] * 100).toString()
        }
    },

    mapVariant: function(variant) {
        switch (variant) {
            case '100':
                return 'Thin 100';
                break;
            case '100italic':
                return 'Thin 100 Italic';
                break;
            case '200':
                return 'Extra-Light 200';
                break;
            case '200italic':
                return 'Extra-Light 200 Italic';
                break;
            case '300':
                return 'Light 300';
                break;
            case '300italic':
                return 'Light 300 Italic';
                break;
            case '400':
            case 'regular':
                return 'Normal 400';
                break;
            case '400italic':
            case 'italic':
                return 'Normal 400 Italic';
                break;
            case '500':
                return 'Medium 500';
                break;
            case '500italic':
                return 'Medium 500 Italic';
                break;
            case '600':
                return 'Semi-Bold 600';
                break;
            case '600italic':
                return 'Semi-Bold 600 Italic';
                break;
            case '700':
                return 'Bold 700';
                break;
            case '700italic':
                return 'Bold 700 Italic';
                break;
            case '800':
                return 'Extra-Bold 800';
                break;
            case '800italic':
                return 'Extra-Bold 800 Italic';
                break;
            case '900':
                return 'Ultra-Bold 900';
                break;
            case '900italic':
                return 'Ultra-Bold 900 Italic';
                break;
            default:
                return 'Unknown Variant';
        }
    }
});

domready(function() {
    var body = $('body');
    body.delegate('click', '[data-g5-fontpicker]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        var FontPicker = storage.get(element);
        if (!FontPicker) {
            FontPicker = new Fonts();
            storage.set(element, FontPicker);
        }

        FontPicker.open(event, element);
    });
});

module.exports = Fonts;
