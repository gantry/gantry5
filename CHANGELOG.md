# 5.1.3
## XX/XX/2015

1. [Common](#common)
    3. [](#bugfix)
        - Fixed "View on GitHub" button in the Changelog modal that was taking you nowhere
        - Equalized blocks sizes are now always rounded to 1 decimal digit and will only be supported this way (fixes #776)
2. [Joomla](#joomla)
    3. [](#bugfix)
        - Object returned by JApplicationSite::getTemplate(true) is not compatible (#499)

# 5.1.2
## 31/08/2015

1. [Common](#common)
    3. [](#bugfix)
        - Fix regression in all collections (Social, Custom JS / CSS, etc) (#761, #762, #764, #767, #768)
        - Fix Argument 1 passed to `RocketTheme\Toolbox\Blueprints\Blueprints::mergeArrays()` must be of the type array
        - Re-index collection lists to avoid gaps in the indexing (also fixes broken lists that were saved)
        - Fixed issue in Layout Manager where top level section settings would show the Block tab (#766)
3. [WordPress](#wordpress)
    3. [](#bugfix)
        - Fixed issue when renaming an Outline that prevented it to further get edited, duplicated or deleted (#588)

# 5.1.1
## 28/08/2015

1. [Common](#common)
    1. [](#new)
        - Layout Manager: Add block settings for nested sections (#539)
        - Layout Manager: Add support for fixed width sections (#115)
        - Custom JS/CSS Particle supports now inline CSS
        - Process shortcodes in custom HTML particle (#369)
        - New Twig extension and filter : json_decode
    2. [](#improved)
        - Dramatically improved the click/touch response in the whole Admin and G5 Particle Module (fixes #551)
        - WAI-ARIA: Thanks to @Mediaversal, a lot of Admin work has been done for accessibility (#754 - ref. #713)
        - Catch and display warnings from SCSS compiler (#705)
        - Dropdowns / Tags fields have been improved for tab stop, it is now easier to navigate through fields via keyboard (partly #713 related)
        - Enable twig debug extension if Gantry debug has been turned on
        - Implemented validation for the Block Size of a Particle Block (#539)
        - Add HTML body class for used layout preset (#750)
    3. [](#bugfix)
        - ToTop Particle allows HTML again in the content field (#720, #721)
        - Fixed issue in Selectize preventing the dropdown to close when loosing focus in non-IE browsers
        - Avoid race conditions when compiling CSS by compiling each file only once (#516)
        - Load default configuration values from Blueprints (#117, #154)
        - Outline Styles: Overriding only some colors in a card may result unexpected values in the others (#536)
        - It is now possible to override the 'enabled' state of a Particle (green / red toggle), when the override is disable, Base will be inherited (#615)
        - Assets particle: Save CSS and JS files into custom/ directory of the theme instead of custom/images/ (#734)
2. [Joomla](#joomla)
    2. [](#improved)
        - Use cleaner formatting in templateDetails.xml for positions
        - Make Debug module position fixed to the bottom of every layout (#715)
    3. [](#bugfix)
        - Fixed blocks using bootstrap responsive utility classes displaying improperly (#722)
        - Gantry update message is showing up even when there is no update (#631)
        - Module positions not showing up after installing/updating theme (#212)
        - Missing padding in modal windows of 3rd party components (#746)
3. [WordPress](#wordpress)
    1. [](#new)
        - Add Platform Settings into Extras menu
        - Add support for Offline mode (#759)
    2. [](#improved)
        - Make Timber functions to work from particles
    3. [](#bugfix)
        - Admin language will fallback to `en_US` if the locale based `.mo` couldn't be loaded (#719)
        - Extra location of the plugin translation `.mo` file changed to the default value `WP_LANG_DIR/plugins/` (#719)
        - Fix fatal error in PHP 5.2: while unsupported, it should still fail gracefully
        - Uninstall is leaving behind cache files and options (#659)
        - Move blueprints for content into its proper location
        - Fixed the styling for the Gantry 5 settings page
        - Fatal error when editing menu item name in the editor (#752)

# 5.1.0
## 16/08/2015

1. [Common](#common)
    1. [](#new)
        - New Menu Item `Disable Dropdowns` option that allows parents to not render sublevels and still be available (thanks @JoomFX - #611 / #675)
        - Add Twig function preg_match() (#627)
        - Add support for new twig tags: assets, scripts and styles
        - Added Icon picker for the To Top Particle, you can now have Icon only, Text only or both (thanks @adi8i - #696)
        - You can now consult the `Changelog` within the admin. A Changelog link can be found in the footer (for the current version), and a button will show up in the updates area when a new version is available.
        - Add an example how to inject custom twig variables from the theme (see index.php in hydrogen)
    2. [](#improved)
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
    3. [](#bugfix)
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
2. [Joomla](#joomla)
    3. [](#bugfix)
        - Fixed available theme notice text problems when no themes are installed (#655)
        - Fixed label alignment in Joomla popup email form (#665)
        - Load missing Joomla assets on AJAX popups (#683, #684)
        - Added missing responsive bootstrap classes required for Joomla editing views (#684)
        - Fix missing preview image in template manager, fix gets applied after upgrading template (#707)
        - Fixed Joomla frontend article editor and popup styling issues (#681)
        - Added missing size class rules for Joomla frontend editing views
        - Fixed Joomla frontend image manager alignment issues
3. [WordPress](#wordpress)
    1. [](#new)
        * WordPress is now integrated with Gantry 5!

# 5.0.1
## 07/16/2015

1. [](#new)
    * Custom CSS / JS Atom now supports JavaScript to be dropped before `</body>` as well as inline scripting (thanks @adi8i)
    * Menu Items can now be set to only display the Icon or Image from the Menu Editor (#574)
2. [](#improved)
    * Added version number to Theme selector and Theme Configuration header (#560)
    * Custom CSS / JS Atom now allows to pick a CSS or JS file via filepicker as well as upload them directly from the picker
    * Minor CSS fixes for Joomla admin when in tablet and smaller viewport (#585)
3. [](#bugfix)
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
2. [](#improved)
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
3. [](#bugfix)
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
