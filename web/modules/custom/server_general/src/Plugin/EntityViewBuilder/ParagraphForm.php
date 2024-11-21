<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\FormsEmbedTrait;

/**
 * The "Form" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.form",
 *   label = @Translation("Paragraph - Form"),
 *   description = "Paragraph view builder for 'Forms from Webform' bundle."
 * )
 */
class ParagraphForm extends EntityViewBuilderPluginAbstract {

  use FormsEmbedTrait;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected EntityFormBuilder $entityFormBuilder;

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\paragraphs\ParagraphInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, ParagraphInterface $entity): array {
    return $this->buildWebformEmbed($entity, $build, 'field_webform', 'field_title', 'field_description');
  }

}
