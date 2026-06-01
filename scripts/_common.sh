#!/bin/bash

#=================================================
# COMMON VARIABLES AND HELPERS
#=================================================

# Per-route log directory served by the echo page.
log_dir="/var/log/$app"

# (Re)create the log directory and files with ownership the app user can read
# and nginx (root) can append to.
setup_log_dir() {
    mkdir -p "$log_dir"
    touch "$log_dir/access.log" "$log_dir/error.log"
    chown -R "$app:$app" "$log_dir"
    chmod 750 "$log_dir"
    chmod 640 "$log_dir/access.log" "$log_dir/error.log"
}
