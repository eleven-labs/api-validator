#!/bin/sh

set -e

main() {
    if [ "$TRAVIS_PHP_VERSION" = '7.2' ]; then
        wget -q https://scrutinizer-ci.com/ocular.phar
        php ocular.phar code-coverage:upload --format=php-clover ./build/phpunit.coverage.xml
    fi
}

main
