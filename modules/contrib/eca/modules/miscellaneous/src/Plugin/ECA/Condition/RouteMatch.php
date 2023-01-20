<?php

namespace Drupal\eca_misc\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\StringComparisonBase;
use Drupal\eca_misc\Plugin\RouteInterface;
use Drupal\eca_misc\Plugin\RouteTrait;

/**
 * Plugin implementation of the ECA condition for entity type and bundle.
 *
 * @EcaCondition(
 *   id = "eca_route_match",
 *   label = "Route match"
 * )
 */
class RouteMatch extends StringComparisonBase {

  use RouteTrait;

  /**
   * {@inheritdoc}
   */
  protected function getLeftValue(): string {
    return $this->getRouteMatch()->getRouteName() ?? '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRightValue(): string {
    return $this->configuration['route'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'request' => RouteInterface::ROUTE_CURRENT,
      'route' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $this->requestFormField($form);
    $form['route'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Route name'),
      '#default_value' => $this->configuration['route'],
      '#weight' => -70,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['request'] = $form_state->getValue('request');
    $this->configuration['route'] = $form_state->getValue('route');
    parent::submitConfigurationForm($form, $form_state);
  }

}
