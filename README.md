Gantry Framework
================

[![Join the chat at https://gitter.im/gantry/gantry5](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/gantry/gantry5?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

Documentation for all the supported platforms can be found from [themes](themes/README.md) folder.

Best place to get started is to take a look into Gantry Prime which is our primary theme development and testing environment.

PS. You should run 'composer install' inside 'src/' folder before installing Gantry 5 to your sites.


After you have done that, just go into your Joomla / WordPress / phpBB / Magento / Grav installation and run ```{PATH_TO_REPO}/bin/install.sh``` in there.

## Joomla

Extensions Manager / Discover / Discover and install all Gantry 5 related extensions. Also remember to enable System - Gantry Administration plugin.

## WordPress

In WordPress you should also install Timber plugin before using the theme.

Directory structure
===================

- assets        System wide media assets (Font Awesome, Whoops etc).
- bin           Scripts to install and build Gantry.
- design        Design images for Gantry.
- engines       Supported engines (Nucleus, Bootstrap etc).
- platforms     Gantry administration, and platform specific plugins etc.
- src           Gantry framework and platform specific overrides.
- themes        Base themes for Gantry.
