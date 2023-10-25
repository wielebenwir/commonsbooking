FROM alpine:3.18 as builder
RUN apk add --no-cache \
	composer \
    php81-ctype \
    gettext \
    git \
	make \
	nodejs \
	npm \
	zip
COPY . /app/
WORKDIR /app
RUN make clean && make build COMPOSER_INSTALL_ARGS="--ignore-platform-req=php --no-dev --optimize-autoloader"

ARG uid=1000
ARG gid=1000
ARG dist_name=commonsbooking.zip
FROM scratch AS dist
COPY --from=builder --chown="$uid:$gid" /app/build/*.zip /$dist_name