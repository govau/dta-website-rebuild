#!/usr/bin/env bash

# Exit immediately if there is an error
set -e

# Cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# Print shell input lines as they are executed
# DO NOT USE THIS IF YOU USE SECRETS IN THIS SCRIPT
set -x

# Include build env vars
source "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/../scripts/buildrc"

# Increase memory limit for php to resolve
# `drush config-import` memory usage limit
echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Fixup circleci permissions for drupal
# The directory <em class="placeholder">sites/default/files</em> is not writable
chown -R www-data:www-data  ${SCRIPT_DIR}/../docroot/sites/default/files/
# The govCMS installer requires write permissions to sites/default/settings.php
chown -R www-data:www-data  ${SCRIPT_DIR}/../docroot/sites/default/settings.php

apt install -y libncurses5
 drush -y site-install

# Fix for error "Entities exist of type <em class="placeholder">Shortcut link</em> and
# <em class="placeholder">Shortcut set</em> <em class="placeholder">Default</em>.
# These entities need to be deleted before importing."
drush ev '\Drupal::entityManager()->getStorage("shortcut_set")->load("default")->delete();'

# We want to mimic the app running on cloudfoundry, so we call
# preprocess.sh before starting the application to hopefully pick up
# any issues there.
CF_INSTANCE_INDEX=0 DRUPAL_UUID="59f85df3-5f18-45dd-bf6a-40977b57a842" \
  ./scripts/start.sh
