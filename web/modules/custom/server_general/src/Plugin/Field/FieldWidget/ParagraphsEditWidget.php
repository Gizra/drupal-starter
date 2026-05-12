<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;

/**
 * Plugin implementation of the 'paragraphs_edit' paragraphs widget.
 *
 * Extends the core ParagraphsWidget to add an "Edit standalone" button
 * that opens the paragraph edit form in a separate page.
 *
 * @FieldWidget(
 *   id = "paragraphs_edit",
 *   label = @Translation("Paragraphs (edit in new tab)"),
 *   description = @Translation("Paragraphs widget with edit in new tab option."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
#[FieldWidget(
  id: 'paragraphs_edit',
  label: new TranslatableMarkup('Paragraphs (edit in new tab)'),
  description: new TranslatableMarkup('Paragraphs widget with edit in new tab option.'),
  field_types: ['entity_reference_revisions']
)]
class ParagraphsEditWidget extends ParagraphsWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];

    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    if (!isset($widget_state['paragraphs'][$delta])) {
      return $element;
    }

    $paragraphs_entity = $widget_state['paragraphs'][$delta]['entity'] ?? NULL;
    if (!$paragraphs_entity) {
      return $element;
    }

    $host = $items->getEntity();
    $item_mode = $widget_state['paragraphs'][$delta]['mode'] ?? 'edit';

    if ($item_mode === 'closed' || $item_mode === 'convert') {
      $edit_new_tab_url = Url::fromRoute('paragraphs_edit.edit_form', [
        'root_parent_type' => $host->getEntityTypeId(),
        'root_parent' => $host->id(),
        'paragraph' => $paragraphs_entity->id(),
      ]);

      $edit_new_tab_url->setOption('query', [
        'destination' => \Drupal::request()->getRequestUri(),
      ]);

      if (!isset($element['top']['actions']['dropdown_actions'])) {
        $element['top']['actions']['dropdown_actions'] = [];
      }

      $element['top']['actions']['dropdown_actions']['edit_new_tab_button'] = [
        '#type' => 'link',
        '#title' => $this->t('Edit standalone'),
        '#url' => $edit_new_tab_url,
        '#attributes' => [
          'class' => ['button', 'button--small', 'paragraphs-dropdown-action'],
        ],
        '#access' => $paragraphs_entity->access('update'),
        '#weight' => 0,
      ];
    }

    return $element;
  }

}
