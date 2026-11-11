# Single source of truth for the PHP version used by all stages.
ARG PHP_VERSION=8.3

######################################################################################################
#############################                  STAGE 1: BUILD                   ######################
######################################################################################################

# Install composer dependencies in a dedicated stage so the runtime image stays
# free of composer and the dev toolchain.
FROM php:${PHP_VERSION}-fpm-alpine AS builder

# composer binary from the official composer image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /build

# Only the manifests first, to leverage layer caching for dependencies.
COPY composer.json composer.lock* ./

# Install runtime (non-dev) dependencies only.
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
    || echo "no composer dependencies to install"

######################################################################################################
#############################                  STAGE 2: RUNTIME                 ######################
######################################################################################################

FROM php:${PHP_VERSION}-fpm-alpine AS runtime

# Apply available OS security patches on top of the base image.
RUN apk upgrade --no-cache

# Create a non-root user to run php-fpm.
RUN addgroup -g 10001 app && adduser -u 10001 -G app -S -H app

ENV DATA_ROOT="/app"

WORKDIR /app

# Application code (public front controllers + src) and the served attachments.
COPY src ./src
COPY public ./public
COPY attachments ./attachments

# Vendor dir from the builder (present only if there were dependencies).
COPY --from=builder /build/vendor ./vendor

# Bake the challenge flag INTO the image at build time, at /app/flag.txt —
# OUTSIDE the served attachments/ directory on purpose, so path traversal has a
# target. The flag is intentionally NOT committed to the repo (it would spoil
# the lab). Override with --build-arg FLAG=...
ARG FLAG=FLAG{DESKMINT_ROOT_TICKET}
RUN printf '%s\n' "$FLAG" > /app/flag.txt \
    && chown -R app:app /app

# Make php-fpm listen on a TCP socket the nginx container can reach.
RUN { \
      echo '[www]'; \
      echo 'user = app'; \
      echo 'group = app'; \
      echo 'listen = 9000'; \
    } > /usr/local/etc/php-fpm.d/zz-deskmint.conf

USER app

EXPOSE 9000

CMD ["php-fpm"]
