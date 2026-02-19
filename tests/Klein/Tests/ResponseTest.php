<?php

declare(strict_types=1);

// phpcs:disable
/**
 * Klein (klein.php) - A fast & flexible router for PHP
 *
 * @author Chris O'Hara <cohara87@gmail.com>
 * @author Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright (c) Chris O'Hara
 * @link https://github.com/klein/klein.php
 * @license MIT
 */
// phpcs:enable

namespace Klein\Tests;

use Klein\DataCollection\HeaderDataCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Klein\DataCollection\ResponseCookieDataCollection;
use Klein\Exceptions\LockedResponseException;
use Klein\Exceptions\ResponseAlreadySentException;
use Klein\HttpStatus;
use Klein\Response;
use Klein\ResponseCookie;

/**
 * Responses Test.
 */
class ResponseTest extends AbstractKleinTest {

  /**
   * Test protocol version get set.
   */
  public function testProtocolVersionGetSet() {
    $version_reg_ex = '/^[0-9]\.[0-9]$/';

    // Empty constructor.
    $response = new Response();

    $this->assertNotNull($response->protocolVersion());
    $this->assertIsString($response->protocolVersion());
    $this->assertMatchesRegularExpression($version_reg_ex, $response->protocolVersion());

    // Set in method.
    $response = new Response();
    $response->protocolVersion('2.0');

    $this->assertSame('2.0', $response->protocolVersion());
  }

  /**
   * Test body get set.
   */
  public function testBodyGetSet() {
    // Empty constructor.
    $response = new Response();

    $this->assertEmpty($response->body());

    // Body set in constructor.
    $response = new Response('dog');

    $this->assertSame('dog', $response->body());

    // Body set in method.
    $response = new Response();
    $response->body('testing');

    $this->assertSame('testing', $response->body());
  }

  /**
   * Test code get set.
   */
  public function testCodeGetSet() {
    // Empty constructor.
    $response = new Response();

    $this->assertNotNull($response->code());
    $this->assertIsInt($response->code());

    // Code set in constructor.
    $response = new Response(NULL, 503);

    $this->assertSame(503, $response->code());

    // Code set in method.
    $response = new Response();
    $response->code(204);

    $this->assertSame(204, $response->code());
  }

  /**
   * Test status getter.
   */
  public function testStatusGetter() {
    $response = new Response();

    $this->assertIsObject($response->status());
    $this->assertTrue($response->status() instanceof HttpStatus);
  }

  /**
   * TEst headers getter.
   */
  public function testHeadersGetter() {
    $response = new Response();

    $this->assertIsObject($response->headers());
    $this->assertTrue($response->headers() instanceof HeaderDataCollection);
  }

  /**
   * Test cookies getter.
   */
  public function testCookiesGetter() {
    $response = new Response();

    $this->assertIsObject($response->cookies());
    $this->assertTrue($response->cookies() instanceof ResponseCookieDataCollection);
  }

  /**
   * Test prepend.
   */
  public function testPrepend() {
    $response = new Response('ein');
    $response->prepend('Kl');

    $this->assertSame('Klein', $response->body());
  }

  /**
   * Test append.
   */
  public function testAppend() {
    $response = new Response('Kl');
    $response->append('ein');

    $this->assertSame('Klein', $response->body());
  }

  /**
   * Test lock toggle and getters.
   */
  public function testLockToggleAndGetters() {
    $response = new Response();

    $this->assertFalse($response->isLocked());

    $response->lock();

    $this->assertTrue($response->isLocked());

    $response->unlock();

    $this->assertFalse($response->isLocked());
  }

  /**
   * Test locked not modifiable.
   */
  public function testLockedNotModifiable() {
    $response = new Response();
    $response->lock();

    // Get initial values.
    $protocol_version = $response->protocolVersion();
    $body = $response->body();
    $code = $response->code();

    // Attempt to modify.
    try {
      $response->protocolVersion('2.0');
    }
    catch (LockedResponseException $e) {
    }

    try {
      $response->body('WOOT!');
    }
    catch (LockedResponseException $e) {
    }

    try {
      $response->code(204);
    }
    catch (LockedResponseException $e) {
    }

    try {
      $response->prepend('cat');
    }
    catch (LockedResponseException $e) {
    }

    try {
      $response->append('dog');
    }
    catch (LockedResponseException $e) {
    }

    // Assert nothing has changed.
    $this->assertSame($protocol_version, $response->protocolVersion());
    $this->assertSame($body, $response->body());
    $this->assertSame($code, $response->code());
  }

  /**
   * Test send headers.
   *
   * Testing headers is a pain in the ass. ;)
   *
   * Technically... we can't. So, yea.
   *
   * Attempt to run in a separate process so we can
   * at least call our internal methods
   */
  public function testSendHeaders() {
    $response = new Response('woot!');
    $response->headers()->set('test', 'sure');
    $response->headers()->set('Authorization', 'Basic asdasd');

    $response->sendHeaders();

    $this->expectOutputString('');
  }

  /**
   * Test send headers in isolate process.
   */
  #[RunInSeparateProcess]
  public function testSendHeadersInIsolateProcess() {
    $this->testSendHeaders();
  }

  /**
   * Test send cookies.
   *
   * Testing cookies is exactly like testing headers.
   *
   * ... So, yea.
   */
  public function testSendCookies() {
    $response = new Response();
    $response->cookies()->set('test', 'woot!');
    $response->cookies()->set('Cookie-name', 'wtf?');

    $response->sendCookies();

    $this->expectOutputString('');
  }

  /**
   * Test send cookies in isolate process.
   */
  #[RunInSeparateProcess]
  public function testSendCookiesInIsolateProcess() {
    $this->testSendCookies();
  }

  /**
   * Test send body.
   */
  public function testSendBody() {
    $response = new Response('woot!');
    $response->sendBody();

    $this->expectOutputString('woot!');
  }

  /**
   * Test send.
   */
  public function testSend() {
    $response = new Response('woot!');
    $response->send();

    $this->expectOutputString('woot!');
    $this->assertTrue($response->isLocked());
  }

  /**
   * Test send when already sent.
   */
  public function testSendWhenAlreadySent() {
    $this->expectException(ResponseAlreadySentException::class);

    $response = new Response();
    $response->send();

    $this->assertTrue($response->isLocked());

    $response->send();
  }

  /**
   * Test send calls fast CGI finish request.
   *
   * This uses some crazy exploitation to make sure that the
   * `fastcgi_finish_request()` function gets called.
   * Because of this, this MUST be run in a separate process.
   */
  #[RunInSeparateProcess]
  public function testSendCallsFastCgiFinishRequest() {
    // Custom fastcgi function.
    implement_custom_fastcgi_function();

    $this->expectOutputString('fastcgi_finish_request');

    $response = new Response();
    $response->send();
  }

  /**
   * Test chunk.
   */
  public function testChunk() {
    $content = [
      'initial content',
      'more',
      'content',
    ];

    $response = new Response($content[0]);

    $response->chunk();
    $response->chunk($content[1]);
    $response->chunk($content[2]);

    $this->expectOutputString(
      dechex(strlen($content[0])) . "\r\n$content[0]\r\n"
      . dechex(strlen($content[1])) . "\r\n$content[1]\r\n"
      . dechex(strlen($content[2])) . "\r\n$content[2]\r\n"
    );
  }

  /**
   * Test header.
   */
  public function testHeader() {
    $headers = [
      'test' => 'woot!',
      'test' => 'sure',
      'okay' => 'yup',
    ];

    $response = new Response();

    // Make sure the headers are initially empty.
    $this->assertEmpty($response->headers()->all());

    // Set the headers.
    foreach ($headers as $name => $value) {
      $response->header($name, $value);
    }

    $this->assertNotEmpty($response->headers()->all());

    // Set the headers.
    foreach ($headers as $name => $value) {
      $this->assertSame($value, $response->headers()->get($name));
    }
  }

  /**
   * Test cookie.
   */
  #[Group('testCookie')]
  public function testCookie() {
    $test_cookie_data = [
      'name'   => 'name',
      'value'  => 'value',
      'expiry'  => NULL,
      'path'   => '/path',
      'domain'  => 'whatever.com',
      'secure'  => TRUE,
      'httponly' => TRUE,
    ];

    $response = new Response();

    // Make sure the cookies are initially empty.
    $this->assertEmpty($response->cookies()->all());

    // Set a cookies.
    $response->cookie(
      $test_cookie_data['name'],
      $test_cookie_data['value'],
      $test_cookie_data['expiry'],
      $test_cookie_data['path'],
      $test_cookie_data['domain'],
      $test_cookie_data['secure'],
      $test_cookie_data['httponly']
    );

    $this->assertNotEmpty($response->cookies()->all());

    $the_cookie = $response->cookies()->get($test_cookie_data['name']);

    $this->assertNotNull($the_cookie);
    $this->assertTrue($the_cookie instanceof ResponseCookie);
    $this->assertSame($test_cookie_data['name'], $the_cookie->getName());
    $this->assertSame($test_cookie_data['value'], $the_cookie->getValue());
    $this->assertSame($test_cookie_data['path'], $the_cookie->getPath());
    $this->assertSame($test_cookie_data['domain'], $the_cookie->getDomain());
    $this->assertSame($test_cookie_data['secure'], $the_cookie->getSecure());
    $this->assertSame($test_cookie_data['httponly'], $the_cookie->getHttpOnly());
    $this->assertNotNull($the_cookie->getExpire());
  }

  /**
   * Test no cache.
   */
  public function testNoCache() {
    $response = new Response();

    // Make sure the headers are initially empty.
    $this->assertEmpty($response->headers()->all());

    $response->noCache();

    $this->assertContains('no-cache', $response->headers()->all());
  }

  /**
   * Test redirect.
   */
  public function testRedirect() {
    $url = 'http://google.com/';
    $code = 302;

    $response = new Response();
    $response->redirect($url, $code);

    $this->assertSame($code, $response->code());
    $this->assertSame($url, $response->headers()->get('location'));
    $this->assertTrue($response->isLocked());
  }

  /**
   * Test dump.
   */
  public function testDump() {
    $response = new Response();

    $this->assertEmpty($response->body());

    $response->dump('test');

    $this->assertStringContainsString('test', $response->body());
  }

  /**
   * Test dump array.
   */
  public function testDumpArray() {
    $response = new Response();

    $this->assertEmpty($response->body());

    $response->dump(['sure', 1, 10, 17, 'ok' => 'no']);

    $this->assertNotEmpty($response->body());
    $this->assertNotEquals('<pre></pre>', $response->body());
  }

  /**
   * Test file send.
   */
  public function testFileSend() {
    $file_name = 'testing';
    $file_mime = 'text/plain';

    $this->kleinApp->respond(
      function ($request, $response, $service) use ($file_name, $file_mime) {
        $response->file(__FILE__, $file_name, $file_mime);
      }
    );

    $this->kleinApp->dispatch();

    // Expect our output to match our file.
    $this->expectOutputString(
      file_get_contents(__FILE__)
    );

    // Assert headers were passed.
    $this->assertEquals(
      $file_mime,
      $this->kleinApp->response()->headers()->get('Content-Type')
    );
    $this->assertEquals(
      filesize(__FILE__),
      $this->kleinApp->response()->headers()->get('Content-Length')
    );
    $this->assertStringContainsString(
      $file_name,
      $this->kleinApp->response()->headers()->get('Content-Disposition')
    );
  }

  /**
   * Test file send loose args.
   */
  public function testFileSendLooseArgs() {
    $this->kleinApp->respond(
      function ($request, $response, $service) {
        $response->file(__FILE__);
      }
    );

    $this->kleinApp->dispatch();

    // Expect our output to match our file.
    $this->expectOutputString(
      file_get_contents(__FILE__)
    );

    // Assert headers were passed.
    $this->assertEquals(
      filesize(__FILE__),
      $this->kleinApp->response()->headers()->get('Content-Length')
    );
    $this->assertNotNull(
      $this->kleinApp->response()->headers()->get('Content-Type')
    );
    $this->assertNotNull(
      $this->kleinApp->response()->headers()->get('Content-Disposition')
    );
  }

  /**
   * Test file send when already sent.
   */
  public function testFileSendWhenAlreadySent() {
    $this->expectException(ResponseAlreadySentException::class);

    // Expect our output to match our file.
    $this->expectOutputString(
      file_get_contents(__FILE__)
    );

    $response = new Response();
    $response->file(__FILE__);

    $this->assertTrue($response->isLocked());

    $response->file(__FILE__);
  }

  /**
   * Test file send with non existent file.
   */
  public function testFileSendWithNonExistentFile() {
    $this->expectException(\RuntimeException::class);

    // Ignore the file warning.
    $old_error_val = error_reporting();
    error_reporting(E_ALL ^ E_WARNING);

    $response = new Response();
    $response->file(__DIR__ . '/some/bogus/path/that/does/not/exist');

    error_reporting($old_error_val);
  }

  /**
   * Test file send calls fast CGI finish request.
   *
   * This uses some crazy exploitation to make sure that the
   * `fastcgi_finish_request()` function gets called.
   * Because of this, this MUST be run in a separate process.
   */
  #[RunInSeparateProcess]
  public function testFileSendCallsFastCgiFinishRequest() {
    // Custom fastcgi function.
    implement_custom_fastcgi_function();

    // Expect our output to match our file.
    $this->expectOutputString(
      file_get_contents(__FILE__) . 'fastcgi_finish_request'
    );

    $response = new Response();
    $response->file(__FILE__);
  }

  /**
   * Test JSON.
   */
  public function testJson() {
    // Create a test object to be JSON encoded/decoded.
    $test_object = (object) [
      0 => 'cheese',
      'dog' => 'bacon',
      'integer' => 1,
      'double' => 1.5,
      '_weird' => TRUE,
      'uniqid' => uniqid(),
    ];

    // Expect our output to match our json encoded test.
    $this->expectOutputString(
      json_encode($test_object)
    );

    $this->kleinApp->respond(
      function ($request, $response, $service) use ($test_object) {
        $response->json($test_object);
      }
    );

    $this->kleinApp->dispatch();

    // Assert headers were passed.
    $this->assertEquals(
      'no-cache',
      $this->kleinApp->response()->headers()->get('Pragma')
    );
    $this->assertEquals(
      'no-store, no-cache',
      $this->kleinApp->response()->headers()->get('Cache-Control')
    );
    $this->assertEquals(
      'application/json',
      $this->kleinApp->response()->headers()->get('Content-Type')
    );
  }

  /**
   * Test JSON with prefix.
   */
  public function testJsonWithPrefix() {
    // Create a test object to be JSON encoded/decoded.
    $test_object = [
      'cheese',
    ];
    $prefix = 'dogma';

    $this->kleinApp->respond(
      function ($request, $response, $service) use ($test_object, $prefix) {
        $response->json($test_object, $prefix);
      }
    );

    $this->kleinApp->dispatch();

    // Expect our output to match our json encoded test object.
    $this->expectOutputString(
      'dogma(' . json_encode($test_object) . ');'
    );

    // Assert headers were passed.
    $this->assertEquals(
      'no-cache',
      $this->kleinApp->response()->headers()->get('Pragma')
    );
    $this->assertEquals(
      'no-store, no-cache',
      $this->kleinApp->response()->headers()->get('Cache-Control')
    );
    $this->assertEquals(
      'text/javascript',
      $this->kleinApp->response()->headers()->get('Content-Type')
    );
  }

}
