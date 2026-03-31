<?php

declare(strict_types=1);

namespace Drupal\paragraphs_simple_edit\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command: activate the preview panel and load the preview iframe.
 */
class ActivatePreviewPanelCommand implements CommandInterface {

  /**
   * Constructs the command.
   *
   * @param string $previewUrl
   *   The URL to load inside the preview iframe.
   */
  public function __construct(protected readonly string $previewUrl) {}

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      'command' => 'pseActivatePreviewPanel',
      'previewUrl' => $this->previewUrl,
    ];
  }

}
