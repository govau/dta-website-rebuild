## Docker

This site can be run on Docker with the provided [Dockerfile](./Dockerfile).
This image is used as part of the [CI pipeline](./circleci/config.yml) to test
changes. The image is automatically built and published on docker hub at [govau/dta-website-rebuild](https://hub.docker.com/r/govau/dta-website-rebuild/).

You can use docker to run the site locally during development using your local
copy of the repository. The site requires a database to run,
so the easiest way is to use the provided [docker-compose.yml](./docker-compose.yml) to create a mysql container and a container to run the site, and set database credentials.
The current directory will be mounted at `/app`.

```
# Get the repository if you need
git clone https://github.com/govau/dta-website-rebuild.git

cd dta-website-rebuild

# Start the database and site containers in the background.
docker-compose up -d

# Get the site container name
docker ps

# We assume your container name is 'dtawebsiterebuild_drupal_1'

# Start a shell in the application container
docker exec -it dtawebsiterebuild_drupal_1 bash

# Install dependencies using composer
composer install
```

The site is now accessible at [http://localhost:8080](http://localhost:8080).

## Updating the Dockerfile

Whenever the Dockerfile changes on the `develop` branch, Docker hub will
automatically re-build the image and publish it as [govau/dta-website-rebuild:latest](https://hub.docker.com/r/govau/dta-website-rebuild/).

TODO - add instructions to test your Dockerfile change locally before
merging to `develop`.

## Cheatsheet

Start docker containers: `docker-compose up`

Stop docker containers: `docker-compose down`

Delete all exited containers:`docker rm $(docker ps -a -q -f status=exited)`
