<?php

declare(strict_types=1);

namespace Drupal\paragraphs_simple_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a paragraph preview using the frontend theme.
 */
final class ParagraphPreviewController extends ControllerBase {

  /**
   * Constructs a ParagraphPreviewController.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The private temp store factory.
   */
  public function __construct(protected readonly PrivateTempStoreFactory $tempStoreFactory) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('tempstore.private'),
    );
  }

  /**
   * Renders the paragraph preview.
   *
   * Uses the unsaved entity stored in PrivateTempStore when available,
   * falling back to the saved entity from the database.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return array
   *   Render array.
   */
  public function preview(ParagraphInterface $paragraph): array {
    $store = $this->tempStoreFactory->get('paragraphs_simple_edit');
    $input = $store->get('preview:' . $paragraph->id());

    if ($input) {
      // Clone so we never mutate the entity in the static cache.
      $paragraph = clone $paragraph;
      foreach ($paragraph->getFields() as $field_name => $field_items) {
        if ($field_items->getFieldDefinition()->isReadOnly() || !isset($input[$field_name])) {
          continue;
        }
        try {
          $paragraph->set($field_name, $input[$field_name]);
        }
        catch (\Exception $e) {
          // Some field types (e.g. computed or entity-reference fields with
          // a non-trivial widget) may not accept the raw POST format — skip.
        }
      }
    }

    $build = $this->entityTypeManager()
      ->getViewBuilder('paragraph')
      ->view($paragraph, 'full');

    // Remove cache keys so the renderer never returns a stale cached render
    // of the saved paragraph instead of our modified entity.
    $build['#cache'] = ['max-age' => 0];

    return $build;
  }

}
