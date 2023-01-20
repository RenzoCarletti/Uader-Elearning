<?php

namespace Drupal\Tests\eca_base\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the "eca_count" action plugin.
 *
 * @group eca
 * @group eca_base
 */
class ListCountTest extends KernelTestBase {

  /**
   * The modules.
   *
   * @var string[]
   *   The modules.
   */
  protected static $modules = [
    'system',
    'user',
    'eca',
    'eca_base',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(static::$modules);
  }

  /**
   * Tests TokenSetValue.
   */
  public function testTokenSetValue(): void {
    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = \Drupal::service('plugin.manager.action');
    /** @var \Drupal\eca\Token\TokenInterface $token_services */
    $token_services = \Drupal::service('eca.token_services');

    $count = 3;
    $list = (array) $this->randomObject($count);
    $token_services->addTokenData('list', $list);
    /** @var \Drupal\eca_base\Plugin\Action\ListCount $action */
    $action = $action_manager->createInstance('eca_count', [
      'token_name' => 'my_custom_token:value1',
      'list_token' => 'list',
    ]);
    $this->assertTrue($action->access(NULL));
    $action->execute(NULL);
    $this->assertEquals($count, $token_services->replaceClear('[my_custom_token:value1]'));
    $this->assertEquals('', $token_services->replaceClear('[my_custom_token:value2]'));
  }

}
