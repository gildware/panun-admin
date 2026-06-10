#!/bin/sh
# Hostinger Laravel scheduler — edit the two paths below, then chmod 755 this file.
#
# PHP binary (check hPanel → Advanced → PHP Configuration, or run: which php via SSH)
PHP_BIN="/opt/alt/php83/usr/bin/php"
#
# Full path to artisan — on your server you are in the "dev" folder (run: pwd)
ARTISAN="/home/u397782854/domains/panunkaergar.com/dev/artisan"

LOG_DIR="$(dirname "$ARTISAN")/storage/logs"
mkdir -p "$LOG_DIR"

cd "$(dirname "$ARTISAN")"
$PHP_BIN "$ARTISAN" schedule:run >> "$LOG_DIR/cron-schedule.log" 2>&1
