#!/usr/bin/env bash

# Just a script to make life easier. Make sure you're logged in otherwise it won't work!

echo "Copying deploy .htaccess file..."

cp .htaccess-deploy docroot/.htaccess

cf push

echo "Putting local .htaccess back..."

cp .htaccess-local docroot/.htaccess
