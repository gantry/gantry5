# Adding Joomla! 4 support for themes

Themes designed to Joomla! 3 do not work in Joomla! 4 without some changes. This document helps you to add Joomla! 4 support to your themes.

**NOTE:** Joomla! 4 support in Gantry is still work in progress!

## Updating theme files to make the theme to run in Joomla! 4.0

Joomla! 4 has made some changes to the templates, which means that also Gantry Themes need to be adjusted to make them to work in the latest Joomla!

- Update theme initialization to work in Joomla! 4.0
  (please copy updated `includes`, `fields` and `html` folders to your theme!)
- Update theme installation  to work in Joomla! 4.0
  (please copy or update `install.php` in your theme!)
- Update page templates to work in Joomla! 4.0
  (please copy or update `component.php`, `error.php`, `index.php`, `offline.php` in your theme!)
- Update SCSS files:
  - `fadeout($x, n%)` to `fade-out($x, 0.n)`
  - `transparentize($x, n%)` to `transparentize($x, 0.n)`

## JavaScript libraries

Note that Joomla 4 removes support for:

- jQuery UI
- Mootools
- Bootstrap 2.2

Those still work, but I recommend those to be removed from the themes.

## Errors

### Incompatible units px and rem

See [commit b16d10e](https://github.com/gantry/gantry5/commit/b16d10eb2b29a866628c3807ce31ad67b0141278) on how to fix it in your theme.
