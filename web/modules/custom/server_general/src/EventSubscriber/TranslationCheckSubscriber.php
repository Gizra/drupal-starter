<?php

declare(strict_types=1);

namespace Drupal\server_general\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles graceful redirects for translation-related edge cases.
 */
class TranslationCheckSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct(
    protected RouteMatchInterface $currentRouteMatch,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => [
        ['redirectIfTranslationExists', 10],
      ],
    ];
  }

  /**
   * Redirects gracefully when the target translation already exists.
   *
   * Prevents a WSOD when a user accesses
   * /node/[nid]/translations/add/en/[langcode] for an already-translated node.
   */
  public function redirectIfTranslationExists(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    if ($this->currentRouteMatch->getRouteName() !== 'entity.node.content_translation_add') {
      return;
    }

    $node = $this->currentRouteMatch->getParameter('node');
    if (!$node instanceof NodeInterface) {
      return;
    }

    /** @var \Drupal\Core\Language\LanguageInterface|null $target */
    $target = $this->currentRouteMatch->getParameter('target');
    if (!$target || !$node->hasTranslation($target->getId())) {
      return;
    }

    $this->messenger->addWarning($this->t('A @language translation of %title already exists.', [
      '@language' => $target->getName(),
      '%title' => $node->label(),
    ]));

    $overview_url = Url::fromRoute('entity.node.content_translation_overview', ['node' => $node->id()]);
    $event->setResponse(new RedirectResponse($overview_url->toString()));
  }

}
