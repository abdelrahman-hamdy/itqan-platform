#!/bin/bash

# Laravel Scheduler Worker Script for Local Development
# This script runs the Laravel scheduler every minute
# Usage: ./scheduler-worker.sh

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Log file
LOG_FILE="$SCRIPT_DIR/storage/logs/scheduler-worker.log"

# Ensure log directory exists
mkdir -p "$SCRIPT_DIR/storage/logs"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log_message "=========================================="
log_message "Laravel Scheduler Worker Started"
log_message "Project: itqan-platform"
log_message "Directory: $SCRIPT_DIR"
log_message "=========================================="

# Run the scheduler continuously
while true; do
    log_message "Running scheduler..."

    # Run Laravel scheduler
    php artisan schedule:run >> "$LOG_FILE" 2>&1

    # Wait 60 seconds before next run
    sleep 60
done
