<?php

namespace Drupal\server_general;

/**
 * Embed configurable Block.
 *
 * @property \Drupal\Core\Block\BlockManager $blockManager
 *
 * To use this trait it is assumed above services are present. You may use the
 * following `create` method in your PEVB plugin, in order to have them.
 *
 * @code
 * public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
 *   $build = parent::create($container, $configuration, $plugin_id, $plugin_definition);
 *   $build->blockManager = $container->get('plugin.manager.block');
 *
 *   return $build;
 * }
 * @endcode
 */
trait EmbedBlockTrait {

  /**
   * Embeds a block into the build.
   *
   * @param string $block_id
   *   The block ID.
   * @param array $config
   *   Configuration array for the block. Default is empty.
   *
   * @return array
   *   The render array.
   */
  protected function embedBlock(string $block_id, array $config = []) {
    return \Drupal::service('block_plugin.view_builder')->view($block_id, $config);
  }

}
