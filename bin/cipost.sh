#!/usr/bin/env bash

# Exit immediately if there is an error
set -e

# cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# Test Drush and Drupal Console, and perform a cache rebuild.

main() {

  echo 'Checking Drush...'
  drush status

  echo 'Clearing caches...'
  drush cr

  echo 'Checking Drupal Console...'
  drupal --version

}

main $@
