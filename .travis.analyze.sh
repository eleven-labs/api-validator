#!/bin/sh

set -e

version() { 
    printf "%03d%03d" $(echo "$1" | tr '.' ' '); 
}

phpstan_download() {
    if [ ! -f "phpstan.phar" ];then
        curl -sOL https://github.com/phpstan/phpstan/releases/download/0.10.5/phpstan.phar
    fi
}

phpstan_analyze() {
    php ./phpstan.phar analyze --ansi
}

main() {
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    if [ $(version $PHP_VERSION) -ge $(version "7.1") ]; then
        phpstan_download
        phpstan_analyze
    else
        echo "[warn] you need at least PHP 7.1 to use analyze the project with phpstan"
    fi
}

main
