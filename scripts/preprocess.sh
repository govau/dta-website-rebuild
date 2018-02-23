#!/usr/bin/env bash

# Exit immediately if there is an error
set -e

# cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# echo out each line of the shell as it executes
set -x

# This script is executed each running container from .bp-config/options.json via ADDITIONAL_PREPROCESS_CMDS
# See https://docs.cloudfoundry.org/buildpacks/php/gsg-php-config.html#options

# Only execute on the first application instance
if [[ "${CF_INSTANCE_INDEX}" = "0" ]]; then
  echo "I am the first instance"

  # If DRUPAL_UUID is defined, change our UUID to it if necessary
  if [[ -n ${DRUPAL_UUID+x} ]]; then
    CURRENT_UUID=$(./bin/drush config-get "system.site" uuid)
    if [[ "${DRUPAL_UUID}" != "${CURRENT_UUID}" ]]; then
      ./bin/drush config-set "system.site" uuid "${DRUPAL_UUID}"
    fi
  fi

else
  echo "I am not the first instance"
fi

./bin/drush cache-rebuild
