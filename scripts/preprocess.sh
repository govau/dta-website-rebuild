#!/usr/bin/env bash

# Exit immediately if there is an error
set -e

# Cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# Print shell input lines as they are read.
set -x

# We only want these scripts to happen here if we are running in the test environment.
# The other environments will get the same treatment via Circle.

if [[ -n ${ENVIRONMENT+x} ]]; then
  echo "Currently running in the ${ENVIRONMENT} environment."
  if [[ ${ENVIRONMENT} = "test" ]]; then

    # Add included mysql cli to PATH
    PATH="${HOME}/scripts/mysql:${PATH}"

    # Add vendored bin dir to PATH
    PATH="${HOME}/bin:${PATH}"

    cd docroot

    # Only execute on the first application instance
    if [[ "${CF_INSTANCE_INDEX}" = "0" ]]; then
      echo "I am the first instance"

      CACHE_FLAG="false"

      # If DRUPAL_UUID is defined, change our UUID to it if necessary
      if [[ -n ${DRUPAL_UUID+x} ]]; then
        CURRENT_UUID=$(drush cget "system.site" --format=json | jq -r .uuid )
        if [[ ${DRUPAL_UUID} != ${CURRENT_UUID} ]]; then
          drush config-set -y "system.site" uuid "${DRUPAL_UUID}"
        fi
      fi

      # Import the config from sync dir
      config_status_file=$(mktemp)

      CONFIG_STATUS=$(drush config-status &>$config_status_file)

      printf "%s\n" "$CONFIG_STATUS"

      haystack=$(< $config_status_file)

      case "${haystack}" in
        *"No differences"*)
          printf "%s\n" "$CONFIG_STATUS"
          exit
          ;;
        *)
          echo "Updates required."
          printf "%s\n" "$CONFIG_STATUS"
          drush config-import -y
          CACHE_FLAG="true"
          ;;
      esac

      # Run updatedb if necessary
      UPDATEDB_STATUS=$(drush updatedb-status 2>/dev/null)
      if [[ $UPDATEDB_STATUS != "" ]]; then
        printf "%s\n" "$UPDATEDB_STATUS"
        echo "Updates required"
        error_file=$(mktemp)
        UPDATEDB_OUTPUT+=$(drush updatedb -y --entity-updates --no-cache-clear 2>$error_file)
        printf "%s\n" "$UPDATEDB_OUTPUT"
        err=$(< $error_file)
        case "${err}" in
          *"Update failed"*)
            echo "An error occured!"
            printf "%s\n" "$UPDATEDB_OUTPUT"
            exit 1
            ;;
          *)
            echo "Updates performed without error. Please check output."
            printf "%s\n" "$UPDATEDB_OUTPUT"
            CACHE_FLAG="true"
            ;;
        esac
      else
        echo "No updates required."
      fi

      # Clear the caches if required.
      if [ "$CACHE_FLAG" = "true" ]; then
        drush cache-rebuild
      fi
    else
      echo 'I am not the first instance'
    fi
  fi
else
  echo "Currently running in a local environment (or an environment without the correct environment variables set!)"
fi

echo "preprocess.sh finished"
