#!/usr/bin/env bash

# Exit immediately if there is an error
set -e

# cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# Copy over the deployment .htaccess file.

main() {

  rm -rf /docroot/.htaccess
  mv .htaccess-deploy /docroot/.htaccess

}

main $@
