ARG BASE_IMAGE
FROM $BASE_IMAGE

RUN apt-get update
RUN DEBIAN_FRONTEND=noninteractive apt-get install -y -o Dpkg::Options::="--force-confold" --no-install-recommends --no-install-suggests optipng jpegoptim
