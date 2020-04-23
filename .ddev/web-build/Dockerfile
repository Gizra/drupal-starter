ARG BASE_IMAGE
FROM $BASE_IMAGE

ENV COMPOSER_HOME=/usr/local/composer

# We try to avoid when possible relying on composer to download global, so in PHPCS case we can use the phar.
RUN curl -L https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar -o /usr/local/bin/phpcs && chmod +x /usr/local/bin/phpcs
RUN curl -L https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar -o /usr/local/bin/phpcbf && chmod +x /usr/local/bin/phpcbf

# If however we need to download a package, we use `cgr` for that.
RUN composer global require consolidation/cgr
RUN $COMPOSER_HOME/vendor/bin/cgr drupal/coder:^8.3.1
RUN $COMPOSER_HOME/vendor/bin/cgr dealerdirect/phpcodesniffer-composer-installer

# Register Drupal's code sniffer rules.
RUN phpcs --config-set installed_paths $COMPOSER_HOME/global/drupal/coder/vendor/drupal/coder/coder_sniffer --verbose
# Make Codesniffer config file writable for ordinary users in container.
RUN chmod 666 /usr/local/bin/CodeSniffer.conf
# Make COMPOSER_HOME writable if regular users need to use it.
RUN chmod -R ugo+rw $COMPOSER_HOME
# Now turn it off, because ordinary users will want to be using the default
ENV COMPOSER_HOME=""