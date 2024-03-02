#!/bin/bash

# Check if your Pantheon live sites are properly backed up each day.

# Add known problematic sites to EXCLUDED_SITES environment variable.
# @see https://plugins.jenkins.io/envinject/
# Example: EXCLUDED_SITES=("foo" "bar")

if [ -z "${EXCLUDED_SITES+x}" ]; then
  EXCLUDED_SITES=()
fi

# Fetch all sites
SITES_JSON=$(terminus site:list --format=json 2>/dev/null)

# Filter non-frozen sites
NON_FROZEN_SITES=$(echo "$SITES_JSON" | jq -r '.[] | select(.frozen == false) | .name')

# Initialize flag for missing backups
MISSING_BACKUP_FLAG=0

# Function to check if a site is in the EXCLUDED_SITES array
function is_excluded() {
    local site=$1
    for excluded_site in "${EXCLUDED_SITES[@]}"; do
        if [[ "$site" == "$excluded_site" ]]; then
            return 0
        fi
    done

    local live_initialized
    live_initialized=$(terminus env:list --format=json "${site}" | jq -r '.live.initialized')
    if [[ "$live_initialized" == "false" ]]; then
        return 0
    fi

    return 1
}

# Iterate through each non-frozen site.
for site_name in "${NON_FROZEN_SITES[@]}"; do

  # Check if the site should be excluded.
  if is_excluded "$site_name"; then
    continue
  fi

  echo "Checking backups for site: $site_name"

  # Fetch backups for site
  BACKUPS_JSON=$(terminus backup:list "${site_name}.live" --format=json 2>/dev/null)

  # Components to check
  COMPONENTS=("files" "code" "database")

  for component in "${COMPONENTS[@]}"; do
    # Get the latest backup date for the component
    LATEST_BACKUP_DATE=$(echo "$BACKUPS_JSON" | jq -r --arg COMPONENT "${component}" 'to_entries[] | select(.key | contains($COMPONENT)) | .value.date' | while read -r date; do date -d "$date" +%s; done | sort -nr | head -n1)

    # Get the current date
    CURRENT_DATE=$(date +%s)

    # Calculate the time difference in seconds
    TIME_DIFF=$((CURRENT_DATE - LATEST_BACKUP_DATE))

    # Check if backup is older than 2 days (172800 seconds)
    if [ $TIME_DIFF -gt 172800 ]; then
      echo "WARNING: No $component backup in the past 2 days for site: $site_name"
      MISSING_BACKUP_FLAG=1
    fi
  done
done

# Exit status based on missing backups.
exit $MISSING_BACKUP_FLAG
