
FROM webdevops/php-nginx:8.1-alpine
ENV WEB_DOCUMENT_ROOT=/app/web
ENV PHP_DISMOD=bz2,calendar,exiif,ffi,gettext,ldap,imap,pdo_pgsql,pgsql,soap,sockets,sysvmsg,sysvsm,sysvshm,shmop,xsl,apcu,vips,yaml,imagick,mongodb,amqp
RUN echo variables_order = "EGPCS" >> /opt/docker/etc/php/php.ini

# See all required environment variables defined in docker-compose.yml

# Cleanup not needed packages from base image:
# https://github.com/webdevops/Dockerfile/blob/master/docker/php-official/8.1-alpine/Dockerfile
RUN apk --purge del apk-tools

COPY .docker_build /app

RUN mkdir -p /app/web/sites/default/files /app/private

WORKDIR /app/private
RUN chown -R application:application .

WORKDIR /app/web
RUN chown -R application:application .

