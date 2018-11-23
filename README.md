# www.dta.gov.au

[![CircleCI](https://circleci.com/gh/govau/dta-website-rebuild.svg?style=svg)](https://circleci.com/gh/govau/dta-website-rebuild)

www..dta.gov.au is built with Drupal 8 around a GovCMS8 core.

Please be aware that while the site uses GovCMS8, it no longer actively follows developments to the GovCMS8 code base.

# Local installation

A local installation requires [Composer](https://getcomposer.org/) and a local web server such as M/WAMP or [Docker](https://www.docker.com/). This repository contains a Dockerfile.

The following instructions assume knowledge of the Drupal installation procedure and that you know how to set up and run a local web server and other applications such as Drush.

1. Clone this repository.
2. Run `composer install` from within the top level of the repository. This will install all the required files and folders including themes, modules, libraries and core.
3. Follow the relevant procedure to access the site over your web server of choice.
4. A fresh local installation will need to be installed to create the database, so visit [your local server/]install.php
5. Once the site is installed and running, import the configuration, which is included in the repository. Use either the administration user interface (`Configuration -> Development -> Configuration synchronisation` then `Import all` or [Drush](https://www.drush.org/) `drush cim -y`.
6. You can then generate content using [Devel Generate](https://www.drupal.org/project/devel) (installed but not enabled by default) or import a database from the beta site itself. You will probably need to create a new administration user with `drush user:create` if you do not have administrator credentials on the Beta site as any users you created during the installation process will be destroyed.

For best performance, we recommend adding [Prestissimo](https://github.com/hirak/prestissimo) to your global Composer before installing the site, as it enables dependencies to load in parallel, significantly reducing the install time:

`composer global require "hirak/prestissimo:^0.3"`

# Adding new modules

All modules and libraries are downloaded using Composer.

For example, to install the Display Suite module (which is already installed), run `composer require drupal/ds:[version]`. It is generally a good idea to include the version number you wish to install to lock the version and avoid accidental upgrades in the future. This will add the relevant module to the `composer.json` file and update the `composer.lock` file. The deployment system will use these files to download the appropriate files onto the cloud.gov.au. *You do not need to upload modules to the hosting environment.*

## Updating modules or core

To update a module or Drupal core, follow the procedure under 'Adding new modules' and use the version of the module you wish to update.

Updated modules sometimes require updates to the database. To do this via the UI, login as an administrator and go to `(Drupal 8 logo) -> Run updates` and follow the instructions. It is a good idea to backup the database before doing this. This can also be done using `drush updb` using the Cloud Foundry `run-task` command (see below).

# Theme updates

Instructions on updating the theme are available from the theme repository at https://github.com/govau/dta-gov-au. The thing to remember is that it too is updated and tracked using Composer.

# Updating configuration

Once you have finished development work locally, export the configuration using the administration menu or with `drush cex -y`. This will export the current site configuration to a series of `yml` files which are then committed to the repository.

1. Create a new branch for the change.
2. Commit the updated, new or removed configuration files to the branch.
3. Submit a pull request to begin testing (see below).

# Deployment

Deployment to cloud.gov.au is accomplished using Github pull requests and Circle CI. To begin integration and testing, submit a pull request into the `develop` branch from your new `feature` branch. This will start automated testing against the `develop` branch.

Once the branch has passed its tests, you can merge to deploy the new code onto the [staging site](https://dta-website.staging.digital.gov.au). Similarly to production, this is behind AWS cloudfront CDN. You can also access the staging site directly i.e. bypassing the cdn: https://dta-website-staging-direct.apps.y.cld.gov.au

Make sure that the new configuration is imported on the staging site using the administration UI or use `drush cim -y` via the Cloud Foundry app (`cf run-task [APP NAME] "source scripts/buildrc && drush cim -y"`) and then clear the site caches. Clearing the caches via the site UI is not recommended as it will cause a 503 error on cloud.gov.au. Instead use `cf run-task [APP NAME] "source scripts/buildrc && drush cr"`.

Once you are satisfied that the changes are working as expected, you can deploy to master using the same procedure. It is *strongly* recommended you export a copy of the beta database with the Backup and Migrate module (`Configuration -> Development -> Backup and migrate` then `Backup now`), just in case. When the tests are complete, merge the changes to deploy the site to the production environment. Import the configuration and clear the caches as described above (if necessary).

# Importing configuration

If the automated configuration import does not happen for some reason, you can import the new configuration via the Drupal UI. Login as an administrator, and go to `Configuration -> Development -> Configuration synchronisation` then `Import all`. It is a good idea to follow this up with a cache rebuild via the UI or Drush (see above).
