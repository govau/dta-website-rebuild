#!/usr/bin/env bash

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

  if [[ $CIRCLE_BRANCH = master || $CIRCLE_BRANCH = develop ]]; then
    RUN_PHPUNIT=1
  else
    # Only run phpunit if anything in core is different to the develop branch
    for file in $(git diff --name-only --diff-filter=d origin/develop...); do
      if [[ $file == docroot/core* ]]; then
        RUN_PHPUNIT=1
        break
      fi
    done
  fi

  if [ -n "$RUN_PHPUNIT" ]; then
    pushd docroot/core
      phpunit --testsuite=unit --exclude-group \
        Composer,DependencyInjection,PageManager,jsonapi,legacy
    popd
  fi
fi
