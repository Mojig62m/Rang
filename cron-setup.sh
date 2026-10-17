#!/bin/bash
# Bamero WordPress Cron Setup Script
# This script sets up a system cron job to replace WordPress's internal cron
# for production deployments where DISABLE_WP_CRON=true

# Configuration
CRON_USER="www-data"
WP_CLI_PATH="/usr/local/bin/wp"
SITE_PATH="/var/www/html"
CRON_COMMAND="$WP_CLI_PATH --path=$SITE_PATH cron event run --all"

# Check if running as root
if [ "$(id -u)" != "0" ]; then
    echo "Error: This script must be run as root"
    exit 1
fi

# Check if wp-cli is available
if [ ! -x "$WP_CLI_PATH" ]; then
    echo "Error: wp-cli not found at $WP_CLI_PATH"
    exit 1
fi

# Check if site path exists
if [ ! -d "$SITE_PATH" ]; then
    echo "Error: Site path $SITE_PATH does not exist"
    exit 1
fi

# Check if cron job already exists
if crontab -u "$CRON_USER" -l 2>/dev/null | grep -q "$CRON_COMMAND"; then
    echo "Cron job already exists for user $CRON_USER"
    echo "Current cron jobs:"
    crontab -u "$CRON_USER" -l
    exit 0
fi

# Add cron job to run every 5 minutes
(crontab -u "$CRON_USER" -l 2>/dev/null; echo "*/5 * * * * $CRON_COMMAND") | crontab -u "$CRON_USER" -

echo "Cron job added successfully for user $CRON_USER"
echo "Cron job: */5 * * * * $CRON_COMMAND"

# Verify the cron job was added
echo ""
echo "Verifying cron job..."
if crontab -u "$CRON_USER" -l 2>/dev/null | grep -q "$CRON_COMMAND"; then
    echo "✓ Cron job verified"
else
    echo "✗ Failed to add cron job"
    exit 1
fi

# Also add notification worker specific cron (every minute for SMS queue)
NOTIFICATION_CRON="$WP_CLI_PATH --path=$SITE_PATH cron event run bamero_notification_worker"

if ! crontab -u "$CRON_USER" -l 2>/dev/null | grep -q "$NOTIFICATION_CRON"; then
    (crontab -u "$CRON_USER" -l 2>/dev/null; echo "* * * * * $NOTIFICATION_CRON") | crontab -u "$CRON_USER" -
    echo "Notification worker cron added: * * * * * $NOTIFICATION_CRON"
fi

echo ""
echo "All cron jobs set up successfully!"
