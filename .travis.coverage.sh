#!/bin/sh

set -e

main() {
    wget -q https://scrutinizer-ci.com/ocular.phar
    php ocular.phar code-coverage:upload --format=php-clover ./build/phpunit.coverage.xml
}

main
