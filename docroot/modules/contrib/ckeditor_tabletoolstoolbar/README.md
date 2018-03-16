This module integrates the CKEditor "Table Tools Toolbar" plugin.

It provides a plugin for CKEditor 4, that add toolbar's groups with buttons
 relevant to tabletools plugin's table's context menu items actions.

DEPENDENCIES
-------------
This module requires to install the CKEditor "Table Tools Toolbar" plugin.

HOW TO INSTALL DEPENDENCIES VIA COMPOSER:

1. Add ckeditor/tabletoolstoolbar repositories to your `composer.json`.

```
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "ckeditor/tabletoolstoolbar",
            "version": "0.0.1",
            "type": "drupal-library",
            "dist": {
                "url": "https://download.ckeditor.com/tabletoolstoolbar/releases/tabletoolstoolbar_0.0.1.zip",
                "type": "zip"
            }
        }
    }
],
```

2. Execute `composer require ckeditor/tabletoolstoolbar`
3. Make sure there is the file `libraries/tabletoolstoolbar/plugin.js`.

HOW TO INSTALL DEPENDENCIES MANUALLY:
1. Download the plugin on the project page : https://ckeditor.com/addon/tabletoolstoolbar
2. Create a libraries folder in your drupal root if it doesn't exist
3. Extract the plugin archive in the librairies folder
4. Make sure there is the file `libraries/tabletoolstoolbar/plugin.js`.

HOW TO USE
-----------
- Go to the format and editor config page and click configure on the format your 
want to edit : 
http://drupalvm.dev/admin/config/content/formats
- Add the plugin buttons in your editor toolbar. 
- Save, that's it.
