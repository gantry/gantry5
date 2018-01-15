# 5.4.23
## mm/dd/2018

1. [Common](#common)
    1. [](#new)
        - Updated `scssphp` to v0.7.4
        - Development Mode: Started using CSS Source Maps instead of inline comments
1. [WordPress](#wordpress)
    1. [](#new)
        - Updated Timber to v1.6.0

# 5.4.22
## 12/12/2017

1. [Common](#common)
    1. [](#bugfix)
        - Regression: Removed layout reference conflict check to prevent issues with inheritance and with older layouts
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fixed PHP 7.2 warning when using Joomla articles and categories in particles (#2188)
        
# 5.4.21
## 12/12/2017

1. [Joomla](#joomla)
    1. [](#bugfix)
        - Regression: PHP 7.2 warning fix in admin broke links from Joomla Template Manager (#2194)

# 5.4.20
## 12/11/2017

1. [Common](#common)
    1. [](#improved)
        - Remove a deprecated `Twig_Extension` function
    1. [](#bugfix)
        - Fixed PHP 7.2 warning when compiling SCSS
        - Fixed PHP 7.2 warning when using older layout format
        - Parts of inherited layouts break randomly in Layout Manager causing layout corruption if saved (#1460)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fixed PHP 7.2 warning in admin
1. [WordPress](#wordpress)
    1. [](#new)
        - Helium/Hydrogen: you can now disable Content display in Blog and Archive-type views
    1. [](#bugfix)
        - Fixed directory lookup issue on WordPress.com Business instances (possible fix for other MS installations) (#2179)
        - Fixed plugin and theme packages because of a build script issue (#2154)
        - Fixed missing `title` attribute for the menu items (#2107)
        - Fixed potentially registering same extension `GantryTwig` twice (#2034)

# 5.4.19
## 10/18/2017

1. [Common](#common)
    1. [](#bugfix)
        - This release addresses a false positive issue by ClamAV. Please upgrade to this latest version as soon as possible.
        - Fixed issue with inhering an empty section, not properly clearing out particles in the current one (#2137)
1. [WordPress](#wordpress)
    1. [](#new)
        - Updated Timber to v1.5.2
        - Helium/Hydrogen: added option for auto-generated excerpts
1. [Grav](#grav)
    1. [](#bugfix)
        - Fixed broken AJAX if `Absolute URLs` in Grav has been turned on

# 5.4.18
## 09/21/2017

1. [Common](#common)
    1. [](#new)
        - Added `responsive-font` mixin to Nucleus (#2106)
    1. [](#improved)
        - Added link target option for `Logo / Image` particle (#1887)
        - Trigger change visualizer when selecting an image (#2059)
    1. [](#bugfix)
        - Fixed inheritance overlay in Layout Manager masking all sections in a block of a container (#2114)
        - Fixed error when cloning a section with no particles (#2116, thanks @drnasin)
        - Fixed IE Edge issue in admin where the navigation bar would disappear and never reappear (#2118)
        - Properly update Collapse / Expand titles when using Collapse All / Expand All (#2004)
1. [Joomla](#joomla)
    1. [](#new)
        - Added official Joomla 3.8 support (#2111)
    1. [](#bugfix)
        - Fixed particles not using selected timezone (#2072)
        - Fixed frontend editing error when trying to open media picker (#2102)
1. [WordPress](#wordpress)
    1. [](#new)
        - Added multi-language support for outline assignments (#634)
    1. [](#bugfix)
        - Fixed double escaping links, titles and users name in WP Posts particle (#2085)
1. [Grav](#grav)
    1. [](#improved)
        - Grav Content particle looks now for authors `name`, `alias` or `username` in page header
        - Added option to cli command `bin/plugin gantry5 child-theme` to clone the theme settings (#2086)
    1. [](#bugfix)
        - Fixed outline/particle assignments when `Include default language` in Grav was `No` (#2115)
        - Fixed outline assigment priority to slightly prefer outlines assigned to language
        - Fixed cases where the top level menu would wrap below the theme title in admin (#2099)

# 5.4.17
## 08/25/2017

1. [Common](#common)
    1. [](#bugfix)
        - Fixed HTML meta tag to use property or name attribute depending on the key (#2090)
        - Regression: Fixed the same HTML id attribute being used twice in particles (#2088)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Helium: Fixed jQuery conflict with JSN UniForm and potentially other extensions (#2082)
1. [WordPress](#wordpress)
    1. [](#new)
        - Updated Timber to v1.4.1
        - Content Array particle now allows you to use single posts by providing their ID number

# 5.4.16
## 08/16/2017

1. [Common](#common)
    1. [](#bugfix)
        - Regression: Fixed missing `g-wrapper` classes in the layout

# 5.4.15
## 08/15/2017

1. [Common](#common)
    1. [](#new)
        - Added AJAX support for particles (#1376)
        - Added new twig filter `|attribute_array` to convert array of key => attribute into HTML
        - Helium/Hydrogen: added missing styling for disabled button state
    1. [](#improved)
        - Improved usability by changing order of Tabs in admin (#2061)
    1. [](#bugfix)
        - Fixed bug in nested `collection.list` on `container.tabs` (#1995)
        - Fixed no space between block attributes (#2074)
        - Helium: Fixed typo in preset 3 (#2077)
1. [Joomla](#joomla)
    1. [](#improved)
        - Joomla Articles particle won't display images alt tags (#2076)
    1. [](#bugfix)
        - Removed forced input heights set on 19px (#2063)
1. [Grav](#grav)
    1. [](#new)
        - Added cli command `bin/plugin gantry5 child-theme` which allows you to create a child theme
        - Added multi-language support for outline and position assignments (#1651, #2068)
        - Added outline and position assignments by Page Type

# 5.4.14
## 07/06/2017

1. [Common](#common)
    1. [](#improved)
        - Accessibility: Menu / Offcanvas: Offcanvas and hamburger toggle menu are now ARIA compatible (#1891)
        - Filepicker Field: File listing mode (thumbnails/list) is now remembered and restored (#1697)
        - Filepicker Field: UI updates, container is now more spacious and in list view the thumbnails are visible
        - Date and Joomla Articles/WordPress Posts/Grav Content particles: Added new date format: `Month Day, Year` (#2042)
    1. [](#bugfix)
        - Menu: Disable Dropdowns still Shows Indicator (#2031)
        - Menu: Fixed frontend menu 'Extended' option, resetting heights of wrapping containers not necessarily related to the menu itself (#2025)
        - Menu / Offcanvas: Fixed issue preventing the offcanvas toggle to show when Menu set with only icons (#1939)
        - Menu: Fixed issue with touch devices where ending the scroll gesture on the offcanvas menu would trigger the expansion of a parent menu item (#1620)
        - Fixed Tag Attributes 'enter' key causing the value to get lost while triggering the Apply (#1860)
        - Filepicker: Fixed issue with upper case extensions not getting recognized and failing to upload (#1852)
1. [WordPress](#wordpress)
    1. [](#new)
        - Updated Timber to v1.3.3
1. [Grav](#grav)
    1. [](#bugfix)
        - Fixed Particles Picker not adapting to height and scroll position in Layout Manager (#1942)

# 5.4.13
## 06/06/2017

1. [Common](#common)
    1. [](#new)
        - Updated Lightcase to v2.4.0
        - If debug mode is enabled, add HTML comments to recognize particles and positions (#639)
    1. [](#improved)
        - Menu particle: Add aria-label for icon-only menu items for better accessibility support (#1888)
    1. [](#bugfix)
        - Fixed bug with enabled field when editing disabled particle in the layout (#1571)
        - Helium: Add missing `System Messages` particle to Default and Offline layouts (#1962)
1. [Joomla](#joomla)
    1. [](#new)
        - Use the new package uninstall protection feature in Joomla 3.7
        - Embedded `System - Gantry 5` settings into the component settings (#2010)
    1. [](#bugfix)
        - Fixed issue with Regular Labs Cache Cleaner (#1833)
        - Fixed issue with Hydrogen textarea (#1973)
        - Fixed some caching issues when changing between Production and Development modes
        - Fixed untranslated month names in particles (#1322)
        - Fixed `|number_format` twig filter to use the current locale
1. [WordPress](#wordpress)
    1. [](#new)
        - Updated Timber to v1.3.1
    1. [](#bugfix)
        - Fixed checkboxes next to the setting enablers in Content tab getting unchecked after refresh (#1986)
        - Fixed `Missing argument 2 for modify_gantry5_locale()`
        - Fixed external scripts and CSS with query parameters being broken (#1975)
1. [Grav](#grav)
    1. [](#bugfix)
        - Fixed CSS/JS pipelines, though you need to set `js_minify: false` to keep the menu working (#2001)

# 5.4.12
## 04/26/2017

1. [Common](#common)
    1. [](#new)
        - Updated Bootstrap 3 to v3.3.7
    1. [](#bugfix)
        - Fixed potential error: `Undefined property: stdClass::$inherit` in when processing layout
        - Fixed a bug in `Layout::updateInheritance()` when inheritance is missing
1. [Joomla](#joomla)
    1. [](#new)
        - Added official support for Joomla 3.7
1. [Grav](#grav)
    1. [](#bugfix)
        - Fixed broken styles / scripts if CSS / JS Pipeline has been enabled (#1941)

# 5.4.11
## 04/03/2017

1. [Common](#common)
    1. [](#new)
        - Updated Lightcase.js to v2.3.6
        - Helium: Added admin controls for setting link and link hover colors. You might need to resave your theme settings (#1626)
    1. [](#bugfix)
        - Fixed nested collection list multi-item edit not functioning (#1924)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Helium and Hydrogen: Fixed frontend calendar icon size
1. [WordPress](#wordpress)
    1. [](#new)
        - Add options to disable outline assignments individually for both posts and pages, including custom types (#1279)
        - Hydrogen and Helium: Blog and Archive type pages can now display posts in columns
    1. [](#improved)
        - Administration: Hide assignment types with no items in them to make the page shorter
1. [Grav](#grav)
    1. [](#new)
        - Added `Read More` toggle for blog item page
    1. [](#bugfix)
        - Fixed missing publish date from content array particle

# 5.4.10
## 03/10/2017

1. [Common](#common)
    1. [](#new)
        - Helium: Updated Owl Carousel to v2.2.1
    1. [](#bugfix)
        - Fixed bad HTML markup in assignments administration (#1917, thanks @Quy)
        - Fixed regression in handling `container.set` (#1889)
        - Fixed missing closing tag when editing layout (#1919, thanks @Quy)
        - Fixed potential issues with URLs containing spaces (#1902)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Helium and Hydrogen: Frontend calendar icon size fixes (#1914)
        - Helium and Hydrogen: Login module styling issues (#1910, #1911)
        - Hydrogen: Frontend article editor, tooltips open up way to the right (#1912)
        - Helium: Frontend article editor, article search button misaligned and partly covered (#1913)
1. [WordPress](#wordpress)
    1. [](#new)
        - Added content post-processing to automatically resolve all stream URI links
1. [Grav](#grav)
    1. [](#new)
        - Added content post-processing to automatically resolve all stream URI links

# 5.4.9
## 02/23/2017

1. [Common](#common)
    1. [](#new)
        - Add support for atom caching when in production mode (similar to particle caching)
          - If you have overridden `partials/page.html.twig` or `partials/page_head.html.twig`, please update them
    1. [](#improved)
        - Added accessibility support for Font Awesome icons (#1873, thanks @N8Solutions)
    1. [](#bugfix)
        - Fixed `{% pageblock bottom %}...{% endpageblock %}` not working from atoms
        - Fixed issues with nested `collection.list` items after upgrading to Gantry 5.4.7 (#1877)
        - Fixed issues with `container.set` and `container.tabs` (#1882)
1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Fixed placeholder having the same color as real input values (#1876)
        - Fixed potential XSS vulnerability by updating Timber library

# 5.4.8
## 02/14/2017

1. [Common](#common)
    1. [](#bugfix)
        - Fix regression: `Edit All Items` removes the field values (#1869)
        - Fixed issue where collection is not working if the field selector is nested: `main.items` (#1867)

# 5.4.7
## 02/10/2017

1. [Common](#common)
    1. [](#new)
        - Added support for extending existing blueprint files without replacing them (#904)
    1. [](#improved)
        - Make Whoops not to report PHP startup errors and warnings (#1821)
        - Helium: Remove forced font color settings for Home outline and `g-helium-style` body class (#1783)
        - Helium: Remove underline being added by Bootstrap on social icons and menu items (#1854)
    1. [](#bugfix)
        - Fixed change in core SCSS not detected after Gantry 5 update when in production mode (#1752, #1847)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Namespaced positions references in the DOM to avoid Joomla conflicts (#1832)
        - Fixed particle button styling issues in Advanced Module Manager
        - Fixed fatal error when editing gantry menu item or particle module and Gantry theme isn't set to default (#1845)
        - Helium: Fixed wrong line height in Breadcrumbs divider (#1678)
1. [WordPress](#wordpress)
    1. [](#new)
        - Add support for Bedrock (#1429)
1. [Grav](#grav)
    1. [](#bugfix)
        - Fixed `Gantry 5` menu item in the Grav Admin Panel is not being highlighted (#1840), requires Admin plugin v1.2.11
        - Fixed issue with Production / Development Toggle (#1846)
        - Fixed missing `bottom` JS position on default page types
        - Fixed admin and nucleus translations defaulting to English (#1855)
        - Fixed multiple new particles of the same type not being properly saved on positions page (#1790)

# 5.4.6
## 01/26/2017

1. [Common](#common)
    1. [](#new)
        - Allow custom SCSS files to be inserted from particles and atoms by `<link rel="stylesheet" href="particle.scss" />`
        - Add support to refer JS/CSS files without using `url()` function in twig files (streams are handled internally)
        - Allow custom SCSS files to be inserted from both `Page Settings` / `Assets` / `CSS` and `Assets Atom` (#215, #424, #1692)
        - Add support for `{% pageblock bottom %}...{% endpageblock %}` to add HTML into the page (#1161)
          - where first parameter is one of: `body_top` | `top` | `bottom` | `body_bottom`
          - supports also `with { priority: n }` to set the priority for the block (recommended range 10 ... -10)
    1. [](#improved)
        - Menu items in frontend now render icons with `aria-hidden="true"` for accessibility (#1629)
        - Helium: Content Cubes particle uses now linear gradient made out of Accent Color 1 and 2 for its background (#1809)
    1. [](#bugfix)
        - Fixed a bug in `|html` filter
        - Fixed wrong ordering of custom CSS/JS assets
        - Fixed nested field selector in `collection.list` loosing its value when you edit all items (#1817)
        - Helium: Updated OwlCarousel, fixed a bug when OwlCarousel disappeared when only 1 item was set (#1801)
        - Helium: Fix logo image overlapping hamburger menu icon in mobile view (#1691)
        - Hydrogen: Fixed menu dropdowns items aligned to left when in RTL mode (#1753)
        - Fixed extended menu items starting from 3rd level and below not expanding vertically as expected (#1778)
        - Fixed search icon misalignment in Particles/Modules/Widgets pickers (#1827)
1. [Joomla](#joomla)
    1. [](#improved)
        - Allow previously hardcoded module and component wrappers to be overridden
        - Administrator: Add submenu to access both `Available Themes` and `Default Theme` (#1764)
        - Hide theme prefix from Outline names (#1724)
    1. [](#bugfix)
        - Fixed all `<script>` tags being corrupted in some Windows installs due to broken `uniqid()` function
        - Fixed country code on HTML tag being in lower case, enabling translations in Snipcart (#1822)
1. [WordPress](#wordpress)
    1. [](#improved)
        - Changed priority of `Front Page` and `Home Page` assignments to be higher than the rest of the group (#1762)
    1. [](#bugfix)
        - Fixed occasional `Undefined index: object_id` when trying to save menu with a separator (#1819)
        - Hydrogen: Fix pagination styling in mobile view (#1563)
        - Hydrogen / Helium: Fixed deleted or renamed `Home` outline reverting back (#1785)
          - For existing sites please see [Issue 1785](https://github.com/gantry/gantry5/issues/1785) to fix the issue
1. [Grav](#grav)
    1. [](#new)
        - Added support for `Maintenance` plugin
        - Added particle for `LangSwitcher` plugin
        - Added particle for `Feed` plugin
    1. [](#improved)
        - Rename `Appearance` to `Gantry 5` as it seems to be less confusing for most users
        - Display changes indicator when in the Positions Manager and changes happen (#1741)
    1. [](#bugfix)
        - Fixed particles inside positions having extra margin and padding (`g-content` class)
        - Fixed menu rendering issues in multi-language sites
        - Fixed login particle
        - Fixed login in offline mode accepting invalid credentials (#1808)
        - Fixed Positions Add button (#1803)
        - Fixed misaligned style for key/value field (#1789)

# 5.4.5
## 01/16/2017

1. [Common](#common)
    1. [](#bugfix)
        - Turn off menu caching for now as it caches also modules/widgets/particles inside the menu
        - Fixed bug in particle caching which causes some particles to have the same cache id
        - Fixed offcanvas menu not working properly in some sites
        - Fixed spaces in images not being urlencoded with `%20` when using `url()` function
        - Fixed empty badge in Atoms (#1798)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fix white page if the page has bad UTF8 characters (usually from badly encoded translations)
1. [Grav](#grav)
    1. [](#bugfix)
        - Fixed positions rendering escaped HTML code (#1797)

# 5.4.4
## 01/14/2017

1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Fixed broken RokSprocket and RokGallery: `Call to a member function addScript() on null` (#1794)
        - Fixed inline JavaScript rendered multiple times in `wp_footer` (#1795)
1. [Grav](#grav)
    1. [](#bugfix)
        - Fixed namespace reference for Grav Page, throwing errors for PHP < 7

# 5.4.3
## 01/13/2017

1. [Common](#common)
    1. [](#new)
        - Add particle caching when in production mode making a noticeable speed increase on particle heavy pages.
          Installed Gantry 5 themes should to be updated to a version which supports particle caching.
    1. [](#bugfix)
        - Fixed `$1` and `\\1` being lost inside `<pre>` and `<code>` blocks (#1782)
        - Admin: Fixed `Back to Setup` button not working after page reload
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fixed loading template styles `preg_replace(): Compilation failed` error (#1769)
        - Fixed blank page when rendering ACL rules in frontend Joomla forms (#1767, #1775)
        - Worked around Joomla rendering issue on duplicate module positions and modules (#1721)
        - Fixed PHP 5.3 incompatibility in `System - Gantry 5` plugin (crashes Joomla admin!)
1. [WordPress](#wordpress)
    1. [](#improved)
        - Improved title styling for Login Form particle (#1774, thanks @adi8i)
1. [Grav](#grav)
    1. [](#new)
        - Added particle for `Breadcrumbs` plugin (#1786)
    1. [](#bugfix)
        - Fixed missing particle type in position page when hovering over particle (#1771)
        - Fixed home assignment bug (#1773)
        - Fixed engine page types missing when creating a new page (#1749)

# 5.4.2
## 12/20/2016

1. [Common](#common)
    1. [](#bugfix)
        - Fix regression: Do not change links which aren't using Gantry streams in platform filter events (#1756)

# 5.4.1
## 12/13/2016

1. [Grav](#grav)
    1. [](#bugfix)
        - Fixed Grav and Grav Admin dependencies versions

# 5.4.0
## 12/13/2016

1. [Common](#common)
    1. [](#new)
        - Added Lightbox support by creating `Lightcase Atom`. By default this feature can be used by adding `data-rel="lightcase"` into the link
    1. [](#improved)
        - Start using more strict YAML syntax (compatible to future version of YAML compiler)
        - Removed hack to manipulate URL and inject default page and nonce in Grav and Wordpress
    1. [](#bugfix)
        - Fixed broken responsive support classes (#1705)
        - Fixed known PHP 7.1 issues
        - Ignore broken Gantry 5 themes instead of throwing exception
1. [Joomla](#joomla)
    1. [](#new)
        - Resolve all stream URI links in Joomla page
    1. [](#improved)
        - Improved outline assignments logic
    1. [](#bugfix)
        - Fixed error outline rendering modules which were assigned to menu items (#1732)
1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Fixed broken link from Gantry admin to plugin settings
        - Fixed outline duplication copying assignments (#1719)
        - Fixed widgets and particles not showing up in menu (#1715)
1. [Grav](#grav)
    1. [](#new)
        - Grav is now integrated with Gantry 5!

# 5.3.9
## 11/23/2016

1. [Common](#common)
    1. [](#improved)
        - Helium: Load `jQuery` from particles instead of using `JavaScript Frameworks` atom to load it into every page
    1. [](#bugfix)
        - Fixed disabled atoms being rendered (#1671)
        - Fixed issues with responsive support classes (#1487)
        - Helium: Fixed tab rendering issues in `Content tabs` particle (#1635)
        - Fixed issue preventing particles to be switched between in the Inheritance panel
1. [Joomla](#joomla)
    1. [](#improved)
        - Automatically load jQuery and Mootools frameworks in error page if particles or atoms request them
    1. [](#bugfix)
        - Fixed issue where Apache rewrite rule is overriding component but hitting `404 Page Not Found` instead of Joomla properly routing to the new location
1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Fixed PHP warning when saving menu with no menu items
        - Fixed incompatibility with WooCommerce Payu Latam plugin (#1628)
        - Fixed `Gantry: Please set current configuration before using $gantry["config"]` (#942)

# 5.3.8
## 11/10/2016

1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Fixed Gantry settings not being available in multi-site environments (#1610, thanks @dudewithamood)
        - Fixed issues with Gantry menu mixing up menu item parameters and ordering after using WordPress Importer (#1669)

# 5.3.7
## 11/09/2016

1. [Common](#common)
    1. [](#new)
        - Updated FontAwesome to 4.7.0 which includes [Grav](https://getgrav.org)'s Logo!
    1. [](#bugfix)
        - Helium: Fixed wrong font being used for the content titles (#1603)
        - Helium: Remove Expanded section padding on tablet-range view
        - Helium: Remove unneeded menu overlay when viewing site on touch devices
        - Hydrogen / Helium: Fixed Offcanvas toggle visibility setting (#1630)
        - Prevent broken Layout from breaking Gantry administration
        - Fixed Collection Lists' multi edit collapse/expand that could potentially end up stuck closed (#1612)
        - Keep focus when clearing Inheritance dropdown (#1632)
        - Hide non-overridable fields inside tabs (#1665)
        - Fixed nested collections being non-editable after using `Edit all items` button in a parent collection (#1612)
        - Fixed Base Outline loosing all particles when loading another outline with inheritance (#1617)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fix permissions for editing Particle modules without having access to Gantry admin (#1476)
        - Fixed Gantry menu editor loosing content of CSS field in Joomla menu item (#1656)
        - Fixed menu ordering issues when menu item alias got changed; to become effective menu must be saved once in Gantry (#595)
        - Fixed 'joomla.categories' YAML field type displaying trashed and archived categories (#1625)
1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Hydrogen / Helium: Fixed password field translation (#1657)
        - Fixed editing the menu item titles under `Appearance > Menu` changing the order of menu items in Gantry (#1016)

# 5.3.6
## 10/06/2016

1. [Common](#common)
    1. [](#new)
        - Updated scssphp to v0.6.6
        - Added `nomarginleft`, `nomarginright`, `nopaddingleft`, `nopaddingright` CSS utility classes to Nucleus
        - Add configuration option to use default platform upload path when adding new images via file picker (#1597)
        - SCSS compiler: Make all URLs https compatible by replacing `http://` protocol with `//`
        - Helium: Added a second sidebar `Aside` to the default layout. It and `Sidebar` have also been set to have fixed size
        - Add initial support for translating form field `label` and `description` (#84)
        - Collections: Multi edit now features a global input label filter and a way to collapse/expand the items (#1579)
    1. [](#improved)
        - Display sorted sidebar folders in Filepicker
        - Better coordination for dragged items in Menu and Layout Manager (related #1576)
        - Using regular select for Dropdown Animation
        - Social Particle: New Display option allows to show icons only, text only or both (#1565)
        - Helium: Removed redundant favicon field in the Styles tab. Please use the one in the Page Settings.
        - Updated Google Fonts (+3 fonts)
        - It is now possible to disable links in a Logo / Image particle (thanks @adi8i - #1607)
    1. [](#bugfix)
        - Fixed title editing for newly added Outlines (#1555)
        - Fixed fields set to not override still displaying overridable in Tabs containers (#1552)
        - Fixed broken URLs for non-existing files in Custom HTML particle
        - Fixed XML errors outside Gantry triggering an error (thanks @Chrissi2812, #1567)
        - Fixed issue with Block attributes not rendering when the parent Section was inheriting (#1577, #1580)
        - Fixed particles getting lost in offcancas section when loading layout preset with older format (#1593)
        - Fixed first time compilation of custom.scss not working (#1590)
        - Fixed override checkboxes showing up in settings tabs when they should not (#1578)
        - Fixed enablers in `Particle Defaults` having custom value and still appearing to be unchecked (#1570)
        - Fixed inheritance converting associative arrays into objects causing associative lists to be missing in inherited sections and particles (#1585)
1. [Joomla](#joomla)
    1. [](#new)
        - Add plugin events `onGantry5AdminInit` and `onGantry5ThemeInit` to allow custom Twig filters and functions (#1584)
    1. [](#bugfix)
        - Fixed routing for `index.php?Itemid=xxx` URLs inside particles
        - Joomla Articles Particle: Add field for entering article ids (thanks @JoomFX - #1591)
        - Fixed accessing Particle module from Joomla Module Manager if user does not have access to edit template (#1476)
        - Menu Manager: Display info message and prevent user from saving menu if menu items have been checked out (#1019)
        - Fixed missing system message in component modals (#1156)
        - Fixed typo on custom translation filename (#1600)
        - Fixed error in Menu particle when site has no default menu selected for the language
1. [WordPress](#wordpress)
    1. [](#improved)
        - Extended categories field to allow selecting custom taxonomies (#1535)
        - Update Timber to 1.1.5 for new features (#1556)
    1. [](#bugfix)
        - Helium: Fixed duration parameter in Content Tabs particle
        - Fixed Colorpicker zIndex in Particle Settings (#1574)
        - Fixed wrong protocol in compiled CSS files in a site that uses both http and https (#1594)

# 5.3.5
## 09/02/2016

1. [Common](#common)
    1. [](#new)
        - Implemented platform specific composer dependencies
    1. [](#bugfix)
        - Fixed compiled CSS files having bad relative URLs, regression was introduced with [v5.3.3](http://gantry.org/#changelog:v=5.3.3) (#1528)
        - Outlines in the Load panel in LM are now capitalized properly (#1520)
        - Fixed Global filter for Assignments (#1521)
        - Fixed disabled menu items still showing up on front-end and not displaying as disabled in the admin (#1532)
        - Fixed validation warning icon piling up when Applying and after an error (#1526)
        - Fixed untranslated string in Atoms validation (#1525)
        - Removed extra `assets` and `engines` folders from `gantry-media://` stream
1. [WordPress](#wordpress)
    1. [](#new)
        - Include Timber Library v1.1.3 into Gantry Plugin. Removes dependency to Timber Plugin. (#1542)
    1. [](#improved)
        - Updated Hydrogen and Helium themes to use Timber 1.1 classes
    1. [](#bugfix)
        - Fixed `Undefined index: link` when saving menu

# 5.3.4
## 08/24/2016

1. [Common](#common)
    1. [](#bugfix)
        - **Patch Release**: This patch release fixes a regression introduced with [v5.3.3](http://gantry.org/#changelog:v=5.3.3) where Layout Manager and Menu Manager item settings were not clickable.

# 5.3.3
## 08/24/2016

1. [Common](#common)
    1. [](#new)
        - Updated FontAwesome to v4.6.3
    1. [](#improved)
        - Failed streams in compiled SCSS will now be transformed to 404 URIs, instead of keeping the stream. This will create less confusion on the errors displayed on frontend (#1457, #1443, #1331)
        - Block Variations now display both label and actual class names inline, to better identify and use variations. Hovering over a selected variation will also now display the actual class name as a tooltip.
        - Filepicker files are now displaying sorted by name (#1478)
        - Social particle should use simple select instead of fancy selectize (#1490)
        - If debug mode is enabled, display whoops error instead of catching exceptions
        - Reworked the UI for assignments Filters and Togglers
        - Various RTL fixes and improvements (#1494, #1508, #1511, #1512)
    1. [](#bugfix)
        - Fixed inherited/default atoms from base outline displaying even if there are no atoms assigned in `Page Settings`
        - Fixed issue with globally disabled Particles that were appearing as enabled in the LM and could be drag and dropped (#1496)
        - Fixed wrongly rendered `disabled` attribute for Assignments items. Causing them to get lost on next save (#1501)
        - Section layout setting `Fullwidth (Boxed Content)` always shows as default in the Layout Manager (#1515)
        - Fixed select and selectize form fields not recognising difference between 0 and ''
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fixed extra Default outline that was added in the non-English Joomla installation during clean installation of template (#1461)
        - Fixed issue with Tabs container, conflicting with Tabs in the modals. Improved specificity (#1463)
        - Helium Template: Updateserver not implemented in templateDetails.xml (#1488)
        - Unassigning outline from all menu items does not have any effect (#1451)
1. [WordPress](#wordpress)
    1. [](#new)
        - Helium: Added missing `languages/` directory with the `.pot` translation template file
    1. [](#improved)
        - Helium: Added multiple `raw` filters in the content views
    1. [](#bugfix)
        - Helium: Offline page should now properly display the offline message set in the Gantry 5 settings page.
        - Added missing custom directory to Timber template lookup (#1465)
        - Fixed `|trans` twig filter having `gantry5` textdomain hardcoded (#1459)
        - Fixed possible fatal error in menu particle (#1493)
        - Fixed new outline having checkboxes in Content tab selected (#1482)

# 5.3.2
## 07/19/2016

1. [Common](#common)
    1. [](#new)
        - Helium: Added two new button variations - `button-square` and `button-bevel`
        - Implemented new Tabs Container that allows to better organize Particles fields in the admin (#1026 - [more details](https://github.com/gantry/gantry5/issues/1026#issuecomment-232265381))
        - Hydrogen and Helium now require Gantry 5.3.2
        - Updated Google Fonts library (+70 new fonts)
    1. [](#improved)
        - Helium: Improved OwlCarousel color overlay support
        - Helium: Improved `shadow` variation
        - Hydrogen: Improved enqueueing of `comment-reply` script in the Comments twig template
        - Changed Joomla Articles and WordPress Posts particle to use the new Tabs container
        - Increased PHP timeout for CSS compiler to prevent issues in slow shared servers
        - Helium: OwlCarousel Color Overlay is now alternated to match while transitioning
    1. [](#bugfix)
        - Helium: Fixed wrong dropdown menu item text hover color
        - Fixed missing languages files in Gantry 5 Particle Module, causing JS errors and preventing the Picker to work
        - Definitive fix for z-index issue Layout Manager when sections were inheriting without children (#1430)
        - Always ensure that the `G5T` method (translations for JS) is available (#1434)
        - Better escaping for JS translations
        - Fixed issue in Layout Manager when inheriting an empty Section from another Outline (#1435)
        - Fixed mis-representation of an inherited Section/Particle when set to "No Inheritance" but with all the Replace options selected
        - Fixed inherited Sections with empty grids, not displaying the "Drop particles here..." message
        - Fixed issue in the Font Picker and local fonts throwing JS error
        - Proper fix for nested fields within containers (#924, #1026)
        - Fixed `Undefined property: stdClass::$outline` in `Layout::inherit()`
        - Fixed issue with modals in Firefox where the bottom end wouldn't have enough margin (thanks @coder4life - #1454)
        - Fixed issue with Offcanvas that on Touch devices would cause the Offcanvas to close while touch-scrolling (#1447)
        - Fixed issue with `input.multicheckbox` field throwing errors when not used in LM
        - Fixed save in menu editor, menu items were not saved properly (#1439)
1. [Joomla](#joomla)
    1. [](#new)
        - Allow to install and update Gantry in Joomla 3.6
        - Helium is now going to be available from Joomla Updates
        - Improve template installation by adding support for nice looking installation and upgrade messages (written in twig)
        - Add support to install sample data separately from the template
    1. [](#improved)
        - Helium: Enhanced Menu Modules in Offcanvas (#1442)
    1. [](#bugfix)
        - Menu subtitles get wiped out from all menu items when saving menu (#1438)
        - Fixed missing language loading in Gantry 5 Particle Module (#1437)
1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Fixed Fatal error when using BuddyPress (thanks @AlwynBarry - #1441)
        - Fixed `Missing argument 4 for gantry5_upgrader_source_selection()` (#1440)

# 5.3.1
## 07/11/2016

1. [Common](#common)
    1. [](#new)
        - Added permanent warning at the top of admin when using PHP 5.4. Gantry will soon drop PHP 5.4 support. Please upgrade as soon as possible. [More details](http://gantry.org/blog/php54-end-of-support)
    1. [](#improved)
        - Allow Presets description to be translatable (#1212)
        - Converted all hardcoded JS strings to translatable languages (#1212)
        - Added proper HTML5 subtypes to sections in Helium
    1. [](#bugfix)
        - Fixed `Can't use method return value in write context` on PHP 5.4 (#1413)
        - Fixed `Document::addScript` not allowing string argument (#1414)
        - Fixed Outlines rename from the dropdown switcher (#1422)
        - Fixed `Invalid argument supplied for foreach()` error when duplicating outline (#1416)
        - Fixed `Undefined property: stdClass::$childen` (#1431)
        - Fixed duplicating collection items not triggering the display of the multiple edit button (#1432)
        - Fixed issue that was preventing Menu Item titles (in Menu Manager) to be renamed
        - Fixed z-index issue in Layout Manager when sections were inheriting without children (#1430)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fixed warning in `Gantry 5 Particle` module about not using a Gantry 5 Theme (#1420)
        - Fixed Bootstrap table having always a border (#1330)
        - Fixed Bootstrap pagination having too much margin (#1389)

# 5.3.0
## 07/08/2016

1. [Common](#common)
    1. [](#new)
        - **Inheritance**: It is now possible to have individual Sections and Particles to Inherit from a different Outline and to decide what to Inherit specifically (attributes, children, blocks). Once a Section or Particle have been set to Inherit, any change applied to the parent Section / Particle will automatically reflect to the inheriting one (#50, #303, #340, #361, #575, #1018, #1213, #1312)
        - Added support for DebugBar (#386)
        - Removed outdated LESS compiler (see #273)
        - Updated SCSS compiler to support version 3.4 (#1117)
        - Updated Bourbon SCSS library to v4.2.7
        - New `input.multicheckbox` field. Takes options like a select and renders as a list of checkboxes
        - New `input.radios` field. Allows to create a list of radio selectors. `options` entry should be of kind key/value
        - Added possibility to place `<svg>` code directly inside the Logo particle
        - Creating New Outlines will now offer to either load based off of Presets or existing Outlines. When selecting existing Outlines you can now decide whether you want to Inherit or not (#1386)
        - Added priority field for CSS/JS Assets and Atom (#1321)
    1. [](#improved)
        - Hide `Particle Defaults` tab from everywhere else but in `Base Outline`
        - Do not display `Atoms` in `Particle Defaults`
        - Display only shared particle settings in `Particle Defaults`
        - Tweaked text contrast across the Admin UI (#1326)
        - Sections Layout `Inherit` option is now renamed to `Inherit from Page Settings` (#1349)
        - Assignments cards have now maximum height declared for better presentation and readability
        - Removed rename capability from Base Outline (#1350)
        - Do not close the Atoms modal when clicking on the overlay, in order to prevent accidental loss of changes.
        - When deleting an Outline it will now highlight the title of the Outline in question
        - Make configration overrides persistent, meaning that the value will stay checked even if the value is identical to the global value (#1346)
        - Improved block sizes compatibility with IE10 and IE11 (thanks @Rdechamps - #1407)
    1. [](#bugfix)
        - Fixed disabled particle rendering in menu item (thanks @nikola3244 - #1313)
        - Fixed typos in tooltips and notices (#1318)
        - Fixed issue with Icons Picker Select button no properly re-enabling when only switching dropdown (#1290)
        - Fixed potential JavaScript error on frontend when the Menu particle was disabled
        - Fixed Preset Match (star) being applied only in Base but not in the rest of the Outlines
        - Fixed issue in parent themes streams initialization (thanks @nikola3244 - #251, #1325)
        - Fixed `Document::urlFilter` handling URLs inside `<pre>` and `<code>` tags (#1328)
        - Fixed `collection.list` inside `container.set` not working (#1333)
        - Removed Nucleus CSS rule `.g-block.visible-desktop {}` that was overriding the media queries (#1344)
        - Layout Manager will now prevent clearing single empty rows upon save (#1368)
        - Font Picker: Fixed potential conflict issue when a Local and a Remote fonts were matching name
        - Fixed minor issue that would cause the flickering of the Layout while scrolling, when the Layout was shorter than the Sidebar (#1378)
        - Section titles in the Layout Manager that don't fit are now collapsing with ellipsis and a title (#1392)
        - Fixed missing configuration when duplicating system outlines
        - Fixed issues with single select field with multiple values (#1402)
        - Fixed `select.select` with `multiple` option enabled, storing only the first selected option rather than all (#1402)
        - Fixed DOM parser issue with HTML tags when adding inline JS/CSS (#1359)
        - Fixed issue with anchors and Offcanvas not resetting the overlay in IE and Firefox (#1399)
1. [Joomla](#joomla)
    1. [](#new)
        - Enable `Layout` tab for `Base Outline`
    1. [](#improved)
        - Hydrogen for Joomla loads now optional `alert` assets from Nucleus to fix potential styling issues
        - Gantry 5 Particle now displays, as a tooltip, the Particle type in the lists of modules when hovering over the badge (#1373)
        - Gantry 5 Particle badge for unselected Particles is now orange, to distinct from the selected ones (green)
        - Added warning message to particle module when there is no default template set (#1316)
    1. [](#bugfix)
        - Fixed issue with `Link Title Attribute` menu setting in Joomla, where the value would be translated as subtitle in Gantry, rather than `title=""` attribute (#1176)
        - Fixed untranslated 404 page title (#1001)
        - Fixed wrong title in newly created outline
        - Fixed content array particle: alias in link duplicating (#1400)
        - Fixed particle module not caching Javascript / CSS (#977)
        - Fixed exception thrown in administration if parent theme was not enabled in Joomla
1. [WordPress](#wordpress)
    1. [](#new)
        - Extend Assignments with multiple `BuddyPress` conditionals. This requires BuddyPress 2.6 and newer (thanks @horch004 - #1298)
        - Extend Assignments with possibility to assign outline to all posts or archive page of custom post type (thanks @horch004 - #1298)
    1. [](#improved)
        - Gantry 5 Particle Widget is now compatible with WordPress Customizer and will live-refresh on change (#869)
        - Add support for Widgets with checkboxes that use the trick of hidden/checkbox fields with the same name (#1014)
    1. [](#bugfix)
        - Fixed post type priority not being used in assignments (#1340)
        - Fixed menu particle missing `Expand on Hover` option (#1360)
        - Fixed Admin incompatibility with Jetpack (#1184)
        - Fixed updating plugins causing endless maintenance mode when `display_errors = On` (#1271)
        - Fixed missing layout denying access to admin (#1319)

# 5.2.18
## 05/27/2016

1. [Common](#common)
    1. [](#new)
        - Creating and duplicating Outlines now offers a modal where title and preset can be pre-compiled, without having to edit it later (#207)
    1. [](#improved)
        - Filepicker now allows uploading and deleting UTF-8 named files
    1. [](#bugfix)
        - Fixed Filepicker `root` property failing when mixing streams with non-streams paths (#1305)
        - Fixed `button` input field (thanks @nikola3244 - #1308)
        - Fixed `Oops, Cannot delete non-existing folder (500 internal error)` during Cache Clearing and when compiling YAML and Twig settings were disabled (#1306)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fixed regression in positioning module content by removing `row-fluid` wrapping from Joomla modules
        - Fixed `Gantry 5 - Presets` plugin being enabled during upgrades (#1285)

# 5.2.17
## 05/19/2016

1. [Common](#common)
    1. [](#bugfix)
        - Fixed `Warning: Zend OPcache API is restricted by "restrict_api" configuration directive`
        - Fixed backward compatibility for custom menus where the hovering wouldn't be the default behavior (#1293)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fixed media manager not rendering correctly in frontend editor (#986)
        - Fixed modal issues with Fabrik (#1147)
        - Wrap all Joomla content to `row-fluid` to fix some Bootstrap layout issues
        - Fixed articles particle displaying unpublished, trashed and archived articles (#1289)
1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Work around commit issues to WP SVN to allow again automated updates (5.2.16 was skipped, see [changelog](http://gantry.org/#changelog:v=5.2.16&p=wordpress))

# 5.2.16
## 05/17/2016

1. [Common](#common)
    1. [](#new)
        - Hydrogen: The template now includes the emoji fonts (thanks @810 - #1253)
        - Frontend: Exposed `G5.$` and `G5.ready` JavaScript utils (ref, #1256)
        - Menu Particle: Added new option `Expand on Hover` to allow / disallow menu items to expand on mouseover or limit them to click / touch only (#1256)
        - Menu Editor: It is now possible to disable menu items directly from the editor without having to pass through the platform (#1020)
    1. [](#improved)
        - Extended top level menus with a fixed width are now respecting the directional setting (#1252)
        - Menu Manager: Cog wheel settings for Menu Items as well as Columns sorting icons, will now always appear on Touch Devices instead of been hover controlled only (related to #1254 and #1218)
        - Included woff2 version of the local Roboto font
        - Tweaked UI for multiple grids inside a container (#1278)
        - Saving Assignments will now only post enabled items instead of the whole lot, making the save faster and reducing the probability of hitting a `max_input_vars` limit issue (#1279)
    1. [](#bugfix)
        - Fixed Sub-items back arrow in Menu Manager not responding to tap in Touch Devices (#1254, #1218)
        - Fixed issue that was preventing Atoms from properly getting sorted and deleted on touch devices (#1259)
1. [Joomla](#joomla)
    1. [](#new)
        - Add particle badges support for `Advanced Module Manager` (thanks @nonumber)
        - Make Gantry menu to honour new `Display in menu` field in Joomla! 3.5.1 (#1255)
    1. [](#improved)
        - The Joomla Articles Particle now offers the option to pick either `intro` or `fulltext` image (thanks @nikola3244 - #1261, related to #1258)
    1. [](#bugfix)
        - Fixed `Joomla Articles` particle limits category selection to 20 categories only (thanks @nikola3244 - #1260)
        - Fixed broken language filtering for categories and articles
        - Worked around bug 72151 in **PHP 5.6.21** and **PHP 7.0.6** which was causing some data for articles not to be initialized
        - Fixed `The menu selected is empty!` in Menu editor when PHP `default_charset` is not `UTF-8` (#1257)
1. [WordPress](#wordpress)
    1. [](#improved)
        - Added missing `home`, `outline`, `language` and `direction` properties to `Framework\Page` class
    1. [](#bugfix)
        - Fixed HTML entities not encoded properly in menu item titles (#1248)

# 5.2.15
## 04/25/2016

1. [Common](#common)
    1. [](#new)
        - Updated FontAwesome to v4.6.1 (+23 icons)
        - Icons Picker will now show the title of each icon when hovering to see the preview
        - Updated Google Fonts library
        - Sample Content Particle now include the ID and CSS fields for the individual items (#1199)
    1. [](#bugfix)
        - Fixed loss of settings for Particles / Modules menu items when moved to a different menu level (#1243)
        - Various Admin RTL tweaks (#1195)
        - Fixed expand / collapse in Filepicker (#1246)
        - Override checkboxes are now getting detected as changes when checked / unchecked (#333)
        - Fixed rendering issue in layout if all blocks next to each other are `Fixed Size` and some of them have nothing in them
        - Locked the Particle Settings editing overlay in Gantry 5 Particle Module, to prevent losing settings by accident (#1247, related to #1227)
        - [CHANGE]: Copyright Particle output now renders without the hardcoded `Copyright` word that couldn't be translated. Before: `Copyright © 2016 SiteOwner`, After: `SiteOwner © 2016` (#950)
        - [REGRESSION] Disabling `Compile twig` attempts to write lots of directories to hard drive root (#1250)
        - Prevent resolving stream paths outside of defined scheme root
1. [Joomla](#joomla)
    1. [](#improved)
        - Enable HTML5 document support from Joomla
    1. [](#bugfix)
        - Fixed case where multiple badges of the Particle type, could potentially show up in the Modules Manager
1. [WordPress](#wordpress)
    1. [](#improved)
        - Improved current URL detection for Menu Item based Assignments with possibility of filtering custom server ports (#1208)

# 5.2.14
## 04/15/2016

1. [Common](#common)
    1. [](#new)
        - Implemented `sprintf()` compatible parameter support for twig `trans()` filter
        - Implemented `duplicate` action for collections items (#1220)
    1. [](#bugfix)
        - Updated Whoops to latest version (fixes PHP7 issues with some uncatched exceptions)
        - Fixed Zend opcache without file checks causes issues in admin (#1222)
        - Downgrading PHP version causes fatal errors on cached twig files (#947)
        - Themes list: Fix fatal error if theme had a loop in parent themes
        - Admin: Rename `Settings` tab to `Particle Defaults` to avoid confusion
        - Added missing language translations for all admin template files (part of #1212)
        - Prevent to close the modal of collections and forms (Particle Settings, Menu Settings) (#1227)
        - Fixed adding new rows and editing section/particle settings in LM on touch devices (#1218)
        - Fixed case in the colorpicker where potentially the opacity would go `-0` causing the field not to validate (#1217)
        - Fixed Outline Assignments not staying set if `max_input_vars` has too small value; display error instead
        - Fixed Particle Defaults loosing values if `max_input_vars` has too small value; display error instead (#1226)
        - Prevent Applying / Saving multiple times when an occurrence is already running (#1185)
        - Workaround to prevent embedded iframe to throw JS errors in same cases (#1224)
1. [Joomla](#joomla)
    1. [](#new)
        - Added support to have Joomla articles and categories in particles (#1225)
        - Added `Joomla Articles` particle
        - Added support for Joomla Template & Menu ACL in Gantry admin (#600)
    1. [](#bugfix)
        - Fixed duplicating template style while caching was turned on not being recognized as Gantry 5 outline (#1200)
        - Fixed logo particle link going to current page rather than home page on J! 3.5 (#1210)
        - Module instance edit fails with "You are not permitted to use that link to directly access that page" on J! 3.5 (#1215)
        - Gantry update is shown even if the new version was just installed (#1204)
        - Untranslated string `COM_GANTRY5_PARTICLE_NOT_INITIALIZED` (#1118)
1. [WordPress](#wordpress)
    1. [](#new)
        - Added `WordPress Posts` particle
        - Extend Assignments with multiple `WooCommerce` conditionals (#1150)
        - Add possibility of choosing if posts should display theirs content or excerpt on blog and archive-type pages in Hydrogen
    1. [](#bugfix)
        - Fixed issue where bad value in `wp_upload_dir()['relative']` is causing error in Image Picker (#1233)

# 5.2.13
## 03/16/2016

1. [Common](#common)
    1. [](#new)
        - Implemented an universal method `gantry.load()` to include common JS frameworks from Twig on all platforms (#1132)
    1. [](#improved)
        - The `dropdown-offset-x()` mixin now includes a 3rd option that allows to disable or customize the offsets for the first level dropdown child (fixes #1182, thanks @JoomFX)
        - Add possibility to target all particles with a single CSS rule `div.g-particle` (#909)
    1. [](#bugfix)
        - Fixed menu item height difference between regular and parent menu items (#1183)
        - Remove unnecessary error: `Theme does not have Base Outline` (#1107)
1. [Joomla](#joomla)
    1. [](#improved)
        - Load template language overrides from `custom/language`
    1. [](#bugfix)
        - Fixed error on saving system outline layouts (#1167)
1. [WordPress](#wordpress)
    1. [](#new)
        - Allow Gantry theme upgrades from WordPress theme uploader (#1165)
    1. [](#improved)
        - Removed hardcoded `h2` tag from Login Form particle title. You can still place your `HTML` code inside of the input field.
    1. [](#bugfix)
        - Fixed Hydrogen Child theme to reference properly `g5_hydrogen` parent directory
        - Fixed Gantry 5 Clear Cache fires during every plugin installation/update (#996)
        - Fixed child comment reply input position in Hydrogen
        - Fixed `Undefined $_GLOBALS` on the WP login page when the Offline Mode is enabled

# 5.2.12
## 02/27/2016

1. [Common](#common)
    1. [](#new)
        - Add support for toggling offcanvas visibility on non-mobile devices
    1. [](#bugfix)
        - Fixed a regression and removed `very-large-desktop-range` from `breakpoint` mixin
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Remove "always render component" workaround introduced in 5.2.8 (fixes #1157, thanks @JoomFx and @nonumber)

# 5.2.11
## 02/23/2016

1. [Common](#common)
    1. [](#new)
        - Added `very-large-desktop-range` to `breakpoint` mixin in order to be used when working with screen resolutions of 1920px+
        - Added option to parse Twig in Custom HTML particle (#1144)
    1. [](#improved)
        - Collection Lists now have a maximum height set, triggering a scrollbar in case the amount of items is big (#1139)
    1. [](#bugfix)
        - [CHANGE]: The `dependencies.scss` file does not import `nucleus/theme/base` anymore. **IMPORTANT**: if you are a theme developer, make sure you adjust your dependencies file and include the theme base at the top of your theme.scss (#1152)
        - System outlines should not be able to assign to pages (Fixes #1146)
        - Fixed frontend rendering if page settings have never been saved
        - Fixed tooltips in IE Edge and in some circumstances on Firefox (#1154)
        - Fixed `404 Not Found` when creating new outline
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Admin: Fix potential fatal error when saving Outline Assignments
        - Update Joomla template style when saving layout
1. [WordPress](#wordpress)
    1. [](#new)
        - Fixed Child Theme support in Hydrogen (requires update of Hydrogen theme) (#1149)
        - Added sample Hydrogen Child theme to git (#1149)
    1. [](#improved)
        - Add Ability to Duplicate Base in Outlines (#846)
    1. [](#bugfix)
        - Fixed typo in `posts_per_page` custom WordPress field (thanks @adi8i - #1153)

# 5.2.10
## 02/08/2016

1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Fix clearing cache on plugin update (Fixes #1125)
        - Clear opcache and statcache on plugin update
        - Fix saving/applying widgets in menu (#1130)

# 5.2.9
## 02/04/2016

1. [Common](#common)
    1. [](#bugfix)
        - Fixed potential issue with deletion of Outlines when server doesn't support `DELETE` request method (#1124)
        - Fixed `404 Not Found` when adding an asset on page settings (#1126)
        - Fixed the add button next to the Outlines title (#1116)
1. [WordPress](#wordpress)
    1. [](#new)
        - New selectize field that list all pages / posts (thanks @adi8i - #1131)

# 5.2.8
## 01/27/2016

1. [Common](#common)
    1. [](#new)
        - Add support for nested collections in particles (#924)
        - Add configuration options to disable Twig and YAML compiling / caching
    1. [](#bugfix)
        - Fixed defer attribute for JavaScript
        - Ignore missing atom if debug has not been enabled (#1106)
        - Fix `Custom CSS / JS` Atom having bad HTML with non-existing file path (#1105)
        - Forcing Mobile Menu Items to always display full width no matter the breakpoint (thanks @JoomFX - #1109)
        - Fixed zIndex issue in Mobile Menu in Firefox and IE (thanks @JoomFX - #1109)
        - Fixed "Keep Centered" Menu Items option that was instead showing up left aligned (fixes #1119)
1. [Joomla](#joomla)
    1. [](#new)
        - Template installer: Copy configuration for new outlines
    1. [](#bugfix)
        - JavaScript Frameworks Atom: Load also Bootstrap CSS when enabling Bootstrap Framework
        - Compatibility fix for some plugins which require non-empty component output to work properly
1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Internal Error in admin Settings tab when there are no menus (#1102)
        - Fix footer scripts from main content (#1113)

# 5.2.7
## 01/05/2016

1. [Common](#common)
    1. [](#bugfix)
        - Fixed Menu option "Render Titles" not rendering titles at all
        - Fixed potential 404 response in admin when trying to access Particle Settings via modal (#1088)
        - Worked around PHP 5.5 bug on loading global configuration
        - Fixed caching of admin AJAX requests (#1078)
1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Remove RokGallery and RokSprocket from the Widget Picker (#1092)
        - Fix Timbers `render_string()` and `compile_string()` functions (#1077)
        - Removed description meta tag to avoid duplications of it. This should be handled by plugins (#892)

# 5.2.6
## 12/21/2015

1. [Common](#common)
    1. [](#new)
        - Implement `Remove Container` mode to make section to use all the available space (#549)
    1. [](#improved)
        - Index of the column being deleted is now based on DOM rather than list id, making it more accurate (#1071)
        - Improve Google analytics atom tooltip and placeholder (#1079)
        - Updated Google Fonts
    1. [](#bugfix)
        - Fixed typo in menu particle that was preventing the rendering of the animation class
        - Fixed admin js to deferred, guaranteeing global variables to be available (#1076)
1. [Joomla](#joomla)
    1. [](#new)
        - Create atom to load jQuery, Bootstrap and Mootools from Joomla (#1057)
    1. [](#bugfix)
        - Hydrogen: Fixed assigning outline from a plugin having no effect (#1080)
        - Fixed outline id in body tag being wrong for some pages, like error page
1. [WordPress](#wordpress)
    1. [](#new)
        - Create atom to load jQuery from WordPress and Bootstrap and Mootools from CDN (#1057)
        - Added missing default configuration for Home outline in Hydrogen

# 5.2.5
## 12/17/2015

1. [Common](#common)
    1. [](#new)
        - Menu items have a new `Dropdown Direction` option, along with new mixins (`dropdown-left`, `dropdown-center`, `dropdown-right`), that will allow to configure where a dropdown should open to, relative to its parent. (thanks @Bokelmann , @JoomFX and @ramon12 - #1058)
    1. [](#improved)
        - Selectize is now name-spaced with a `g-` prefix to avoid potential conflicts
        - Layout Manager: Add Row and Section Settings action icons are now always visible
        - Decimal size classes (`size-33-3`) are also using flexgrid (thanks @adi8i - #1047)
        - Reworked all tooltips. They are now JS based instead of CSS making the behavior more predictable as well as allowing longer text and HTML as content.
        - Allow theme developer to assign attributes to grid element in layout preset file
        - Styles, Settings and Page groups of type `hidden` will now get properly hidden from the view
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fixed dismissal links alignment for alerts (#1022)
        - Fixed Production / Development Mode switch if file caching is turned on (#1051)
1. [Wordpress](#wordpress)
    1. [](#new)
        - Separate configuration for each Multi Site blog (#921)
    1. [](#improved)
        - Display notification for the logged in user when site is offline (#760)
    1. [](#bugfix)
        - Fixed plugin settings being disabled when theme failed to load
        - Fixed XFN (rel) missing from menu HTML output (#1064)
        - Fixed inline JavaScript in Footer block gets loaded before the files (#1060)
        - Fixed empty assignments being reloaded from theme configuration (#884)
        - Fixed broken links in `Available Themes` page (#1004)
        - Fixed Base Item in Menu particle being empty (#1033)
        - Fixed Saving menu failed: Failed to update main-menu (#1055)
        - Fixed frontend showing wrong menu items

# 5.2.4
## 11/30/2015

1. [Common](#common)
    1. [](#new)
        - Updated FontAwesome to v4.5.0 (+20 icons)
    1. [](#improved)
        - Prefixed `.colorpicker` class name to avoid potential conflicts
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fixed Particles picked from Menu Item of type `Gantry 5 Themes » custom` filtering out HTML
        - Fixed `Undefined variable: gantry` in some sites
        - Fixed missing translations in **System - Gantry 5** plugin
        - Fixed fatal error in **Particle Module** if default style does not point to Gantry template
1. [Wordpress](#wordpress)
    1. [](#bugfix)
        - Add missing variable `wordpress` in Twig context
        - URL Encoding Menu Items to allow use of special characters such as plus (#1017)

# 5.2.3
## 11/16/2015

1. [Common](#common)
    1. [](#new)
        - Offcanvas section now adds an option to switch between CSS3 and CSS2 animations, CSS3 being default and fastest. An HTML class is also added as CSS hook (`g-offcanvas-css3` / `g-offcanvas-css2`). When dealing with fixed elements in the page (such as headroom), it might be necessary to switch to CSS2. (Thanks @under24, @JoomFX, @adi8i and @ramon12)
1. [Joomla](#joomla)
    1. [](#new)
        - Add updates support for Joomla! 3.5 (#999)
        - Module Picker now shows also the Module ID (#1002)
    1. [](#bugfix)
        - Gantry 5 module still renders title and container when particle is disabled (#991)
        - Fix template installation if using PostgreSQL
1. [WordPress](#wordpress)
    1. [](#new)
        - Added body classes `dir-ltr` and `dir-rtl` based on current text direction settings in WordPress
        - Added new body class specific to the currently used outline
    1. [](#bugfix)
        - **Clear Cache** does not clear Timber Twig files (#995)
        - Gantry 5 widget still renders title and container when particle is disabled (#991)
        - Fixed meta conditional checks in single post layout in Hydrogen

# 5.2.2
## 11/10/2015

1. [Common](#common)
    1. [](#new)
        - Added new `|imagesize` Twig Filter that returns `width="X" height="Y"` as attributes for images
        - Add notification message on missing particle in frontend (#185)
    1. [](#improved)
        - Menu Editor now displays the current level of a column while navigating through it (#985)
    1. [](#bugfix)
        - Fixed again compatibility for PHP 5.3 and prevent from failing with the error "Parse error: syntax error, unexpected '[' in ..."
        - Fixed CSS and JavaScript, potentially rendering empty when only inline was specified without any location
        - Fixed some themes having full width containers after upgrade to Gantry 5.2 (#967)
        - Fixed check for enabled/disabled for Atoms and Assets (#988)
        - Fixed Menu Editor where items could be dragged between different levels (#985)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Disable frontend editing for Gantry particle module, fixes 404 error (#966)
1. [WordPress](#wordpress)
    1. [](#improved)
        - Greatly improve page load time (#738)
    1. [](#bugfix)
        - Hydrogen: Fix fatal error if Gantry hasn't been loaded (#983)
        - Fix potential Fatal Error during installation

# 5.2.1
## 11/02/2015

1. [Common](#common)
    1. [](#new)
        - Hydrogen now requires Gantry 5.2.0 or higher and will display a notice if theme requirements aren't met
    1. [](#improved)
        - Added particle icons for Particle Picker in the Menu Editor
        - Clear Gantry cache after Gantry upgrade
        - Clear theme cache after theme upgrade
    1. [](#bugfix)
        - Fixed regression in Layout Manager where a malformed JSON output was preventing from drag and dropping particles around (#959)
        - Restored auto focus on Search fields for Icons, Fonts and Module/Widget Pickers
        - Fixed deprecated use of `Twig_Filter_Function` (fixes #961)
        - Fix saving two or more positions using the same key
        - New Layout Format: Fix loading position with different key to id
1. [Joomla](#joomla)
    1. [](#bugfix)
        -  Upgrading Gantry may cause `g-container` to disappear (#957)
1. [WordPress](#wordpress)
    1. [](#improved)
        - Removed Hydrogen conditional tags for loading `page_head.html.twig` file
        - Added particle icons for Login Form and Menu
    1. [](#bugfix)
        - Fixed a `Fatal error: Cannot use object of type Closure as array` that could occur with some widgets

# 5.2.0
## 10/29/2015

1. [Common](#common)
    1. [](#new)
        - Updated Hydrogen and Admin with the new Gantry logo. Thanks Henning!
        - Page Settings: Implemented new feature that allows to specify global and/or per-outline overrides for Meta Tags, Body attributes, Assets, Favicons, etc.
        - Atoms are moved from Layout to Page Settings. Migration is automatic and backward compatibility proof
        - File Picker: It is now possible to preview the images from the thumbnails list
        - Tags / Multiselection now include an `[x]` button to easily remove items via click
        - Layouts: New file syntax, which combines the best of both existing file syntaxes into a single format
        - Layouts: Add support for nested wrapper divs with customizable id and classes (#548)
    1. [](#improved)
        - Copyright Particle now allows the `owner` field to contain HTML (thank you @topwebs / #906, #908)
        - Default Outline now shows a 'default' tag in the Outlines Page (#926)
        - Logo Particle is renamed to Logo / Image Particle.
        - Minor Collections CSS tweaks
        - Date Particle: Added commonly used option `October 22, 2015`
        - Layouts: Add support for customizing section ids (was bound to title before)
        - Prefixed Admin CSS file to appear more specific and possibly avoid potential conflicts (g-admin.css) (#944)
        - All particles have now unique id: `{{ id }}`
        - Make sidebars in default layout presets to have fixed width (size will not change when another sidebar is inactive)
    1. [](#bugfix)
        - Fixed the config files lookup using relative instead of absolute paths
        - Fixed issue in admin where overrides for Enabled toggle wouldn't be showing checked, causing the value to reset to Base Outline
        - Fixed Admin Styles issue where indicator wouldn't show in certain cases.
        - Fixed `.equal-height` utility not fully expanding the content (#902)
        - Reverted Assignments scrollbars due to Chrome issue [we will re-enable the functionality as soon as the bug is fixed] (#851)
        - Logo / Image Particle: the `rel` attribute will now smartly be added for `home` only if the URL matches the Site root.
        - Logo / Image Particle: the `class` attribute will not render empty anymore if there are no classes assigned.
        - Fixed issue where Settings in Outlines overrides could potentially never remove the stored `yaml`, making it impossible to reset an entire section to Default (#929)
        - Fixed issue where Tag fields wouldn't trigger the indicator change
        - Fixed Collections not loading the default values defined in the `yaml`
        - Fixed bad html output in menu particle
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Disable caching from Particle Module by default (#925)
1. [WordPress](#wordpress)
    1. [](#new)
        - Ability to add custom CSS classes to Widgets
    1. [](#improved)
        - Improved URL comparing on menu item Assignments when permalinks are enabled
    1. [](#bugfix)
        - Renaming of Outlines from navigation bar will now properly refresh all links with the new value (#912)
        - Fixed issue in Hydrogen where Visual Composer wouldn't work on Pages
        - Fixed open_basedir warning in admin when getting list of Gantry themes

# 5.1.6
## 10/14/2015

1. [Common](#common)
    1. [](#improved)
        - Displaying Assignments' action bar in the footer like in the other sections
        - Minor style enhancements to the key/value field
    1. [](#bugfix)
        - Fixed an Internal Server Error that could occur when site has no menus and user tries to access Settings tab (#898)
        - Fixed text color for inputs and textareas when appearing in the menu (#896)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Restored the old behavior from Gantry 5.1.4 where Bootstrap CSS/JS isn't loaded unless needed by the page content
1. [WordPress](#wordpress)
    1. [](#new)
        - Enable shortcodes in Text Widget and widgets that use `widget_content` filter (#887)
    1. [](#improved)
        - Particles should be now prepared on wp_enqueue_scripts so the WordPress scripts checks should work properly
    1. [](#bugfix)
        - Widget positions with upper case letters are always empty (#889)
        - Tag attributes aren't rendered in CSS/JS Atom, even though they're there (#888)

# 5.1.5
## 09/30/2015

1. [Common](#common)
    1. [](#new)
        - Add support for twig `{{ gantry.page.url({'var': 'value'}) }}` to request current URL with extra attributes (#875)
    1. [](#improved)
        - Enhanced the droppable areas for Menu Editor in the sublevels (#132)
    1. [](#bugfix)
        - If `layout.yaml` file is missing, wrong layout preset gets loaded
        - Fixed issue with multiple dropdown menu items not closing properly in some scenarios (#863)
        - Fatal error if there is empty outline configuration directory (#867)
        - Fixed issue with ajax calls where in some scenarios the URL would match a `method` causing the Ajax to fail (#865)
        - Fixed `Declaration of ThemeTrait::updateCss() must be compatible with ThemeInterface::updateCss()` in PHP 5.4
        - Extending `{% block head_platform %}` from platform independent file does not have any effect (#876)
        - Fixed improperly rendered blocks sizes when equalized (ie, `33.3 | 33.3 | 33.3`) (#881)
        - Fixed `str_repeat(): Second argument has to be greater than or equal to 0` happening sometimes in admin
1. [Joomla](#joomla)
    1. [](#new)
        - Implement support for Joomla objects in twig (#873)
        - Implement support for static Joomla function calls in twig (#874)
    1. [](#bugfix)
        - Added missing Module Class Suffix entry field for the Gantry Particle Module (#871)
1. [WordPress](#wordpress)
    1. [](#new)
        - New `[loadposition id=""][/loadposition]` shortcode for loading widgets inside of content
    1. [](#improved)
        - Changes indicator is now showing in Widgets and Customizer, whenever an instance gets modified and in order to remind of saving (#822)
        - Gantry updates are now available and interactive in the Admin via a Purple bar notification (#718)
        - Improve widget rendering for particles, roksprocket and rokgallery
    1. [](#bugfix)
        - Duplicating outline may cause 'Preset not found' error (#859)
        - Fix WooCommerce and some other plugins from having missing CSS and JavaScript (requires theme update) (#855)
        - Fixed fatal errors with PHP <= 5.3, causing Hydrogen and Gantry to not display the proper errors of PHP incompatibility (#833)
        - Fixed customizer JS errors thrown due to wrongly formatted `before_widget` (#864)
        - Newly cloned Outline should not have any assignments (#866)
        - Fixed duplicated `<title>` tag in head (#870)
        - Fixed 404 and Offline in Hydrogen loading assigned outline rather than hardcoded layout
        - Widget Particle: widget call without an instance fails (#880)
        - Using only characters from foreign alphabets like greek or hebrew is breaking menu (#691)
        - Menu name containing foreign alphabets causes issues in admin
        - Fixed a bug causing presets in the menu to show up vertically instead of horizontally

# 5.1.4
## 09/18/2015

1. [Common](#common)
    1. [](#new)
        - Updated Google Fonts library (+4 fonts)
    1. [](#improved)
        - Menu Particle: Implement base item support (#666)
        - Remove empty class div on Particle Module/Widget (#778)
        - Added additional utility block variation to provide equal heights when using box variations side by side (#845)
        - All Particles now show a dedicated Icon in the Layout Manager and UI enhancements have been made on the Particles Picker (#935)
    1. [](#bugfix)
        - Fixed tab level for Offcanvas Section
        - Removed unnecessary margin from select fields in admin
        - Theme list displays wrong version number on each theme (#849)
        - Adding dropdown width in Menu breaks the menu (#850)
        - Menu items missing after upgrade (#843)
        - Clicking on new Modules/Widgets/Particles in menu throw 400 Bad Request (#837)
        - Menu Manager `Dropdown Style` = `Extended` should ignore value in `Dropdown Width` (#852)
        - Filepicker thumbnail preview now renders if the image contains spaces
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Update minimum Joomla version requirement to 3.4.1 (fixes issues with `JModuleHelper::getModuleList()` missing)
        - Fixed `Menu Heading` item type not displaying subtitles when set from Menu Editor
        - Updated Hydrogen template thumbnail and preview images
1. [WordPress](#wordpress)
    1. [](#new)
        - Ability to set custom cache path when hosting company doesn't allow PHP files in `wp-content/cache` ie. WPEngine
        - Added Gantry streams to the `kses` allowed protocols
    1. [](#bugfix)
        - Fixed Offline Mode not working properly
        - Added missing Hydrogen block variations

# 5.1.3
## 09/15/2015

1. [Common](#common)
    1. [](#improved)
        - Icons Picker doesn't allow to select icons when none of them is actually selected (#813)
        - Reduce overall memory usage
        - Twig url(): Add support for timestamp max age (#821)
        - Added notice to Custom JS/CSS atom that inline code should be stripped out of &lt;script&gt; and &lt;style&gt; tags.
    1. [](#bugfix)
        - Fixed "View on GitHub" button in the Changelog modal that was taking you nowhere
        - Equalized blocks sizes are now always rounded to 1 decimal digit and will only be supported this way (fixes #776)
        - Fix 'mkdir(): File exists' exception when copying existing folder structure (#225)
        - Only the first menu item is showing up when menu is starting at level > 1 (#780)
        - Error in menu starting at level > 1: in_array() expects parameter 2 to be array, string given (#803)
        - Fixed `Division by zero` error when setting a Block to `Fixed` and when that block is the only one, at 100%, in the grid (#804)
        - Fixed checkbox field issue not storing the off state in `YAML`, needed for things such as Swipe gesture option (fixes #802)
        - Saving outline style will not properly update CSS in some platforms (#816)
        - SCSS Compiler issue: White page if compiler gets interrupted (#805)
        - Fixed override logic in admin for the Styles panel where switching between Presets wasn't taking into account defaults from Base (#818, #820)
        - Global context is not available for particles in the menu
        - Cached index.yaml.php files are getting updated on every request (#834)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Object returned by JApplicationSite::getTemplate(true) is not compatible (#499)
        - Fix 'Parameter 1 to PlgSystemRedirect::handleError() expected to be a reference' (#755)
        - Fix blank custom pages when format is not HTML (#786)
        - Duplicating outlines does not copy everything (#470)
        - Deleting outline may cause 500 errors in the backend (#774)
1. [WordPress](#wordpress)
    1. [](#new)
        - Implement a particle Widget (#714)
        - Added Login Form particle
        - Hook streams URL filter into the_content, the_excerpt, widget_text and widget_content filters (#779)
        - Added new stream for wp-content directory
        - Added ability to use Widgets in menu (#726)
        - Added wpautop enabler in Hydrogen settings
    1. [](#improved)
        - Added WooCommerce support in themes declaration
        - Added missing the_excerpt filter to excerpts in Hydrogen
    1. [](#bugfix)
        - Load style.css on all pages (#819)
        - Add missing `dir="rtl"` to &lt;html&gt; tag when WordPress is in the RTL mode
        - Error on displaying widget modal: strtolower() expects to be string, array given (#831)
        - `Front Page` conditional check in Assignments isn't working when a single page is set as Front Page

# 5.1.2
## 08/31/2015

1. [Common](#common)
    1. [](#bugfix)
        - Fix regression in all collections (Social, Custom JS / CSS, etc) (#761, #762, #764, #767, #768)
        - Fix Argument 1 passed to `RocketTheme\Toolbox\Blueprints\Blueprints::mergeArrays()` must be of the type array
        - Re-index collection lists to avoid gaps in the indexing (also fixes broken lists that were saved)
        - Fixed issue in Layout Manager where top level section settings would show the Block tab (#766)
1. [WordPress](#wordpress)
    1. [](#bugfix)
        - Fixed issue when renaming an Outline that prevented it to further get edited, duplicated or deleted (#588)

# 5.1.1
## 08/28/2015

1. [Common](#common)
    1. [](#new)
        - Layout Manager: Add block settings for nested sections (#539)
        - Layout Manager: Add support for fixed width sections (#115)
        - Custom JS/CSS Particle supports now inline CSS
        - Process shortcodes in custom HTML particle (#369)
        - New Twig extension and filter : json_decode
    1. [](#improved)
        - Dramatically improved the click/touch response in the whole Admin and G5 Particle Module (fixes #551)
        - WAI-ARIA: Thanks to @Mediaversal, a lot of Admin work has been done for accessibility (#754 - ref. #713)
        - Catch and display warnings from SCSS compiler (#705)
        - Dropdowns / Tags fields have been improved for tab stop, it is now easier to navigate through fields via keyboard (partly #713 related)
        - Enable twig debug extension if Gantry debug has been turned on
        - Implemented validation for the Block Size of a Particle Block (#539)
        - Add HTML body class for used layout preset (#750)
    1. [](#bugfix)
        - ToTop Particle allows HTML again in the content field (#720, #721)
        - Fixed issue in Selectize preventing the dropdown to close when loosing focus in non-IE browsers
        - Avoid race conditions when compiling CSS by compiling each file only once (#516)
        - Load default configuration values from Blueprints (#117, #154)
        - Outline Styles: Overriding only some colors in a card may result unexpected values in the others (#536)
        - It is now possible to override the 'enabled' state of a Particle (green / red toggle), when the override is disable, Base will be inherited (#615)
        - Assets particle: Save CSS and JS files into custom/ directory of the theme instead of custom/images/ (#734)
1. [Joomla](#joomla)
    1. [](#improved)
        - Use cleaner formatting in templateDetails.xml for positions
        - Make Debug module position fixed to the bottom of every layout (#715)
    1. [](#bugfix)
        - Fixed blocks using bootstrap responsive utility classes displaying improperly (#722)
        - Gantry update message is showing up even when there is no update (#631)
        - Module positions not showing up after installing/updating theme (#212)
        - Missing padding in modal windows of 3rd party components (#746)
1. [WordPress](#wordpress)
    1. [](#new)
        - Add Platform Settings into Extras menu
        - Add support for Offline mode (#759)
    1. [](#improved)
        - Make Timber functions to work from particles
    1. [](#bugfix)
        - Admin language will fallback to `en_US` if the locale based `.mo` couldn't be loaded (#719)
        - Extra location of the plugin translation `.mo` file changed to the default value `WP_LANG_DIR/plugins/` (#719)
        - Fix fatal error in PHP 5.2: while unsupported, it should still fail gracefully
        - Uninstall is leaving behind cache files and options (#659)
        - Move blueprints for content into its proper location
        - Fixed the styling for the Gantry 5 settings page
        - Fatal error when editing menu item name in the editor (#752)

# 5.1.0
## 08/16/2015

1. [Common](#common)
    1. [](#new)
        - New Menu Item `Disable Dropdowns` option that allows parents to not render sublevels and still be available (thanks @JoomFX - #611 / #675)
        - Add Twig function preg_match() (#627)
        - Add support for new twig tags: assets, scripts and styles
        - Added Icon picker for the To Top Particle, you can now have Icon only, Text only or both (thanks @adi8i - #696)
        - You can now consult the `Changelog` within the admin. A Changelog link can be found in the footer (for the current version), and a button will show up in the updates area when a new version is available.
        - Add an example how to inject custom twig variables from the theme (see index.php in hydrogen)
    1. [](#improved)
        - Available Themes: Open Preview in a new window
        - Updated Google Fonts library (+2 fonts)
        - Rendered titles for Menu Items is now an option of the Menu Particle (#670)
        - Updated Hydrogen sample content to be more platform agnostic
        - Menu Items rendering on frontend do not render an `id` anymore, since the id is already available in the classname (#629)
        - Improved UI/UX for Atoms section. A maximum of 5 Atoms are now ever displayed per row, returning to a new one if needed (#451)
        - Improved SCSS 3.3/3.4 compatibility
        - Accessibility improvements in admin (#673)
        - Improve error message when parsing JS/CSS assets block fails (#704)
        - Change url() logic for plain ?foo=bar urls to avoid issues when url is requested together with domain
    1. [](#bugfix)
        - Administrator responsive issues in Settings Panel (#603)
        - Anchor links should not be modified (#624)
        - '&' symbol in external menu item not outputting properly (#598)
        - Remove layout tab from base outline (#628)
        - Trying to get property of non-object when accessing page without menu item (#632)
        - Fixed layout manager particle titles overflowing boxes in smaller sizes (#637)
        - Normalized height of standard select element to match other admin form elements
        - Enable date particle, analytics and assets atoms by default to avoid confusion (#330)
        - Fixed collapse of cards in Assignments when filtering with a non-matching word (#672)
        - Fixed resizing Particles in LM where attached events wouldn't get properly removed and causing oddities with the History (fixes #556)
        - Disable whoops when Gantry is in production mode and debug mode has been disabled (#681)
        - Removed additional padding from modals that were incorrectly inheriting it
        - Logo Particle now properly redirects to Home even if clicked from a subpage (#676)
        - Menu Particle: max levels does not work (#698)
        - Ignore non-overrideable values in Settings page (#621)
        - Exceptions thrown outside Gantry are not triggering the default error page, but intercepted by Whoops (#649)
1. [Joomla](#joomla)
    1. [](#bugfix)
        - Fixed available theme notice text problems when no themes are installed (#655)
        - Fixed label alignment in Joomla popup email form (#665)
        - Load missing Joomla assets on AJAX popups (#683, #684)
        - Added missing responsive bootstrap classes required for Joomla editing views (#684)
        - Fix missing preview image in template manager, fix gets applied after upgrading template (#707)
        - Fixed Joomla frontend article editor and popup styling issues (#681)
        - Added missing size class rules for Joomla frontend editing views
        - Fixed Joomla frontend image manager alignment issues
1. [WordPress](#wordpress)
    1. [](#new)
        - WordPress is now integrated with Gantry 5!

# 5.0.1
## 07/16/2015

1. [](#new)
    * Custom CSS / JS Atom now supports JavaScript to be dropped before `</body>` as well as inline scripting (thanks @adi8i)
    * Menu Items can now be set to only display the Icon or Image from the Menu Editor (#574)
1. [](#improved)
    * Added version number to Theme selector and Theme Configuration header (#560)
    * Custom CSS / JS Atom now allows to pick a CSS or JS file via filepicker as well as upload them directly from the picker
    * Minor CSS fixes for Joomla admin when in tablet and smaller viewport (#585)
1. [](#bugfix)
    * Fixed regression with the style of Collections in admin and supporting long strings  (#569)
    * Fixed Assignments filtering failing with empty Menus (#578)
    * Fixed UTF8 special characters being stripped out of Module Gantry 5 Particle causing the value to be lost (#570)
    * Fixed initial load of Module Particle not updating the link to the Joomla Module Manager instance (#582)
    * Fixed Menu Item subtitles in Menu Editor, causing any subtitle to get lost (#579)
    * Fixed Menu Item target not getting synched up between Joomla and Gantry (#584)
    * Fixed dropdowns on frontend not working when Offcanvas was disabled (#583)
    * Fixed edge case where resizing the browser wouldn't properly recalculate the Particles Picker size (#585)
    * Fixed issue in Menu Editor where it was possible to create more than one empty Columns (#585)

# 5.0.0
## 07/13/2015

1. [](#new)
    * Menu:
        - Particles and Modules can be now dropped in non-parent Menu Items (click on a Menu Item in the Menu Editor to get the virtual sublevel)
        - Implemented the option for menu items to append an Hash value (ie, http://yourcustomlink.com/page#hash)
    * Layout Manager:
        - UI/UX enhancements in the Particles Picker. It is now fixed and follows the scrolling of the page, making dragging and dropping particles to the bottom of a Layout much easier
        - When loading a new Layout while keeping the Particles, a warning will pop up in case some of the particles could get lost
    * Particles:
          - You can now load Particles via Joomla `{loadposition}`
          - Social Particle: now includes a Title parameter
          - Menu Particle:
              - It is now possible to choose the - Active - menu from the dropdown
              - Prevent from rendering empty Menu on frontend
              - Fixed Menu Start Level
          - To Top: Scrolling to the top is now smooth
    * Global and generic changes:
        - Added a new Development / Production toggle under the Extras tab which allows to toggle between the two states from within Gantry 5 admin
        - Production / Development now compile different CSS output. In Production mode, everything is compressed, in Development mode CSS is expanded and Line Numbers are added to easily reference the files (This only applies for the Styles Panel and custom.scss)
        - Enhanched Filepicker, it now uses streams, supports drag&drop from desktop for upload and allows to delete files (if they are overridden files)
        - Initial work on the multi language support
        - More body classes added:
            - Menu Item page suffix
            - Print-mode if previewing a print page
1. [](#improved)
    * Menu:
        - Subtitles are now displaying in the interface
    * Assignments:
        - Introduced a new filter to display only the active assignments
        - You can now assign to a different Language
    * Styles:
        - Less aggressive box-sizing to automatically support, out of the box, Joomla and 3rd parties implementations.
    * Particles:
        - Menu Particle:
            - Accessibility improvements
    * Global and generic changes:
        - Updated Google Fonts library (+15 fonts)
        - Use Protocol less urls for loading Google Fonts (so it is http and https compatible)
        - In Joomla Module Manager, Gantry 5 Particle Modules will now display a badge with their type
        - Improved RTL support and automatic detection
1. [](#bugfix)
    * Menu:
        - Many bug fixes to synchronize Joomla with Gantry 5
        - Fixed cases where Particles / Modules wouldn't be deletable
        - Preventing disabled Particles from showing up on frontend when they are disabled
    * Outlines:
        - Preventing Default and non-deletable outlines from being deleted (UI adjusted accordingly)
        - Fixed case where an outline wouldn't be deletable due to a wrong flag set
    * Gantry 5 Particle Module:
        - Prevent Joomla from stripping out HTML content
        - You can now reset the value and change the Particle type
    * Layout Manager:
        - Fixed the preset informations when using history and jumping between different presets
        - History session fixes
    * Layout (frontend):
        - Fixed cases where the cumulative sum of side by side sections wouldn't be 100% as expected
        - Fixed issue where side by side sections (sidebars/main) would have the main overflowing in one of the sidebars
    * Particles:
        - Menu Particle:
            - Fixed Menu Start Level
            - Fixed Offcanvas menu height calculations
    * Global and generic changes:
        - System Plugin and Particle Module are now PHP 5.3 compatible and won't fail with the error "Parse error: syntax error, unexpected '[' in ..."
        - Fixed "Cache path not defined for compiled files"
        - Fixed untranslated positions string in the Joomla Module Editor on frontend
        - Fixed Page Heading not displaying in the Gantry 5 Custom View page
