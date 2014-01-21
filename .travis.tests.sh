#! /usr/bin/env bash

if [ -z $SCRIPTS_DIR ]; then
  export SCRIPTS_DIR=`pwd`/classes
fi

echo "Script Directory: $SCRIPTS_DIR"

cd tests/classes && phpunit --configuration phpunit.xml --coverage-text
