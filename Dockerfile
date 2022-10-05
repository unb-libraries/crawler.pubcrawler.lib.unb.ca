FROM ghcr.io/unb-libraries/php-cli:8.x

COPY ./build /build
RUN mv /build/php/app-php.ini "$PHP_CONFD_DIR/zz_app.ini" && \
  $RSYNC_MOVE /build/ /app/ && \
  composer install

ENTRYPOINT ["./bin/pubcrawler", "pubcrawler:scrape"]

# Container metadata.
LABEL ca.unb.lib.generator="PHP" \
  com.microscaling.docker.dockerfile="/Dockerfile" \
  com.microscaling.license="MIT" \
  org.label-schema.build-date=$BUILD_DATE \
  org.label-schema.description="crawler.pubcrawler.lib.unb.ca is the publication metadata scraper driving pubcrawler.lib.unb.ca." \
  org.label-schema.name="crawler.pubcrawler.lib.unb.ca" \
  org.label-schema.schema-version="1.0" \
  org.label-schema.url="https://crawler.pubcrawler.lib.unb.ca" \
  org.label-schema.vcs-ref=$VCS_REF \
  org.label-schema.vcs-url="https://github.com/unb-libraries/crawler.pubcrawler.lib.unb.ca" \
  org.label-schema.vendor="University of New Brunswick Libraries" \
  org.label-schema.version=$VERSION \
  org.opencontainers.image.source="https://github.com/unb-libraries/crawler.pubcrawler.lib.unb.ca"
