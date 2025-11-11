<?php

namespace Drupal\paragraphs_simple_edit\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'paragraphs_simple_edit_default' widget.
 */
#[FieldWidget(
  id: 'paragraphs_simple_edit_default',
  label: new TranslatableMarkup('Simple Edit (Default)'),
  description: new TranslatableMarkup('The paragraphs form that allows editing, adding, and deleting paragraphs on dedicated pages.'),
  field_types: ['entity_reference_revisions']
)]
final class ParagraphsSimpleEditDefaultWidget extends ParagraphsWidget {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The theme manager.
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * The redirect destination.
   */
  protected RedirectDestinationInterface $redirectDestination;

  /**
   * The settings that are no longer necessary.
   */
  protected static array $unusedSettings = [
    'autocollapse' => 'none',
    'closed_mode_threshold' => 0,
    'add_mode' => 'dropdown',
  ];

  /**
   * Constructs a ParagraphsSimpleEditDefaultWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, ThemeManagerInterface $theme_manager, RedirectDestinationInterface $redirect_destination) {
    $this->entityTypeManager = $entity_type_manager;
    $this->themeManager = $theme_manager;
    $this->redirectDestination = $redirect_destination;
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $entity_field_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('theme.manager'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    // Set default values for unused settings. We are not removing these
    // to avoid any problems when parent widget is being used.
    foreach (static::$unusedSettings as $element_key => $element_value) {
      $defaults[$element_key] = $element_value;
    }
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    // Instead of removing these elements, we are just hiding those
    // to avoid any problems when parent widget is being used.
    foreach (static::$unusedSettings as $element_key => $element_value) {
      $elements[$element_key]['#access'] = FALSE;
    }

    // Form display mode is only useful when edit_mode is open.
    if (!empty($elements['form_display_mode'])) {
      $elements['form_display_mode']['#states'] = [
        'visible' => [
          'select[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][edit_mode]"]' => ['value' => 'open'],
        ],
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Title: @title', ['@title' => $this->getSetting('title')]);
    $summary[] = $this->t('Plural title: @title_plural', [
      '@title_plural' => $this->getSetting('title_plural'),
    ]);

    $edit_mode = $this->getSettingOptions('edit_mode')[$this->getSetting('edit_mode')];
    $closed_mode = $this->getSettingOptions('closed_mode')[$this->getSetting('closed_mode')];

    $summary[] = $this->t('Edit mode: @edit_mode', ['@edit_mode' => $edit_mode]);
    $summary[] = $this->t('Closed mode: @closed_mode', ['@closed_mode' => $closed_mode]);

    if ($this->getSetting('edit_mode') == 'open') {
      $summary[] = $this->t('Form display mode: @form_display_mode', [
        '@form_display_mode' => $this->getSetting('form_display_mode'),
      ]);
    }

    if ($this->getDefaultParagraphTypeLabelName() !== NULL) {
      $summary[] = $this->t('Default paragraph type: @default_paragraph_type', [
        '@default_paragraph_type' => $this->getDefaultParagraphTypeLabelName(),
      ]);
    }
    $features_labels = array_intersect_key($this->getSettingOptions('features'), array_filter($this->getSetting('features')));
    if (!empty($features_labels)) {
      $summary[] = $this->t('Features: @features', ['@features' => implode(', ', $features_labels)]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
    $item = $items[$delta];
    $paragraph = $item->entity;
    if (!$paragraph || $paragraph->isNew()) {
      return $element;
    }

    $host = $items->getEntity();

    if (!$host->id()) {
      // Edit and Delete links require saved paragraph entities with IDs.
      // For new host entities, paragraphs are created on form submission.
      return $element;
    }

    // Links shouldn't be there when edit mode is set to open.
    $edit_mode = $this->getSetting('edit_mode');
    if ($edit_mode == 'open') {
      return $element;
    }

    $destination = $this->redirectDestination->getAsArray();

    $edit_url = Url::fromRoute('paragraphs_edit.edit_form', [
      'root_parent_type' => $host->getEntityTypeId(),
      'root_parent' => $host->id(),
      'paragraph' => $paragraph->id(),
    ],
    [
      'query' => $destination,
    ]);

    $delete_url = Url::fromRoute('paragraphs_edit.delete_form', [
      'root_parent_type' => $host->getEntityTypeId(),
      'root_parent' => $host->id(),
      'paragraph' => $paragraph->id(),
    ],
    [
      'query' => $destination,
    ]);

    $element['top']['actions']['actions'] = [
      '#type' => 'dropbutton',
      '#dropbutton_type' => 'extrasmall',
      '#links' => [
        'edit' => [
          'title' => $this->t('Edit'),
          'url' => $edit_url,
        ],
        'delete' => [
          'title' => $this->t('Delete'),
          'url' => $delete_url,
        ],
      ],
      '#weight' => 10,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    $field_name = $this->fieldDefinition->getName();

    if (!isset($elements['add_more'])) {
      return $elements;
    }

    $host = $items->getEntity();

    if (!$host->id()) {
      // Add links require host entity ids.
      return $elements;
    }

    $bundle_fields = $this->entityFieldManager
      ->getFieldDefinitions($items->getEntity()->getEntityTypeId(), $items->getEntity()->bundle());

    if (!isset($bundle_fields[$field_name])) {
      return $elements;
    }

    $field_config = $bundle_fields[$field_name];
    $handler_settings = $field_config->getSetting('handler_settings');
    $target_bundles = $handler_settings['target_bundles'] ?? [];

    if (empty($target_bundles)) {
      $paragraph_types = $this->entityTypeManager
        ->getStorage('paragraphs_type')
        ->loadMultiple();
      $target_bundles = array_keys($paragraph_types);
    }

    $destination = $this->redirectDestination->getAsArray();

    $add_links = [];
    foreach ($target_bundles as $bundle) {
      $paragraph_type = $this->entityTypeManager
        ->getStorage('paragraphs_type')
        ->load($bundle);

      if (!$paragraph_type) {
        continue;
      }

      $add_links[$bundle] = [
        'title' => $this->t('Add @type', ['@type' => $paragraph_type->label()]),
        'url' => Url::fromRoute('paragraphs_modal_add.add_form', [
          'root_parent_type' => $host->getEntityTypeId(),
          'root_parent' => $host->id(),
          'parent_field_name' => $field_name,
          'paragraphs_type' => $bundle,
        ],
        [
          'query' => $destination,
        ]),
      ];
    }

    if (empty($add_links)) {
      return $elements;
    }

    $elements['add_more'] = [
      '#type' => 'dropbutton',
      '#dropbutton_type' => 'extrasmall',
      '#links' => $add_links,
      '#attributes' => [
        'class' => ['paragraph-simple-edit--add-button'],
      ],
    ];

    // Add css for claro theme to fix styling for add button.
    if ($this->themeManager->getActiveTheme()->getName() == 'claro') {
      $elements['add_more']['#attached']['library'][] = 'paragraphs_simple_edit/widget.claro';
    }

    return $elements;
  }

}
