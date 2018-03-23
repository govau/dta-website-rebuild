# About

This module integrates the mmenu jquery plugin with Drupal's menu system with
the aim of having an off-canvas mobile menu and a horizontal menu at wider
widths. It integrates with your theme's breakpoints to allow you to trigger the
display of the horizontal menu at your desired breakpoint. The mobile off-canvas
menu is displayed through a toggle icon or with the optional integration of
swipe gestures.

# Install

## Bower method:

If you have bower installed you can change directory into the responsive_menu
module directory and run `bower install`. This will create a `libraries`
directory which you _must_ move to your Drupal root.

## Drush make method:

From your Drupal root you can run drush make with the following parameters:
```
drush make --no-core modules/contrib/responsive_menu/responsive_menu.make.yml . @none
```

Adjust the path to the responsive_menu make file as needed. This will install
the needed third party libraries in `/libraries`.

## Manual method:

The only library required by this module is the
[mmenu](http://mmenu.frebsite.nl) plugin. You need to download the jQuery
version and place it in `/libraries` in your docroot (create the directory if
you need to). Rename your newly placed download to mmenu, so the resulting path
is `/libraries/mmenu`. This module will look in `/libraries/mmenu/dist` for
the javascript files so ensure you have the correct file structure.

The other two libraries which add functionality (if desired) are the
[superfish](https://github.com/joeldbirch/superfish) plugin and the
[hammerjs](http://hammerjs.github.io) library. Place those in `/libraries` and
rename them to superfish and hammerjs. For superfish you should have a structure
like `/libraries/dist/js`, while hammerjs should be simply
`/libraries/hammerjs`.

# Configuration

As an administrator visit `/admin/config/user-interface/responsive-menu`

You can set the various options. Some of the options will require the libraries
to be present before allowing configuration.

There is an option to _not use_ the breakpoint which in turn will not add the
breakpoint css file to the page. This will allow you to use the off-canvas menu
at any screen size. Alternatively you might want to use your own menu at desktop
screen widths and control the visibility of both with your own css.

# Block placement

The module provides two blocks, one for the horizontal menu, labeled in the
block UI as "Horizontal menu". The other is labeled as "Responsive menu mobile
icon" and is the 'burger' menu icon and text which allows the user to toggle the
mobile menu open and closed. Both blocks should be placed in an appropriate
region, like the header region. The horizontal menu is designed to replace any
existing main menu block you might already have in your theme.

The placement of the horizontal menu block is optional, the off-canvas menu will
work regardless of the existence of the horizontal menu block.

# Theming and theme compatibility

This module should be compatible with most themes. One basic requirement is that
the theme includes a wrapping 'page' div. This is so that mmenu knows which
elements belong inside the wrapper when the off canvas menu is opened. Bartik is
an example of a theme with a wrapping div. Bootstrap (3) is an example of a
theme which doesn't have the wrapping div (although you can easily add one to
page.html.twig, see [this issue](https://www.drupal.org/node/2727345)). More
details on how to set up the divs are on an [mmenu documentation
page](http://mmenu.frebsite.nl/tutorials/off-canvas/the-page.html).

It should also be noted that the default css that comes with the module provides
some _very basic_ styling and should be copied and pasted into your theme's css
so that you can modify it to fit your theme's style. Once you've copied and
pasted the css into your stylesheet you can disable the inclusion of the
module's css on the settings page.

# Licenses

The licenses for the libraries used by this module are:

hammerjs: MIT mmenu: Creative Commons Attribution-NonCommercial superfish: MIT

The mmenu plugin used to have an MIT license but has changed to the CC
NonCommercial license. So you'll need to pay the developer a fee to use it in a
commercial web site. Alternatively you can download the earlier MIT licensed
version which should be compatible.
