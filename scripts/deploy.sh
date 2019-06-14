#!/usr/bin/env bash

# Exit immediately if there is an error
set -e

# Cause a pipeline (for example, curl -s http://sipb.mit.edu/ | grep foo) to produce a failure return code if any command errors not just the last command of the pipeline.
set -o pipefail

# Print shell input lines as they are read.
set -v

# Include build env vars
source "$(dirname "$0")/buildrc"

# login to cloud foundry if env vars are present
login() {

  if [[ -z "$CF_ORG" ]]; then
    echo "CF env vars not found, assuming you are already logged in to cf"
    return
  fi

  if [[ "${GIT_BRANCH}" = "master" ]]; then
    cf api $CF_API_PROD
    cf auth "$CF_USERNAME" "$CF_PASSWORD_PROD"
  else
    cf api $CF_API_STAGING
    cf auth "$CF_USERNAME" "$CF_PASSWORD_STAGING"
  fi

  cf target -o $CF_ORG
  cf target -s $CF_SPACE
}

# main script function
#
main() {

  login

  case "${GIT_BRANCH}" in
    master)
      cf zero-downtime-push dta-website -f manifest-prod.yml
      ;;
    develop)
      cf zero-downtime-push dta-website-staging -f manifest-staging.yml
      ;;
    *)
      cf zero-downtime-push ${GIT_REPO}-`basename "${GIT_BRANCH}"` -f manifest.yml
      ;;
  esac

}

main $@
