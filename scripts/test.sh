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

pushd docroot

  find . -name \*.php ! -name global_namespace_php5.php -type f -print0 | xargs -0 -n1 php -l

  pushd core

    phpunit --testsuite=unit --exclude-group \
      Composer,DependencyInjection,PageManager,jsonapi

  popd

popd
