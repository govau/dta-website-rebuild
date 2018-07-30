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

echo "preprocess.sh started"

# Add included mysql cli to PATH
PATH="${HOME}/scripts/mysql:${PATH}"

# Add vendored bin dir to PATH
PATH="${HOME}/bin:${PATH}"

echo "Path updated to ${PATH}"

echo "preprocess.sh finished"
