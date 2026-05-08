#!/bin/bash
set -e

ENV="${1:-TEST}"

VERSION="${GITHUB_REF_NAME%_live}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/../manual_steps/${VERSION}.sh"

if [ -f "$SCRIPT_DIR" ]; then
  echo "Running manual steps for $VERSION on $ENV..."
  bash "$SCRIPT_DIR" "$ENV"
else
  echo "No manual steps found for $VERSION, skipping..."
fi
