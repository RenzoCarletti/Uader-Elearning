<?php

namespace Drupal\moodle_rest\Services;

/**
 * Exception generated when making a Moodle REST WS request.
 *
 * Codes used:
 *   500 - An exception from Guzzle, will include GuzzleException in previous.
 *   404 - If no WS Function to call was specified.
 *   403 - Moodle Access Control Exception, body array ::getBody().
 *   400 - Other Moodle Errors, see body array ::getBody().
 *   1 - Host or token not configured.
 */
class MoodleRestException extends \RuntimeException {

  /**
   * @var array
   */
  private $body;

  public function __construct(string $message = "", int $code = 0, \Throwable $previous = NULL, array $body = []) {
    $this->body = $body;
    parent::__construct($message, $code, $previous);
  }

  /**
   * Return the body array of the Moodle exception.
   *
   * @return array
   *   ['exception' => string, 'errorcode' => string, 'message' => string]
   */
  public function getBody() {
    return $this->body;
  }

}
