#!/bin/bash

# Gantry5 PHP 8.3 Compatibility Test Runner

# Check if PHP 8.3 is available
if ! command -v php &> /dev/null; then
    echo "PHP not found. Please make sure PHP is installed and available in PATH."
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")

echo "----------------------------------------"
echo "Gantry5 PHP 8.3 Compatibility Test Suite"
echo "----------------------------------------"
echo "Using PHP version: $PHP_VERSION"

# Change to Gantry5 root directory
cd "$(dirname "$0")/../../../"
ROOT_DIR=$(pwd)

echo "Root directory: $ROOT_DIR"
echo

# Install PHPUnit locally in the tests directory if not available
if [ ! -f "$ROOT_DIR/tests/php83/phpunit.phar" ]; then
    echo "Downloading PHPUnit..."
    cd "$ROOT_DIR/tests/php83"
    curl -LO https://phar.phpunit.de/phpunit-9.phar
    mv phpunit-9.phar phpunit.phar
    chmod +x phpunit.phar
fi

# Run PHPUnit tests using the downloaded PHAR
echo "Running PHP 8.3 compatibility tests..."
php "$ROOT_DIR/tests/php83/phpunit.phar" -c "$ROOT_DIR/phpunit.xml.dist" --testdox

# Exit with the status code from PHPUnit
exit $?