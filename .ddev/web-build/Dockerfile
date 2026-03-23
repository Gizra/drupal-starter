ARG BASE_IMAGE
FROM $BASE_IMAGE

# For being able to optimize images as part of theme compilation, we install
# extra tools here - in a non-interactive way, also trying to avoid extra
# packages that would increase the image size.
RUN set -eux; \
    find /etc/apt/sources.list.d -type f -exec sh -c 'grep -q "packages.sury.org/php" "$1" && rm -f "$1" || true' _ {} \; || true; \
    sed -i '/packages\.sury\.org\/php/d' /etc/apt/sources.list || true; \
    apt-get update; \
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
      -o Dpkg::Options::="--force-confold" \
      --no-install-recommends \
      --no-install-suggests \
      optipng \
      parallel \
      jpegoptim; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*
