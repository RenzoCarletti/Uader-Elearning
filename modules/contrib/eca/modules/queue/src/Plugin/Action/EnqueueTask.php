<?php

namespace Drupal\eca_queue\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca_queue\Task;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enqueue a Task.
 *
 * @Action(
 *   id = "eca_enqueue_task",
 *   label = @Translation("Enqueue a task")
 * )
 */
class EnqueueTask extends ConfigurableActionBase {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * The name of the queue.
   *
   * @var string
   */
  static protected string $queueName = 'eca_task';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_queue\Plugin\Action\EnqueueTask $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setQueueFactory($container->get('queue'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $task_name = $this->tokenServices->replaceClear($this->configuration['task_name']);
    $task_value = $this->tokenServices->replaceClear($this->configuration['task_value']);
    $task_not_before = $this->getEarliestProcessingTime();

    $data = [];
    $token_names = trim($this->configuration['tokens'] ?? '');
    if ($token_names !== '') {
      foreach (DataTransferObject::buildArrayFromUserInput($token_names) as $token_name) {
        $token_name = trim($token_name);
        if ($this->tokenServices->hasTokenData($token_name)) {
          $data[$token_name] = $this->tokenServices->getTokenData($token_name);
        }
      }
    }

    $task = new Task($task_name, $task_value, $data, $task_not_before);
    $queue = $this->queueFactory->get(static::$queueName, TRUE);
    $queue->createQueue();
    if (FALSE === $queue->createItem($task)) {
      throw new \RuntimeException(sprintf("Failed to create queue item for Task '%s' and value '%s' in queue '%s'.", $task->getTaskName(), $task->getTaskValue(), static::$queueName));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'task_name' => '',
      'task_value' => '',
      'tokens' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * Set the queue factory.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function setQueueFactory(QueueFactory $queue_factory): void {
    $this->queueFactory = $queue_factory;
  }

  /**
   * Get the delay in seconds for the task to be created.
   *
   * Can be overwritten by sub-classes, if the support delays.
   *
   * @return int
   *   The delay in seconds for the task to be created.
   */
  protected function getEarliestProcessingTime(): int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['task_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Task name'),
      '#description' => $this->t('When reacting upon the event "ECA processing queued task", you can use this name to identify the task.'),
      '#default_value' => $this->configuration['task_name'],
      '#weight' => -50,
    ];
    $form['task_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Task value'),
      '#description' => $this->t('You may optionally define a task value here for more granular task control.'),
      '#default_value' => $this->configuration['task_value'],
      '#weight' => -40,
    ];
    $form['tokens'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tokens to forward'),
      '#default_value' => $this->configuration['tokens'],
      '#description' => $this->t('Comma separated list of token names from the current context, that will be put into the task.'),
      '#weight' => -30,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['task_name'] = $form_state->getValue('task_name');
    $this->configuration['task_value'] = $form_state->getValue('task_value');
    $this->configuration['tokens'] = $form_state->getValue('tokens');
    parent::submitConfigurationForm($form, $form_state);
  }

}
