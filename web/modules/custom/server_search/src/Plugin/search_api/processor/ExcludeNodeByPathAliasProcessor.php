<?php

namespace Drupal\server_search\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor to exclude specific nodes from being indexed.
 *
 * @SearchApiProcessor(
 *   id = "exclude_nodes_by_path_alias",
 *   label = @Translation("Exclude nodes by path alias"),
 *   description = @Translation("Exclude specific nodes from being indexed given path alias."),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 *   locked = true,
 *   hidden = false,
 * )
 */
class ExcludeNodeByPathAliasProcessor extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected AliasManagerInterface $aliasManager;

  /**
   * The core language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->aliasManager = $container->get('path_alias.manager');
    $processor->languageManager = $container->get('language_manager');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'excluded_nodes' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    $excluded_nodes_array = $this->getConfiguration()['excluded_nodes'];
    if (empty($excluded_nodes_array)) {
      return;
    }

    foreach ($items as $id => $item) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $object */
      $object = $item->getOriginalObject()->getValue();

      if (!$object instanceof NodeInterface || $object->bundle() !== 'landing_page') {
        continue;
      }

      $languages = array_keys($this->languageManager->getLanguages());
      foreach ($languages as $lang_id) {
        // Get current node item path aliases for each language.
        $path_alias = $this->aliasManager->getAliasByPath('/node/' . $object->id(), $lang_id);
        // Check if the nodes' path alias is in the list of excluded aliases.
        if (in_array($path_alias, $excluded_nodes_array)) {
          unset($items[$id]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $excluded_nodes = $this->getConfiguration()['excluded_nodes'];
    $form['#description'] = $this->t('Excludes nodes from indexing given their path aliases.');

    $form['excluded_nodes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Node path aliases to exclude'),
      '#description' => $this->t('Enter the node path aliases to exclude from the search index one per line with leading slash (e.g. "/search" and not "search").'),
      '#default_value' => $excluded_nodes ? implode("\n", $excluded_nodes) : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $excluded_nodes = $form_state->getValue('excluded_nodes');

    // Split the textarea value by newline.
    $excluded_nodes_array = preg_split('/[\r\n]+/', $excluded_nodes);

    // Remove empty values.
    $excluded_nodes_array = array_filter($excluded_nodes_array);

    // Save each line as a separate item in the configuration array.
    $this->setConfiguration(['excluded_nodes' => $excluded_nodes_array]);
  }

}
