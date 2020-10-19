#!/bin/bash
cd ~/Projects/chippyash/source/simple-accounts-3/
vendor/phpunit/phpunit/phpunit -c test/php/local-phpunit.xml --testdox-html contract.html test/php
tdconv -t "Simple Accounts V3" contract.html docs/Test-Contract.md
rm contract.html

