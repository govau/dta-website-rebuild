#!/usr/bin/env bash

# This script is executed on each running container via
# ADDITIONAL_PREPROCESS_CMDS in .bp-config/options.json
# See https://docs.cloudfoundry.org/buildpacks/php/gsg-php-config.html#options

# Exit immediately if there is an error
set -e

# cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# echo out each line of the shell as it executes
set -x

# Add included mysql cli to PATH
PATH="${HOME}/scripts/mysql:${PATH}"

# Add vendored bin dir to PATH
PATH="${HOME}/bin:${PATH}"

cd docroot

# Only execute on the first application instance
if [[ "${CF_INSTANCE_INDEX}" = "0" ]]; then
  echo "I am the first instance"

  # If DRUPAL_UUID is defined, change our UUID to it if necessary
  if [[ -n ${DRUPAL_UUID+x} ]]; then
    CURRENT_UUID=$(drush cget "system.site" --format=json | jq -r .uuid )
    if [[ ${DRUPAL_UUID} != ${CURRENT_UUID} ]]; then
      drush config-set -y "system.site" uuid "${DRUPAL_UUID}"
    fi
  fi

  # Fix for https://www.drupal.org/node/2583113
  # TODO think this isnt needed anymore
  # drush ev '\Drupal::entityManager()->getStorage("shortcut_set")->load("default")->delete();'

  # Import the config from sync dir
  drush config-import -y

else
  echo "I am not the first instance"
fi

drush cache-rebuild

echo "preprocess.sh finished"
