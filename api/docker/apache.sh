#!/bin/sh
set -xe

# Update parameters based on environment variables
composer run-script update-parameters

# Configure Tideways' API
if [ -n "$TIDEWAYS_API_KEY" ]; then
    echo "tideways.api_key = $TIDEWAYS_API_KEY" >> /etc/php/7.0/apache2/conf.d/40-tideways.ini
    echo "tideways.connection = tcp://tideways:9135" >> /etc/php/7.0/apache2/conf.d/40-tideways.ini

    cp /etc/php/7.0/apache2/conf.d/40-tideways.ini /etc/php/7.0/cli/conf.d/40-tideways.ini
fi

# Start Apache with the right permissions
/app/docker/start_safe_perms -DFOREGROUND
