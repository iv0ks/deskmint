ARG NGINX_VERSION=1.27

FROM nginx:${NGINX_VERSION}-alpine AS runtime

# Apply available OS security patches on top of the base image.
RUN apk upgrade --no-cache

# Site config: serve /app/public and forward PHP to the php-fpm container.
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Static assets are served by nginx directly, so it needs the public/ tree.
# (The php-fpm container has the same files for executing the scripts.)
COPY public /app/public

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget -qO- http://127.0.0.1:8080/healthz | grep -q ok || exit 1
