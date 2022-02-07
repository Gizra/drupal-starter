#!/bin/bash

cd ./web/themes/custom/server_theme
npm install
npx prettier --check .
