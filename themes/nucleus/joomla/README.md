== Joomla playground theme

How to install it:

- Run composer in /src
- Enter to Gantry Playground ./themes/nucleus/joomla directory and run in command line:

```
ln -s ../common/css
ln -s ../common/css-compiled
ln -s ../common/fonts
ln -s ../common/images
ln -s ../common/js
ln -s ../common/scss
ln -s ../common/nucleus
ln -s ../common/test
ln -s ../../../src
```

- Run ```composer install```
- Symbolically link the theme to your site:

```
cd {PATH_TO_JOOMLA}
ln -s {PATH_TO_HERE} templates/nucleus
```
