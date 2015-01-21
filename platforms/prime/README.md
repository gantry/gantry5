Gantry Prime
============

Gantry Prime is our primary theme development and testing platform. It is a standalone Gantry5 installation which allows both developers and testers to access all the features in both administration and the site without the overhead and quirks of installing and running a CMS.

Prime itself is a very simple environment which consists of only three small php files and the usual platform specific classes. On top of that it has some template file overrides and the test data consisting of pages, module positions and layouts.

## Installing

You can install Gantry Prime by cloning the Gantry Playground project to your projects directory:

    git clone git@bitbucket.org:rockettheme/gantry-playground.git
    cd gantry-playground
    git checkout develop
    composer install

After this you need to go to your web root and create a new directory:

    mkdir prime
    cd prime
    touch .prime

Now you just need to install prime to your web root by running the following command (pointing to your projects directory):

    ~/MyProjects/gantry-playground/bin/install.sh

And you're set up!

## Updating

You can update your repository at any time by running:

    git pull
    composer update

## Content Pages

All the Gantry Prime pages are located in pages/ folder. Content pages are written by using twig templating language.

Gantry prime uses very simple routing where it just looks if there is a file which has identical path to URI appended with .html.twig for regular html output.

    path/to/my/page => PRIME_ROOT/pages/path/to/my/page.html.twig
    another/path => PRIME_ROOT/pages/another/path.html.twig

Basic structure of the file is:

    {% extends "@nucleus/page.html.twig" %}
    {% do gantry.theme.setLayout('test') %}
    {% block content %}
        Here comes your content.
    {% endblock %}

## Module positions

Module positions work in a similar way to Joomla; named module positions are defined in layout files. Each position can contain a set of modules, which are stored under modules/position_name/ folder. All module files defined in here are global and exist on every page which has the module position defined inside their layout file.

Simple module file is defined below:

    {% extends "@nucleus/partials/module.html.twig" %}
    {% set title = position.name %}
    {% block module %}
    {% endblock %}

## Layouts

Layouts define the structure of the page.

Lookup paths for layouts (against the repository):

    /theme/[current]/layouts
    /theme/[current]/common/layouts
    /platform/prime/layouts

## Streams

Platform specific streams:

    gantry-prime://         /prime
    gantry-layouts://       /prime/layouts, gantry-theme://layouts
    gantry-pages://         /prime/pages
    gantry-positions://     /prime/positions

Common streams:

    gantry-admin://         /prime/admin (only available in admin)
    gantry-media://         /prime/media
    gantry-themes://        /prime/themes
    gantry-theme://         /prime/themes/[current]

Internal streams:

    gantry-cache://         Cached Files
    gantry-blueprints://    Blueprint Files
    gantry-config://        Configuration Files
    gantry-engine://        Nucleus Engine
    gantry-particles://     Gantry Particles
