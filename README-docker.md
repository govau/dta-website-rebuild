## Docker cheatsheet

Build our Dockerfile: `docker build --tag dta-website-rebuild .`

Start drupal: `docker-compose up`. Drupal is now accessible at http://localhost:8080

Stop drupal: `docker-compose down`

Delete all exited containers:`docker rm $(docker ps -a -q -f status=exited)`
