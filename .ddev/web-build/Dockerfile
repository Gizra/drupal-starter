ARG BASE_IMAGE
FROM $BASE_IMAGE

# For being able to optimize images as part of theme compilation, we install
# extra tools here - in a non-interactive way, also trying to avoid extra
# packages that would increase the image size.
RUN DEBIAN_FRONTEND=noninteractive apt-get --fix-missing update || true
RUN DEBIAN_FRONTEND=noninteractive apt-get install -y -o Dpkg::Options::="--force-confold" --no-install-recommends --no-install-suggests optipng jpegoptim
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && npm cache clean --force
