# ETAP 1: Budowniczy (Builder)
# Tutaj instalujemy narzędzia deweloperskie i kompilujemy rozszerzenia
FROM gaibz/ubuntu20-php7.4-nginx:latest AS builder

LABEL maintainer="mrizkihidayat66"

# Zainstaluj pakiety potrzebne do budowy
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        curl \
        build-essential \
        libmagickwand-dev \
        php-dev \
    && rm -rf /var/lib/apt/lists/*

# Zainstaluj i skompiluj Imagick
RUN cd /tmp && \
    curl -o imagick.tgz -L http://pecl.php.net/get/imagick-3.4.4.tgz && \
    tar xvzf imagick.tgz && \
    cd imagick-3.4.4 && \
    phpize && ./configure && make && make install

# Pobierz IonCube
RUN cd /tmp && \
    curl -o ioncube.tar.gz -L http://downloads3.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz && \
    tar xvfz ioncube.tar.gz

# ---
# ETAP 2: Obraz finalny (Produkcyjny)
# Zaczynamy od nowa, od czystego obrazu. Instalujemy tylko potrzebne zależności.
FROM gaibz/ubuntu20-php7.4-nginx:latest

# Zmienne PUID i PGID z docker-compose.yaml
ARG PUID=1000
ARG PGID=1000
ENV PUID=${PUID}
ENV PGID=${PGID}

# Zainstaluj pakiety potrzebne do działania aplikacji (bez dev)
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        ffmpeg \
        mariadb-client \
        libmagickwand-6.q16-6 && \
    rm -rf /var/lib/apt/lists/*

# Kopiuj skompilowane rozszerzenia z etapu "builder"
COPY --from=builder /usr/lib/php/20190902/imagick.so /usr/lib/php/20190902/imagick.so
COPY --from=builder /tmp/ioncube/ioncube_loader_lin_7.4.so /usr/lib/php/20190902/ioncube_loader_lin_7.4.so

# Włącz rozszerzenia PHP
RUN echo "extension=imagick.so" > /etc/php/7.4/fpm/conf.d/20-imagick.ini && \
    echo "zend_extension=ioncube_loader_lin_7.4.so" > /etc/php/7.4/fpm/conf.d/00-ioncube.ini

# Kopiuj kod aplikacji z lokalnego folderu src
COPY src/ /var/www/html/

# Kopiuj pliki konfiguracyjne
COPY default /etc/nginx/sites-available/
COPY filerun-optimization.ini /etc/php/7.4/fpm/conf.d/
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Przygotuj woluminy i dowiązania
RUN mkdir -p /config /user-files && \
    rm -f /etc/nginx/sites-enabled/default.conf && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default.conf && \
    ln -sf /config/config.php /var/www/html/system/data/autoconfig.php

# Kopiuj i ustaw uprawnienia dla skryptu startowego
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

VOLUME ["/config", "/user-files"]
EXPOSE 80

# Uruchom skrypt startowy, który następnie uruchomi supervisord
ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]