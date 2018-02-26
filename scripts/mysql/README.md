## What are these files?

Drush needs the mysql cli to run certain commands, and since it is not present in the php buildpack we have to provide our own.

These files were extracted from a docker instance of mysql like follows:

```

# Start a docker container with the desired mysql
docker run  -it --rm -v $PWD/scripts/mysql:/mysql \
  --workdir=/mysql mysql:8.0.4 bash

# Copy mysql and required libs to /mysql
ldd --verbose -- "$(which mysql)" | grep '^[[:space:]]*/[^:]*:$' | sed 's/[[:space:]]*//g' | sed 's/:$//g' | xargs -I {} cp {} .

```
