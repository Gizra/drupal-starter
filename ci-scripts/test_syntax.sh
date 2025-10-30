#!/bin/bash


# ---------------------------------------------------------------------------- #
#
# Check the syntax of the most recent modified files. Ignore yaml files.
#
# ---------------------------------------------------------------------------- #

FILES=$(git diff-tree --no-commit-id --name-only -r HEAD | grep -v yml$)

for FILE in $FILES
do
  if [ -f "$FILE" ]; then
    # Only lint actual PHP files using the file command
    if file "$FILE" | grep -q "PHP script"; then
      php -l "$FILE"
    fi
  fi
done
