#!/bin/bash

# Use on host machine, not inside DDEV.
# bash drupal-starter/scripts/mass_patch.sh gh_token /tmp/our-little-change.patch
# The working directory shall be the one with all the working copies.

# Check arguments.
if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <github_token> <patch_file>"
  exit 1
fi

GH_TOKEN="$1"
PATCH_FILE="$2"
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

# List of repos to patch
REPOSITORIES=(client1 client2 client3)

git config pull.rebase false

# Clone or update each repository
for REPO in "${REPOSITORIES[@]}"
do
  cd "$SCRIPT_DIR" || exit 1
  DEFAULT_BRANCH=$(curl -s -H "Authorization: token $GH_TOKEN" "https://api.github.com/repos/Gizra/$REPO" | jq -r '.DEFAULT_BRANCH')
  echo "Patching $REPO ($DEFAULT_BRANCH is the default branch)"

  if [ ! -d "$REPO" ]; then
    git clone "git@github.com:Gizra/$REPO.git"
  fi

  cd "$REPO" || continue
  echo "Cleaning $REPO repository"
  git checkout "$DEFAULT_BRANCH"
  git fetch
  git reset --hard origin/"$DEFAULT_BRANCH" || continue
  git clean -f

  echo "Applying patch"
  git apply ../"$PATCH_FILE" || continue
  git checkout -b "$PATCH_FILE" || continue
  git add .
  git commit -m "Apply patch $PATCH_FILE" || continue
  git push origin "$PATCH_FILE"
  curl -H "Authorization: token $GH_TOKEN" \
       -X POST \
       -d '{"title":"Apply patch '$PATCH_FILE'", "head":"'$PATCH_FILE'", "base":"'$DEFAULT_BRANCH'"}' \
       "https://api.github.com/repos/Gizra/$REPO/pulls"
  echo "$REPO is completed"
  echo ""
done
