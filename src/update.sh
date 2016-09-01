#!/bin/bash

METHOD=--quiet
PLATFORMS=("joomla" "wordpress" "grav")

if [[ "$1" == '--loud' || "$1" == '-l' ]]; then
    METHOD=''
fi

for platform in "${PLATFORMS[@]}"
do
    echo "# Updating composer for $platform"
    cd platforms/$platform && composer install --no-dev ${METHOD} && cd ../..
done
 
