# STAGE 1: Builder
# Here we install developer tools and compile extensions
FROM gaibz/ubuntu20-php7.4-nginx:latest AS builder

LABEL maintainer="mrizkihidayat66"

# Install build-time packages
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        curl \
        build-essential \
        libmagickwand-dev \
        php-dev \
    && rm -rf /var/lib/apt/lists/*

# Install and compile Imagick
RUN cd /tmp && \
    curl -o imagick.tgz -L http://pecl.php.net/get/imagick-3.4.4.tgz && \
    tar xvzf imagick.tgz && \
    cd imagick-3.4.4 && \
    phpize && ./configure && make && make install

# Download IonCube
RUN cd /tmp && \
    curl -o ioncube.tar.gz -L http://downloads3.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz && \
    tar xvfz ioncube.tar.gz

# ---
# STAGE 2: Final (Production) Image
# Start again from a clean image. Install only runtime dependencies.
FROM gaibz/ubuntu20-php7.4-nginx:latest

# PUID and PGID variables from docker-compose.yaml
ARG PUID=1000
ARG PGID=1000
ENV PUID=${PUID}
ENV PGID=${PGID}

# Install runtime packages (no dev)
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        ffmpeg \
        mariadb-client \
        libmagickwand-6.q16-6 && \
    rm -rf /var/lib/apt/lists/*

# Copy compiled extensions from the "builder" stage
COPY --from=builder /usr/lib/php/20190902/imagick.so /usr/lib/php/20190902/imagick.so
COPY --from=builder /tmp/ioncube/ioncube_loader_lin_7.4.so /usr/lib/php/20190902/ioncube_loader_lin_7.4.so

# Enable PHP extensions
RUN echo "extension=imagick.so" > /etc/php/7.4/fpm/conf.d/20-imagick.ini && \
    echo "zend_extension=ioncube_loader_lin_7.4.so" > /etc/php/7.4/fpm/conf.d/00-ioncube.ini

# Copy application code from the local src folder
COPY src/ /var/www/html/

# Copy configuration files
COPY default /etc/nginx/sites-available/
COPY filerun-optimization.ini /etc/php/7.4/fpm/conf.d/
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Prepare volumes and symlinks
RUN mkdir -p /config /user-files && \
    rm -f /etc/nginx/sites-enabled/default.conf && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default.conf && \
    ln -sf /config/config.php /var/www/html/system/data/autoconfig.php

# Copy and set permissions for the startup script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

VOLUME ["/config", "/user-files"]
EXPOSE 80

# Run the startup script, which will then run supervisord
ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]