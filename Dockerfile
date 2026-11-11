# Single source of truth for the PHP version.
ARG PHP_VERSION=8.3

######################################################################################################
#############################                  STAGE 1: BUILD                   ######################
######################################################################################################

# Install composer dependencies in a dedicated stage so the runtime image stays
# free of composer and the dev toolchain. (There are no runtime deps today, but
# this keeps the pattern ready and the layer cached.)
FROM php:${PHP_VERSION}-apache AS builder

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /build
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
    || echo "no composer dependencies to install"

######################################################################################################
#############################                  STAGE 2: RUNTIME                 ######################
######################################################################################################

FROM php:${PHP_VERSION}-apache AS runtime

# Apply available OS security patches on top of the base image.
RUN apt-get update && apt-get upgrade -y && rm -rf /var/lib/apt/lists/*

# Serve the app from public/ instead of the default /var/www/html, and make the
# DATA_ROOT env var visible to PHP (Apache strips the environment otherwise).
ENV APACHE_DOCUMENT_ROOT=/app/public \
    DATA_ROOT=/app
RUN sed -ri 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!<Directory /var/www/>!<Directory /app/public/>!' /etc/apache2/apache2.conf \
    && printf 'PassEnv DATA_ROOT\n' > /etc/apache2/conf-available/deskmint.conf \
    && a2enconf deskmint

WORKDIR /app

# Application code (public front controllers + src) and the served attachments.
COPY src ./src
COPY public ./public
COPY attachments ./attachments
COPY --from=builder /build/vendor ./vendor

# Bake the challenge flag INTO the image at build time, at /app/flag.txt —
# OUTSIDE the served attachments/ directory on purpose, so path traversal has a
# target. The flag is intentionally NOT committed to the repo (it would spoil
# the lab). Override with --build-arg FLAG=...
ARG FLAG=FLAG{DESKMINT_ROOT_TICKET}
RUN printf '%s\n' "$FLAG" > /app/flag.txt

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php -r '$c=@file_get_contents("http://127.0.0.1:80/index.php");exit($c!==false?0:1);'
