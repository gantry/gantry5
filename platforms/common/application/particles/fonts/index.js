"use strict";
// fonts list: https://www.googleapis.com/webfonts/v1/webfonts?key=AIzaSyB2yJM8DBwt66u2MVRgb6M4t9CqkW7_IRY
var prime       = require('prime'),
    $           = require('../../utils/elements.moofx'),
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

    previewSentence: 'Wizard boy Jack loves the grumpy Queen\'s fox.',

    constructor: function() {
        this.wf = wf;
        this.data = null;
        this.field = null;
        this.element = null;
        this.throttle = false;
        this.selected = null;
        this.loadedFonts = [];
    },

    open: function(event, element, container) {
        if (!this.data || !this.field) { return this.getData(element); }

        var list = [];
        forEach(this.data, function(value) {
            list.push(value.family);
        });

        if (container) {
            container.empty().appendChild(this.buildLayout());
            this.scroll(container.find('ul.g-fonts-list'));
            this.updateTotal();
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
                if (!contains(this.loadedFonts, dataFont)) { list.push(dataFont); }
                else {
                    element.find('[data-variant="' + variant + '"] .preview').style({
                        fontFamily: dataFont,
                        fontWeight: variant == 'regular' ? 'normal' : variant
                    });
                }
            }, this);

            if (!list || !list.length) { return; }

            wf.load({
                classes: false,
                google: {
                    families: list
                },
                fontactive: bind(function(family, fvd) {
                    var v = container.find('li[data-font="' + family + '"]').data('variant');
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

    select: function(element, variant, target) {
        var baseVariant = element.data('variant');

        if (!this.selected || this.selected.element != element) {
            this.selected = {
                font: element.data('font'),
                baseVariant: baseVariant,
                element: element,
                variants: [baseVariant],
                selected: [],
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
            if (selected) { selected.checked(false); }
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
            var variants = this.selected.element.data('variants'), list = [], variant;
            if (variants.split(',').length > 1) {
                this.manipulateLink(this.selected.font);
                this.selected.element.search('[data-font]').removeClass('g-variant-hide');

                if (!this.selected.loaded) {
                    wf.load({
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

        this.select(element.parent('[data-font]') || element, element.parent('[data-font]') ? element.data('variant') : false, element);

        //console.log(element, this.selected);
        return false;
    },

    updateSelection: function(){
        var preview = $('.g-fonts-footer .font-selected'), selected;
        if (!preview) { return; }

        if (!this.selected.selected.length) { preview.empty(); return; }

        selected = this.selected.selected.sort();
        //console.log(selected.map(this.mapVariant));
        preview.html('<strong>' + this.selected.font + '</strong> (<small>' + selected.join(', ').replace('regular', 'normal') + '</small>)');
    },

    updateTotal: function(){
        var totals = $('.g-fonts-header .font-search-total'),
            count = $('.g-fonts-list > [data-font]:not(.g-font-hide)');

        totals.text(count ? count.length : 0);
    },

    buildLayout: function() {
        var previewSentence = this.previewSentence,
            html = zen('div#g-fonts.g-grid'),
            //sidebar = zen('div.g-sidebar.g-block.size-1-4').bottom(html),
            main = zen('div.g-fonts-main').bottom(html),
            ul = zen('ul.g-fonts-list').bottom(main),
            families = [], list, categories = [], subsets = [];

        // zen('div.settings-block').appendChild(zen('input[type="text"][placeholder="Search..."]')).top(sidebar);

        this.buildHeader(html).top(html);
        this.buildFooter(html).bottom(html);

        ul.on('scroll', bind(this.scroll, this, ul));

        html.delegate('click', '.g-fonts-list li[data-font]', bind(this.toggle, this));
        //html.delegate('click', '.g-fonts-list > li li[data-font]', bind(this.toggle, this));

        async.eachSeries(this.data, bind(function(font, callback) {
            combine(subsets, font.subsets);
            insert(categories, font.category)
            var variants = font.variants.join(',').replace('regular', 'normal'),
                variant = contains(font.variants, 'regular') ? '' : ':' + font.variants[0],
                li = zen('li[data-font="' + font.family + '"][data-variant="' + (variant.replace(':', '') || 'regular') + '"][data-variants="' + variants + '"]').bottom(ul),
                total = font.variants.length + ' style' + (font.variants.length > 1 ? 's' : '');

            zen('div.family').html('<strong>' + font.family + '</strong>, ' + total).bottom(li);

            var variantContainer = zen('ul').bottom(li), variantFont, label;
            async.each(font.variants, bind(function(current) {
                variantFont = zen('li[data-font="' + font.family + '"][data-variant="' + current + '"]').bottom(variantContainer);
                zen('input[type="checkbox"][value="' + current + '"]').bottom(variantFont);
                zen('div.variant').html('<small>' + this.mapVariant(current) + '</small>').bottom(variantFont);
                zen('div.preview').text(previewSentence).bottom(variantFont);

                if (':' + current !== variant && current !== (variant || 'regular')) { variantFont.addClass('g-variant-hide'); }
            }, this));

            /*if (!contains(font.subsets, 'latin')) {
             li.style({display: 'none'});
             }*/

            families.push(font.family + variant);
            callback();
        }, this));

        var catContainer = html.find('select.font-category'), subContainer = html.find('select.font-subsets');

        categories.forEach(function(category) {
            zen('option[value="' + category + '"]').text(properCase(unhyphenate(category))).bottom(catContainer);
        }, this);

        subsets.forEach(function(subset) {
            subset = subset.replace('ext', 'extended');
            zen('option[value="' + subset + '"]').text(properCase(unhyphenate(subset))).bottom(subContainer);
        }, this);

        return html;
    },

    buildHeader: function(html) {
        var container = zen('div.settings-block.g-fonts-header').bottom(html),
            preview = zen('input.float-left.font-preview[type="text"][data-font-preview][placeholder="Font Preview..."][value="' + this.previewSentence + '"]').bottom(container),
            searchWrapper = zen('span.font-search-wrapper.float-right').bottom(container),
            search = zen('input.font-search[type="text"][data-font-search][placeholder="Search Font..."]').bottom(searchWrapper),
            totals = zen('span.font-search-total').bottom(searchWrapper);

        search.on('keyup', bind(this.search, this, search));
        preview.on('keyup', bind(this.updatePreview, this, preview));

        return container;
    },

    buildFooter: function(html) {
        var container = zen('div.settings-block.g-fonts-footer').bottom(html),
            leftContainer = zen('div.float-left.font-left-container').bottom(container),
            rightContainer = zen('div.float-right.font-right-container').bottom(container),
            category = zen('select.font-category').bottom(leftContainer),
            subsets = zen('select.font-subsets').bottom(leftContainer),
            selected = zen('span.font-selected').bottom(rightContainer),
            select = zen('button.button.button-primary').text('Select').bottom(rightContainer);

        return container;
    },

    search: function(input, event){
        var list = $('.g-fonts-list'),
            value = input.value(),
            name, re;

        if (input.previousValue == value) { return true; }

        list.search('> [data-font]').forEach(function(font){
            font = $(font);
            name = font.data('font');

            // we dont want to hide selected fonts
            if (this.selected && this.selected.font == name && this.selected.selected.length) { return; }

            // checks for other criterias such as subset and category goes here TODO

            if (!name.match(new RegExp("^" + value + '|\\s' + value, 'gi'))){
                font.addClass('g-font-hide');
            } else {
                font.removeClass('g-font-hide');
            }
        }, this);

        this.updateTotal();

        clearTimeout(input.refreshTimer);
        input.refreshTimer = setTimeout(bind(function(){
            this.scroll($('ul.g-fonts-list'));
        }, this), 400);

        input.previousValue = value;
    },

    updatePreview: function(input){
        clearTimeout(input.refreshTimer);
        var value = input.value(),
            list = $('.g-fonts-list');
        value = trim(value) ? trim(value) : this.previewSentence;

        if (input.previousValue == value) { return true; }

        //input.refreshTimer = setTimeout(bind(function(){
            list.search('[data-font] .preview').text(value);
        //}, this), 50);

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

var FontsPicker = new Fonts();

domready(function() {
    $('body').delegate('click', '[data-g5-fontpicker]', bind(FontsPicker.open, FontsPicker));
});

module.exports = FontsPicker;