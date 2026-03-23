---
name: drupal-startup
description: Start and prepare a local Drupal project that runs in DDEV, then open the site already logged in. Use when Codex needs to bring a Drupal repo into a ready local state by confirming it is a Drupal/DDEV repo, checking Docker, running `ddev start`, deciding whether to skip or run `git pull origin main` without unnecessary user interruption, installing Composer dependencies, importing config, running database updates, clearing cache, and launching a browser session with a fresh Drush login link.
---

# Drupal Startup

## Overview

Run the repo's local startup workflow in a safe, repeatable order. Prefer short progress updates, avoid unnecessary questions, and end with a browser window opened on a fresh one-time login URL.

## Workflow

1. Confirm the current repository is the intended Drupal/DDEV project.
2. Check whether Docker is available before running DDEV commands.
3. Start DDEV.
4. Inspect Git status and choose the Git path without interrupting the user unless a decision is truly required.
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

Run `git status --short` and `git branch --show-current` before deciding whether to run `git pull origin main`.

Default to continuing the Drupal startup flow without asking the user about Git.

Use this decision rule:
- If the user explicitly asked to update from `origin/main`, run `git pull origin main` only when the worktree is clean.
- If the current branch is `main` and the worktree is clean, run `git pull origin main`.
- If the current branch is not `main`, skip the pull and continue startup without asking.
- If the worktree is dirty, skip the pull and continue startup without asking.

Only interrupt the user for Git if both are true:
- The user explicitly asked for a pull or update.
- The worktree is dirty, or the requested pull would merge `origin/main` into a non-`main` branch.

When you skip the pull, say so in one short sentence and continue.

When conditions allow a pull, run:

```bash
git pull origin main
```

Do not use force operations. Do not discard user changes. Never make Git progress a blocker for the rest of the startup workflow unless the user explicitly requested the pull and wants that resolved first.

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

If `ddev drush uli --no-browser` fails because the `admin` user is blocked, run:

```bash
ddev drush user:unblock admin
```

Then retry the login URL flow.

## Response Style

Keep the user updated as the workflow progresses:
- Say when Docker is being checked.
- Say when DDEV has started.
- Briefly note whether Git pull was run or skipped, without turning it into a question unless a real decision is required.
- Say before the Drupal command sequence begins.
- Confirm that the login URL was launched at the end.

If you stop for a guardrail or failure, explain exactly what blocked the workflow and what decision is needed next.
