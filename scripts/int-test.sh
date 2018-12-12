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

behat --strict --stop-on-failure --config tests/behat/behat.yml
