FROM ghcr.io/unb-libraries/php-cli:8.x

COPY ./build /build
RUN mv /build/php/app-php.ini "$PHP_CONFD_DIR/zz_app.ini" && \
  $RSYNC_MOVE /build/ /app/ && \
  composer install

ENTRYPOINT ["./bin/pubcrawler", "pubcrawler:scrape"]

# Container metadata.
LABEL ca.unb.lib.generator="PHP" \
  org.opencontainers.image.title="crawler.pubcrawler.lib.unb.ca" \
  org.opencontainers.image.description="crawler.pubcrawler.lib.unb.ca is the publication metadata scraper driving pubcrawler.lib.unb.ca." \
  org.opencontainers.image.vendor="University of New Brunswick Libraries" \
  org.opencontainers.image.url="https://crawler.pubcrawler.lib.unb.ca" \
  org.opencontainers.image.source="https://github.com/unb-libraries/crawler.pubcrawler.lib.unb.ca" \
  org.opencontainers.image.version="$VERSION" \
  org.opencontainers.image.revision="$VCS_REF" \
  org.opencontainers.image.created="$BUILD_DATE"
