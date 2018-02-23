#!/usr/bin/env bash

# Exit immediately if there is an error
set -e

# cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# echo out each line of the shell as it executes
set -x

# This script is executed from .bp-config/options.json via ADDITIONAL_PREPROCESS_CMDS
# See https://docs.cloudfoundry.org/buildpacks/php/gsg-php-config.html#options

# Only execute on the first application instance
if [[ "${CF_INSTANCE_INDEX}" == 0]]; then
  echo "I am the first instance"
else
  echo "I am not the first instance"
fi

./bin/drush cache-rebuild
