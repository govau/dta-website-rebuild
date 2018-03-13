#!/usr/bin/env bash

# Exit immediately if there is an error
set -e

# Cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# Print shell input lines as they are executed
# DO NOT USE THIS IF YOU USE SECRETS IN THIS SCRIPT
set -x

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Add vendored bin dir to PATH
PATH="${SCRIPT_DIR}/../bin:${PATH}"

if [ -n "$CIRCLE_BRANCH" ]; then
  # For efficiency we only want to run certain tests against deviations from
  # the base branch, which for now we assume is develop.
  BASE_BRANCH=develop
  if [[ $CIRCLE_BRANCH != $BASE_BRANCH ]] ; then

    #Run php lint, but only against modified php files
    git diff --name-only --diff-filter=M origin/${BASE_BRANCH}... -- '*.php' | xargs -n1 php -l

    # If anything in core has changed, run its phpunit
    for file in $(git diff --name-only --diff-filter=M origin/${BASE_BRANCH}...); do
      if [[ $file == docroot/core* ]]; then
        pushd docroot/core
          phpunit --testsuite=unit --exclude-group \
            Composer,DependencyInjection,PageManager,jsonapi
        popd
        break
      fi
    done

  fi
fi
