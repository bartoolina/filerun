#!/bin/bash
set -e

# Pobierz aktualny UID/GID dla www-data
CURRENT_UID=$(id -u www-data)
CURRENT_GID=$(id -g www-data)

# Sprawdź, czy PUID/PGID są ustawione i czy różnią się od obecnych
if [ -n "$PUID" ] && [ "$PUID" != "$CURRENT_UID" ]; then
    echo ">>>> Zmiana UID dla www-data na: $PUID"
    # Zmień GID grupy, jeśli ma ten sam numer co stary UID
    if [ "$CURRENT_UID" = "$CURRENT_GID" ]; then
        groupmod -o -g "$PUID" www-data
    fi
    usermod -o -u "$PUID" www-data
fi

if [ -n "$PGID" ] && [ "$PGID" != "$CURRENT_GID" ]; then
    echo ">>>> Zmiana GID dla www-data na: $PGID"
    groupmod -o -g "$PGID" www-data
fi

# Generuj certyfikat SSL, jeśli nie istnieje
if [ ! -f /config/keys/cert.key ] || [ ! -f /config/keys/cert.crt ]; then
    echo ">>>> Generating self-signed certificate"
    mkdir -p /config/keys
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout /config/keys/cert.key -out /config/keys/cert.crt \
        -subj "/C=US/ST=State/L=City/O=Organization/OU=Department/CN=example.com"
fi

# Zmień właściciela plików na użytkownika 'www-data' (z nowym UID/GID)
echo ">>>> Setting ownership to www-data..."
chown -R www-data:www-data /var/www/html /config /user-files

echo ">>>> Setting correct permissions..."
chmod -R g+w /var/www/html/system/data

echo ">>>> Starting services..."
# Uruchom oryginalne polecenie CMD
exec "$@"
