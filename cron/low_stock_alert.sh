#!/bin/bash
# Low Stock Alert Cron Job
# Run daily at 08:00
# Add to crontab: 0 8 * * * /opt/data/projects/maura-warehouse/cron/low_stock_alert.sh

cd "$(dirname "$0")/.."
/opt/data/bin/php -c /opt/data/cache/scratch/mariadb/php-ext.ini includes/low_stock_alerts.php admin@maurawarehouse.com >> logs/low_stock_alerts.log 2>&1
