== Grav playground theme

How to install it:

- Enter to Gantry Playground ./themes/nucleus/grav directory and run in command line:

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

- Symbolically link the theme to your site:

```
cd {PATH_TO_GRAV}
ln -s {PATH_TO_HERE} user/themes/nucleus
```
