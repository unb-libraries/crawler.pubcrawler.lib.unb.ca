FROM ghcr.io/unb-libraries/php-cli:8.x

COPY ./build /build
RUN mv /build/php/app-php.ini "$PHP_CONFD_DIR/zz_app.ini" && \
  $RSYNC_MOVE /build/ /app/ && \
  composer install

ENTRYPOINT ["./bin/pubcrawler", "pubcrawler:scrape"]
