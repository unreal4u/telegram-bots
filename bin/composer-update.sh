#!/usr/bin/env bash

vagrant ssh -- -t 'cd /vagrant/; /usr/bin/composer.phar update -o --no-dev --prefer-dist'
