<?php

namespace Drupal\moodle_rest\Services;

use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;

/**
 * Moodle Rest Functions Service.
 *
 * Adds helpers for Moodle Rest Webservice functions.
 * Helper methods should document the required parameters, and the returned
 * expected data, log warnings. Links to the moodle definitions included.
 * If you have access to the site you can also see a summary of all webservice
 * functions at '/admin/webservice/documentation.php'.
 */
class RestFunctions {

  /**
   * The Rest WS connector.
   *
   * @var \Drupal\moodle_rest\Services\MoodleRest
   */
  protected $rest;

  /**
   * The module logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Static cache for repeat queries in the same call.
   *
   * @var []
   */
  protected static $cache = [];

  /**
   * Constructs a RestFunctions object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.channel.moodle_rest service.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Set the Moodle Rest Webservice client.
   *
   * Otherwise default client from container will be used. Setting manually
   * allows overriding the configured Moodle server and token.
   */
  public function setRestClient(MoodleRest $rest) {
    self::$cache = [];
    $this->rest = $rest;
  }

  /**
   * Get the Moodle Rest Webservice client.
   */
  public function getRestClient() {
    if (empty($this->rest)) {
      $this->rest = \Drupal::service('moodle_rest.rest_ws');
    }    
    return $this->rest;
  }

  /**
   * Core webservice.
   *
   * Moodle `core_webservice_*` functions.
   */

  /**
   * Get some site info / user info / list web service functions.
   *
   * Moodle function: core_webservice_get_site_info.
   *
   * @return array
   *   Array as defined by version of Moodle.
   *
   * @throws \Drupal\moodle_rest\Services\MoodleRestException
   */
  public function getSiteInfo() {
    if (empty(self::$cache['site_info'])) {
      self::$cache['site_info'] = $this->getRestClient()->requestFunction('core_webservice_get_site_info');
    }

    return self::$cache['site_info'];
  }

  /**
   * Get Moodle Version.
   *
   * YYYYMMDD      = date of the 1.9 branch (don't change)
   *         X     = release number 1.9.[0,1,2,3,4,5...]
   *          Y.YY = micro-increments between releases.
   *
   * @return string
   *   Version number, or unknown if access to function denied, or error.
   */
  public function getSiteInfoVersion() {
    try {
      $site_info = $this->getSiteInfo();
    }
    catch (MoodleRestException $e) {
      if ($e->getCode() == 403 && $e->getBody()['errorcode'] = 'accessexception') {
        return 'unknown';
      }
      $this->logException($e);
      return 'error';
    }

    return $site_info['version'];
  }

  /**
   * Get Moodle Release.
   *
   * Human friendly form of version.
   *
   * @return string
   *   Release or unknown if access to function denied.
   */
  public function getSiteInfoRelease() {
    try {
      $site_info = $this->getSiteInfo();
    }
    catch (MoodleRestException $e) {
      if ($e->getCode() == 403 && $e->getBody()['errorcode'] = 'accessexception') {
        return 'unknown';
      }
      $this->logException($e);
      return 'error';
    }

    return $site_info['release'];
  }

  /**
   * Get Moodle Username.
   *
   * Name of user accessing the Webservice.
   *
   * @todo what is returned for anonymous access.
   *
   * @return string
   *   Username or '' on error including access denied to the function.
   */
  public function getSiteInfoUsername() {
    try {
      $site_info = $this->getSiteInfo();
    }
    catch (MoodleRestException $e) {
      if ($e->getCode() == 403 && $e->getBody()['errorcode'] = 'accessexception') {
        return '';
      }
      $this->logException($e);
      return '';
    }

    return $site_info['username'];
  }

  /**
   * Get Moodle Sitename.
   *
   * Name of user accessing the Webservice.
   *
   * @todo what is returned for anonymous access.
   *
   * @return string
   *   Username or '' on error including access denied to the function.
   */
  public function getSiteInfoSitename() {
    try {
      $site_info = $this->getSiteInfo();
    }
    catch (MoodleRestException $e) {
      if ($e->getCode() == 403 && $e->getBody()['errorcode'] = 'accessexception') {
        return '';
      }
      $this->logException($e);
      return '';
    }

    return $site_info['sitename'];
  }

  /**
   * Get Moodle Functions available.
   *
   * An array of the function currently available to the user of the webservice.
   *
   * @return array
   *   name => (string) Function name
   *   version => (string) The version number of the component to which the
   *   function belongs. Empty on error including access denied to the info
   *   function itself.
   */
  public function getSiteInfoFunctions() {
    try {
      $site_info = $this->getSiteInfo();
    }
    catch (MoodleRestException $e) {
      if ($e->getCode() == 403 && $e->getBody()['errorcode'] = 'accessexception') {
        return [];
      }
      $this->logException($e);
      return [];
    }

    return $site_info['functions'];
  }

  /**
   * Course webservice.
   *
   * Moodle `core_course_*` functions.
   *
   * @see https://github.com/moodle/moodle/blob/master/course/externallib.php
   */

  /**
   * Courses List.
   *
   * @param array $ids
   *   Optional. Array of course ids. If empty returns all courses except frontpage course.
   *   https://github.com/moodle/moodle/blob/master/course/externallib.php::get_courses_parameters().
   *
   * @return array
   *   Array of courses.
   *   https://github.com/moodle/moodle/blob/master/course/externallib.php::get_courses_returns().
   */
  public function getCourses(array $ids = []): array {
    if (empty($ids)) {
      return $this->getRestClient()->requestFunction('core_course_get_courses');
    }
    else {
      return $this->getRestClient()->requestFunction('core_course_get_courses', ['options' => ['ids' => $ids]]);
    }
  }

  /**
   * Course list by field.
   *
   * @param string $field
   *   Optional field to search by:
   *     'id': course id
   *     'ids': comma separated course ids
   *     'shortname': course short name
   *     'idnumber': course id number
   *     'category': category id the course belongs to.
   * @param string $value
   *   Used for 'value' => 'search_by'.
   *
   * @return array
   *   Array of courses.
   */
  public function getCoursesByField(string $field = '', string $value = ''): array {
    $options = [];
    if (!empty($field)) {
      $options = ['field' => $field, 'value' => $value];
    }
    $result = $this->getRestClient()->requestFunction('core_course_get_courses_by_field', $options);
    $this->logWarning($result);
    return $result['courses'];
  }

  /**
   * User webservice.
   *
   * Moodle `core_user_*` functions.
   *
   * @see https://github.com/moodle/moodle/blob/master/user/externallib.php
   */

  /**
   * Create Users.
   *
   * @param array $users
   *   Array of users. Required keys:
   *   - username string Username policy is defined in Moodle security config.
   *   - firstname string The first name(s) of the user.
   *   - lastname string The family name of the user.
   *   - email string A valid and unique email address.
   *   And one of:
   *   - createpassword int
   *     True if password should be created and mailed to user.
   *   - password string
   *     Plain text password consisting of any characters.
   *
   * @return array
   *   Array of [
   *     id int user id
   *     username string user name
   *   ].
   *
   * @throws \Drupal\moodle_rest\Services\MoodleRestException
   *   Notably: Invalid parameter value detected. Including with message
   *   Invalid parameter value detected (Username already exists: username).
   */
  public function createUsers(array $users): array {
    $result = $this->getRestClient()->requestFunction('core_user_create_users', ['users' => $users]);
    return $result;
  }

  /**
   * Update users.
   *
   * @param array $users
   *   Array of users. Required key 'id', and fields to update.
   *
   * @throws \Drupal\moodle_rest\Services\MoodleRestException
   */
  public function updateUsers(array $users): void {
    $this->getRestClient()->requestFunction('core_user_update_users', ['users' => $users]);
  }

  /**
   * Delete users.
   *
   * @param int[] $user_ids
   *   User IDs.
   *
   * @throws \Drupal\moodle_rest\Services\MoodleRestException
   */
  public function deleteUsers(array $user_ids): void {
    $this->getRestClient()->requestFunction('core_user_delete_users', ['userids' => $user_ids]);
  }

  /**
   * Search users.
   *
   * @param array $criteria
   *   An array of search pairs ['field' => 'value'] to search.
   *   The search is executed with AND operator on the criterias.
   *   Invalid criterias (keys) are ignored.
   *
   * @return array
   *   Array of matching users.
   *
   * @throws \Drupal\moodle_rest\Services\MoodleRestException
   */
  public function getUsers(array $criteria): array {
    $arguments = [];
    foreach ($criteria as $key => $value) {
      $arguments[] = [
        'key' => $key,
        'value' => $value,
      ];
    }
    $result = $this->getRestClient()->requestFunction('core_user_get_users', ['criteria' => $arguments]);
    $this->logWarning($result);
    return $result['users'];
  }

  /**
   * Get users by 'id', 'idnumber', 'username' or 'email'.
   *
   * Retrieve users' information for a specified unique field.
   * If you want to do a user search, use ::getUsers().
   *
   * @param string $field
   *   One of 'id' | 'idnumber' | 'username' | 'email'.
   * @param string[] $values
   *   Values to match.
   *
   * @return array
   *   Array of matching users.
   *
   * @throws \Drupal\moodle_rest\Services\MoodleRestException
   */
  public function getUsersByField(string $field, array $values): array {
    return $this->getRestClient()->requestFunction('core_user_get_users_by_field', [
      'field' => $field,
      'values' => $values,
    ]);
  }

  /**
   * Enrol webservice.
   *
   * Moodle `core_enrol_*` functions.
   *
   * @see https://github.com/moodle/moodle/blob/master/enrol/externallib.php
   */

  /**
   * Get list of courses user is enrolled in.
   *
   * Only active enrolments are returned.
   * Please note the current user must be able to access the course,
   * otherwise the course is not included.
   *
   * @param int $moodle_id
   *   The user Moodle ID.
   * @param bool $return_user_count
   *   Include count of enrolled users for each course.
   *   This can add several seconds to the response time.
   *    Optional (default: false).
   *
   * @return array
   *   Courses.
   */
  public function getUsersCourses(int $moodle_id, bool $return_user_count = FALSE): array {
    $options = [
      'userid' => $moodle_id,
      'returnusercount' => $return_user_count,
    ];
    $result = $this->getRestClient()->requestFunction('core_enrol_get_users_courses', $options);
    return $result;
  }

  /**
   * Completion webservice.
   *
   * Moodle `core_completion_*` functions.
   *
   * @see https://github.com/moodle/moodle/blob/master/completion/classes/external.php 
   */

  /**
   * Get Course completion status.
   *
   * @param int $course_id
   *   Moodle ID of the Course.
   * @param int $user_id
   *   Moodle ID of the User.
   *
   * @return array
   *   Course completion status.
   *
   * @throws moodle_exception
   */
  public function getCourseCompletionStatus(int $course_id, int $user_id): array {
    $options = [
      'courseid' => $course_id,
      'userid' => $user_id,
    ];
    $result = $this->getRestClient()->requestFunction('core_completion_get_course_completion_status', $options);
    $this->logWarning($result);
    return $result['completionstatus'];
  }

  /**
   * Helper function to get course completion percentage.
   *
   * This combines retrieving _get_course_completion_status and
   * _activity_status. It will just return the % activies completed if
   * appropriate.
   *
   * @param int $course_id
   *   Moodle ID of the Course.
   * @param int $user_id
   *   Moodle ID of the User.
   *
   * @return int|null
   *   Percentage 0 - 100 or void if no criteria set or not enrolled.
   *
   * @throws moodle_exception
   */
  public function getCourseCompletionPercentage(int $course_id, int $user_id): ?int {
    $completion = [];
    try {
      $completion = $this->getCourseCompletionStatus($course_id, $user_id);
    }
    catch (MoodleRestException $e) {
      if (($moodle_error = $e->getBody()) && ($moodle_error == 'nocriteriaset')) {
        return NULL;
      }
      else {
        throw $e;
      }
    }
    if ($completion['completed']) {
      return 100;
    }

    $activities = $this->getActivitiesCompletionStatus($course_id, $user_id);
    if (empty($activities)) {
      return NULL;
    }

    $completed_activities = array_filter($activities, function ($activity) {
      return (bool) $activity['timecompleted'];
    });
    return (int) round(count($completed_activities) / count($activities) * 100);
  }

  /**
   * Get Course activity completion status.
   *
   * @param int $course_id
   *   Moodle ID of the Course.
   * @param int $user_id
   *   Moodle ID of the User.
   *
   * @return array
   *   Course activity completion status.
   *
   * @throws moodle_exception
   */
  public function getActivitiesCompletionStatus(int $course_id, int $user_id): array {
    $options = [
      'courseid' => $course_id,
      'userid' => $user_id,
    ];
    $result = $this->getRestClient()->requestFunction('core_completion_get_activities_completion_status', $options);
    $this->logWarning($result);
    return $result['statuses'];
  }

  /**
   * Internal functions.
   */

  /**
   * Write an Moodle Rest Exception to the logger channel.
   *
   * Will include data from the Moodle response body as appropriate.
   */
  private function logException(MoodleRestException $e): void {
    $vars = Error::decodeException($e);
    $vars['@moodle'] = implode(', ', $e->getBody());
    $this->logger->error('%type: @message @moodle in %function (line %line of %file).', $vars);
  }

  /**
   * Log warnings.
   *
   * Moodle includes with some results an additional 'warnings' key value.
   */
  private function logWarning(array $results): void {
    if (!empty($results['warnings'])) {
      $backtrace = debug_backtrace(0, 2);
      foreach ($results['warnings'] as $warning) {
        $vars = [
          '@message' => print_r($warning, TRUE),
          '%function' => $backtrace[1]['function'],
          '%line' => $backtrace[1]['line'],
          '%file' => $backtrace[1]['file'],
        ];
      }
      $this->logger->warning('@message in %function (line %line of %file).', $vars);
    }
  }

}
