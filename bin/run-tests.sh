#!/usr/bin/env bash

vagrant ssh -- -t 'cd /vagrant/; /usr/bin/php vendor/bin/phpunit'
