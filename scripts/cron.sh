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

  TASK_NAME="circleci-cron-$(openssl rand -hex 8)"

  case "${GIT_BRANCH}" in
    master)
      CF_APP=dta-website
      ;;
    develop)
      CF_APP=dta-website-staging
      ;;
    *)
      CF_APP=${GIT_REPO}-`basename "${GIT_BRANCH}"`
      ;;
  esac
  
  # Running a task requires the app to be started i.e. not in the middle of a 
  # deploy.The best way to do this seems to wait for at least one instance to 
  # start, so we'll just wait on the first one.
  # Wait up to 10 minutes
  end=$((SECONDS+600)) 
  while [ $SECONDS -lt $end ]; do
    INSTANCE_STATE="$(cf curl /v2/apps/$(cf app ${CF_APP} --guid)/instances | jq -r '."0".state')"
    if [[ ${INSTANCE_STATE} == "RUNNING" ]]; then
      echo "App is started: ${CF_APP}"
      break
    else
      echo "Waiting for app to start: ${CF_APP}"
      sleep 5
    fi
  done

  cf run-task ${CF_APP} --name "${TASK_NAME}" "source scripts/buildrc && drush cron"

  # Wait up to 5 minutes for task to finish
  end=$((SECONDS+300))
  while [ $SECONDS -lt $end ]; do
    if [[ $(cf tasks "${CF_APP}" | grep "${TASK_NAME}") =~ "RUNNING" ]]; then
      echo "Waiting for task to finish"
      sleep 5
    else
      break
    fi
  done

  CF_TASK_OUTPUT="$(cf tasks "${CF_APP}" | grep "${TASK_NAME}")"
  if [[ ! $CF_TASK_OUTPUT =~ "SUCCEEDED" ]]; then
    echo "Cron task failed or still running"
    echo $CF_TASK_OUTPUT
    exit 1
  else
    echo "Cron task succeeded"
  fi
}

main $@
