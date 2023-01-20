<?php

namespace Drupal\Tests\moodle_rest_migrate\Kernel\Plugin\migrate\source;

use Drupal\moodle_rest\Services\MoodleRest;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\moodle_rest\Kernel\Fixtures\MoodleFixturesTrait;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * @coversDefaultClass \Drupal\moodle_rest_migrate\Plugin\migrate\source\MoodleBase
 *
 * @group moodle_rest_migrate
 */
class MoodleBaseTest extends MigrateTestBase {

  use MoodleFixturesTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'text',
    'user',
    'node',
    'datetime',
    'migrate',
    'moodle_rest_migrate',
    'moodle_rest_migrate_test',
  ];

  /**
   * Tests execution of a migration using the base source.
   *
   * @dataProvider provideGetCourses
   */
  public function testBaseMigrate(array $result): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['node', 'moodle_rest_migrate_test']);

    // Mock results from Moodle Server.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->any())
      ->method('requestFunction')
      ->with('core_course_get_courses')
      ->willReturn($result);
    $this->container->set('moodle_rest.rest_ws', $rest);

    // Run migration.
    $migration_manager = $this->container->get('plugin.manager.migration');
    assert($migration_manager instanceof MigrationPluginManagerInterface);
    $migration = $migration_manager->createInstance('moodle_rest_migrate_test');
    $this->executeMigration($migration);

    // Check the id mapping by getting the course with id 5.
    $id_map = $migration->getIdMap();
    $ids = $id_map->lookUpDestinationIds([5]);
    $node = Node::load($ids[0][0]);

    // Check values migrated for course.
    $this->assertEquals($node->label(), 'Drupal second course');
    $this->assertEquals($node->get('body')->value, '<ul><li>Good</li><li>Description<br /></li></ul>');
    $this->assertEquals($node->get('created')->value, 1619438474);
    $this->assertEquals($node->get('changed')->value, 1619438474);
    $this->assertEquals($node->get('moodle_start_date')->value, '2021-04-01T21:00:00');
    $this->assertNull($node->get('moodle_end_date')->value);
  }

  /**
   * Tests execution of a migration using the course by field source.
   *
   * @dataProvider provideGetCoursesByField
   */
  public function testCourseByFieldMigrate(array $result): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['node', 'moodle_rest_migrate_test']);

    // Mock results from Moodle Server.
    $rest = $this->getMockBuilder(MoodleRest::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rest->expects($this->any())
      ->method('requestFunction')
      ->with('core_course_get_courses_by_field')
      ->willReturn($result);
    $this->container->set('moodle_rest.rest_ws', $rest);

    // Run migration.
    $migration_manager = $this->container->get('plugin.manager.migration');
    assert($migration_manager instanceof MigrationPluginManagerInterface);
    $migration = $migration_manager->createInstance('moodle_rest_migrate_course_by_field_test');
    $this->executeMigration($migration);

    // Check the id mapping by getting the course with id 5.
    $id_map = $migration->getIdMap();
    $ids = $id_map->lookUpDestinationIds([5]);
    $node = Node::load($ids[0][0]);

    // Check values migrated for course.
    $this->assertEquals($node->label(), 'Drupal second course');
    $this->assertEquals($node->get('body')->value, '<ul><li>Good</li><li>Description<br /></li></ul>');
    $this->assertEquals($node->get('created')->value, 1619438474);
    $this->assertEquals($node->get('changed')->value, 1619438474);
    $this->assertEquals($node->get('moodle_start_date')->value, '2021-04-01T21:00:00');
    $this->assertNull($node->get('moodle_end_date')->value);
  }

}
