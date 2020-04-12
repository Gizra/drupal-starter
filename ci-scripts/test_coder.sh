#!/bin/bash

# ---------------------------------------------------------------------------- #
#
# Run the coder review.
#
# ---------------------------------------------------------------------------- #

HAS_ERRORS=0
LOCAL_MODE=0
FIX_MODE=0
STD="$1"
while getopts "f" opt
do
    case $opt in
    (f) FIX_MODE=1; STD="$2";;
    (*) ;;
    esac
done

if [[ -z "${TRAVIS_BUILD_DIR}" ]]; then
  set -e
  if [[ -z "$STD" ]]; then
    SELF=$(realpath "$0")
    if [[ "${FIX_MODE}" -eq 1 ]]; then
       if ! bash "$SELF" "-f" Drupal; then
          exit 1
       fi
       if ! bash "$SELF" "-f" DrupalPractice; then
         exit 1
       fi
    else
      if ! bash "$SELF" Drupal; then
          exit 1
       fi
       if ! bash "$SELF" DrupalPractice; then
         exit 1
       fi
    fi

    exit 0
  fi
  SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
  TRAVIS_BUILD_DIR=$(realpath "$SCRIPT_DIR/..")
  LOCAL_MODE=1
  REVIEW_STANDARD="$STD"
fi

##
# Function to run the actual code review
#
# This function takes 2 params:
# @param string $1
#   The file path to the directory or file to check.
# @param string $2
#   The ignore pattern(s).
##
code_review () {
  CMD="phpcs"
  if [[ "${FIX_MODE}" -eq 1 ]]; then
    CMD="phpcbf"
  fi

  echo "${LWHITE}$1${RESTORE}"

  if [[ "${LOCAL_MODE}" -eq 0 ]]; then
    if ! "$CMD" --standard="$REVIEW_STANDARD" -p --colors --extensions=php,module,inc,install,test,profile,theme,css "$1"; then
      HAS_ERRORS=1
    fi
  else
    if ! ddev exec "$CMD" --standard="$REVIEW_STANDARD" -p --colors --ignore=profiles/contrib/*  --extensions=php,module,inc,install,test,profile,theme,css "${1#web/}"; then
      HAS_ERRORS=1
    fi
  fi
}

cd "$TRAVIS_BUILD_DIR" || exit 1
if [[ -d "web/modules/custom" ]];
then
  echo
  if [[ "${FIX_MODE}" -eq 1 ]]; then
    echo "${LBLUE}> Correcting Modules to follow the '${REVIEW_STANDARD}' standard. ${RESTORE}"
  else
    echo "${LBLUE}> Sniffing Modules following '${REVIEW_STANDARD}' standard. ${RESTORE}"
  fi
  for dir in web/modules/custom/*/ ; do
    code_review "$dir"
  done
fi

if [[ "${FIX_MODE}" -eq 1 ]]; then
  echo "${LBLUE}> Correcting Profile to follow the '${REVIEW_STANDARD}' standard. ${RESTORE}"
else
  echo "${LBLUE}> Sniffing Profile following '${REVIEW_STANDARD}' standard. ${RESTORE}"
fi

for dir in web/profiles/*/ ; do
  code_review "$dir"
done

exit $HAS_ERRORS
