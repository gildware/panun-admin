#!/bin/sh
# Hostinger Laravel scheduler — edit the two paths below, then chmod 755 this file.
#
# PHP binary (check hPanel → Advanced → PHP Configuration, or run: which php via SSH)
PHP_BIN="/usr/bin/php"
#
# Full path to artisan (folder that contains artisan, composer.json, bootstrap/)
ARTISAN="/home/USERNAME/domains/YOURDOMAIN.com/public_html/artisan"

cd "$(dirname "$ARTISAN")"
$PHP_BIN "$ARTISAN" schedule:run >> /dev/null 2>&1
