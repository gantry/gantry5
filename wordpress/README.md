== WordPress playground theme

How to install it:

- Run composer in ../src
- Enter to Gantry Playground ./wordpress directory and run in command line:

```
ln -s ../css
ln -s ../css-compiled
ln -s ../fonts
ln -s ../images
ln -s ../js
ln -s ../scss
ln -s ../src
```

- Symbolically link the theme to your site:

```
ln -s {PATH_TO_HERE} /wp-content/themes/nucleus
```
