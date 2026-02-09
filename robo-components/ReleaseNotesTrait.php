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
    $git_command = "git log --pretty=format:'%s'";
    if (!empty($tag)) {
      $git_command .= " $tag..";
    }
    $log = $this->taskExec($git_command)->printOutput(FALSE)->run()->getMessage();
    // Each line contains the first line of the commit message.
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
      $issue_number = NULL;
      $pr_matches = [];

      // Here we need to handle two cases.
      // Simple Merges:
      // Merge pull request #1234 from Gizra/drupal-starter/1234
      // Squash & Merges, so there are messages like:
      // Explanation (#1234)
      preg_match_all('/Merge pull request #([0-9]+)/', $line, $pr_matches);

      if (!isset($pr_matches[1][0])) {
        // Could not detect PR number or it"s a Squash and Merge.
        $pr_matches = [];
        preg_match_all('!\(#([0-9]+)\)!', $line, $pr_matches);
        // If we have no pr number, continue.
        if (!isset($pr_matches[1][0])) {
          continue;
        }
      }
      else {
        // In case of simple merges, we get the issue number from the log,
        // as the issue number is a required part of the branch name.
        // If we cannot detect it, we still print a less verbose changelog line.
        $issue_matches = [];
        preg_match_all('!from [a-zA-Z-_0-9]+/([0-9]+)!', $line, $issue_matches);

        if (isset($issue_matches[1][0])) {
          $issue_number = $issue_matches[1][0];
        }
      }

      $pr_number = $pr_matches[1][0];

      if (!empty($github_org) && !empty($github_project)) {
        /** @var \stdClass $pr_details */
        $pr_details = $this->githubApiGet("repos/$github_org/$github_project/pulls/$pr_number");
        if (!empty($pr_details->user)) {
          $contributors[] = '@' . $pr_details->user->login;
          $additions += $pr_details->additions;
          $deletions += $pr_details->deletions;
          $changed_files += $pr_details->changed_files;
        }
        if (empty($issue_number)) {
          // Try GraphQL first (linked issues in Development section).
          $linked_issues = $this->githubGraphQlGetLinkedIssuesFromPr((int) $pr_number, $github_project, $github_org);
          $issue_number = !empty($linked_issues) ? array_key_first($linked_issues) : NULL;

          // Fall back to parsing PR body if GraphQL didn't find anything.
          if (empty($issue_number)) {
            $issue_number = $this->githubApiGetLinkedIssuesFromPrBody((int) $pr_number, $github_project, $github_org);
          }
        }
        if (!empty($issue_number) && !isset($issue_titles[$issue_number])) {
          /** @var \stdClass $issue_details */
          $issue_details = $this->githubApiGet("repos/$github_org/$github_project/issues/$issue_number");
          if (!empty($issue_details->title)) {
            $issue_titles[$issue_number] = $issue_details->title;
            $contributors[] = '@' . $issue_details->user->login;
          }
        }
      }

      if (empty($issue_number)) {
        if (!empty($pr_number)) {
          $no_issue_lines[] = "- PR #$pr_number";
        }
        continue;
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
      $pull_requests_per_issue[$issue_line][] = "  - $line";

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
    $result = empty($result) ? NULL : json_decode($result);
    if ($http_code == 404) {
      throw new \Exception("404 Not Found error encountered while requesting the API path $path. The path either does not exist, or your token does not have sufficient permissions as Github API returns 404 instead of 403. Details: \n" . print_r($result, TRUE));
    }
    if (substr((string) $http_code, 0, 1) != 2) {
      throw new \Exception("Error: $http_code - Failed to request the API path $path:\n" . print_r($result, TRUE));
    }
    return $result;
  }

  /**
   * Get linked issues from a Pull Request using GitHub's Development links.
   *
   * This function uses GitHub's GraphQL API to fetch issues that are
   * formally linked to the PR via the "Development" section
   * (closingIssuesReferences).
   *
   * @param int $pr_number
   *   The pull request number.
   * @param string $repo
   *   Github repo name.
   * @param string $owner
   *   Github user/organization name.
   *
   * @return array
   *   An array of linked issues with 'number' and 'title' keys.
   */
  protected function githubGraphQlGetLinkedIssuesFromPr(int $pr_number, string $repo, string $owner): array {
    $token = getenv('GITHUB_ACCESS_TOKEN');
    if (empty($token)) {
      return [];
    }

    $query = <<<'GRAPHQL'
query($owner: String!, $repo: String!, $pr: Int!) {
  repository(owner: $owner, name: $repo) {
    pullRequest(number: $pr) {
      closingIssuesReferences(first: 50) {
        nodes {
          number
          title
          repository {
            name
            owner {
              login
            }
          }
        }
      }
    }
  }
}
GRAPHQL;

    $payload = json_encode([
      'query' => $query,
      'variables' => [
        'owner' => $owner,
        'repo' => $repo,
        'pr' => $pr_number,
      ],
    ]);

    $ch = curl_init('https://api.github.com/graphql');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Drupal Starter Release Notes Generator');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: bearer ' . $token,
      'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code !== 200 || empty($result)) {
      return [];
    }

    $data = json_decode($result, TRUE);
    $linked_issues = $data['data']['repository']['pullRequest']['closingIssuesReferences']['nodes'] ?? [];

    $issues = [];
    foreach ($linked_issues as $linked_issue) {
      // Linked issues can be only from same repository.
      $issue_number = $linked_issue['number'];
      $issues[$issue_number] = [
        'number' => $issue_number,
        'title' => $linked_issue['title'] ?? '',
      ];
    }

    return $issues;
  }

  /**
   * Get linked issue from a Pull Request by parsing the PR body.
   *
   * This function uses GitHub's REST API to fetch the PR details and then
   * parses the body to find issue references (e.g., #123 or "Fixes repo#123").
   *
   * @param int $pr_number
   *   The pull request number.
   * @param string $repo
   *   Github repo name.
   * @param string $owner
   *   Github user/organization name.
   *
   * @return string|null
   *   The issue number as a string, or NULL if not found.
   */
  protected function githubApiGetLinkedIssuesFromPrBody(int $pr_number, string $repo, string $owner): ?string {
    $pr = $this->githubApiGet("repos/$owner/$repo/pulls/$pr_number");

    if (!isset($pr->body)) {
      return NULL;
    }

    $issue_matches = [];

    // First try the specific "Fixes" pattern which explicitly indicates issue
    // linkage.
    preg_match_all('!Fixes .+#([0-9]+)!', $pr->body, $issue_matches);
    if (isset($issue_matches[1][0])) {
      return $issue_matches[1][0];
    }

    // Fall back to simple issue reference pattern.
    preg_match_all('!#([0-9]+)!', $pr->body, $issue_matches);
    if (isset($issue_matches[1][0])) {
      return $issue_matches[1][0];
    }

    return NULL;
  }

}
