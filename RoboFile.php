<?php

declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Robo\Symfony\ConsoleIO;
use Robo\Tasks;
use RoboComponents\AutoUpdateTrait;
use RoboComponents\BootstrapTrait;
use RoboComponents\DeploymentTrait;
use RoboComponents\PantheonRemoteTrait;
use RoboComponents\PhpcsTrait;
use RoboComponents\ProjectConfigTrait;
use RoboComponents\ReleaseNotesTrait;
use RoboComponents\SecurityTrait;
use RoboComponents\ThemeTrait;
use RoboComponents\TranslationManagement\ExportFromConfig;
use RoboComponents\TranslationManagement\ImportToConfig;
use RoboComponents\TranslationManagement\ImportToUi;
use Symfony\Component\HttpFoundation\Request;

$GLOBALS['drupal_autoloader'] = require_once 'web/autoload.php';

/**
 * Robo commands.
 */
class RoboFile extends Tasks {

  use AutoUpdateTrait;
  use BootstrapTrait;
  use DeploymentTrait;
  use ExportFromConfig;
  use ImportToConfig;
  use ImportToUi;
  use PhpcsTrait;
  use PantheonRemoteTrait;
  use ProjectConfigTrait;
  use ReleaseNotesTrait;
  use SecurityTrait;
  use ThemeTrait;

  /**
   * {@inheritdoc}
   */
  protected function getThemeName(): string {
    return 'server_theme';
  }

  /**
   * {@inheritdoc}
   */
  protected function getGithubProject(): string {
    return 'Gizra/drupal-starter';
  }

  /**
   * Defines a list of languages installed on the site.
   *
   * Edit as languages are installed/removed. This is used for translation
   * management. Do not include 'en', 'und' or 'zxx'.
   */
  protected function getInstalledLanguages(): array {
    return [
      'ar',
      'es',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function deployConfigAutodeploy(string $token, string $github_token): void {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $project_name = $pantheon_info['name'];

    $result = $this->taskExec('ssh-keygen -t rsa -f pantheon-key -P ""')->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception('The key generation failed.');
    }

    $result = $this->taskExec("terminus connection:info $project_name.dev --fields='Git Command' --format=string | awk '{print $3}'")
      ->printOutput(FALSE)
      ->run();
    $pantheon_git_url = trim($result->getMessage());

    $this->say("The project was prepared for automatic deployment to Pantheon using GitHub Actions");
    $this->say("");

    $gh_available = $this->deployInstallGhCli();

    if ($gh_available) {
      $this->say("GitHub Secrets and Variables can be set automatically using gh CLI.");
      $io = new ConsoleIO($this->input(), $this->output());
      $automate = $io->confirm('Would you like to automatically set up GitHub Secrets and Variables now?', TRUE);

      if ($automate) {
        $this->say("");
        $this->say("Setting up GitHub Secrets and Variables...");

        if ($this->setGithubSecretFromValue('TERMINUS_TOKEN', $token)) {
          $this->say("✓ Set TERMINUS_TOKEN secret");
        }
        if ($this->setGithubSecretFromFile('PANTHEON_DEPLOY_KEY', 'pantheon-key')) {
          $this->say("✓ Set PANTHEON_DEPLOY_KEY secret");
        }
        if ($this->setGithubSecretFromValue('GH_TOKEN', $github_token)) {
          $this->say("✓ Set GH_TOKEN secret");
        }
        if ($this->setGithubVariable('PANTHEON_GIT_URL', $pantheon_git_url)) {
          $this->say("✓ Set PANTHEON_GIT_URL variable");
        }

        $this->say("");
        $this->say("GitHub Secrets and Variables have been configured!");
        $this->say("");
        $this->say("Optional: You can also set ROLLBAR_SERVER_TOKEN and DEPLOY_EXCLUDE_WARNING variables if needed:");
        $this->say("  gh variable set ROLLBAR_SERVER_TOKEN --body 'your-token' --repo " . $this->getGithubProject());
        $this->say("  gh variable set DEPLOY_EXCLUDE_WARNING --body 'warning1|warning2' --repo " . $this->getGithubProject());
      }
      else {
        $this->printManualInstructions($pantheon_git_url);
      }
    }
    else {
      $this->say("");
      $this->printManualInstructions($pantheon_git_url);
    }

    $this->say("");
    $this->say("Remaining steps:");
    $this->say("1. Add the SSH public key to the Pantheon account:");
    $this->say("   - Key location: pantheon-key.pub");
    $this->say("   - Instructions: https://pantheon.io/docs/ssh-keys");
    $this->say("");
    $this->say("2. IMPORTANT: Keep the pantheon-key file secure and DO NOT commit it to the repository.");
    $this->say("   After adding the public key to Pantheon, you can delete the pantheon-key files locally.");
    $this->say("");
    $this->say("3. Ensure nested docroot is configured: https://pantheon.io/docs/nested-docroot");
    $this->say("");
    $this->say("For more details, see the 'Automatic Deployment to Pantheon' section in README.md");
  }

  /**
   * Ensures GitHub CLI (gh) is available, installing if needed.
   */
  public function deployInstallGhCli(): bool {
    $gh_available = $this->taskExec('which gh')
      ->printOutput(FALSE)
      ->run()
      ->wasSuccessful();

    if ($gh_available) {
      return TRUE;
    }

    $this->say("GitHub CLI (gh) is not installed. Installing it now...");

    $install_commands = [
      'sudo apt-get update -qq',
      'sudo apt-get install -y -qq curl',
      'curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg 2>/dev/null',
      'sudo chmod go+r /usr/share/keyrings/githubcli-archive-keyring.gpg',
      'echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null',
      'sudo apt-get update -qq',
      'sudo apt-get install -y -qq gh',
    ];

    foreach ($install_commands as $cmd) {
      $result = $this->taskExec($cmd)->run();
      if (!$result->wasSuccessful()) {
        $this->say("Warning: Failed to install gh CLI automatically.");
        return FALSE;
      }
    }

    $this->say("GitHub CLI installed successfully!");
    return TRUE;
  }

  /**
   * Prints manual setup instructions for GitHub Secrets/Variables.
   */
  protected function printManualInstructions(string $pantheon_git_url): void {
    $this->say("Please complete the following steps manually:");
    $this->say("");
    $this->say("1. Set up the following GitHub Secrets:");
    $this->say("   - Go to: Settings → Secrets and variables → Actions → Secrets → New repository secret");
    $this->say("   - TERMINUS_TOKEN: (the Pantheon machine token you passed to this command)");
    $this->say("   - PANTHEON_DEPLOY_KEY: (paste the contents of pantheon-key file)");
    $this->say("   - GH_TOKEN: (the GitHub personal access token you passed to this command)");
    $this->say("");
    $this->say("2. Set up the following GitHub Variables:");
    $this->say("   - Go to: Settings → Secrets and variables → Actions → Variables → New repository variable");
    $this->say("   - PANTHEON_GIT_URL: " . $pantheon_git_url);
    $this->say("   - ROLLBAR_SERVER_TOKEN: (your Rollbar token, optional)");
    $this->say("   - DEPLOY_EXCLUDE_WARNING: (warnings to exclude, optional)");
  }

  /**
   * Sets a GitHub repository secret from a string value via stdin.
   */
  protected function setGithubSecretFromValue(string $name, string $value): bool {
    $tmp = tempnam(sys_get_temp_dir(), 'gh-secret-');
    if ($tmp === FALSE) {
      $this->say("Failed to create temp file for $name.");
      return FALSE;
    }
    chmod($tmp, 0600);
    if (file_put_contents($tmp, $value) === FALSE) {
      @unlink($tmp);
      $this->say("Failed to write temp file for $name.");
      return FALSE;
    }
    try {
      return $this->setGithubSecretFromFile($name, $tmp);
    }
    finally {
      @unlink($tmp);
    }
  }

  /**
   * Sets a GitHub repository secret from a file via stdin.
   */
  protected function setGithubSecretFromFile(string $name, string $path): bool {
    $cmd = sprintf(
      'gh secret set %s --repo %s < %s',
      escapeshellarg($name),
      escapeshellarg($this->getGithubProject()),
      escapeshellarg($path)
    );
    return $this->taskExec($cmd)->run()->wasSuccessful();
  }

  /**
   * Sets a GitHub repository (non-secret) variable.
   */
  protected function setGithubVariable(string $name, string $value): bool {
    $cmd = sprintf(
      'gh variable set %s --body %s --repo %s',
      escapeshellarg($name),
      escapeshellarg($value),
      escapeshellarg($this->getGithubProject())
    );
    return $this->taskExec($cmd)->run()->wasSuccessful();
  }

  /**
   * Bootstraps Drupal 8 in addition to Robo.
   *
   * @throws \Exception
   */
  public function __construct() {
    if (!file_exists('web/sites/default/settings.php')) {
      // Drupal is not yet initialized, do not attempt to boot.
      return;
    }
    try {
      chdir(__DIR__ . '/web');
      $request = Request::createFromGlobals();
      $kernel = DrupalKernel::createFromRequest($request, $GLOBALS['drupal_autoloader'], 'prod');
      $kernel->handle($request);
      chdir(__DIR__);
    }
    catch (\Exception $e) {
      // Do not fail, there are several commands that do not need Drupal.
      $this->yell($e->getMessage());
    }
  }

}
