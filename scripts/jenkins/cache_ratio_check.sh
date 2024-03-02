#!/bin/bash

shopt -s lastpipe

# Check if your Pantheon live sites are cached properly.
# It checks the CDN cache hit ratio, if anonymous visitors
# got fully cached requests or not, at least for some parts
# of the website.
# Let's say if max-age header is set to zero, it will throw an alert.

# Add known problematic sites to EXCLUDED_SITES environment variable.
# @see https://plugins.jenkins.io/envinject/
# Example: EXCLUDED_SITES=("foo" "bar")

if [ -z "${EXCLUDED_SITES+x}" ]; then
  EXCLUDED_SITES=()
fi

# Fetch all sites
SITES_JSON=$(terminus site:list --format=json 2>/dev/null)
# Filter non-frozen sites
SITES_TO_CHECK=$(echo "$SITES_JSON" | jq -r '.[] | select(.frozen == false) | .name')

# Initialize flag for cache issues
CACHE_ISSUE_FLAG=0

# Function to check cache hit ratio
check_cache_hit_ratio() {
  local site_name=$1

  # Get metrics in CSV format
  local metrics_csv
  metrics_csv=$(terminus env:metrics "${site_name}.live" --format=csv 2>/dev/null)

  # Convert CSV to an array of the last three cache hit ratios
  IFS=$'\n' echo "$metrics_csv" | tail -n 4 | cut -d ',' -f6 | tr -d '%' | tail -n 3 | read -r -a cache_hit_ratios

  # Initialize counter for consecutive zero hit ratios
  local zero_hit_ratio_count=0

  # Loop through cache hit ratios
  for ratio in "${cache_hit_ratios[@]}"; do
    # Skip iteration if the ratio is not a valid number
    if ! [[ "$ratio" =~ ^[0-9]+(\.[0-9]+)?$ ]]; then
      echo "WARNING: Invalid cache hit ratio '$ratio' for ${site_name}, skipping..."
      continue
    fi

    # Use 'bc' to compare the floating-point number
    if [[ $(echo "$ratio <= 0" | bc -l) -eq 1 ]]; then
      # Increment counter
      ((zero_hit_ratio_count++))
    fi
  done

  # Check if all the last three values are 0% cache hit ratios
  if [ "$zero_hit_ratio_count" -eq 3 ]; then
    echo "ALERT: ${site_name} has had a 0% cache hit ratio for the last 3 days."
    CACHE_ISSUE_FLAG=1
  fi
}

# Check if a site should be excluded
is_excluded() {
  local site=$1
  for excluded_site in "${EXCLUDED_SITES[@]}"; do
    if [[ "$site" == "$excluded_site" ]]; then
      return 0
    fi
  done
  return 1
}

# Iterate over sites and check their cache hit ratios
for site in "${SITES_TO_CHECK[@]}"; do
  if is_excluded "$site"; then
    continue
  fi
  check_cache_hit_ratio "$site"
done

# Exit with 1 if any cache issues were found
exit $CACHE_ISSUE_FLAG

