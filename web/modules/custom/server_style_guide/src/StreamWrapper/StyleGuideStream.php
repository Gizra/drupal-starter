<?php

declare(strict_types=1);

namespace Drupal\server_style_guide\StreamWrapper;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StreamWrapper\LocalReadOnlyStream;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the server-style-guide:// stream wrapper.
 *
 * Provides read-only access to images bundled within the server_style_guide
 * module, allowing Drupal image styles to process them without requiring files
 * to be in public:// or private://.
 */
class StyleGuideStream extends LocalReadOnlyStream {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Style guide module files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Read-only image files bundled with the server_style_guide module.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath(): string {
    // Two levels up from src/StreamWrapper/ lands at the module root.
    return dirname(__DIR__, 2) . '/images';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl(): string {
    global $base_url;
    $path = str_replace('\\', '/', $this->getTarget());
    $path = UrlHelper::encodePath($path);
    $module_path = \Drupal::service('extension.list.module')->getPath('server_style_guide');
    return "{$base_url}/{$module_path}/images/$path";
  }

}
