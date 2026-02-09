#!/usr/bin/env bash
#
# Check for filenames incompatible with Windows NTFS filesystem.
# Runs against git-tracked files so untracked/ignored files are skipped.

set -euo pipefail

ISSUES=0
MAX_PATH_LENGTH=260
MAX_COMPONENT_LENGTH=255

# Windows reserved characters (excluding / and \ which are path separators).
RESERVED_CHARS_PATTERN='[<>:"|?*]'

# Windows reserved device names (case-insensitive), with or without extension.
RESERVED_NAMES='(CON|PRN|AUX|NUL|COM[0-9]|LPT[0-9])'

report() {
  local file="$1"
  local reason="$2"
  echo "  $file"
  echo "    -> $reason"
  echo
  ((ISSUES++)) || true
}

echo "Scanning git-tracked files for Windows NTFS incompatibilities..."
echo

while IFS= read -r -d '' filepath; do
  # Check total path length.
  if (( ${#filepath} > MAX_PATH_LENGTH )); then
    report "$filepath" "Path exceeds $MAX_PATH_LENGTH characters (${#filepath} chars)"
  fi

  # Check each path component (directory or filename).
  IFS='/' read -ra parts <<< "$filepath"
  for part in "${parts[@]}"; do
    # Check component length.
    if (( ${#part} > MAX_COMPONENT_LENGTH )); then
      report "$filepath" "Component '$part' exceeds $MAX_COMPONENT_LENGTH characters"
    fi

    # Check for reserved characters.
    if [[ "$part" =~ $RESERVED_CHARS_PATTERN ]]; then
      report "$filepath" "Contains reserved character in '$part'"
    fi

    # Check for trailing dot or space.
    if [[ "$part" =~ [.\ ]$ ]]; then
      report "$filepath" "Component '$part' ends with a dot or space"
    fi

    # Check for reserved device names (e.g. CON, CON.txt, nul.tar.gz).
    basename_no_ext="${part%%.*}"
    if [[ -n "$basename_no_ext" && "${basename_no_ext^^}" =~ ^${RESERVED_NAMES}$ ]]; then
      report "$filepath" "Component '$part' uses reserved device name '${basename_no_ext^^}'"
    fi
  done
done < <(git -C "$(git rev-parse --show-toplevel)" ls-files -z)

if (( ISSUES == 0 )); then
  echo "No NTFS compatibility issues found."
else
  echo "Found $ISSUES issue(s)."
  exit 1
fi
