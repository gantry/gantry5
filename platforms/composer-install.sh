#!/bin/bash

OUTPUT=--quiet
if [[ "$1" == '--loud' || "$1" == '-l' ]]; then
    OUTPUT=''
fi

echo "# Updating composer for Grav"
cd grav/gantry5 && composer install --no-dev ${OUTPUT} && cd ../..

