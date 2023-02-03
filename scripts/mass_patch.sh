#!/bin/bash

# Use on host machine, not inside DDEV.
# bash drupal-starter/scripts/mass_patch.sh gh_token /tmp/our-little-change.patch
# The working directory shall be the one with all the working copies.

GH_TOKEN="$1"
PATCH_FILE="$2"
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

# List of repositories to patch
repositories=(client1 client2 client3)

git config pull.rebase false

# Clone or update each repository
for repo in "${repositories[@]}"
do
  cd "$SCRIPT_DIR" || exit 1
  default_branch=$(curl -s -H "Authorization: token $GH_TOKEN" "https://api.github.com/repos/Gizra/$repo" | jq -r '.default_branch')
  echo "Patching $repo ($default_branch is the default branch)"

  if [ ! -d "$repo" ]; then
    git clone "git@github.com:Gizra/$repo.git"
  fi

  cd "$repo" || continue
  echo "Cleaning $repo repository"
  git checkout "$default_branch"
  git fetch
  git reset --hard origin/"$default_branch"
  git clean -f

  echo "Applying patch"
  git apply ../"$PATCH_FILE" || continue
  git checkout -b "$PATCH_FILE"
  git add .
  git commit -m "Apply patch $PATCH_FILE"
  git push origin "$PATCH_FILE"
  curl -H "Authorization: token $GH_TOKEN" \
       -X POST \
       -d '{"title":"Apply patch '$PATCH_FILE'", "head":"'$PATCH_FILE'", "base":"'$default_branch'"}' \
       "https://api.github.com/repos/Gizra/$repo/pulls"
  echo "$repo is completed, next one.."
  echo ""
done
