FROM ghcr.io/unb-libraries/nginx-php:3.24.x

COPY ./build /build
# mbstring: without it symfony/console's polyfill calls iconv //IGNORE, which musl rejects.
RUN apk --no-cache add "php${PHP_VERSION}-mbstring" && \
  mv /build/php/app-php.ini "$PHP_CONFD_DIR/zz_app.ini" && \
  $RSYNC_MOVE /build/ /app/ && \
  composer install

ENTRYPOINT ["./bin/pubcrawler", "pubcrawler:scrape"]

# Container metadata.
ARG BUILD_DATE
ARG VCS_REF
ARG VERSION
LABEL ca.unb.lib.generator="PHP" \
  org.opencontainers.image.title="crawler.pubcrawler.lib.unb.ca" \
  org.opencontainers.image.description="crawler.pubcrawler.lib.unb.ca is the publication metadata scraper driving pubcrawler.lib.unb.ca." \
  org.opencontainers.image.vendor="University of New Brunswick Libraries" \
  org.opencontainers.image.authors="UNB Libraries <libsupport@unb.ca>" \
  org.opencontainers.image.url="https://crawler.pubcrawler.lib.unb.ca" \
  org.opencontainers.image.source="https://github.com/unb-libraries/crawler.pubcrawler.lib.unb.ca" \
  org.opencontainers.image.version="$VERSION" \
  org.opencontainers.image.revision="$VCS_REF" \
  org.opencontainers.image.created="$BUILD_DATE"
