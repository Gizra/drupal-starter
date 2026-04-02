<?php

declare(strict_types=1);

namespace Drupal\server_ai_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\server_ai_content\Service\ContentGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for generating AI content.
 */
final class AiContentGenerateForm extends FormBase {

  /**
   * The content generator service.
   *
   * @var \Drupal\server_ai_content\Service\ContentGenerator
   */
  protected ContentGenerator $contentGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->contentGenerator = $container->get('server_ai_content.content_generator');
    return $instance;
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Describe the landing page you want to create'),
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

    try {
      $node = $this->contentGenerator->generate($prompt, 'landing_page');
      $this->messenger()->addStatus($this->t('Landing page "@title" has been generated as a draft.', [
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
