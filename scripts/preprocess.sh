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

  # Import the config from sync dir
  drush config-import -y

  # Run updatedb if necessary
  UPDATEDB_STATUS=$(drush updatedb-status 2>/dev/null)
  if [[ $UPDATEDB_STATUS != "" ]]; then
    echo "Updates required"
    echo "Test exit!"
    exit 1
    error_file=$(mktemp)
    UPDATEDB_OUTPUT+=$(drush updatedb -y --no-cache-clear 2>$error_file)
    err=$(< $error_file)
    case "${err}" in
      *"Update failed"*)
        echo "An error occured"
        echo $err
        exit 1
        ;;
      *)
        echo "Updates performed without error. Please check output"
        echo $err
        ;;
    esac
  else
    echo "No updates required."
    echo "Test exit!"
    exit 1
  fi

  # Uninstall modules on certain environments.
  if [[ -n ${ENVIRONMENT+x} ]]; then
    echo "Currently running in the ${ENVIRONMENT} environment."
    if [[ ${ENVIRONMENT} = "staging" ]] || [[ ${ENVIRONMENT} = "production" ]]; then
      DEVEL_STATUS=$(drush pm-list --pipe --type=module --status=enabled --no-core --fields=name | grep "devel")
      if [[ $DEVEL_STATUS != "" ]]; then
        drush pm-uninstall devel -y
      fi
      KINT_STATUS=$(drush pm-list --pipe --type=module --status=enabled --no-core --fields=name | grep "kint")
      if [[ $KINT_STATUS != "" ]]; then
        drush pm-uninstall kint -y
      fi
      LINK_CSS_STATUS=$(drush pm-list --pipe --type=module --status=enabled --no-core --fields=name | grep "link_css")
      if [[ $LINK_CSS_STATUS != "" ]]; then
        drush pm-uninstall link_css -y
      fi
    fi
  else
    echo "Currently running in a local environment (or an environment without the correct environment variables set!)"
  fi
else
  echo "I am not the first instance"
fi

drush cache-rebuild

echo "preprocess.sh finished"
