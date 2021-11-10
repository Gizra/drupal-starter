#!/usr/bin/env bash

# Set up ddev for use on gitpod

set -eu -o pipefail

MYDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# Generate a config.gitpod.yaml that adds the gitpod
# proxied ports so they're known to ddev.
shortgpurl="${GITPOD_WORKSPACE_URL#'https://'}"

cat <<CONFIGEND > ${MYDIR}/config.gitpod.yaml
#ddev-gitpod-generated
use_dns_when_possible: false

# Throwaway ports, otherwise Gitpod throw an error 'port needs to be > 1024'
router_http_port: "8888"
router_https_port: "8889"

additional_fqdns:
- 8888-${shortgpurl}
- 8025-${shortgpurl}
- 8036-${shortgpurl}
CONFIGEND

# Forces proper external base URL.
DRUPAL_BASE=$(gp url 8888)
mkdir -p web/sites/all/drush
cat <<DRUSH_CFG > web/sites/all/drush/drush.yml
options:
  uri: "$DRUPAL_BASE"
DRUSH_CFG

# We need host.docker.internal inside the container,
# So add it via docker-compose.host-docker-internal.yaml
hostip=$(awk "\$2 == \"$HOSTNAME\" { print \$1; }" /etc/hosts)

# Misc housekeeping before start
ddev config global --router-bind-all-interfaces

yes | ddev start
