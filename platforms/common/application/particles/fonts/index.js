"use strict";
// fonts list: https://www.googleapis.com/webfonts/v1/webfonts?key=AIzaSyB2yJM8DBwt66u2MVRgb6M4t9CqkW7_IRY
var prime       = require('prime'),
    $           = require('../../utils/elements.utils'),
    zen         = require('elements/zen'),
    storage     = require('prime/map')(),
    Emitter     = require('prime/emitter'),
    Bound       = require('prime-util/prime/bound'),
    Options     = require('prime-util/prime/options'),
    domready    = require('elements/domready'),

    bind        = require('mout/function/bind'),
    map         = require('mout/array/map'),
    forEach     = require('mout/array/forEach'),
    contains    = require('mout/array/contains'),
    last        = require('mout/array/last'),
    split       = require('mout/array/split'),
    removeAll   = require('mout/array/removeAll'),
    insert      = require('mout/array/insert'),
    find        = require('mout/array/find'),
    combine     = require('mout/array/combine'),
    merge       = require('mout/object/merge'),

    unhyphenate = require('mout/string/unhyphenate'),
    properCase  = require('mout/string/properCase'),
    trim        = require('mout/string/trim'),

    modal       = require('../../ui').modal,
    async       = require('async'),

    request     = require('agent'),

    wf          = require('./webfont');

require('../../utils/elements.viewport');

var Fonts = new prime({

    mixin: Bound,

    inherits: Emitter,

    previewSentence: {
        'latin': 'Wizard boy Jack loves the grumpy Queen\'s fox.',
        'latin-ext': 'Wizard boy Jack loves the grumpy Queen\'s fox.',
        'cyrillic': 'В чащах юга жил бы цитрус? Да, но фальшивый экземпляр!',
        'cyrillic-ext': 'В чащах юга жил бы цитрус? Да, но фальшивый экземпляр!',
        'devanagari': 'एक पल का क्रोध आपका भविष्य बिगाड सकता है',
        'greek': 'Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός',
        'greek-ext': 'Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός',
        'khmer': 'ខ្ញុំអាចញ៉ាំកញ្ចក់បាន ដោយគ្មានបញ្ហា',
        'telugu': 'దేశ భాషలందు తెలుగు లెస్స',
        'vietnamese': 'Tôi có thể ăn thủy tinh mà không hại gì.'
    },

    constructor: function() {
        this.wf = wf;
        this.data = null;
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

    open: function(event, element, container) {
        if (!this.data || !this.field) { return this.getData(element); }

        var list = [];
        forEach(this.data, function(value) {
            list.push(value.family);
        });

        if (container) {
            container.empty().attribute('style', null).appendChild(this.buildLayout());
            this.scroll(container.find('ul.g-fonts-list'));
            this.updateTotal();
            this.selectFromValue();
            return;
        }

        modal.open({
            content: 'Loading...',
            className: 'g5-dialog-theme-default g5-modal-fonts',
            afterOpen: bind(function(container) {
                setTimeout(bind(function() {
                    container.empty().appendChild(this.buildLayout());
                    this.scroll(container.find('ul.g-fonts-list'));
                    this.updateTotal();
                    this.selectFromValue();
                }, this), 1);
            }, this)
        });
    },

    getData: function(element) {
        var data = element.data('g5-fontpicker');
        if (!data) {
            throw new Error('No fontpicker data found');
        }

        data = JSON.parse(data);
        this.field = $(data.field);

        modal.open({
            content: 'Loading...',
            className: 'g5-dialog-theme-default g5-modal-fonts',
            remote: data.data,
            remoteLoaded: bind(function(response, instance) {
                if (response.error) {
                    instance.elements.content.html(response.body.html + '[' + data.data + ']');
                    return false;
                }

                this.data = response.body.items;

                this.open(null, element, instance.elements.content);
            }, this)
        });
    },

    scroll: function(container) {
        clearTimeout(this.throttle);
        this.throttle = setTimeout(bind(function() {
            var elements = (container.find('ul.g-fonts-list') || container).inviewport(' > li:not(.g-font-hide)', 5000),
                list = [];

            if (!elements) { return; }

            $(elements).forEach(function(element) {
                element = $(element);
                var dataFont = element.data('font'),
                    variant = element.data('variant');

                if (!contains(this.loadedFonts, dataFont)) { list.push(dataFont + (variant != 'regular' ? ':' + variant : '')); }
                else {
                    element.find('[data-variant="' + variant + '"] .preview').style({
                        fontFamily: dataFont,
                        fontWeight: variant == 'regular' ? 'normal' : variant
                    });
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
        }, this), 250);
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
        var value = this.field.value();

        if (!value.match('family=')) { return false; }

        var split = value.split('&'),
            family = split[0],
            split2 = family.split(':'),
            name = split2[0].replace('family=', '').replace(/\+/g, ' '),
            variants = split2[1] ? split2[1].split(',') : ['regular'],
            subset = split[1] ? split[1].replace('subset=', '').split(',') : ['latin'];

        if (contains(variants, '400')) {
            removeAll(variants, '400');
            insert(variants, 'regular');
        }
        if (contains(variants, '400italic')) {
            removeAll(variants, '400italic');
            insert(variants, 'italic');
        }

        var element = $('ul.g-fonts-list > [data-font="' + name + '"]');

        this.selected = {
            font: name,
            baseVariant: element.data('variant'),
            element: element,
            variants: variants,
            selected: [],
            charsets: subset,
            availableVariants: element.data('variants').split(','),
            expanded: false,
            loaded: false
        };

        variants.forEach(function(variant) {
            this.select(element, variant);
            element.find('> ul > [data-variant="' + variant + '"]').removeClass('g-variant-hide');
        }, this);

        var charsetSelected = element.find('.font-charsets-selected');
        if (charsetSelected) { charsetSelected.text('(' + subset.length + ' selected)'); }

        $('ul.g-fonts-list')[0].scrollTop = element[0].offsetTop;
        this.toggleExpansion();
        setTimeout(bind(function() { this.toggleExpansion(); }, this), 50);
    },

    select: function(element, variant/*, target*/) {
        var baseVariant = element.data('variant');

        if (!this.selected || this.selected.element != element) {
            if (variant && this.selected) {
                var charsetSelected = this.selected.element.find('.font-charsets-selected');
                if (charsetSelected) { charsetSelected.text('(1 selected)'); }
            }
            this.selected = {
                font: element.data('font'),
                baseVariant: baseVariant,
                element: element,
                variants: [baseVariant],
                selected: [],
                charsets: ['latin'],
                availableVariants: element.data('variants').split(','),
                expanded: false,
                loaded: false
            };
        }

        if (!variant) {
            this.toggleExpansion();
        }


        if (variant) {
            var selected = ($('ul.g-fonts-list > [data-font]:not([data-font="' + this.selected.font + '"]) input[type="checkbox"]:checked'));
            if (selected) {
                selected.checked(false);
                selected.parent('[data-variants]').removeClass('font-selected');
            }
            var checkbox = this.selected.element.find('input[type="checkbox"][value="' + variant + '"]'),
                checked = checkbox.checked();
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
                            var style = this.fvdToStyle(family, fvd),
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
        var preview = $('.g-particles-footer .font-selected'), selected;
        if (!preview) { return; }

        if (!this.selected.selected.length) {
            preview.empty();
            this.selected.element.removeClass('font-selected');
            return;
        }

        selected = this.selected.selected.sort();
        this.selected.element.addClass('font-selected');
        preview.html('<strong>' + this.selected.font + '</strong> (<small>' + selected.join(', ').replace('regular', 'normal') + '</small>)');
    },

    updateTotal: function() {
        var totals = $('.g-particles-header .particle-search-total'),
            count = $('.g-fonts-list > [data-font]:not(.g-font-hide)');

        totals.text(count ? count.length : 0);
    },

    buildLayout: function() {
        this.filters.script = 'latin';
        var previewSentence = this.previewSentence[this.filters.script],
            html = zen('div#g-fonts.g-grid'),
            main = zen('div.g-particles-main').bottom(html),
            ul = zen('ul.g-fonts-list').bottom(main),
            families = [], list, categories = [], subsets = [];


        this.buildHeader(html).top(html);
        this.buildFooter(html).bottom(html);

        ul.on('scroll', bind(this.scroll, this, ul));

        html.delegate('click', '.g-fonts-list li[data-font]', bind(this.toggle, this));

        async.eachSeries(this.data, bind(function(font, callback) {
            combine(subsets, font.subsets);
            insert(categories, font.category);
            this.filters.categories.push(font.category);
            var variants = font.variants.join(',').replace('regular', 'normal'),
                variant = contains(font.variants, 'regular') ? '' : ':' + font.variants[0],
                li = zen('li[data-font="' + font.family + '"][data-variant="' + (variant.replace(':', '') || 'regular') + '"][data-variants="' + variants + '"]').bottom(ul),
                total = font.variants.length + ' style' + (font.variants.length > 1 ? 's' : ''),
                charsets = font.subsets.length > 1 ? ', <span class="font-charsets">' + font.subsets.length + ' charsets <span class="font-charsets-selected">(1 selected)</span></span>' : '';

            var family = zen('div.family').html('<strong>' + font.family + '</strong>, ' + total + charsets).bottom(li),
                charset = family.find('.font-charsets-selected');

            if (charset) {
                charset.popover({
                    placement: 'auto',
                    width: '200',
                    trigger: 'mouse',
                    style: 'font-categories, above-modal'
                }).on('beforeshow.popover', bind(function(popover) {
                    var subsets = font.subsets,
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
                        charset.text('(' + this.selected.charsets.length + ' selected)');
                    }, this));

                    popover.displayContent();
                }, this));
            }

            var variantContainer = zen('ul').bottom(li), variantFont, label;
            async.each(font.variants, bind(function(current) {
                variantFont = zen('li[data-font="' + font.family + '"][data-variant="' + current + '"]').bottom(variantContainer);
                zen('input[type="checkbox"][value="' + current + '"]').bottom(variantFont);
                zen('div.variant').html('<small>' + this.mapVariant(current) + '</small>').bottom(variantFont);
                zen('div.preview').text(previewSentence).bottom(variantFont);

                if (':' + current !== variant && current !== (variant || 'regular')) { variantFont.addClass('g-variant-hide'); }
            }, this));

            if (!contains(font.subsets, 'latin')) {
                li.addClass('g-font-hide');
            }

            families.push(font.family + variant);
            callback();
        }, this));

        var catContainer = html.find('a.font-category'), subContainer = html.find('a.font-subsets');

        catContainer.data('font-categories', categories.join(',')).html('Categories (<small>' + categories.length + '</small>) <i class="fa fa-caret-down"></i>');
        subContainer.data('font-subsets', subsets.join(',')).html('Subsets (<small>' + properCase(unhyphenate(this.filters.script.replace('ext', 'extended'))) + '</small>) <i class="fa fa-caret-down"></i>');

        return html;
    },

    buildHeader: function(html) {
        var container = zen('div.settings-block.g-particles-header').bottom(html),
            preview = zen('input.float-left.font-preview[type="text"][data-font-preview][placeholder="Font Preview..."][value="' + this.previewSentence[this.filters.script] + '"]').bottom(container),
            searchWrapper = zen('span.particle-search-wrapper.float-right').bottom(container),
            search = zen('input.font-search[type="text"][data-font-search][placeholder="Search Font..."]').bottom(searchWrapper);
        zen('span.particle-search-total').bottom(searchWrapper);

        search.on('keyup', bind(this.search, this, search));
        preview.on('keyup', bind(this.updatePreview, this, preview));

        return container;
    },

    buildFooter: function(html) {
        var container = zen('div.settings-block.g-particles-footer').bottom(html),
            leftContainer = zen('div.float-left.font-left-container').bottom(container),
            rightContainer = zen('div.float-right.font-right-container').bottom(container),
            category = zen('a.font-category.button').bottom(leftContainer),
            subsets = zen('a.font-subsets.button').bottom(leftContainer),
            selected = zen('span.font-selected').bottom(rightContainer),
            select = zen('button.button.button-primary').text('Select').bottom(rightContainer),
            current;

        zen('span').html('&nbsp;').bottom(rightContainer);
        zen('button.button.g5-dialog-close').text('Cancel').bottom(rightContainer);

        select.on('click', bind(function() {
            if (!$('ul.g-fonts-list > [data-font] input[type="checkbox"]:checked')) {
                this.field.value('');
                modal.close();
                return;
            }

            var name = this.selected.font.replace(/\s/g, '+'),
                variation = this.selected.selected,
                charset = this.selected.charsets;

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

            this.field.value('family=' + name + (variation.length ? ':' + variation.join(',') : '') + (charset.length ? '&subset=' + charset.join(',') : ''));

            this.field.emit('input');
            $('body').emit('input', {target: this.field});

            modal.close();
        }, this));

        category.popover({
            placement: 'top',
            width: '200',
            trigger: 'mouse',
            style: 'font-categories, above-modal'
        }).on('beforeshow.popover', bind(function(popover) {
            var categories = category.data('font-categories').split(','),
                content = popover.$target.find('.g5-popover-content'),
                checked;

            content.empty();

            var div;
            categories.forEach(function(category) {
                current = contains(this.filters.categories, category) ? 'checked' : '';
                zen('div').html('<label><input type="checkbox" ' + current + ' value="' + category + '"/> ' + properCase(unhyphenate(category)) + '</label>').bottom(content);
            }, this);

            content.delegate('click', 'input[type="checkbox"]', bind(function(event, input) {
                input = $(input);
                checked = content.search('input[type="checkbox"]:checked');
                this.filters.categories = checked ? checked.map('value') : [];
                category.find('small').text(this.filters.categories.length);
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
            var subs = subsets.data('font-subsets').split(','),
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
        var list = $('.g-fonts-list'),
            value = input.value(),
            name, data;

        list.search('> [data-font]').forEach(function(font) {
            font = $(font);
            name = font.data('font');
            data = find(this.data, { family: name });
            font.removeClass('g-font-hide');

            // We dont want to hide selected fonts
            if (this.selected && this.selected.font == name && this.selected.selected.length) { return; }

            // Filter by Subset
            if (!contains(data.subsets, this.filters.script)) {
                font.addClass('g-font-hide');
                return;
            }

            // Filter by Category
            if (!contains(this.filters.categories, data.category)) {
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
            list = $('.g-fonts-list');

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