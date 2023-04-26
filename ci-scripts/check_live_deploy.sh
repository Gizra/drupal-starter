#!/bin/bash
git fetch origin refs/remotes/*:refs/remotes/*

# We remove the _live suffix
RELEASE_TAG=${TRAVIS_TAG%_live}

# Let's say we have 1.2.3_live tag right now.
# It can only go through if 1.2.3 tag is already pushed.
if [ "$(git tag -l "$RELEASE_TAG")" ]; then
  # And it points to the same commit.
  TAG_A=$(git rev-list -n 1 "$TRAVIS_TAG")
  TAG_B=$(git rev-list -n 1 "$RELEASE_TAG")
  if [[ "$TAG_A" == "$TAG_B" ]]
  then
    exit 0
  fi

  echo "$RELEASE_TAG AND $TRAVIS_TAG must point to the same commit. Giving up!"

  exit 1
fi

echo "$RELEASE_TAG is missing. Deploy to TEST first."
exit 1
