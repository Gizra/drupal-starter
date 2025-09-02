<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\server_general\SocialShareTrait;

/**
 * Helper trait to build the node group elements.
 */
trait ElementNodeGroupThemeTrait {

  use LineSeparatorThemeTrait;
  use SocialShareTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * Build the node group element.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   * @param array $element
   *   An element to render for group.
   * @param array $elements
   *   An array of elements to add element into.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementNodeGroup(NodeInterface $entity, array $element, array $elements): array {
    // Add group title as page title.
    $elements[] = $this->buildPageTitle($entity->getTitle());
    // Show the body text.
    $elements[] = $this->buildProcessedText($entity);
    // Add line separator for better UX.
    $elements[] = $this->buildLineSeparator();
    // Wrap element text for consistency.
    $elements[] = $this->wrapProseText($element);
    // Add line separator for better UX.
    $elements[] = $this->buildLineSeparator();
    // Add social share icons.
    $elements[] = $this->buildSocialShare($entity);
    // Better vertical spacing for elements.
    $elements = $this->wrapContainerVerticalSpacing($elements);
    // Use wide container.
    $elements = $this->wrapContainerWide($elements);
    // Add padding to bottom & return.
    return $this->wrapContainerBottomPadding($elements);
  }

  /**
   * Prepare element using inline template.
   *
   * @param string|TranslatableMarkup $content
   *   The content to show.
   * @param \Drupal\Core\Url $url
   *   The url used for the link in content.
   * @param string|TranslatableMarkup $text
   *   The text used for the link in content.
   * @param array $context
   *   An array of replacements used in content.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementGroupText(string|TranslatableMarkup $content, Url $url, string|TranslatableMarkup $text, array $context = []) {

    $link = Link::fromTextAndUrl($text, $url)->toRenderable();
    // Add the link into context.
    $context['link'] = $link;
    $element = [
      '#type' => 'inline_template',
      '#template' => $content,
      '#context' => $context,
    ];
    return $element;
  }

}
