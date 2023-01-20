<?php

namespace Drupal\Tests\moodle_rest_user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\moodle_rest\Services\MoodleRest;
use Drupal\moodle_rest\Services\RestFunctions;
use Drupal\moodle_rest_user\Event\MoodleUserAssociate;
use Drupal\moodle_rest_user\UserEventHelper;
use Drupal\Tests\moodle_rest\Kernel\Fixtures\MoodleFixturesTrait;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Test the user events via the helper.
 *
 * @group moodle_rest
 */
class UserEventHelperTest extends KernelTestBase {

  use MoodleFixturesTrait;
  
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'moodle_rest',
    'moodle_rest_user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installConfig('moodle_rest_user');
  }

  /**
   * Check configuration against insert, update delete hook helpers.
   */
  public function testConfig(): void {
    $settings = $this->container->get('config.factory')
      ->get('moodle_rest_user.settings');

    // Defaults - can later be tested in the config edit form.
    $this->assertEquals('moodle_user_id', $settings->get('moodle_id_field'));
    $this->assertEquals(TRUE, $settings->get('associate'));
    $this->assertEquals(FALSE, $settings->get('create'));
    $this->assertEquals(TRUE, $settings->get('update'));
    $this->assertEquals(FALSE, $settings->get('delete'));
    $this->assertEquals(TRUE, $settings->get('pull.login'));
    $this->assertEquals(TRUE, $settings->get('pull.edit'));
    $this->assertEquals(FALSE, $settings->get('pull.view'));
    $this->assertEquals(0, $settings->get('pull.cron'));
    $this->assertEquals(FALSE, $settings->get('push_password'));
    $push = [
      ['drupal' => 'mail/0/value', 'moodle' => 'email'],
    ];
    $this->assertEquals($push, $settings->get('push_fields'));
    $pull = [
      ['drupal' => 'mail/0/value', 'moodle' => 'email'],
    ];
    $this->assertEquals($pull, $settings->get('pull_fields'));

    // Create user. Default config. No ID found.
    $event_dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $event_dispatcher->expects($this->once())
      ->method('dispatch');
    $this->container->set('event_dispatcher', $event_dispatcher);
    $user_event_helper = \Drupal::service('class_resolver')
      ->getInstanceFromDefinition(UserEventHelper::class);

    $account = User::create([
      'mail' => 'test@example.com',
    ]);
    $user_event_helper->userPresave($account);
  }

  /**
   * Test associate an account by email.
   */
  public function testAssociateAccountByEmail(): void {
    // Would be nice to run this via calling the
    // UserEventHelper::associateAccount method calling the event subscribers
    // however it serializes the required mock which breaks phpunit.
    $moodle = $this->getMockBuilder(RestFunctions::class)
      ->disableOriginalConstructor()
      ->getMock();
    $moodle->expects($this->once())
      ->method('getUsersByField')
      ->with('email', ['two@example.com'])
      ->willReturn([
        ['id' => 6],
      ]
    );
    $this->container->set('moodle_rest.rest_functions', $moodle);

    // Called with an event without a moodleId.
    $event_subscriber = $this->container->get('moodle_rest_user.associate_event_subscriber');
    $account = User::create([
      'mail' => 'two@example.com',
    ]);
    $event = new MoodleUserAssociate($account);
    $event_subscriber->associateByEmail($event);
    $this->assertEquals('6', $event->moodleId);

    // Called with an event with a moodleId. RestFunctions shouldn't be called
    // again.
    $account = User::create([
      'mail' => 'one@example.com',
    ]);
    $event = new MoodleUserAssociate($account);
    $event->moodleId = 5;
    $event_subscriber->associateByEmail($event);
    $this->assertEquals('5', $event->moodleId);
  }

  /**
   * Test create user.
   */
  public function testCreateUser(): void {
    $settings = $this->container->get('config.factory')
      ->getEditable('moodle_rest_user.settings');
    $settings->set('create', TRUE);
    $settings->set('push_fields', [
      [
        'drupal' => 'name/0/value',
        'moodle' => 'username',
      ],
      [
        'drupal' => 'mail/0/value',
        'moodle' => 'email',
      ],
      [
        'drupal' => 'name/0/value',
        'moodle' => 'firstname',
      ],
      [
        'drupal' => 'name/0/value',
        'moodle' => 'lastname',
      ],
    ]);
    $settings->save();

    $moodle = $this->getMockBuilder(RestFunctions::class)
      ->disableOriginalConstructor()
      ->getMock();
    $moodle->expects($this->once())
      ->method('createUsers')
      ->with([
        [
          'username' => 'test user',
          'email' => 'test@example.com',
          'lastname' => 'test user',
          'firstname' => 'test user',
        ],
      ])
      ->willReturn([
        ['id' => 2],
      ]
    );
    $this->container->set('moodle_rest.rest_functions', $moodle);

    $account = User::create([
      'name' => 'test user',
      'mail' => 'test@example.com',
    ]);
    $account->save();
  }

  /**
   * Test pull users.
   *
   * @dataProvider provideGetUsers
   */
  public function testPullUsers(array $result): void {
    $settings = $this->container->get('config.factory')
      ->getEditable('moodle_rest_user.settings');
    $settings->set('pull_fields', [
      [
        'drupal' => 'name/0/value',
        'moodle' => 'username',
      ],
      [
        'drupal' => 'mail/0/value',
        'moodle' => 'email',
      ],
    ]);
    $settings->save();

    // Use default REST client from container.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->once())
      ->method('requestFunction')
      ->with('core_user_get_users', [
        'criteria' => [
          ['key' => 'id', 'value' => 4],
        ],
      ])
      ->willReturn($result);
    $this->container->set('moodle_rest.rest_ws', $rest);

    $account = User::create([
      'name' => 'changes',
      'mail' => 'one@example.com',
    ]);
    $account->get('moodle_user_id')->setValue(4);
    $account->save();

    moodle_rest_user_user_login($account);
    \Drupal::entityTypeManager()->getStorage('user')->resetCache();
    $account = User::load($account->id());

    $this->assertEqual($account->getAccountName(), 'student_one');
    $this->assertEqual($account->getEmail(), 'one@example.com');
  }

  /**
   * Test push users.
   */
  public function testPushUsers(): void {
    $moodle = $this->getMockBuilder(RestFunctions::class)
      ->disableOriginalConstructor()
      ->getMock();
    $moodle->expects($this->once())
      ->method('updateUsers')
      ->with([
        [
          'id' => 2,
          'email' => 'changed@example.com',
        ],
      ]);
    $this->container->set('moodle_rest.rest_functions', $moodle);

    $account = User::create([
      'name' => 'test user',
      'mail' => 'test@example.com',
    ]);
    $account->get('moodle_user_id')->setValue(2);
    $account->save();

    $account->setEmail('changed@example.com');
    $account->save();
  }

}
