#!/bin/bash
echo "Email Testing Suite"
echo "================="
echo

if [ -z "$1" ]; then
    echo "Usage: ./run_tests.sh your@email.com [provider]"
    echo "  provider: optional, defaults to value in .env"
    exit 1
fi

EMAIL=$1
PROVIDER=$2

if [ -z "$PROVIDER" ]; then
    echo "Testing with default provider from .env"
else
    echo "Testing with provider: $PROVIDER"
fi

echo
echo "1. Testing Direct API Call"
echo "--------------------------"
php email_direct_test.php $EMAIL $PROVIDER

echo
echo "2. Testing Email Service"
echo "-----------------------"
php email_service_test.php $EMAIL $PROVIDER

echo
echo "3. Testing Template Email"
echo "-----------------------"
php template_email_test.php $EMAIL $PROVIDER

echo
echo "All tests completed!" 