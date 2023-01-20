<?php

namespace Drupal\Tests\moodle_rest\Kernel\Fixtures;

/**
 * Trait to load fixtures.
 */
trait MoodleFixturesTrait {

  /**
   * Array of fixtures.
   *
   * Results from different versions for function calls.
   *
   * @var array
   */
  public static $fixtures = [];

  /**
   * Method to get and set fixtures.
   */
  public static function getFixtures(): array {
    if (empty(self::$fixtures)) {
      include __DIR__ . '/v3.php';
      self::$fixtures['v3'] = $function_results;
    }

    return self::$fixtures;
  }

  /**
   * Data provider for getCourses.
   *
   * @return array
   *   Return data for core_course_get_courses keyed by version.
   */
  public static function provideGetCourses(): array {
    return array_combine(array_keys(self::getFixtures()), [array_column(self::getFixtures(), 'core_course_get_courses')]);
  }

  /**
   * Data provider for getCoursesByField.
   *
   * @return array
   *   Results data for core_course_get_courses_by_field keyed by version.
   */
  public static function provideGetCoursesByField(): array {
    return array_combine(array_keys(self::getFixtures()), [array_column(self::getFixtures(), 'core_course_get_courses_by_field')]);
  }

  /**
   * Data provider for getSiteInfo.
   *
   * @return array
   *   Return data for core_webservice_get_site_info keyed by version.
   */
  public static function provideTestSiteInfo(): array {
    return array_combine(array_keys(self::getFixtures()), [array_column(self::getFixtures(), 'core_webservice_get_site_info')]);
  }

  /**
   * Data provider for getUsers.
   *
   * @return array
   *   Results data for core_user_get_users keyed by version.
   */
  public static function provideGetUsers(): array {
    return array_combine(array_keys(self::getFixtures()), [array_column(self::getFixtures(), 'core_user_get_users')]);
  }

  /**
   * Data provider for getUsersByField.
   *
   * @return array
   *   Results data for core_user_get_users_by_field keyed by version.
   */
  public static function provideGetUsersByField(): array {
    return array_combine(array_keys(self::getFixtures()), [array_column(self::getFixtures(), 'core_user_get_users_by_field')]);
  }


}
