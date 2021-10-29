# More strict SCSS compiler

The new version of SCSS compiler follows the latest SCSS specs and behaves more like the reference implementation.

This means that the compiler is far stricter when it sees badly written SCSS or missing files.

## CSS Compilation Errors

### File not found for @import

Older version of SCSS compiler allowed missing SCSS imports, but it was not specs compliant and has been fixed in the new version.

You should add back the missing file (with added `.scss` extension). It can be empty if you don't need the added styles. The path is either relative to the path of the file where the error gets triggered or in `scss/` folder.

### Incompatible units px and rem

New SCSS compiler doesn't allow you to mix different units together (such as `rem` and `px`) as the conversion isn't reliable. As a workaround you can use a single unit (`rem`) everywhere or change SCSS files to calculate it in the browser.

See [commit b16d10e](https://github.com/gantry/gantry5/commit/b16d10eb2b29a866628c3807ce31ad67b0141278) on how to fix it in your theme.

## Future proofing SCSS

To limit the errors, Gantry 5.5 uses older version of the SCSS compiler by default. To use the newer version you need to set minimum Gantry version in your theme:

`gantry/theme.yaml`
```yaml
configuration:
  ...

  dependencies:
    gantry: '5.5'
```

After doing this, go to styles tab in Gantry admin and
- Extras > Clear Cache
- Recompile CSS

It will reveal if your SCSS has any issues, which you should fix.

Now fix the errors to clear badly written SCSS such as:

- `fadeout($x, n%)` should be `fade-out($x, m)` where `m = n/100` or in range `0...1`
- `transparentize($x, n%)` should be `transparentize($x, m)` where `m = n/100` or in range `0...1`

SCSS compiler will also show a lot of deprecated warnings for features which will not be supported in the next version of Gantry anymore. We will soon release a script to automatically fix those.

**NOTE:** There is still an issue with incompatible units if breakponts use `px`. We are looking to fix it.

# Adding Joomla! 4 support for themes

Themes designed to Joomla! 3 do not really work in Joomla! 4 without some changes. This document helps you to add Joomla! 4 support to your themes.

**NOTE:** Joomla! 4 support in Gantry is still work in progress!

## Updating theme files to make the theme to run in Joomla! 4.0

Joomla! 4 has made some changes to the templates, which means that also Gantry Themes need to be adjusted to make them to work in the latest Joomla!

All the reference files mentioned below can be found from: https://github.com/gantry/gantry5/tree/develop/themes/helium/joomla

- Update theme initialization to work in Joomla! 4.0
  (please copy updated `includes`, `fields` and `html` folders to your theme!)
- Update theme installation  to work in Joomla! 4.0
  (please copy or update `install.php` in your theme!)
- Update page templates to work in Joomla! 4.0
  (please copy or update `component.php`, `error.php`, `index.php`, `offline.php` in your theme!)

**NOTE:** It is very important to update the files instead of replacing them if you or the theme author have made any changes to the files!

## JavaScript libraries

Note that Joomla 4 removes support for:

- jQuery UI
- Mootools
- Bootstrap 2.2

Those still work, but I recommend those to be removed from the themes.

# WordPress Fixes

https://github.com/gantry/gantry5/commit/9d50df3808cf02973c022d885f6c4b96f3dc840e
