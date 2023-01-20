<?php

namespace Drupal\Tests\moodle_rest\Kernel;

use Drupal\moodle_rest\Services\MoodleRestException;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;

/**
 * Test base Rest WS service.
 *
 * @group moodle_rest
 */
class RestServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['moodle_rest'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('moodle_rest');
    $config = $this->config('moodle_rest.settings');
    $config->set('url', 'https://example.com');
    $config->set('wstoken', 'abc123');
    $config->save();
  }

  /**
   * Check configuration handling in request.
   */
  public function testConfig(): void {
    $http_client = $this->getMockBuilder(ClientInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $http_client->expects($this->once())
      ->method('request')
      ->with(
        $this->equalTo('GET'),
        $this->equalTo('https://example.com/webservice/rest/server.php'),
        $this->callback([$this, 'hasToken'])
      )
      ->willReturn(new Response());
    $this->container->set('http_client', $http_client);
 
    $rest_ws = $this->container->get('moodle_rest.rest_ws');
    $rest_ws->request(['query' => ['wsfunction' => 'abc']]);
  }

  /**
   * Parameter test callback
   *
   * @see ::testConfig().
   */
  static function hasToken(array $params): bool {
    return $params['query']['wstoken'] == 'abc123';
  }

  /**
   * Test wsfunction call.
   */
  public function testRequestFunction(): void {
    $http_client = $this->getMockBuilder(ClientInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $http_client->expects($this->exactly(2))
      ->method('request')
      ->withConsecutive([
          $this->equalTo('GET'),
          $this->equalTo('https://example.com/webservice/rest/server.php'),
          $this->callback([$this, 'hasWsfunction']),
        ], [
          $this->equalTo('POST'),
          $this->equalTo('https://example.com/webservice/rest/server.php'),
          $this->callback([$this, 'hasPostWsfunction']),
        ]
      )
      ->willReturn(new Response());
    $this->container->set('http_client', $http_client);
 
    // Without additional options.
    $rest_ws = $this->container->get('moodle_rest.rest_ws');
    $rest_ws->requestFunction('abc');

    // Now with options.
    $rest_ws = $this->container->get('moodle_rest.rest_ws');
    $rest_ws->requestFunction('abc', ['key' => 'value']);
  }

  /**
   * Parameter test callback: Correct wsfunction set.
   *
   * @see testRequestFunction()
   */
  static function hasWsfunction(array $params): bool {
    return $params['query']['wsfunction'] == 'abc';
  }

  /**
   * Parameter test callback: Correct wsfunction and options.
   *
   * @see testRequestFunction().
   */
  static function hasPostWsfunction(array $params): bool {
    return self::hasWsfunction($params) && $params['form_params']['key'] = 'value';
  }

  /**
   * Test guzzle exception.
   */
  public function testRequestException(): void {
    $http_client = $this->getMockBuilder(ClientInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $http_client->expects($this->any())
      ->method('request')
      ->will($this->throwException(new TransferException()));
    $this->container->set('http_client', $http_client);

    $this->expectException(MoodleRestException::class);
    $this->expectExceptionCode(500);
    
    $rest_ws = $this->container->get('moodle_rest.rest_ws');
    $rest_ws->requestFunction('abc', ['key' => 'value']);
  }

  /**
   * Test Moodle exceptions.
   */
  public function testMoodleException(): void {
    $http_client = $this->getMockBuilder(ClientInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $http_client->expects($this->exactly(2))
      ->method('request')
      ->willReturnOnConsecutiveCalls(
        new Response(200, [], '{"exception":"webservice_access_exception","errorcode":"accessexception","message":"Access control exception"}'),
        new Response(200, [], '{"exception":"invalid_parameter_exception","errorcode":"invalidparameter","message":"Invalid parameter value detected"}'),
      );
    $this->container->set('http_client', $http_client);
    
    $rest_ws = $this->container->get('moodle_rest.rest_ws');
    $this->expectException(MoodleRestException::class);
    $this->expectExceptionCode(403);
    try {
      $rest_ws->requestFunction('abc', ['key' => 'value']);
    }
    catch (\Exception $e) { }

    $this->expectException(MoodleRestException::class);
    $this->expectExceptionCode(400);
    $rest_ws->requestFunction('abc', ['key' => 'value']);
  }
}
