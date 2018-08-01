#!/usr/bin/env bash

# Exit immediately if there is an error
set -e

# Cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# Print shell input lines as they are read.
set -x

# Add included mysql cli to PATH
PATH="${HOME}/scripts/mysql:${PATH}"

# Add vendored bin dir to PATH
PATH="${HOME}/bin:${PATH}"

cd docroot

# Only execute on the first application instance
if [[ "${CF_INSTANCE_INDEX}" = "0" ]]; then
  echo "I am the first instance"

  drush status

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

  # Uninstall modules on Circle environments. This is a fallback, just in case.

  DEVEL_STATUS=$(drush pm-list --pipe --type=module --status=enabled --no-core --fields=name | grep "devel" || : 2>/dev/null)

  if [[ -n $DEVEL_STATUS ]]; then
    echo 'Devel installed, uninstalling.'
    drush pm-uninstall devel -y
    CACHE_FLAG="true"
  else
    echo 'Devel not installed.'
  fi

  KINT_STATUS=$(drush pm-list --pipe --type=module --status=enabled --no-core --fields=name | grep "kint" || : 2>/dev/null)

  if [[ -n $KINT_STATUS ]]; then
    echo 'Kint installed, uninstalling.'
    drush pm-uninstall kint -y
    CACHE_FLAG="true"
  else
    echo 'Kint not installed.'
  fi

  LINK_CSS_STATUS=$(drush pm-list --pipe --type=module --status=enabled --no-core --fields=name | grep "link_css" || : 2>/dev/null)

  if [[ -n $LINK_CSS_STATUS ]]; then
    echo 'link_css installed, uninstalling.'
    drush pm-uninstall link_css -y
    CACHE_FLAG="true"
  else
    echo 'link_css not installed.'
  fi

  # Clear the caches if required.
  if [ "$CACHE_FLAG" = "true" ]; then
    drush cache-rebuild
  fi
else
  echo 'I am not the first instance'
fi
