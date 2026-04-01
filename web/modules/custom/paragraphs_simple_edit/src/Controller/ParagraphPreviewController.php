<?php

declare(strict_types=1);

namespace Drupal\paragraphs_simple_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
   * Loads the stored form input from PrivateTempStore (keyed by UUID).
   * For existing paragraphs the saved entity is loaded and the unsaved input
   * applied on top. For new (unsaved) paragraphs an empty entity of the
   * correct bundle is created so no database record is required.
   *
   * @param string $uuid
   *   The paragraph entity UUID used as the TempStore key.
   *
   * @return array
   *   Render array.
   */
  public function preview(string $uuid): array {
    $store = $this->tempStoreFactory->get('paragraphs_simple_edit');
    $data = $store->get('preview:' . $uuid);

    if (!$data) {
      throw new NotFoundHttpException();
    }

    $storage = $this->entityTypeManager()->getStorage('paragraph');

    if ($data['id']) {
      $paragraph = $storage->load($data['id']);
      if (!$paragraph instanceof ParagraphInterface) {
        throw new NotFoundHttpException();
      }
    }
    else {
      // New paragraph — create an unsaved entity of the correct bundle so the
      // PEVB plugin can render it without a database record.
      $paragraph = $storage->create(['type' => $data['bundle']]);
      if (!$paragraph instanceof ParagraphInterface) {
        throw new NotFoundHttpException();
      }
    }

    $paragraph = $this->applyInputToEntity($paragraph, $data['input']);

    $build = $this->entityTypeManager()
      ->getViewBuilder('paragraph')
      ->view($paragraph, 'full');

    // Remove cache keys so the renderer never returns a stale cached render
    // of the saved paragraph instead of our modified entity.
    $build['#cache'] = ['max-age' => 0];

    return $build;
  }

  /**
   * Applies raw POST input to a paragraph entity (recursively).
   *
   * Simple fields (text, link, etc.) are set directly from the raw value.
   * Entity reference revision fields (nested paragraphs) are processed
   * recursively: each saved child entity is cloned and its subform input
   * applied, so inline-edited children (e.g. Quick Link Items with
   * edit_mode: open) reflect the current unsaved state.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph to populate. Will be cloned — the original is not mutated.
   * @param array $input
   *   Raw POST values for this entity's level (top-level or subform).
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   A cloned entity with input applied.
   */
  private function applyInputToEntity(ParagraphInterface $paragraph, array $input): ParagraphInterface {
    $paragraph = clone $paragraph;

    foreach ($paragraph->getFields() as $field_name => $field_items) {
      $definition = $field_items->getFieldDefinition();

      if ($definition->isReadOnly() || !isset($input[$field_name])) {
        continue;
      }

      // File/image uploads are binary and not reproducible from POST text.
      // Keep the saved value from the clone.
      if (in_array($definition->getType(), ['file', 'image'])) {
        continue;
      }

      switch ($definition->getType()) {
        case 'entity_reference':
          $this->applyEntityReferenceField($paragraph, $field_name, $input[$field_name]);
          break;

        case 'link':
          $this->applyLinkField($paragraph, $field_name, $input[$field_name]);
          break;

        case 'entity_reference_revisions':
          $this->applyEntityReferenceRevisionsField($paragraph, $field_name, $field_items, $input[$field_name]);
          break;

        default:
          try {
            $paragraph->set($field_name, $input[$field_name]);
          }
          catch (\Exception $e) {
            // Field type doesn't accept raw POST format — keep saved value.
          }
      }
    }

    return $paragraph;
  }

  /**
   * Applies a raw POST value to an entity_reference field.
   *
   * Normalises widget-specific formats (media library, Select2, autocomplete)
   * to ['target_id' => int] items before calling set().
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity to update.
   * @param string $field_name
   *   The field name.
   * @param array|string $field_input
   *   Raw POST value for this field.
   */
  private function applyEntityReferenceField(ParagraphInterface $paragraph, string $field_name, array|string $field_input): void {
    $items = $this->extractEntityReferenceItems($field_input);
    if ($items === NULL) {
      return;
    }
    try {
      $paragraph->set($field_name, $items);
    }
    catch (\Exception $e) {
      // Keep saved value.
    }
  }

  /**
   * Applies a raw POST value to a link field.
   *
   * Raw POST contains the autocomplete display label "Title (id)" for entity
   * links. Replicates LinkWidget::getUserEnteredStringAsUri() so the entity is
   * resolvable at render time. Also strips double quotes that the widget may
   * wrap around the URI in raw POST.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity to update.
   * @param string $field_name
   *   The field name.
   * @param mixed $field_input
   *   Raw POST value for this field.
   */
  private function applyLinkField(ParagraphInterface $paragraph, string $field_name, mixed $field_input): void {
    if (!is_array($field_input)) {
      return;
    }
    $link_items = [];
    foreach ($field_input as $delta => $item) {
      if (!is_numeric($delta) || !is_array($item)) {
        continue;
      }
      $item['uri'] = $this->normaliseLinkUri($item['uri'] ?? '');
      $link_items[] = $item;
    }
    if (empty($link_items)) {
      return;
    }
    try {
      $paragraph->set($field_name, $link_items);
    }
    catch (\Exception $e) {
      // Keep saved value.
    }
  }

  /**
   * Converts a raw POST link URI to a valid Drupal URI.
   *
   * Mirrors LinkWidget::getUserEnteredStringAsUri():
   * - Strips surrounding double quotes added by the widget in raw POST.
   * - Entity autocomplete "Title (entity_id)" → entity:node/entity_id.
   * - Schemeless paths → internal:/path.
   *
   * @param string $uri
   *   Raw URI string from POST data.
   *
   * @return string
   *   A URI with a valid scheme.
   */
  private function normaliseLinkUri(string $uri): string {
    $uri = trim($uri, '"');

    if (empty($uri) || parse_url($uri, PHP_URL_SCHEME) !== NULL) {
      return $uri;
    }

    if (preg_match('/.+\s\(([^)]+)\)$/', $uri, $matches)) {
      // Entity autocomplete "Title (entity_id)" → entity:node/id.
      // Core LinkWidget also hardcodes node (drupal.org/node/2423093).
      return 'entity:node/' . $matches[1];
    }

    return 'internal:/' . ltrim($uri, '/');
  }

  /**
   * Applies a raw POST value to an entity_reference_revisions field.
   *
   * Iterates saved child paragraphs by delta and recursively applies their
   * subform input. New or reordered items are not handled — saved order is
   * kept.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity to update.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\Core\Field\FieldItemListInterface $field_items
   *   The current field items (used to load referenced children).
   * @param mixed $field_input
   *   Raw POST value for this field.
   */
  private function applyEntityReferenceRevisionsField(ParagraphInterface $paragraph, string $field_name, mixed $field_items, mixed $field_input): void {
    if (!is_array($field_input)) {
      return;
    }
    $children = [];
    foreach ($field_items->referencedEntities() as $delta => $child) {
      if (!($child instanceof ParagraphInterface)) {
        continue;
      }
      $item_input = $field_input[$delta] ?? NULL;
      if ($item_input && isset($item_input['subform'])) {
        $child = $this->applyInputToEntity($child, $item_input['subform']);
      }
      $children[] = $child;
    }
    if (!empty($children)) {
      $paragraph->set($field_name, $children);
    }
  }

  /**
   * Extracts entity reference target IDs from widget-specific POST data.
   *
   * Returns an array of ['target_id' => int] items ready for set(), or NULL
   * if the format is unrecognised (caller keeps the saved field value).
   * An empty array means the user explicitly cleared the field.
   *
   * Supported widget formats:
   * - Media library (media_library_widget): field[selection][N][target_id]
   *   (presence of field[media_library_selection] key identifies this widget)
   * - Select2 (entity_reference_select2): JSON string with entity_id keys
   * - Standard entity autocomplete: field[N][target_id]
   *
   * @param array|string $field_input
   *   The raw POST data for a single entity reference field.
   *
   * @return array<array<string, int>>|null
   *   Normalised items or NULL if the format is unknown.
   */
  private function extractEntityReferenceItems(array|string $field_input): ?array {
    // Select2 widget: submits a JSON-encoded string with entity_id keys.
    if (is_string($field_input)) {
      $decoded = json_decode($field_input, TRUE);
      if (!is_array($decoded)) {
        return NULL;
      }
      $items = [];
      foreach ($decoded as $item) {
        if (isset($item['entity_id']) && is_numeric($item['entity_id'])) {
          $items[] = ['target_id' => (int) $item['entity_id']];
        }
      }
      return $items ?: NULL;
    }

    // Media library widget: selection is stored under a 'selection' sub-array
    // and the presence of 'media_library_selection' marks this widget format.
    if (array_key_exists('media_library_selection', $field_input)) {
      $selection = $field_input['selection'] ?? [];
      if (empty($selection)) {
        return [];
      }
      $items = [];
      foreach ($selection as $item) {
        if (isset($item['target_id']) && is_numeric($item['target_id'])) {
          $items[] = ['target_id' => (int) $item['target_id']];
        }
      }
      return $items;
    }

    // Standard delta-indexed format: field[N][target_id] = int.
    $items = [];
    foreach ($field_input as $delta => $item) {
      if (is_numeric($delta) && is_array($item) && isset($item['target_id']) && is_numeric($item['target_id'])) {
        $items[] = ['target_id' => (int) $item['target_id']];
      }
    }
    // Return NULL for unknown formats so the saved value is preserved.
    return empty($items) ? NULL : $items;
  }

}
