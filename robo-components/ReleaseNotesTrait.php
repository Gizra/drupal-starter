<?php

namespace RoboComponents;

/**
 * Release notes generator using Git history.
 */
trait ReleaseNotesTrait {

  /**
   * Generates log of changes since the given tag.
   *
   * @param string|null $tag
   *   The git tag to compare since. Usually the tag from the previous release.
   *   If you're releasing for example 1.0.2, then you should get changes since
   *   1.0.1, so $tag = 1.0.1. Omit for detecting the last tag automatically.
   *
   * @throws \Exception
   */
  public function generateReleaseNotes(?string $tag = NULL): void {
    $result = 0;
    // Check if the specified tag exists or not.
    if (!empty($tag)) {
      $result = $this->taskExec("git tag | grep \"$tag\"")
        ->printOutput(FALSE)
        ->run()
        ->getMessage();
      if (empty($result)) {
        $this->say('The specified tag does not exist: ' . $tag);
      }
    }

    if (empty($result)) {
      $latest_tag = $this->taskExec("git tag --sort=version:refname | tail -n1")
        ->printOutput(FALSE)
        ->run()
        ->getMessage();
      if (empty($latest_tag)) {
        throw new \Exception('There are no tags in this repository.');
      }
      if ($this->confirm("Would you like to compare from the latest tag: $latest_tag?")) {
        $tag = $latest_tag;
      }
    }

    // Detect organization / repository name from git remote.
    $remote = $this->taskExec("git remote get-url origin")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    if (!empty($remote)) {
      $origin_parts = preg_split('/[:\/]/', str_replace('.git', '', $remote));
      if (!empty($origin_parts[1]) && !empty($origin_parts[2])) {
        $github_org = $origin_parts[1];
        $github_project = $origin_parts[2];
      }
    }

    if (!isset($github_org) || !isset($github_project)) {
      $this->say('No GitHub project or GitHub organization found, so not trying to fetch details from GitHub API.');
    }

    // This is the heart of the release notes, the git history, we get all the
    // commits since the specified last version and later on we parse
    // the output. Optionally we enrich it with metadata from GitHub REST API.
    $git_command = "git log --pretty=format:'%s¬¬|¬¬%b'";
    if (!empty($tag)) {
      $git_command .= " $tag..";
    }
    $log = $this->taskExec($git_command)->printOutput(FALSE)->run()->getMessage();
    $lines = explode("\n", $log);

    $this->say('Copy release notes below');

    $this->printReleaseNotesTitle('Changelog');

    $pull_requests_per_issue = [];
    $no_issue_lines = [];
    $contributors = [];
    $issue_titles = [];
    $additions = 0;
    $deletions = 0;
    $changed_files = 0;

    foreach ($lines as $line) {
      $log_messages = explode("¬¬|¬¬", $line);
      $pr_matches = [];

      // Here we need to handle two cases.
      // Simple Merges:
      // Merge pull request #1234 from Gizra/drupal-starter/1234
      // Squash & Merges, so there are messages like:
      // Explanation (#1234)
      preg_match_all('/Merge pull request #([0-9]+)/', $line, $pr_matches);

      if (count($log_messages) < 2) {
        // No log message at all, not meaningful for changelog.
        continue;
      }
      if (!isset($pr_matches[1][0])) {
        // Could not detect PR number or it"s a Squash and Merge.
        $pr_matches = [];
        preg_match_all('!\(#([0-9]+)\)!', $line, $pr_matches);
        if (!isset($pr_matches[0][0])) {
          continue;
        }
      }

      $log_messages[1] = trim(str_replace('* ', '', $log_messages[1]));

      $pr_number = $pr_matches[1][0];
      if (!empty($github_org) && !empty($github_project)) {
        /** @var \stdClass $pr_details */
        $pr_details = $this->githubApiGet("repos/$github_org/$github_project/pulls/$pr_number");
        if (!empty($pr_details->user)) {
          if (isset($pr_details->user->type) && $pr_details->user->type === 'Bot') {
            // Exclude Dependabot merges from release notes.
            continue;
          }
          $contributors[] = '@' . $pr_details->user->login;
          $additions += $pr_details->additions;
          $deletions += $pr_details->deletions;
          $changed_files += $pr_details->changed_files;

          if (empty($log_messages[1])) {
            $log_messages[1] = $pr_details->title;
          }
        }
      }

      if (empty($log_messages[1])) {
        // Whitespace-only log message, not meaningful for changelog.
        continue;
      }

      // The issue number is a required part of the branch name,
      // So usually we can grab it from the log too, but that's optional
      // If we cannot detect it, we still print a less verbose changelog line.
      $issue_matches = [];
      preg_match_all('!from [a-zA-Z-_0-9]+/([0-9]+)!', $line, $issue_matches);

      if (isset($issue_matches[1][0])) {
        $issue_number = $issue_matches[1][0];
      }
      else {
        // Retrieve the issue number from the PR description via GitHub API.
        $pr = NULL;
        if (!empty($github_project) && !empty($github_org)) {
          $pr = $this->githubApiGet("repos/$github_org/$github_project/pulls/$pr_number");
        }
        if (!isset($pr->body)) {
          $no_issue_lines[] = "- $log_messages[1] (#$pr_number)";
          continue;
        }
        preg_match_all('!#([0-9]+)!', $pr->body, $issue_matches);
        if (!isset($issue_matches[1][0])) {
          $no_issue_lines[] = "- $log_messages[1] (#$pr_number)";
          continue;
        }
        $issue_number = $issue_matches[1][0];
      }

      if (!empty($issue_number)) {
        if (!isset($issue_titles[$issue_number]) && !empty($github_org) && !empty($github_project)) {
          /** @var \stdClass $issue_details */
          $issue_details = $this->githubApiGet("repos/$github_org/$github_project/issues/$issue_number");
          if (!empty($issue_details->title)) {
            $issue_titles[$issue_number] = $issue_details->title;
            $contributors[] = '@' . $issue_details->user->login;
          }
        }

        if (isset($issue_titles[$issue_number])) {
          $issue_line = "- $issue_titles[$issue_number] (#$issue_number)";
        }
        else {
          $issue_line = "- Issue #$issue_number";
        }
        if (!isset($pull_requests_per_issue[$issue_line])) {
          $pull_requests_per_issue[$issue_line] = [];
        }
        $pull_requests_per_issue[$issue_line][] = "  - $log_messages[1] (#{$pr_matches[1][0]})";
      }
      else {
        $no_issue_lines[] = "- $log_messages[1] (#$pr_number)";
      }
    }

    foreach ($pull_requests_per_issue as $issue_line => $pr_lines) {
      print $issue_line . "\n";
      foreach ($pr_lines as $pr_line) {
        print $pr_line . "\n";
      }
    }

    $this->printReleaseNotesSection('', $no_issue_lines);

    if (isset($github_org)) {
      $contributors = array_count_values($contributors);
      arsort($contributors);
      $this->printReleaseNotesSection('Contributors', $contributors, TRUE);

      $this->printReleaseNotesSection('Code statistics', [
        "Lines added: $additions",
        "Lines deleted: $deletions",
        "Files changed: $changed_files",
      ]);
    }
  }

  /**
   * Print a section for the release notes.
   *
   * @param string $title
   *   Section title.
   * @param array $lines
   *   Bullet points.
   * @param bool $print_key
   *   Whether to print the key of the array.
   */
  protected function printReleaseNotesSection(string $title, array $lines, bool $print_key = FALSE): void {
    if (!empty($title)) {
      $this->printReleaseNotesTitle($title);
    }
    foreach ($lines as $key => $line) {
      if ($print_key) {
        print "- $key ($line)\n";
      }
      elseif (substr($line, 0, 1) == '-') {
        print "$line\n";
      }
      else {
        print "- $line\n";
      }
    }
  }

  /**
   * Print a title for the release notes.
   *
   * @param string $title
   *   Section title.
   */
  protected function printReleaseNotesTitle(string $title): void {
    echo "\n\n## $title\n";
  }

  /**
   * Performs a GET request towards GitHub API using personal access token.
   *
   * @param string $path
   *   Resource/path to GET.
   *
   * @return mixed|null
   *   Decoded response or NULL.
   *
   * @throws \Exception
   */
  protected function githubApiGet(string $path) {
    $token = getenv('GITHUB_ACCESS_TOKEN');
    $username = getenv('GITHUB_USERNAME');
    if (empty($token)) {
      throw new \Exception('Specify the personal access token in GITHUB_ACCESS_TOKEN environment variable before invoking the release notes generator in order to be able to fetch details of issues and pull requests');
    }
    if (empty($username)) {
      throw new \Exception('Specify the GitHub username in GITHUB_USERNAME environment variable before invoking the release notes generator in order to be able to fetch details of issues and pull requests');
    }
    // We might not have a sane Drupal instance, let's not rely on Drupal API
    // to generate release notes.
    $ch = curl_init('https://api.github.com/' . $path);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Drupal Starter Release Notes Generator');
    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $token);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $result = empty($result) ? NULL : json_decode($result);
    if ($http_code == 404) {
      throw new \Exception("404 Not Found error encountered while requesting the API path $path. The path either does not exist, or your token does not have sufficient permissions as Github API returns 404 instead of 403. Details: \n" . print_r($result, TRUE));
    }
    if (substr((string) $http_code, 0, 1) != 2) {
      throw new \Exception("Error: $http_code - Failed to request the API path $path:\n" . print_r($result, TRUE));
    }
    return $result;
  }

}
