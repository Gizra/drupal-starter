#!/bin/bash

# Get the commit message from the environment variable or from git
git_commit_message=${TRAVIS_COMMIT_MESSAGE:-$(git log -1 --pretty=%B)}

# Initialize variables
issue_number=""
pr_number=""

# Try to extract the issue number from the commit message.
issue_matches=()
if [[ $git_commit_message =~ from\ [a-zA-Z-_0-9]+/([0-9]+) ]]; then
    issue_matches=("${BASH_REMATCH[@]}")
    issue_number=${issue_matches[1]}
else
    # Check for PR number in the format (#1234)
    if [[ $git_commit_message =~ \(\#([0-9]+)\) ]]; then
        pr_number=${BASH_REMATCH[1]}

        # Retrieve PR information from GitHub API
        pr_info=$(curl -H "Authorization: token $GITHUB_TOKEN" "https://api.github.com/repos/$TRAVIS_REPO_SLUG/pulls/$pr_number")

        # Extract issue number from PR body
        if [[ $pr_info =~ \#([0-9]+) ]]; then
            issue_number=${BASH_REMATCH[1]}
        else
            echo "Could not determine the issue number from the PR description."
            exit 1
        fi
    else
        echo "Could not determine the issue or PR number from the commit message: $git_commit_message"
        exit 1
    fi
fi

# Check if the script should notify
if [ "$TRAVIS_BRANCH" == "main" ] && [ "$TRAVIS_EVENT_TYPE" == "push" ] && [ -z "$TRAVIS_TAG" ] && [ -n "$issue_number" ]; then
    # Determine the message based on whether PR number is available
    if [ -n "$pr_number" ]; then
        message="Could not deploy PR #$pr_number to Pantheon properly."
    else
        message="Could not deploy the last PR / commit to Pantheon properly."
    fi

    github_api_url="https://api.github.com/repos/$TRAVIS_REPO_SLUG/issues/$issue_number/comments"

    exit_code=$(curl -X POST -H "Authorization: token $GITHUB_TOKEN" -d "{\"body\": \"$message\"}" "$github_api_url" -o /dev/null -w '%{http_code}')

    if [ "$exit_code" -ne 200 ] && [ "$exit_code" -ne 201 ]; then
        echo "Failed to post the message to GitHub. HTTP response code: $exit_code"
        exit 1
    fi
else
    echo "Notification conditions not met or issue number not found. No action taken."
fi
