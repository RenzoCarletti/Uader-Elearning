<?php

namespace Drupal\Tests\eca_access\Kernel;

use Drupal\eca\Entity\Eca;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Execution chain tests using plugins of eca_access.
 *
 * @group eca
 * @group eca_access
 */
class AccessExecutionChainTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'eca',
    'eca_access',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(static::$modules);
    User::create(['uid' => 0, 'name' => 'anonymous'])->save();
    User::create(['uid' => 1, 'name' => 'admin'])->save();

    Role::create(['id' => 'test_role_eca', 'label' => 'Test Role ECA'])->save();
    User::create([
      'uid' => 2,
      'name' => 'authenticated',
      'roles' => ['test_role_eca'],
    ])->save();
    user_role_grant_permissions('test_role_eca', [
      'access content',
    ]);

    // Create an Article content type.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'new_revision' => TRUE,
    ]);
    $node_type->save();
    node_add_body_field($node_type);
    // Create a Page content type.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
      'new_revision' => TRUE,
    ]);
    $node_type->save();
    node_add_body_field($node_type);
  }

  /**
   * Tests entity access using eca_access plugins.
   */
  public function testEntityAccess(): void {
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo(User::load(2));

    $article = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(),
      'status' => FALSE,
    ]);
    $article->save();
    $this->assertFalse($article->access('view'));

    // This config does the following:
    // 1. It reacts upon determining entity access, restricted to node article.
    // 2. Upon that, it grants access for anonymous users.
    $eca_config_values = [
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'entity_access',
      'label' => 'ECA entity access',
      'modeller' => 'fallback',
      'version' => '1.0.0',
      'events' => [
        'entity_access' => [
          'plugin' => 'access:entity',
          'label' => 'Node article access',
          'configuration' => [
            'entity_type_id' => 'node',
            'bundle' => 'article',
            'operation' => 'view',
          ],
          'successors' => [
            ['id' => 'grant_access', 'condition' => ''],
          ],
        ],
      ],
      'conditions' => [],
      'gateways' => [],
      'actions' => [
        'grant_access' => [
          'plugin' => 'eca_access_set_result',
          'label' => 'Grant access',
          'configuration' => [
            'access_result' => 'allowed',
          ],
          'successors' => [],
        ],
      ],
    ];
    $ecaConfig = Eca::create($eca_config_values);
    $ecaConfig->trustData()->save();
    \Drupal::entityTypeManager()->getAccessControlHandler('node')->resetCache();

    $article = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(),
      'status' => FALSE,
    ]);
    $article->save();
    $this->assertTrue($article->access('view'));

    $page = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'status' => FALSE,
    ]);
    $page->save();
    $this->assertFalse($page->access('view'), "Access must still be revoked on nodes other than articles.");
  }

  /**
   * Tests field access using eca_access plugins.
   */
  public function testFieldAccess(): void {
    user_role_grant_permissions('test_role_eca', [
      'administer nodes',
      'bypass node access',
    ]);
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo(User::load(2));

    $article = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(),
      'status' => TRUE,
      'body' => ['value' => $this->randomMachineName()],
    ]);
    $article->save();
    $this->assertTrue($article->access('view'));
    $this->assertTrue($article->title->access('edit'));
    $this->assertTrue($article->body->access('edit'));

    // This config does the following:
    // 1. It reacts upon determining field access, restricted to node article
    //    and the body field.
    // 2. Upon that, it blocks access for all users.
    $eca_config_values = [
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'entity_access',
      'label' => 'ECA entity access',
      'modeller' => 'fallback',
      'version' => '1.0.0',
      'events' => [
        'field_access' => [
          'plugin' => 'access:field',
          'label' => 'Node article body field access',
          'configuration' => [
            'entity_type_id' => 'node',
            'bundle' => 'article',
            'operation' => 'edit',
            'field_name' => 'body',
          ],
          'successors' => [
            ['id' => 'revoke_access', 'condition' => ''],
          ],
        ],
      ],
      'conditions' => [],
      'gateways' => [],
      'actions' => [
        'revoke_access' => [
          'plugin' => 'eca_access_set_result',
          'label' => 'Revoke access',
          'configuration' => [
            'access_result' => 'forbidden',
          ],
          'successors' => [],
        ],
      ],
    ];
    $ecaConfig = Eca::create($eca_config_values);
    $ecaConfig->trustData()->save();
    \Drupal::entityTypeManager()->getAccessControlHandler('node')->resetCache();

    $article = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(),
      'status' => FALSE,
      'body' => ['value' => $this->randomMachineName()],
    ]);
    $article->save();
    $this->assertTrue($article->access('view'));
    $this->assertTrue($article->title->access('edit'));
    $this->assertFalse($article->body->access('edit'));

    $page = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'status' => TRUE,
      'body' => ['value' => $this->randomMachineName()],
    ]);
    $page->save();
    $this->assertTrue($page->access('view'));
    $this->assertTrue($page->title->access('edit'));
    $this->assertTrue($page->body->access('edit'));
  }

}
