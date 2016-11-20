#!/usr/bin/env bash

vagrant ssh -- -t 'cd /vagrant; /usr/bin/php vendor/bin/doctrine orm:schema-tool:drop --force;/usr/bin/php vendor/bin/doctrine orm:schema-tool:create;'
