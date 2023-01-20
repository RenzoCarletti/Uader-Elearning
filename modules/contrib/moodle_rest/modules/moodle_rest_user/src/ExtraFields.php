<?php

namespace Drupal\moodle_rest_user;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\moodle_rest\Services\MoodleRestException;
use Drupal\moodle_rest\Services\RestFunctions;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds psudeo fields for related user course moodle data.
 */
class ExtraFields implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Moodle REST functions service.
   *
   * @var \Drupal\moodle_rest\Services\RestFunctions
   */
  protected $moodle;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Current Route Match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * DirectoryExtraFieldDisplay constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\moodle_rest\Services\RestFunctions $moodle
   *   Moodle REST functions service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Current Route Match.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RestFunctions $moodle, AccountProxyInterface $current_user, RouteMatchInterface $route_match, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moodle = $moodle;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('moodle_rest.rest_functions'),
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('module_handler')
    );
  }

  /**
   * Gets the "extra fields" for a bundle.
   *
   * @see hook_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {
    $fields = [];
    if ($this->moduleHandler->moduleExists('moodle_rest_course')) {
      $fields['node']['moodle_course']['display']['moodle_user_completion'] = [
        'label' => $this->t('User course completion'),
        'description' => $this->t("Current user course completion progress."),
        'visible' => FALSE,
      ];
    }

    return $fields;
  }

  /**
   * Adds view with arguments to view render array if required.
   *
   * @see moodle_rest_user_node_view()
   */
  public function nodeView(array &$build, NodeInterface $node, EntityViewDisplayInterface $display, $view_mode) {
    // Add course completion status.
    if ($display->getComponent('moodle_user_completion')) {
      $build['moodle_user_completion'] = $this->getCourseCompletion($node);
    }
  }

  /**
   * Retrieves view, and sets render array.
   */
  protected function getCourseCompletion(NodeInterface $node) {
    if (empty($node->moodle_course_id)) {
      return;
    }
    $moodle_course_id = $node->moodle_course_id->value;
    if (empty($moodle_course_id)) {
      return;
    }

    if ($user = $this->routeMatch->getParameter('user')) {
      if (is_int($user)) {
        $user = $this->entityTypeManager->getStorage('user')->load($user);
      }
    }
    else {
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    }
    $moodle_user_id = $user->moodle_user_id->value;
    if (empty($moodle_user_id)) {
      return;
    }

    try {
      $progress = $this->moodle->getCourseCompletionPercentage($moodle_course_id, $moodle_user_id);
    }
    catch (MoodleRestException $e) {
      \watchdog_exception('moodle_rest_user', $e);
      return;
    }
    return [
      '#theme' => 'moodle_rest_user_course_completion',
      '#progress' => $progress,
      '#attached' => [
        'library' => [
          'moodle_rest_user/course',
        ],
      ],
    ];

  }

}
