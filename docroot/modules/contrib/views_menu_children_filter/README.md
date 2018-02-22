**SUMMARY**

The Views Menu Children Filter module adds a contextual filter to Views for
showing child entities of the specified parent entity in the menu system. Site
builders can select the menus they want to search within and provide the parent
id via a contextual filter in just a few clicks.

For a full description of the module, visit the project page:
  http://drupal.org/project/views_menu_children_filter

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/views_menu_children_filter


**REQUIREMENTS**

* Views (in Core)


**INSTALLATION**

Install as usual, see http://drupal.org/node/1897420 for further information.


**CONFIGURATION**

* When you add the "Menu Children" contextual filter to your custom view, you can
  configure the menus you wish to search.


**TROUBLESHOOTING**

* If your menu children aren't showing up, check the following

  - Are you providing the correct parent ID in your contextual filter? The
    simplest way to do this is to select:

    "Provide default value" -> "Content ID from URL"

  - Is your view filtering based on content type? If so, does it have the
    appropriate settings to be able to show the child items?


**CONTACT**

Current maintainers:
* [cravecode](https://www.drupal.org/user/1693456)
* [bmcclure](https://www.drupal.org/user/278485)
