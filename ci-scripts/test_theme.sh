#!/bin/bash

cd ./web/themes/custom/server_theme
node install
npx prettier --check .
