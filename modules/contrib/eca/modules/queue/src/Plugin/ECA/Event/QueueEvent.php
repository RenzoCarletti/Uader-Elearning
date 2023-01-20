<?php

namespace Drupal\eca_queue\Plugin\ECA\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_queue\Event\ProcessingTaskEvent;
use Drupal\eca_queue\QueueEvents;

/**
 * Plugin implementation for ECA Queue events.
 *
 * @EcaEvent(
 *   id = "eca_queue",
 *   deriver = "Drupal\eca_queue\Plugin\ECA\Event\QueueEventDeriver"
 * )
 */
class QueueEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'processing_task' => [
        'label' => 'ECA processing queued task',
        'event_name' => QueueEvents::PROCESSING_TASK,
        'event_class' => ProcessingTaskEvent::class,
        'tags' => Tag::RUNTIME,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    if ($this->eventClass() === ProcessingTaskEvent::class) {
      $values = [
        'task_name' => '',
        'task_value' => '',
      ];
    }
    else {
      $values = [];
    }
    return $values + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    if ($this->eventClass() === ProcessingTaskEvent::class) {
      $form['task_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Task name'),
        '#default_value' => $this->configuration['task_name'],
      ];
      $form['task_value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Task value (optional'),
        '#default_value' => $this->configuration['task_value'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    if ($this->eventClass() === ProcessingTaskEvent::class) {
      $this->configuration['task_name'] = $form_state->getValue('task_name');
      $this->configuration['task_value'] = $form_state->getValue('task_value');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    $configuration = $ecaEvent->getConfiguration();
    $argument_task_name = isset($configuration['task_name']) ? mb_strtolower(trim($configuration['task_name'])) : '';
    $argument_task_value = isset($configuration['task_value']) ? mb_strtolower(trim($configuration['task_value'])) : '';
    if ($argument_task_name === '') {
      return '*';
    }
    $wildcard = $argument_task_name;
    if ($argument_task_value !== '') {
      $wildcard .= '::' . $argument_task_value;
    }
    return $wildcard;
  }

}
