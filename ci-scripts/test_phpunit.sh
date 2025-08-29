#!/usr/bin/env bash
set -e

ddev phpunit --exclude-group=Rollbar
