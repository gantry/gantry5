== Joomla playground theme

How to install it:

- Enter to Gantry Playground ./joomla directory and run in command line:

```
ln -s ../css
ln -s ../css-compiled
ln -s ../fonts
ln -s ../images
ln -s ../js
ln -s ../scss
ln -s ../wordpress/nucleus
ln -s ../wordpress/test
```

- Run ```composer install```
- Symbolically link the theme to your site:

```
cd {PATH_TO_JOOMLA}
ln -s {PATH_TO_HERE} templates/nucleus
```
