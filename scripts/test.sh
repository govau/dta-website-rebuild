#!/usr/bin/env bash

# Tests that do NOT require a drupal instance can be run here.
# If you need to add a test that requires drupal, add it to int-test.sh instead.

# Exit immediately if there is an error
set -e

# Cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# Print shell input lines as they are executed
# DO NOT USE THIS IF YOU USE SECRETS IN THIS SCRIPT
set -x

# Include build env vars
source "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/buildrc"

if [ -n "$CIRCLE_BRANCH" ]; then
  # Running php lint on every php file is slow, so we only want to run it against
  # changes from the develop branch.
  # We dont run php lint on master or develop.

  if [[ $CIRCLE_BRANCH != master && $CIRCLE_BRANCH != develop ]]; then

    #Run php lint, but only against modified php files
    git diff --name-only --diff-filter=M origin/develop... -- '*.php' | xargs -n1 php -l
  fi

fi
