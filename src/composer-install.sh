#!/bin/bash

OUTPUT=--quiet
PLATFORMS=("joomla" "wordpress" "grav" "prime")

if [[ "$1" == '--loud' || "$1" == '-l' ]]; then
    OUTPUT=''
fi

for platform in "${PLATFORMS[@]}"
do
    echo "# Updating composer for $platform"
    cd platforms/$platform && composer install --no-dev ${OUTPUT} && cd ../..
done
 
