<?php

declare(strict_types=1);

namespace Drupal\server_ai_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\server_ai_content\Service\ContentGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for generating AI content.
 */
final class AiContentGenerateForm extends FormBase {

  /**
   * Constructs an AiContentGenerateForm.
   */
  public function __construct(
    protected ContentGenerator $contentGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('server_ai_content.content_generator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'server_ai_content_generate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeTypeInterface $node_type = NULL): array {
    if (!$node_type) {
      $this->messenger()->addError($this->t('Content type is required.'));
      return $form;
    }

    $form['node_type'] = [
      '#type' => 'value',
      '#value' => $node_type->id(),
    ];

    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Describe the @type you want to create', [
        '@type' => $node_type->label(),
      ]),
      '#description' => $this->t('Example: "Create a landing page for the 2026 New Year celebration. Include a hero section, event details, FAQ section, and a call to action for registration."'),
      '#required' => TRUE,
      '#rows' => 5,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $prompt = $form_state->getValue('prompt');
    $content_type = $form_state->getValue('node_type');

    try {
      $node = $this->contentGenerator->generate($prompt, $content_type);
      $this->messenger()->addStatus($this->t('"@title" has been generated as a draft.', [
        '@title' => $node->getTitle(),
      ]));
      $form_state->setRedirectUrl($node->toUrl());
    }
    catch (\Throwable $e) {
      $this->messenger()->addError($this->t('Content generation failed: @message', [
        '@message' => $e->getMessage(),
      ]));
      $this->getLogger('server_ai_content')->error('AI content generation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
