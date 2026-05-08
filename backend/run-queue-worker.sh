#!/usr/bin/env bash
#
# Database queue worker for production (hosting panels / Supervisor that need a file path).
# After deploy, point the process manager to the full path of this file, e.g.:
#   /home/USER/domain/www/backend/run-queue-worker.sh
# Optional: enable “run as-is / do not wrap with handler” in the panel if jobs fail to start.
#
set -euo pipefail
cd "$(dirname "$0")"
exec php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
