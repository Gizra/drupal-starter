#!/usr/bin/env bash
set -e

ddev phpunit --do-not-cache-result --exclude-group=Rollbar
