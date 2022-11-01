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
    $plugin_block = $this->blockManager->createInstance($block_id, $config);
    // Some blocks might implement access check.
    $access_result = $plugin_block->access($this->currentUser);
    // Return empty render array if user doesn't have access.
    // $access_result can be boolean or an AccessResult class.
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
      // You might need to add some cache tags/contexts.
      return [];
    }

    return $plugin_block->build();
  }

}
