<?php

namespace Drupal\moodle_rest_user\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Moodle User Integration settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'moodle_rest_user_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['moodle_rest_user.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $account_fields = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    foreach ($account_fields as $field_name => $field_definition) {
      $field_options[$field_name] = $field_definition->getLabel();
    }
    $form['associate_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Associate'),
    ];
    $form['associate_wrapper']['associate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Associate'),
      '#description' => $this->t('Attempt to associate Drupal users with Moodle users. By default using email address'),
      '#default_value' => $this->config('moodle_rest_user.settings')->get('associate'),
    ];
    $form['associate_wrapper']['moodle_id_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Moodle ID'),
      '#description' => $this->t('The field to store the Moodle internal ID. Used for association and most mapped operations'),
      '#default_value' => $this->config('moodle_rest_user.settings')->get('moodle_id_field'),
      '#options' => $field_options,
    ];

    $form['push'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Push'),
    ];
    $form['push']['create'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create'),
      '#description' => $this->t('Create new Moodle users when a new Drupal user is created.'),
      '#default_value' => $this->config('moodle_rest_user.settings')->get('create'),
    ];
    $form['push']['update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update'),
      '#description' => $this->t('Send mapped fields to associated Moodle Users when a Drupal use is updated.'),
      '#default_value' => $this->config('moodle_rest_user.settings')->get('update'),
    ];
    $form['push']['delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete'),
      '#description' => $this->t('Delete associated Moodle users when a Drupal account is deleted.'),
      '#default_value' => $this->config('moodle_rest_user.settings')->get('delete'),
    ];
    if (empty($user_input['push_fields'])) {
      $push_fields = $this->config('moodle_rest_user.settings')->get('push_fields');
    }
    else {
      $push_fields = $user_input['push_fields'];
    }
    $form['push']['push_fields'] = [
      '#type' => 'table',
      '#caption' => $this->t('Field mapping'),
      '#header' => [$this->t('Drupal'), $this->t('Moodle')],
      '#attributes' => ['id' => 'push-fields-table'],
    ];
    foreach ($push_fields as $delta => $mapping) {
      $form['push']['push_fields'][$delta] = [
        'drupal' => [
          '#type' => 'textfield',
          '#title' => $this->t('Drupal field'),
          '#title_display' => 'invisible',
          '#default_value' => $mapping['drupal'],
        ],
        'moodle' => [
          '#type' => 'textfield',
          '#title' => $this->t('Moodle field'),
          '#title_display' => 'invisible',
          '#default_value' => $mapping['moodle'],
        ],
      ];
    }
    $form['push']['push_add_row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add push row'),
      '#limit_validation_errors' => [],
      '#submit' => ['::addPushRow'],
      '#ajax' => [
        'callback' => [$this, 'refreshPush'],
        'event' => 'click',
        'disable-refocus' => TRUE,
        'wrapper' => 'push-fields-table',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['pull'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pull'),
    ];
    $form['pull']['login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('On Login'),
      '#description' => $this->t('Pull associated Drupal users fields from Moodle on log in.'),
      '#default_value' => $this->config('moodle_rest_user.settings')->get('pull.login'),
    ];
    $form['pull']['edit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Before edit'),
      '#description' => $this->t('Pull associated Drupal users fields from Moodle as user edit page loaded.'),
      '#default_value' => $this->config('moodle_rest_user.settings')->get('pull.edit'),
    ];
    $form['pull']['view'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Before view'),
      '#description' => $this->t('Pull associated Drupal users fields from Moodle as user page loaded.'),
      '#default_value' => $this->config('moodle_rest_user.settings')->get('pull.view'),
    ];
    // Todo add configuration about how many users how often or something to
    // cron.
    $form['pull']['cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cron'),
      '#description' => $this->t('Pull associated Drupal users fields from Moodle regularly using cron.'),
      '#default_value' => $this->config('moodle_rest_user.settings')->get('pull.cron'),
    ];
    if (empty($user_input['pull_fields'])) {
      $pull_fields = $this->config('moodle_rest_user.settings')->get('pull_fields');
    }
    else {
      $pull_fields = $user_input['pull_fields'];
    }
    $form['pull']['pull_fields'] = [
      '#type' => 'table',
      '#caption' => $this->t('Field mapping'),
      '#header' => [$this->t('Drupal'), $this->t('Moodle')],
      '#attributes' => ['id' => 'pull-fields-table'],
    ];
    foreach ($pull_fields as $delta => $mapping) {
      $form['pull']['pull_fields'][$delta] = [
        'drupal' => [
          '#type' => 'textfield',
          '#title' => $this->t('Drupal field'),
          '#title_display' => 'invisible',
          '#default_value' => $mapping['drupal'],
        ],
        'moodle' => [
          '#type' => 'textfield',
          '#title' => $this->t('Moodle field'),
          '#title_display' => 'invisible',
          '#default_value' => $mapping['moodle'],
        ],
      ];
    }
    $form['pull']['pull_add_row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add pull row'),
      '#limit_validation_errors' => [],
      '#submit' => ['::addPullRow'],
      '#ajax' => [
        'callback' => [$this, 'refreshPull'],
        'event' => 'click',
        'disable-refocus' => TRUE,
        'wrapper' => 'pull-fields-table',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback: ::buildForm.
   */
  public function refreshPush(array &$form, FormStateInterface $form_state) {
    return $form['push']['push_fields'];
  }

  /**
   * Submit handler.
   *
   * Add an empty row to the mapping.
   */
  public function addPushRow(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $user_input['push_fields'][] = [
      'drupal' => '',
      'moodle' => '',
    ];
    $form_state->setUserInput($user_input);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback: ::buildForm.
   */
  public function refreshPull(array &$form, FormStateInterface $form_state) {
    return $form['pull']['pull_fields'];
  }

  /**
   * Submit handler.
   *
   * Add an empty row to the mapping.
   */
  public function addPullRow(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $user_input['pull_fields'][] = [
      'drupal' => '',
      'moodle' => '',
    ];
    $form_state->setUserInput($user_input);
    $form_state->setRebuild();
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pull_fields = array_filter($form_state->getValue('pull_fields'), function ($fields) {
      return $fields['drupal'] != '' && $fields['moodle'] != '';
    });
    $push_fields = array_filter($form_state->getValue('push_fields'), function ($fields) {
      return $fields['drupal'] != '' && $fields['moodle'] != '';
    });
    $this->config('moodle_rest_user.settings')
      ->set('associate', $form_state->getValue('associate'))
      ->set('moodle_id_field', $form_state->getValue('moodle_id_field'))
      ->set('create', $form_state->getValue('create'))
      ->set('update', $form_state->getValue('update'))
      ->set('delete', $form_state->getValue('delete'))
      ->set('pull', [
        'login' => $form_state->getValue('login'),
        'edit' => $form_state->getValue('edit'),
        'view' => $form_state->getValue('view'),
        'cron' => $form_state->getValue('cron'),
      ])
      ->set('push_fields', $push_fields)
      ->set('pull_fields', $pull_fields)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
