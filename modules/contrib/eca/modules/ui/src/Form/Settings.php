<?php

namespace Drupal\eca_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure ECA settings for this site.
 */
class Settings extends ConfigFormBase {

  /**
   * The default documentation domain.
   *
   * @var string|null
   */
  protected ?string $defaultDocumentationDomain;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->defaultDocumentationDomain = $container->getParameter('eca.default_documentation_domain');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eca_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['eca.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('eca.settings');
    $form['log_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Log level'),
      '#options' => RfcLogLevel::getLevels(),
      '#default_value' => $config->get('log_level'),
      '#weight' => -20,
    ];
    if ($this->defaultDocumentationDomain) {
      $form['documentation_domain'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Documentation domain'),
        '#description' => $this->t('This domain is used for creating links to further documentation resources about ECA plugins. The official documentation resource is <a href=":url" target="_blank" rel="noreferrer nofollow">:url</a>. Leave blank to disable documentation links at all.', [':url' => $this->defaultDocumentationDomain]),
        '#default_value' => $config->get('documentation_domain'),
        '#weight' => -10,
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Config\Config $config */
    if ($config = $this->config('eca.settings')) {
      $config->set('log_level', $form_state->getValue('log_level'));
      if ($this->defaultDocumentationDomain) {
        $config->set('documentation_domain', $form_state->getValue('documentation_domain'));
      }
      $config->save();
    }
    parent::submitForm($form, $form_state);
  }

}
