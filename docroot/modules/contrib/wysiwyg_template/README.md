## SUMMARY

This module works with the template plugins for TinyMCE, CK Editor and
FCK Editor, which allow a user to select an HTML template from a drop-down and
insert it into the WYSIWYG text-area.

For a full description visit the project page:

- http://drupal.org/project/wysiwyg_template

Bug reports, feature suggestions and latest developments:

- http://drupal.org/project/issues/wysiwyg_template


## REQUIREMENTS

* [CKEditor Templates plugin](http://ckeditor.com/addon/templates)

**Note** if using composer to manage dependencies, this library will be installed for you. Otherwise place the library at `/libraries/templates` manually or using *drush make*.

For composer to be able to find the library, you have to add this section into your root composer.json file before installing the module:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        },
        {
            "type": "package",
            "package": {
                "name": "ckeditor/templates",
                "type": "drupal-library",
                "version": "4.5.7",
                "dist": {
                    "type": "zip",
                    "url": "https://download.ckeditor.com/templates/releases/templates_4.5.7.zip",
                    "reference": "master"
                }
            }
        }
    ]
}
```

## INSTALLATION

* Install as usual, see http://drupal.org/node/70151 for further information.

* Go to `Administer > Configuration > Content authoring > Text formats and editors` and

  - Click *Configure* to set up your editor profile(s).

  - Under *Toolbar Configuration* you'll see *Insert templates* as a new option in the *Available buttons* list.

* `Administer > Configuration > Content authoring > WYSIWYG templates` to create new templates.

## EDITOR COMPATIBILITY

Supported editors:

 - CKEditor 4.5.7
