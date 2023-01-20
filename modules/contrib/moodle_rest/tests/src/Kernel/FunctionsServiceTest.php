<?php

namespace Drupal\Tests\moodle_rest\Kernel;

use Drupal\moodle_rest\Services\MoodleRest;
use Drupal\moodle_rest\Services\MoodleRestException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\moodle_rest\Kernel\Fixtures\MoodleFixturesTrait;
use Psr\Log\LoggerInterface;

/**
 * Test REST functions.
 *
 * @group moodle_rest
 */
class FunctionsServiceTest extends KernelTestBase {

  use MoodleFixturesTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['moodle_rest'];

  /**
   * Test site info and related functions only call api once.
   *
   * @dataProvider provideTestSiteInfo
   */
  public function testSiteInfo(array $result): void {
    // Use default REST client from container.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->once())
      ->method('requestFunction')
      ->with('core_webservice_get_site_info')
      ->willReturn($result);
    $this->container->set('moodle_rest.rest_ws', $rest);

    $rest_functions = $this->container->get('moodle_rest.rest_functions');
    $this->assertEquals($result, $rest_functions->getSiteInfo());
    $this->assertEquals($result['version'], $rest_functions->getSiteInfoVersion());
    $this->assertEquals($result['release'], $rest_functions->getSiteInfoRelease());
    $this->assertEquals($result['functions'], $rest_functions->getSiteInfoFunctions());
  }

  /**
   * Test handling exceptions in siteInfo field methods.
   */
  public function testSiteInfoExceptions(): void {
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->any())
      ->method('requestFunction')
      ->will($this->throwException(new MoodleRestException('', 500)));
    $this->container->set('moodle_rest.rest_ws', $rest);
    $rest_functions = $this->container->get('moodle_rest.rest_functions');

    $this->assertEquals('error', $rest_functions->getSiteInfoVersion());
    $this->assertEquals('error', $rest_functions->getSiteInfoRelease());
    $this->assertEquals('', $rest_functions->getSiteInfoUsername());
    $this->assertEquals('', $rest_functions->getSiteInfoSitename());
    $this->assertEquals([], $rest_functions->getSiteInfoFunctions());

    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->any())
      ->method('requestFunction')
      ->will($this->throwException(new MoodleRestException('', 403, NULL, ['errorcode' => 'accessexception'])));
    $rest_functions->setRestClient($rest);

    $this->assertEquals('unknown', $rest_functions->getSiteInfoVersion());
    $this->assertEquals('unknown', $rest_functions->getSiteInfoRelease());
    $this->assertEquals('', $rest_functions->getSiteInfoUsername());
    $this->assertEquals('', $rest_functions->getSiteInfoSitename());
    $this->assertEquals([], $rest_functions->getSiteInfoFunctions());
  }

  /**
   * Test swapping the rest client.
   */
  public function testSwitchRestClient(): void {
    // Use default REST client from container.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->once())
      ->method('requestFunction')
      ->with('core_webservice_get_site_info')
      ->willReturn(['original_result']);
    $this->container->set('moodle_rest.rest_ws', $rest);

    $rest_functions = $this->container->get('moodle_rest.rest_functions');
    $this->assertEquals(['original_result'], $rest_functions->getSiteInfo());

    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->once())
      ->method('requestFunction')
      ->with('core_webservice_get_site_info')
      ->willReturn(['new_result']);
    $rest_functions->setRestClient($rest);
    $this->assertEquals(['new_result'], $rest_functions->getSiteInfo());
  }

  /**
   * Test get courses.
   *
   * @covers ::getCourses
   *
   * @dataProvider provideGetCourses
   */
  public function testGetCourses(array $result): void {
    // Use default REST client from container.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->once())
      ->method('requestFunction')
      ->with('core_course_get_courses')
      ->willReturn($result);
    $this->container->set('moodle_rest.rest_ws', $rest);

    $rest_functions = $this->container->get('moodle_rest.rest_functions');
    $this->assertEquals($result, $rest_functions->getCourses());
  }

  /**
   * Test get core_courses_courses by field.
   *
   * @covers ::getCourses
   *
   * @dataProvider provideGetCoursesByField
   */
  public function testGetCoursesByField(array $result): void {
    // Use default REST client from container.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->once())
      ->method('requestFunction')
      ->with('core_course_get_courses_by_field')
      ->willReturn($result);
    $this->container->set('moodle_rest.rest_ws', $rest);

    $courses = $result['courses'];
    $rest_functions = $this->container->get('moodle_rest.rest_functions');
    $this->assertEquals($courses, $rest_functions->getCoursesByField());
  }

  /**
   * Test get core_course_get_courses_by_field arguments, exceptions & warnings.
   */
  public function testGetCoursesByFieldArguments(): void {

    // Request a single course id.
    // Then request courses and get an access denied on one.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->any())
      ->method('requestFunction')
      ->withConsecutive(
        ['core_course_get_courses_by_field', ['field' => 'id', 'value' => 1]],
        [
          'core_course_get_courses_by_field', [
            'field' => 'ids',
            'value' => '1,2',
          ],
        ],
      )
      ->willReturnOnConsecutiveCalls(
        ['courses' => [['id' => 1]], 'warnings' => []],
        [
          'courses' => [
            ['id' => 1],
          ],
          'warnings' => [
            [
              'item' => 'course',
              'itemid' => 2,
              'warningcode' => '1',
              'message' => 'No access rights in course context',
            ],
          ],
        ],
      );
    $this->container->set('moodle_rest.rest_ws', $rest);
    $logger = $this->getMockBuilder(LoggerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $logger->expects($this->once())
      ->method('warning');
    $this->container->set('logger.channel.moodle_rest', $logger);
    $rest_functions = $this->container->get('moodle_rest.rest_functions');
    $this->assertEquals([['id' => 1]], $rest_functions->getCoursesByField('id', 1));
    $this->assertEquals([['id' => 1]], $rest_functions->getCoursesByField('ids', '1,2'));

    // Send incorrect parameters get an exception.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->once())
      ->method('requestFunction')
      ->will(
        $this->throwException(new MoodleRestException('Moodle exception', 400, NULL, [
          'exception' => 'invalid_parameter_exception',
          'errorcode' => 'invalidparameter',
          'message' => 'Invalid parameter value detected (Invalid field name)',
          'debuginfo' => 'Invalid field name',
        ]))
      );
    $rest_functions->setRestClient($rest);
    try {
      $rest_functions->getCoursesByField('invalid', 1);
    }
    catch (MoodleRestException $e) {
      $this->assertInstanceOf(MoodleRestException::class, $e);
      $this->assertEquals(400, $e->getCode());
      $this->assertEquals('invalid_parameter_exception', $e->getBody()['exception']);
    }
  }

  /**
   * Tests basic user functions.
   *
   * Very basic test against the requestFunction call.
   *
   * @covers ::createUsers
   * @covers ::updateUsers
   * @covers ::deleteUsers
   */
  public function testUserFunctions() {
    $create_users = [
      [
        'username' => 'user_example',
        'email' => 'user@example.com',
        'firstname' => 'user',
        'lastname' => 'example',
        'createpassword' => TRUE,
      ],
    ];
    $update_users = [
      'id' => 6,
      'password' => 'example',
    ];
    $delete_users = [3, 6];

    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->any())
      ->method('requestFunction')
      ->withConsecutive(
        ['core_user_create_users', ['users' => $create_users]],
        ['core_user_update_users', ['users' => $update_users]],
        ['core_user_delete_users', ['userids' => $delete_users]],
      )
      ->willReturnOnConsecutiveCalls(
        [
          [
            'id' => '6',
            'username' => 'user_example',
          ],
        ],
        NULL,
        NULL
      );
    $this->container->set('moodle_rest.rest_ws', $rest);

    $rest_functions = $this->container->get('moodle_rest.rest_functions');
    $this->assertEquals(
      [['id' => '6', 'username' => 'user_example']],
      $rest_functions->createUsers($create_users)
    );
    $this->assertNull($rest_functions->updateUsers($update_users));
    $this->assertNull($rest_functions->deleteUsers($delete_users));
  }

  /**
   * Test get users.
   *
   * @covers ::getUsers
   *
   * @dataProvider provideGetUsers
   */
  public function testGetUsers(array $result): void {
    // Use default REST client from container.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->once())
      ->method('requestFunction')
      ->with('core_user_get_users', [
        'criteria' => [
          ['key' => 'firstname', 'value' => 'Student'],
        ],
      ])
      ->willReturn($result);
    $this->container->set('moodle_rest.rest_ws', $rest);

    $rest_functions = $this->container->get('moodle_rest.rest_functions');
    $this->assertEquals($result['users'], $rest_functions->getUsers(['firstname' => 'Student']));
  }

  /**
   * Test get users by field.
   *
   * @covers ::getUsersByField
   *
   * @dataProvider provideGetUsersByField
   */
  public function testGetUsersByField(array $result): void {
    // Use default REST client from container.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->once())
      ->method('requestFunction')
      ->with('core_user_get_users_by_field', [
        'field' => 'email',
        'values' => ['two@example.com'],
      ])
      ->willReturn($result);
    $this->container->set('moodle_rest.rest_ws', $rest);

    $rest_functions = $this->container->get('moodle_rest.rest_functions');
    $this->assertEquals($result, $rest_functions->getUsersByField('email', ['two@example.com']));
  }

}
