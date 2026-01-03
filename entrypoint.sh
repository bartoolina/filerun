#!/bin/bash
set -e

# Get the current UID/GID for www-data
CURRENT_UID=$(id -u www-data)
CURRENT_GID=$(id -g www-data)

# Check if PUID/PGID are set and if they differ from the current ones
if [ -n "$PUID" ] && [ "$PUID" != "$CURRENT_UID" ]; then
    echo ">>>> Changing UID for www-data to: $PUID"
    # Change the group GID if it has the same number as the old UID
    if [ "$CURRENT_UID" = "$CURRENT_GID" ]; then
        groupmod -o -g "$PUID" www-data
    fi
    usermod -o -u "$PUID" www-data
fi

if [ -n "$PGID" ] && [ "$PGID" != "$CURRENT_GID" ]; then
    echo ">>>> Changing GID for www-data to: $PGID"
    groupmod -o -g "$PGID" www-data
fi

# Change file owner to 'www-data' user (with the new UID/GID)
echo ">>>> Setting ownership to www-data..."
chown -R www-data:www-data /var/www/html /config /user-files

echo ">>>> Setting correct permissions..."
chmod -R g+w /var/www/html/system/data

echo ">>>> Starting services..."
# Execute the original CMD
exec "$@"
