---
name: drupal-startup
description: Start and prepare a local Drupal project that runs in DDEV, then open the site already logged in. Use when Codex needs to bring a Drupal repo into a ready local state by confirming it is a Drupal/DDEV repo, checking Docker, running `ddev start`, optionally updating from `origin/main` when it is safe and clearly approved, installing Composer dependencies, importing config, running database updates, clearing cache, and launching a browser session with a fresh Drush login link.
---

# Drupal Startup

## Overview

Run the repo's local startup workflow in a safe, repeatable order. Prefer short progress updates, stop before risky Git operations, and end with a browser window opened on a fresh one-time login URL.

## Workflow

1. Confirm the current repository is the intended Drupal/DDEV project.
2. Check whether Docker is available before running DDEV commands.
3. Start DDEV.
4. Inspect Git status before updating from `origin/main`.
5. Run the required project update commands in order.
6. Generate a one-time login URL and launch it in the browser.

## Repository Check

Before doing anything stateful, confirm you are in the expected project root.

Use a lightweight check such as:

```bash
pwd
test -f .ddev/config.yaml
test -f composer.json
```

If the repo does not look like a DDEV Drupal project, stop and explain what is missing.

## Docker And DDEV

Check Docker first with a lightweight command such as `docker info` or `docker ps`.

If the Docker command is blocked by sandbox permissions rather than by Docker itself, immediately retry with escalation instead of treating that as a Docker outage.

If Docker is unavailable:
- Report that Docker is not running yet.
- If desktop launch is possible and appropriate, request approval to open Docker Desktop.
- Wait until Docker responds before continuing.

Run `ddev start` from the repository root once Docker is ready.

If `ddev start` fails, stop and surface the error instead of guessing at repair steps.

## Git Safety Guardrails

Run `git status --short` and `git branch --show-current` before `git pull origin main`.

Pause and ask the user before pulling if either condition is true:
- The worktree is dirty.
- The checked-out branch is not the branch the user intends to update.

Reason: `git pull origin main` merges `origin/main` into the current branch, which is safe only when that is intentional.

When you pause, make the next step explicit:
- Offer a safe fallback: skip `git pull` and continue the Drupal startup sequence.
- Offer the risky path: intentionally run `git pull origin main` into the current branch.
- If the user's reply is vague or ambiguous, default to the safe fallback and continue without pulling.

If the repo is clean and the branch choice is intentional, run:

```bash
git pull origin main
```

Do not use force operations. Do not discard user changes.

## Drupal Update Sequence

After `ddev start` succeeds and Git is in the intended state, run these commands from the repo root in this order:

```bash
ddev composer install
ddev drush cim -y
ddev drush updb -y
ddev drush cr
ddev drush cr
```

Keep the double cache rebuild because this skill is intentionally mirroring the requested local workflow.

If any command fails, stop immediately, report the failing command, and include the error output in summary form.

If `ddev composer install` reports "Nothing to install, update or remove", treat that as a successful result and continue.

## Launch Logged-In Drupal

Generate a fresh login URL with Drush and open it in the browser.

Preferred pattern:

```bash
ddev launch "$(ddev drush uli --no-browser)"
```

If shell quoting or nested command execution is awkward in the current environment:

1. Run `ddev drush uli --no-browser`.
2. Capture the returned URL.
3. Open that URL with `ddev launch <url>` or another approved browser-opening command.

Mention that the URL is one-time-use and time-limited.

If browser launching is blocked by sandbox or desktop permissions, request escalation for `ddev launch` rather than stopping after printing the URL.

The login flow may land on Drupal's password-reset or user-edit screen for the admin account. That is still a successful outcome for this skill as long as the user is authenticated and can continue working.

## Response Style

Keep the user updated as the workflow progresses:
- Say when Docker is being checked.
- Say when DDEV has started.
- Say before the Git decision happens, especially if the repo is dirty.
- Say before the Drupal command sequence begins.
- Confirm that the login URL was launched at the end.

If you stop for a guardrail or failure, explain exactly what blocked the workflow and what decision is needed next.
