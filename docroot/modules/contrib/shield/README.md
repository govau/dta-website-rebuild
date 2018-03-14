Shield
------

### Summary

PHP Authentication shield. It creates a simple shield for the site with HTTP
basic authentication. It hides the sites, if the user does not know a simple
username/password. It handles Drupal as a
["walled garden"](http://en.wikipedia.org/wiki/Walled_garden_%28technology%29).

This module helps you to protect your (dev) site with HTTP authentication.

### Configuration

If you don't need the authentication just live blank the user field.

1. enable the module
2. go to the admin interface (admin/config/system/shield) and fill the form
3. nothing else :)

### Key module

The configuration storage supports storing the authentication in configuration
or in secure keys using http://www.drupal.org/project/key module. For the most
secure keys, use the key module 1.7 or higher which has a multi-value
user/password key for storing the user and password in a single key.

***See: <https://www.drupal.org/project/shield>***
