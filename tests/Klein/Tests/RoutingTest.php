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

use Klein\App;
use Klein\DataCollection\RouteCollection;
use Klein\Exceptions\DispatchHaltedException;
use Klein\Exceptions\HttpException;
use Klein\Exceptions\RoutePathCompilationException;
use Klein\Exceptions\UnhandledException;
use Klein\Klein;
use Klein\Request;
use Klein\Response;
use Klein\Route;
use Klein\ServiceProvider;
use Klein\Tests\Mocks\MockRequestFactory;

/**
 * Routing Test.
 */
class RoutingTest extends AbstractKleinTest {

  /**
   * Test basic.
   */
  public function testBasic() {
    $this->expectOutputString('x');

    $this->kleinApp->respond(
      '/',
      function () {
        echo 'x';
      }
    );
    $this->kleinApp->respond(
      '/something',
      function () {
        echo 'y';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/')
    );
  }

  /**
   * Test callable.
   */
  public function testCallable() {
    $this->expectOutputString('okok');

    $this->kleinApp->respond('/', [__NAMESPACE__ . '\Mocks\TestClass', 'GET']);
    $this->kleinApp->respond('/', __NAMESPACE__ . '\Mocks\TestClass::GET');

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/')
    );
  }

  /**
   * Test callback arguments.
   */
  public function testCallbackArguments() {
    // Create expected objects.
    $expected_objects = [
      'request'     => NULL,
      'response'    => NULL,
      'service'     => NULL,
      'app'       => NULL,
      'klein'      => NULL,
      'matched'     => NULL,
      'methods_matched' => NULL,
    ];

    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $e, $f, $g) use (&$expected_objects) {
        $expected_objects['request']         = $a;
        $expected_objects['response']        = $b;
        $expected_objects['service']         = $c;
        $expected_objects['app']             = $d;
        $expected_objects['klein']           = $e;
        $expected_objects['matched']         = $f;
        $expected_objects['methods_matched'] = $g;
      }
    );

    $this->kleinApp->dispatch();

    $this->assertTrue($expected_objects['request'] instanceof Request);
    $this->assertTrue($expected_objects['response'] instanceof Response);
    $this->assertTrue($expected_objects['service'] instanceof ServiceProvider);
    $this->assertTrue($expected_objects['app'] instanceof App);
    $this->assertTrue($expected_objects['klein'] instanceof Klein);
    $this->assertTrue($expected_objects['matched'] instanceof RouteCollection);
    $this->assertTrue(is_array($expected_objects['methods_matched']));

    $this->assertSame($expected_objects['request'], $this->kleinApp->request());
    $this->assertSame($expected_objects['response'], $this->kleinApp->response());
    $this->assertSame($expected_objects['service'], $this->kleinApp->service());
    $this->assertSame($expected_objects['app'], $this->kleinApp->app());
    $this->assertSame($expected_objects['klein'], $this->kleinApp);
  }

  /**
   * Test app reference.
   */
  public function testAppReference() {
    $this->expectOutputString('ab');

    $this->kleinApp->respond(
      '/',
      function ($request, $response, $service, $app) {
        $app->state = 'a';
      }
    );
    $this->kleinApp->respond(
      '/',
      function ($request, $response, $service, $app) {
        $app->state .= 'b';
      }
    );
    $this->kleinApp->respond(
      '/',
      function ($request, $response, $service, $app) {
        print $app->state;
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/')
    );
  }

  /**
   * Test dispatch output.
   */
  public function testDispatchOutput() {
    $expected_output = [
      'returned1' => 'alright!',
      'returned2' => 'woot!',
    ];

    $this->kleinApp->respond(
      function () use ($expected_output) {
        return $expected_output['returned1'];
      }
    );
    $this->kleinApp->respond(
      function () use ($expected_output) {
        return $expected_output['returned2'];
      }
    );

    $this->kleinApp->dispatch();

    // Expect our output to match our ECHO'd output.
    $this->expectOutputString(
      $expected_output['returned1'] . $expected_output['returned2']
    );

    // Make sure our response body matches the concatenation of what we returned
    // in each callback.
    $this->assertSame(
      $expected_output['returned1'] . $expected_output['returned2'],
      $this->kleinApp->response()->body()
    );
  }

  /**
   * Test dispatch output not sent.
   */
  public function testDispatchOutputNotSent() {
    $this->kleinApp->respond(
      function () {
        return 'test output';
      }
    );

    $this->kleinApp->dispatch(NULL, NULL, FALSE);

    $this->expectOutputString('');

    $this->assertSame(
      'test output',
      $this->kleinApp->response()->body()
    );
  }

  /**
   * Test dispatch output captured.
   */
  public function testDispatchOutputCaptured() {
    $expected_output = [
      'echoed' => 'yup',
      'returned' => 'nope',
    ];

    $this->kleinApp->respond(
      function () use ($expected_output) {
        echo $expected_output['echoed'];
      }
    );
    $this->kleinApp->respond(
      function () use ($expected_output) {
        return $expected_output['returned'];
      }
    );

    $output = $this->kleinApp->dispatch(NULL, NULL, TRUE, Klein::DISPATCH_CAPTURE_AND_RETURN);

    // Make sure nothing actually printed to the screen.
    $this->expectOutputString('');

    // Make sure our returned output matches what we ECHO'd.
    $this->assertSame($expected_output['echoed'], $output);

    // Make sure our response body matches what we returned.
    $this->assertSame($expected_output['returned'], $this->kleinApp->response()->body());
  }

  /**
   * Test dispatch output replaced.
   */
  public function testDispatchOutputReplaced() {
    $expected_output = [
      'echoed' => 'yup',
      'returned' => 'nope',
    ];

    $this->kleinApp->respond(
      function () use ($expected_output) {
        echo $expected_output['echoed'];
      }
    );
    $this->kleinApp->respond(
      function () use ($expected_output) {
        return $expected_output['returned'];
      }
    );

    $this->kleinApp->dispatch(NULL, NULL, FALSE, Klein::DISPATCH_CAPTURE_AND_REPLACE);

    // Make sure nothing actually printed to the screen.
    $this->expectOutputString('');

    // Make sure our response body matches what we echoed.
    $this->assertSame($expected_output['echoed'], $this->kleinApp->response()->body());
  }

  /**
   * Test dispatch output prepended.
   */
  public function testDispatchOutputPrepended() {
    $expected_output = [
      'echoed' => 'yup',
      'returned' => 'nope',
      'echoed2' => 'sure',
    ];

    $this->kleinApp->respond(
      function () use ($expected_output) {
        echo $expected_output['echoed'];
      }
    );
    $this->kleinApp->respond(
      function () use ($expected_output) {
        return $expected_output['returned'];
      }
    );
    $this->kleinApp->respond(
      function () use ($expected_output) {
        echo $expected_output['echoed2'];
      }
    );

    $this->kleinApp->dispatch(NULL, NULL, FALSE, Klein::DISPATCH_CAPTURE_AND_PREPEND);

    // Make sure nothing actually printed to the screen.
    $this->expectOutputString('');

    // Make sure our response body matches what we echoed.
    $this->assertSame(
      $expected_output['echoed'] . $expected_output['echoed2'] . $expected_output['returned'],
      $this->kleinApp->response()->body()
    );
  }

  /**
   * Test dispatch output appended.
   */
  public function testDispatchOutputAppended() {
    $expected_output = [
      'echoed' => 'yup',
      'returned' => 'nope',
      'echoed2' => 'sure',
    ];

    $this->kleinApp->respond(
      function () use ($expected_output) {
        echo $expected_output['echoed'];
      }
    );
    $this->kleinApp->respond(
      function () use ($expected_output) {
        return $expected_output['returned'];
      }
    );
    $this->kleinApp->respond(
      function () use ($expected_output) {
        echo $expected_output['echoed2'];
      }
    );

    $this->kleinApp->dispatch(NULL, NULL, FALSE, Klein::DISPATCH_CAPTURE_AND_APPEND);

    // Make sure nothing actually printed to the screen.
    $this->expectOutputString('');

    // Make sure our response body matches what we echoed.
    $this->assertSame(
      $expected_output['returned'] . $expected_output['echoed'] . $expected_output['echoed2'],
      $this->kleinApp->response()->body()
    );
  }

  /**
   * Test dispatch response replaced.
   */
  public function testDispatchResponseReplaced() {
    $expected_body = 'You SHOULD see this';
    $expected_code = 201;

    $expected_append = 'This should be appended?';

    $this->kleinApp->respond(
      '/',
      function ($request, $response) {
        // Set our response code.
        $response->code(569);

        return 'This should disappear';
      }
    );
    $this->kleinApp->respond(
      '/',
      function () use ($expected_body, $expected_code) {
        print_r(['RESPONSE 1' => $expected_body]);
        return new Response($expected_body, $expected_code);
      }
    );
    $this->kleinApp->respond(
      '/',
      function () use ($expected_append) {
        print_r(['RESPONSE 2' => $expected_append]);
        return $expected_append;
      }
    );

    $this->kleinApp->dispatch(MockRequestFactory::create('/'), NULL, FALSE, Klein::DISPATCH_CAPTURE_AND_RETURN);

    // Make sure our response body and code match up.
    $this->assertSame(
      $expected_body . $expected_append,
      $this->kleinApp->response()->body()
    );
    $this->assertSame(
      $expected_code,
      $this->kleinApp->response()->code()
    );
  }

  /**
   * Test respond return.
   */
  public function testRespondReturn() {
    $return_one = $this->kleinApp->respond(
      function () {
        return 1337;
      }
    );
    $return_two = $this->kleinApp->respond(
      function () {
        return 'dog';
      }
    );

    $this->kleinApp->dispatch(NULL, NULL, FALSE);

    $this->assertTrue(is_callable($return_one));
    $this->assertTrue(is_callable($return_two));
  }

  /**
   * Test respond return chaining.
   */
  public function testRespondReturnChaining() {
    $return_one = $this->kleinApp->respond(
      function () {
        return 1337;
      }
    );
    $return_two = $this->kleinApp->respond(
      function () {
        return 1337;
      }
    )->getPath();

    $this->assertSame($return_one->getPath(), $return_two);
  }

  /**
   * Test catchall implicit.
   */
  public function testCatchallImplicit() {
    $this->expectOutputString('b');

    $this->kleinApp->respond(
      '/one',
      function () {
        echo 'a';
      }
    );
    $this->kleinApp->respond(
      function () {
        echo 'b';
      }
    );
    $this->kleinApp->respond(
      '/two',
      function () {
      }
    );
    $this->kleinApp->respond(
      '/three',
      function () {
        echo 'c';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/two')
    );
  }

  /**
   * Test catchall asterisk.
   */
  public function testCatchallAsterisk() {
    $this->expectOutputString('b');

    $this->kleinApp->respond(
      '/one',
      function () {
        echo 'a';
      }
    );
    $this->kleinApp->respond(
      '*',
      function () {
        echo 'b';
      }
    );
    $this->kleinApp->respond(
      '/two',
      function () {
      }
    );
    $this->kleinApp->respond(
      '/three',
      function () {
        echo 'c';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/two')
    );
  }

  /**
   * Test catchall implicit triggers 404.
   */
  public function testCatchallImplicitTriggers404() {
    $this->expectOutputString("b404\n");

    $this->kleinApp->onHttpError(
      function ($code) {
        if (404 === $code) {
          echo "404\n";
        }
      }
    );

    $this->kleinApp->respond(
      function () {
        echo 'b';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/')
    );
  }

  /**
   * Test regex.
   */
  public function testRegex() {
    $this->expectOutputString('zz');

    $this->kleinApp->respond(
      '@/bar',
      function () {
        echo 'z';
      }
    );

    $this->kleinApp->respond(
      '@/[0-9]s',
      function () {
        echo 'z';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/bar')
    );
    $this->kleinApp->dispatch(
      MockRequestFactory::create('/8s')
    );
    $this->kleinApp->dispatch(
      MockRequestFactory::create('/88s')
    );
  }

  /**
   * Test regex negate.
   */
  public function testRegexNegate() {
    $this->expectOutputString("y");

    $this->kleinApp->respond(
      '!@/foo',
      function () {
        echo 'y';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/bar')
    );
  }

  /**
   * Test normal negate.
   */
  public function testNormalNegate() {
    $this->expectOutputString('');

    $this->kleinApp->respond(
      '!/foo',
      function () {
        echo 'y';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/foo')
    );
  }

  /**
   * Test 404.
   */
  public function test404() {
    $this->expectOutputString("404\n");

    $this->kleinApp->onHttpError(
      function ($code) {
        if (404 === $code) {
          echo "404\n";
        }
      }
    );

    $this->kleinApp->respond(
      '/',
      function () {
        echo 'a';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/foo')
    );

    $this->assertSame(404, $this->kleinApp->response()->code());
  }

  /**
   * Test params basic.
   */
  public function testParamsBasic() {
    $this->expectOutputString('blue');

    $this->kleinApp->respond(
      '/[:color]',
      function ($request) {
        echo $request->param('color');
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/blue')
    );
  }

  /**
   * Test params integer success.
   */
  public function testParamsIntegerSuccess() {
    $this->expectOutputString("string(3) \"987\"");

    $this->kleinApp->respond(
      '/[i:age]',
      function ($request) {
        $age = $request->param('age');

        printf('%s(%d) "%s"', gettype($age), strlen($age), $age);
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/987')
    );
  }

  /**
   * Test params integer fail.
   */
  public function testParamsIntegerFail() {
    $this->expectOutputString('404 Code');

    $this->kleinApp->onHttpError(
      function ($code) {
        if (404 === $code) {
          echo '404 Code';
        }
      }
    );

    $this->kleinApp->respond(
      '/[i:age]',
      function ($request) {
        echo $request->param('age');
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/blue')
    );
  }

  /**
   * Test params alphanum.
   */
  public function testParamsAlphaNum() {
    $this->kleinApp->respond(
      '/[a:audible]',
      function ($request) {
        echo $request->param('audible');
      }
    );

    $this->assertSame(
      'blue42',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/blue42')
      )
    );
    $this->assertSame(
      '',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/texas-29')
      )
    );
    $this->assertSame(
      '',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/texas29!')
      )
    );
  }

  /**
   * Test params hex.
   */
  public function testParamsHex() {
    $this->kleinApp->respond(
      '/[h:hexcolor]',
      function ($request) {
        echo $request->param('hexcolor');
      }
    );

    $this->assertSame(
      '00f',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/00f')
      )
    );
    $this->assertSame(
      'abc123',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/abc123')
      )
    );
    $this->assertSame(
      '',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/876zih')
      )
    );
    $this->assertSame(
      '',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/00g')
      )
    );
    $this->assertSame(
      '',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/hi23')
      )
    );
  }

  /**
   * Test params slug.
   */
  public function testParamsSlug() {
    $this->kleinApp->respond(
      '/[s:slug_name]',
      function ($request) {
        echo $request->param('slug_name');
      }
    );

    $this->assertSame(
      'dog-thing',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/dog-thing')
      )
    );
    $this->assertSame(
      'a_badass_slug',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/a_badass_slug')
      )
    );
    $this->assertSame(
      'AN_UPERCASE_SLUG',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/AN_UPERCASE_SLUG')
      )
    );
    $this->assertSame(
      'sample-wordpress-like-post-slug-based-on-the-title-2013-edition',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/sample-wordpress-like-post-slug-based-on-the-title-2013-edition')
      )
    );
    $this->assertSame(
      '',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/%!@#')
      )
    );
    $this->assertSame(
      '',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/')
      )
    );
    $this->assertSame(
      '',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/dog-%thing')
      )
    );
  }

  /**
   * Test path params are url decoded.
   */
  public function testPathParamsAreUrlDecoded() {
    $this->kleinApp->respond(
      '/[:test]',
      function ($request) {
        echo $request->param('test');
      }
    );

    $this->assertSame(
      'Knife Party',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/Knife%20Party')
      )
    );

    $this->assertSame(
      'and/or',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/and%2For')
      )
    );
  }

  /**
   * Test path params are url decoded to RFC 39865 spec.
   */
  public function testPathParamsAreUrlDecodedToRfc3986Spec() {
    $this->kleinApp->respond(
      '/[:test]',
      function ($request) {
        echo $request->param('test');
      }
    );

    $this->assertNotSame(
      'Knife Party',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/Knife+Party')
      )
    );

    $this->assertSame(
      'Knife+Party',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/Knife+Party')
      )
    );
  }

  /**
   * Test 404 triggers once.
   */
  public function test404TriggersOnce() {
    $this->expectOutputString('d404 Code');

    $this->kleinApp->onHttpError(
      function ($code) {
        if (404 === $code) {
          echo '404 Code';
        }
      }
    );

    $this->kleinApp->respond(
      function () {
        echo "d";
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/notroute')
    );
  }

  /**
   * Test 404 route definition order doesn't effect when 404 handlers called.
   */
  public function test404RouteDefinitionOrderDoesntEffectWhen404HandlersCalled() {
    $this->expectOutputString('onetwo404 Code');

    $this->kleinApp->respond(
      function () {
        echo 'one';
      }
    );
    $this->kleinApp->respond(
      '404',
      function () {
        echo '404 Code';
      }
    );
    $this->kleinApp->respond(
      function () {
        echo 'two';
      }
    );

    // Ignore our deprecation error.
    $old_error_val = error_reporting();
    error_reporting(E_ALL ^ E_USER_DEPRECATED);

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/notroute')
    );

    error_reporting($old_error_val);
  }

  /**
   * Test method catch all.
   */
  public function testMethodCatchAll() {
    $this->expectOutputString('yup!123');

    $this->kleinApp->respond(
      'POST',
      NULL,
      function ($request) {
        echo 'yup!';
      }
    );
    $this->kleinApp->respond(
      'POST',
      '*',
      function ($request) {
        echo '1';
      }
    );
    $this->kleinApp->respond(
      'POST',
      '/',
      function ($request) {
        echo '2';
      }
    );
    $this->kleinApp->respond(
      function ($request) {
        echo '3';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'POST')
    );
  }

  /**
   * Test lazy trailing match.
   */
  public function testLazyTrailingMatch() {
    $this->expectOutputString('this-is-a-title-123');

    $this->kleinApp->respond(
      '/posts/[*:title][i:id]',
      function ($request) {
        echo $request->param('title')
        . $request->param('id');
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/posts/this-is-a-title-123')
    );
  }

  /**
   * Test format match.
   */
  public function testFormatMatch() {
    $this->expectOutputString('xml');

    $this->kleinApp->respond(
      '/output.[xml|json:format]',
      function ($request) {
        echo $request->param('format');
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/output.xml')
    );
  }

  /**
   * Test dot separator.
   */
  public function testDotSeparator() {
    $this->expectOutputString('matchA:slug=ABCD_E--matchB:slug=ABCD_E--');

    $this->kleinApp->respond(
      '/[*:cpath]/[:slug].[:format]',
      function ($rq) {
        echo 'matchA:slug=' . $rq->param("slug") . '--';
      }
    );
    $this->kleinApp->respond(
      '/[*:cpath]/[:slug].[:format]?',
      function ($rq) {
        echo 'matchB:slug=' . $rq->param("slug") . '--';
      }
    );
    $this->kleinApp->respond(
      '/[*:cpath]/[a:slug].[:format]?',
      function ($rq) {
        echo 'matchC:slug=' . $rq->param("slug") . '--';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create("/category1/categoryX/ABCD_E.php")
    );

    $this->assertSame(
      'matchA:slug=ABCD_E--matchB:slug=ABCD_E--',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/category1/categoryX/ABCD_E.php')
      )
    );
    $this->assertSame(
      'matchB:slug=ABCD_E--',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/category1/categoryX/ABCD_E')
      )
    );
  }

  /**
   * Test controller action style route match.
   */
  public function testControllerActionStyleRouteMatch() {
    $this->expectOutputString('donkey-kick');

    $this->kleinApp->respond(
      '/[:controller]?/[:action]?',
      function ($request) {
        echo $request->param('controller')
           . '-' . $request->param('action');
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/donkey/kick')
    );
  }

  /**
   * Test respond argument order.
   */
  public function testRespondArgumentOrder() {
    $this->expectOutputString('abcdef');

    $this->kleinApp->respond(
      function () {
        echo 'a';
      }
    );
    $this->kleinApp->respond(
      NULL,
      function () {
        echo 'b';
      }
    );
    $this->kleinApp->respond(
      '/endpoint',
      function () {
        echo 'c';
      }
    );
    $this->kleinApp->respond(
      'GET',
      NULL,
      function () {
        echo 'd';
      }
    );
    $this->kleinApp->respond(
      ['GET', 'POST'],
      NULL,
      function () {
        echo 'e';
      }
    );
    $this->kleinApp->respond(
      ['GET', 'POST'],
      '/endpoint',
      function () {
        echo 'f';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/endpoint')
    );
  }

  /**
   * Test trailing match.
   */
  public function testTrailingMatch() {
    $this->kleinApp->respond(
      '/?[*:trailing]/dog/?',
      function ($request) {
        echo 'yup';
      }
    );

    $this->assertSame(
      'yup',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/cat/dog')
      )
    );
    $this->assertSame(
      'yup',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/cat/cheese/dog')
      )
    );
    $this->assertSame(
      'yup',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/cat/ball/cheese/dog/')
      )
    );
    $this->assertSame(
      'yup',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/cat/ball/cheese/dog')
      )
    );
    $this->assertSame(
      'yup',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('cat/ball/cheese/dog/')
      )
    );
    $this->assertSame(
      'yup',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('cat/ball/cheese/dog')
      )
    );
  }

  /**
   * Test trailing possessive match.
   */
  public function testTrailingPossessiveMatch() {
    $this->kleinApp->respond(
      '/sub-dir/[**:trailing]',
      function ($request) {
        echo 'yup';
      }
    );

    $this->assertSame(
      'yup',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/sub-dir/dog')
      )
    );

    $this->assertSame(
      'yup',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/sub-dir/cheese/dog')
      )
    );

    $this->assertSame(
      'yup',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/sub-dir/ball/cheese/dog/')
      )
    );

    $this->assertSame(
      'yup',
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create('/sub-dir/ball/cheese/dog')
      )
    );
  }

  /**
   * Test NS dispatch.
   */
  public function testNsDispatch() {
    $this->kleinApp->with(
      '/u',
      function ($kleinApp) {
        $kleinApp->respond(
          'GET',
          '/?',
          function ($request, $response) {
            echo "slash";
          }
        );
        $kleinApp->respond(
          'GET',
          '/[:id]',
          function ($request, $response) {
            echo "id";
          }
        );
      }
    );

    $this->kleinApp->onHttpError(
      function ($code) {
        if (404 === $code) {
          echo "404";
        }
      }
    );

    $this->assertSame(
      "slash",
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create("/u")
      )
    );
    $this->assertSame(
      "slash",
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create("/u/")
      )
    );
    $this->assertSame(
      "id",
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create("/u/35")
      )
    );
    $this->assertSame(
      "404",
      $this->dispatchAndReturnOutput(
        MockRequestFactory::create("/35")
      )
    );
  }

  /**
   * Test NS dispatch external.
   */
  public function testNsDispatchExternal() {
    $ext_namespaces = $this->loadExternalRoutes();

    $this->kleinApp->respond(
      404,
      function ($request, $response) {
        echo "404";
      }
    );

    foreach ($ext_namespaces as $namespace) {
      $this->assertSame(
        'yup',
        $this->dispatchAndReturnOutput(
          MockRequestFactory::create($namespace . '/')
        )
      );

      $this->assertSame(
        'yup',
        $this->dispatchAndReturnOutput(
          MockRequestFactory::create($namespace . '/testing/')
        )
      );
    }
  }

  /**
   * Test NS dispatch external required.
   */
  public function testNsDispatchExternalRerequired() {
    $ext_namespaces = $this->loadExternalRoutes();

    $this->kleinApp->respond(
      404,
      function ($request, $response) {
        echo "404";
      }
    );

    foreach ($ext_namespaces as $namespace) {
      $this->assertSame(
        'yup',
        $this->dispatchAndReturnOutput(
          MockRequestFactory::create($namespace . '/')
        )
      );

      $this->assertSame(
        'yup',
        $this->dispatchAndReturnOutput(
          MockRequestFactory::create($namespace . '/testing/')
        )
      );
    }
  }

  /**
   * Test 405 default request.
   */
  public function test405DefaultRequest() {
    $this->kleinApp->respond(
      ['GET', 'POST'],
      '/',
      function () {
        echo 'fail';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'DELETE')
    );

    $this->assertEquals('405 Method Not Allowed', $this->kleinApp->response()->status()->getFormattedString());
    $this->assertEquals('GET, POST', $this->kleinApp->response()->headers()->get('Allow'));
  }

  /**
   * Test no 405 non match routes.
   */
  public function testNo405OnNonMatchRoutes() {
    $this->kleinApp->respond(
      ['GET', 'POST'],
      NULL,
      function () {
        echo 'this shouldn\'t cause a 405 since this route doesn\'t count as a match anyway';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'DELETE')
    );

    $this->assertEquals(404, $this->kleinApp->response()->code());
  }

  /**
   * Test 405 routes.
   */
  public function test405Routes() {
    $result_array = [];

    $this->expectOutputString('_');

    $this->kleinApp->respond(
      function () {
        echo '_';
      }
    );
    $this->kleinApp->respond(
      'GET',
      '/sure',
      function () {
        echo 'fail';
      }
    );
    $this->kleinApp->respond(
      ['GET', 'POST'],
      '/sure',
      function () {
        echo 'fail';
      }
    );
    $this->kleinApp->respond(
      405,
      function ($a, $b, $c, $d, $e, $f, $methods) use (&$result_array) {
        $result_array = $methods;
      }
    );

    // Ignore our deprecation error.
    $old_error_val = error_reporting();
    error_reporting(E_ALL ^ E_USER_DEPRECATED);

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/sure', 'DELETE')
    );

    error_reporting($old_error_val);

    $this->assertCount(2, $result_array);
    $this->assertContains('GET', $result_array);
    $this->assertContains('POST', $result_array);
    $this->assertSame(405, $this->kleinApp->response()->code());
  }

  /**
   * Test 405 error handler.
   */
  public function test405ErrorHandler() {
    $result_array = [];

    $this->expectOutputString('_');

    $this->kleinApp->respond(
      function () {
        echo '_';
      }
    );
    $this->kleinApp->respond(
      'GET',
      '/sure',
      function () {
        echo 'fail';
      }
    );
    $this->kleinApp->respond(
      ['GET', 'POST'],
      '/sure',
      function () {
        echo 'fail';
      }
    );
    $this->kleinApp->onHttpError(
      function ($code, $klein, $matched, $methods, $exception) use (&$result_array) {
        $result_array = $methods;
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/sure', 'DELETE')
    );

    $this->assertCount(2, $result_array);
    $this->assertContains('GET', $result_array);
    $this->assertContains('POST', $result_array);
    $this->assertSame(405, $this->kleinApp->response()->code());
  }

  /**
   * Test options default request.
   */
  public function testOptionsDefaultRequest() {
    $this->kleinApp->respond(
      function ($request, $response) {
        $response->code(200);
      }
    );
    $this->kleinApp->respond(
      ['GET', 'POST'],
      '/',
      function () {
        echo 'fail';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'OPTIONS')
    );

    $this->assertEquals('200 OK', $this->kleinApp->response()->status()->getFormattedString());
    $this->assertEquals('GET, POST', $this->kleinApp->response()->headers()->get('Allow'));
  }

  /**
   * Test options routes.
   */
  public function testOptionsRoutes() {
    $access_control_headers = [
      [
        'key' => 'Access-Control-Allow-Origin',
        'val' => 'http://example.com',
      ],
      [
        'key' => 'Access-Control-Allow-Methods',
        'val' => 'POST, GET, DELETE, OPTIONS, HEAD',
      ],
    ];

    $this->kleinApp->respond(
      'GET',
      '/',
      function () {
        echo 'fail';
      }
    );
    $this->kleinApp->respond(
      ['GET', 'POST'],
      '/',
      function () {
        echo 'fail';
      }
    );
    $this->kleinApp->respond(
      'OPTIONS',
      NULL,
      function ($request, $response) use ($access_control_headers) {
        // Add access control headers.
        foreach ($access_control_headers as $header) {
          $response->header($header['key'], $header['val']);
        }
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'OPTIONS')
    );

    // Assert headers were passed.
    $this->assertEquals('GET, POST', $this->kleinApp->response()->headers()->get('Allow'));

    foreach ($access_control_headers as $header) {
      $this->assertEquals($header['val'], $this->kleinApp->response()->headers()->get($header['key']));
    }
  }

  /**
   * Test header default request.
   */
  public function testHeadDefaultRequest() {
    $expected_headers = [
      [
        'key' => 'X-Some-Random-Header',
        'val' => 'This was a GET route',
      ],
    ];

    $this->kleinApp->respond(
      'GET',
      NULL,
      function ($request, $response) use ($expected_headers) {
        $response->code(200);

        // Add access control headers.
        foreach ($expected_headers as $header) {
          $response->header($header['key'], $header['val']);
        }
      }
    );
    $this->kleinApp->respond(
      'GET',
      '/',
      function () {
        echo 'GET!';
        return 'more text';
      }
    );
    $this->kleinApp->respond(
      'POST',
      '/',
      function () {
        echo 'POST!';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'HEAD')
    );

    // Make sure we don't get a response body.
    $this->expectOutputString('');

    // Assert headers were passed.
    foreach ($expected_headers as $header) {
      $this->assertEquals($header['val'], $this->kleinApp->response()->headers()->get($header['key']));
    }
  }

  /**
   * Test head method match.
   */
  public function testHeadMethodMatch() {
    $test_strings = [
      'oh, hello',
      'yea',
    ];

    $test_result = NULL;

    $this->kleinApp->respond(
      ['GET', 'HEAD'],
      NULL,
      function ($request, $response) use ($test_strings, &$test_result) {
        $test_result .= $test_strings[0];
      }
    );
    $this->kleinApp->respond(
      'GET',
      '/',
      function ($request, $response) use ($test_strings, &$test_result) {
        $test_result .= $test_strings[1];
      }
    );
    $this->kleinApp->respond(
      'POST',
      '/',
      function ($request, $response) use (&$test_result) {
        $test_result .= 'nope';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'HEAD')
    );

    $this->assertSame(
      implode('', $test_strings),
      $test_result
    );
  }

  /**
   * Test get path for.
   */
  public function testGetPathFor() {
    $this->kleinApp->respond(
      '/dogs',
      function () {
      }
    )->setName('dogs');

    $this->kleinApp->respond(
      '/dogs/[i:dog_id]/collars',
      function () {
      }
    )->setName('dog-collars');

    $this->kleinApp->respond(
      '/dogs/[i:dog_id]/collars/[a:collar_slug]/?',
      function () {
      }
    )->setName('dog-collar-details');

    $this->kleinApp->respond(
      '/dog/foo',
      function () {
      }
    )->setName('dog-foo');

    $this->kleinApp->respond(
      '/dog/[i:dog_id]?',
      function () {
      }
    )->setName('dog-optional-details');

    $this->kleinApp->respond(
      '@/dog/regex',
      function () {
      }
    )->setName('dog-regex');

    $this->kleinApp->respond(
      '!@/dog/regex',
      function () {
      }
    )->setName('dog-neg-regex');

    $this->kleinApp->respond(
      '@\.(json|csv)$',
      function () {
      }
    )->setName('complex-regex');

    $this->kleinApp->respond(
      '!@^/admin/',
      function () {
      }
    )->setName('complex-neg-regex');

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'HEAD')
    );

    $this->assertSame(
      '/dogs',
      $this->kleinApp->getPathFor('dogs')
    );
    $this->assertSame(
      '/dogs/[i:dog_id]/collars',
      $this->kleinApp->getPathFor('dog-collars')
    );
    $this->assertSame(
      '/dogs/idnumberandstuff/collars',
      $this->kleinApp->getPathFor(
        'dog-collars',
        [
          'dog_id' => 'idnumberandstuff',
        ]
      )
    );
    $this->assertSame(
      '/dogs/[i:dog_id]/collars/[a:collar_slug]/?',
      $this->kleinApp->getPathFor('dog-collar-details')
    );
    $this->assertSame(
      '/dogs/idnumberandstuff/collars/d12f3d1f2d3/?',
      $this->kleinApp->getPathFor(
        'dog-collar-details',
        [
          'dog_id' => 'idnumberandstuff',
          'collar_slug' => 'd12f3d1f2d3',
        ]
      )
    );
    $this->assertSame(
      '/dog/foo',
      $this->kleinApp->getPathFor('dog-foo')
    );
    $this->assertSame(
      '/dog',
      $this->kleinApp->getPathFor('dog-optional-details')
    );
    $this->assertSame(
      '/',
      $this->kleinApp->getPathFor('dog-regex')
    );
    $this->assertSame(
      '/',
      $this->kleinApp->getPathFor('dog-neg-regex')
    );
    $this->assertSame(
      '@/dog/regex',
      $this->kleinApp->getPathFor('dog-regex', NULL, FALSE)
    );
    $this->assertNotSame(
      '/',
      $this->kleinApp->getPathFor('dog-neg-regex', NULL, FALSE)
    );
    $this->assertSame(
      '/',
      $this->kleinApp->getPathFor('complex-regex')
    );
    $this->assertSame(
      '/',
      $this->kleinApp->getPathFor('complex-neg-regex')
    );
    $this->assertSame(
      '@\.(json|csv)$',
      $this->kleinApp->getPathFor('complex-regex', NULL, FALSE)
    );
    $this->assertNotSame(
      '/',
      $this->kleinApp->getPathFor('complex-neg-regex', NULL, FALSE)
    );
  }

  /**
   * Test dispatch halt.
   */
  public function testDispatchHalt() {
    $this->expectOutputString('2,4,7,8,');

    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        $kleinApp->skipThis();
        echo '1,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '2,';
        $kleinApp->skipNext();
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '3,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '4,';
        $kleinApp->skipNext(2);
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '5,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '6,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '7,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '8,';
        $kleinApp->skipRemaining();
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '9,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '10,';
      }
    );

    $this->kleinApp->dispatch();
  }

  /**
   * Test dispatch skip causes 404.
   */
  public function testDispatchSkipCauses404() {
    $this->expectOutputString('404');

    $this->kleinApp->onHttpError(
      function ($code) {
        if (404 === $code) {
          echo '404';
        }
      }
    );

    $this->kleinApp->respond(
      'POST',
      '/steez',
      function ($a, $b, $c, $d, $kleinApp) {
        $kleinApp->skipThis();
        echo 'Style... with ease';
      }
    );
    $this->kleinApp->respond(
      'GET',
      '/nope',
      function ($a, $b, $c, $d, $kleinApp) {
        echo 'How did I get here?!';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/steez', 'POST')
    );
  }

  /**
   * Test dispatch abort.
   */
  public function testDispatchAbort() {
    $this->expectOutputString('1,');

    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '1,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        $kleinApp->abort();
        echo '2,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '3,';
      }
    );

    $this->kleinApp->dispatch();

    $this->assertSame(404, $this->kleinApp->response()->code());
  }

  /**
   * Test dispatch abort with code.
   */
  public function testDispatchAbortWithCode() {
    $this->expectOutputString('1,');

    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '1,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        $kleinApp->abort(404);
        echo '2,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '3,';
      }
    );

    $this->kleinApp->dispatch();

    $this->assertSame(404, $this->kleinApp->response()->code());
  }

  /**
   * Test dispatch abort calls http error.
   */
  public function testDispatchAbortCallsHttpError() {
    $test_code = 666;
    $this->expectOutputString('1,aborted,' . $test_code);

    $this->kleinApp->onHttpError(
      function ($code, $kleinApp) {
        echo 'aborted,';
        echo $code;
      }
    );

    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '1,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) use ($test_code) {
        $kleinApp->abort($test_code);
        echo '2,';
      }
    );
    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) {
        echo '3,';
      }
    );

    $this->kleinApp->dispatch();

    $this->assertSame($test_code, $this->kleinApp->response()->code());
  }

  /**
   * Test dispatch exception rethrows unknown code.
   */
  public function testDispatchExceptionRethrowsUnknownCode() {
    $this->expectException(UnhandledException::class);

    $this->expectOutputString('');

    $test_message = 'whatever';
    $test_code = 666;

    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) use ($test_message, $test_code) {
        throw new DispatchHaltedException($test_message, $test_code);
      }
    );

    $this->kleinApp->dispatch();

    $this->assertSame(404, $this->kleinApp->response()->code());
  }

  /**
   * Test throw HTTP exception handled properly.
   */
  public function testThrowHttpExceptionHandledProperly() {
    $this->expectOutputString('');

    $this->kleinApp->respond(
      '/',
      function ($a, $b, $c, $d, $kleinApp) {
        throw HttpException::createFromCode(400);

        // phpcs:disable
        echo 'hi!';
        // phpcs:enable
      }
    );

    $this->kleinApp->dispatch(MockRequestFactory::create('/'));

    $this->assertSame(400, $this->kleinApp->response()->code());
  }

  /**
   * Test HTTP exception stops route matching.
   */
  public function testHttpExceptionStopsRouteMatching() {
    $this->expectOutputString('one');

    $this->kleinApp->respond(
      function () {
        echo 'one';

        throw HttpException::createFromCode(404);
      }
    );
    $this->kleinApp->respond(
      function () {
        echo 'two';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/notroute')
    );
  }

  /**
   * Test options alias.
   */
  public function testOptionsAlias() {
    $this->expectOutputString('1,2,');

    // With path.
    $this->kleinApp->options(
      '/',
      function () {
        echo '1,';
      }
    );

    // Without path.
    $this->kleinApp->options(
      function () {
        echo '2,';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'OPTIONS')
    );
  }

  /**
   * Test head alias.
   */
  public function testHeadAlias() {
    // HEAD requests shouldn't return data.
    $this->expectOutputString('');

    // With path.
    $this->kleinApp->head(
      '/',
      function ($request, $response) {
        echo '1,';
        $response->headers()->set('Test-1', 'yup');
      }
    );

    // Without path.
    $this->kleinApp->head(
      function ($request, $response) {
        echo '2,';
        $response->headers()->set('Test-2', 'yup');
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'HEAD')
    );

    $this->assertTrue($this->kleinApp->response()->headers()->exists('Test-1'));
    $this->assertTrue($this->kleinApp->response()->headers()->exists('Test-2'));
    $this->assertFalse($this->kleinApp->response()->headers()->exists('Test-3'));
  }

  /**
   * Test get alias.
   */
  public function testGetAlias() {
    $this->expectOutputString('1,2,');

    // With path.
    $this->kleinApp->get(
      '/',
      function () {
        echo '1,';
      }
    );

    // Without path.
    $this->kleinApp->get(
      function () {
        echo '2,';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/')
    );
  }

  /**
   * Test post alias.
   */
  public function testPostAlias() {
    $this->expectOutputString('1,2,');

    // With path.
    $this->kleinApp->post(
      '/',
      function () {
        echo '1,';
      }
    );

    // Without path.
    $this->kleinApp->post(
      function () {
        echo '2,';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'POST')
    );
  }

  /**
   * Test put alias.
   */
  public function testPutAlias() {
    $this->expectOutputString('1,2,');

    // With path.
    $this->kleinApp->put(
      '/',
      function () {
        echo '1,';
      }
    );

    // Without path.
    $this->kleinApp->put(
      function () {
        echo '2,';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'PUT')
    );
  }

  /**
   * Test delete alias.
   */
  public function testDeleteAlias() {
    $this->expectOutputString('1,2,');

    // With path.
    $this->kleinApp->delete(
      '/',
      function () {
        echo '1,';
      }
    );

    // Without path.
    $this->kleinApp->delete(
      function () {
        echo '2,';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/', 'DELETE')
    );
  }

  /**
   * Advanced string route matching tests.
   *
   * As the original Klein project was designed as a PHP version of Sinatra,
   * many of the following tests are ports of the Sinatra ruby equivalents:
   * https://github.com/sinatra/sinatra/blob/cd82a57154d57c18acfadbfefbefc6ea6a5035af/test/routing_test.rb.
   */

  /**
   * Test matches encoded slashes.
   */
  public function testMatchesEncodedSlashes() {
    $this->kleinApp->respond(
      '/[:a]',
      function ($request) {
        return $request->param('a');
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/foo%2Fbar'),
      NULL,
      TRUE,
      Klein::DISPATCH_CAPTURE_AND_RETURN
    );

    $this->assertSame(200, $this->kleinApp->response()->code());
    $this->assertSame('foo/bar', $this->kleinApp->response()->body());
  }

  /**
   * Test matches dot as named param.
   */
  public function testMatchesDotAsNamedParam() {
    $this->kleinApp->respond(
      '/[:foo]/[:bar]',
      function ($request) {
        return $request->param('foo');
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/user@example.com/name'),
      NULL,
      TRUE,
      Klein::DISPATCH_CAPTURE_AND_RETURN
    );

    $this->assertSame(200, $this->kleinApp->response()->code());
    $this->assertSame('user@example.com', $this->kleinApp->response()->body());
  }

  /**
   * Test matches dot outside of named param.
   */
  public function testMatchesDotOutsideOfNamedParam() {
    $file = NULL;
    $ext = NULL;

    $this->kleinApp->respond(
      '/[:file].[:ext]',
      function ($request) use (&$file, &$ext) {
        $file = $request->param('file');
        $ext = $request->param('ext');

        return 'woot!';
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/unicorn.png'),
      NULL,
      TRUE,
      Klein::DISPATCH_CAPTURE_AND_RETURN
    );

    $this->assertSame(200, $this->kleinApp->response()->code());
    $this->assertSame('woot!', $this->kleinApp->response()->body());
    $this->assertSame('unicorn', $file);
    $this->assertSame('png', $ext);
  }

  /**
   * Test matches literal dots in paths.
   */
  public function testMatchesLiteralDotsInPaths() {
    $this->kleinApp->respond(
      '/file.ext',
      function () {
      }
    );

    // Should match.
    $this->kleinApp->dispatch(
      MockRequestFactory::create('/file.ext')
    );
    $this->assertSame(200, $this->kleinApp->response()->code());

    // Shouldn't match.
    $this->kleinApp->dispatch(
      MockRequestFactory::create('/file0ext')
    );
    $this->assertSame(404, $this->kleinApp->response()->code());
  }

  /**
   * Test matches literal dots in path before named param.
   */
  public function testMatchesLiteralDotsInPathBeforeNamedParam() {
    $this->kleinApp->respond(
      '/file.[:ext]',
      function () {
      }
    );

    // Should match.
    $this->kleinApp->dispatch(
      MockRequestFactory::create('/file.ext')
    );
    $this->assertSame(200, $this->kleinApp->response()->code());

    // Shouldn't match.
    $this->kleinApp->dispatch(
      MockRequestFactory::create('/file0ext')
    );
    $this->assertSame(404, $this->kleinApp->response()->code());
  }

  /**
   * Test multiple unsafe characters aren't over quoted.
   */
  public function testMultipleUnsafeCharactersArentOverQuoted() {
    $this->kleinApp->respond(
      '/[a:site].[:format]?/[:id].[:format2]?',
      function () {
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/site.main/id.json')
    );
    $this->assertSame(200, $this->kleinApp->response()->code());
  }

  /**
   * Test matches literal plus signs in paths.
   */
  public function testMatchesLiteralPlusSignsInPaths() {
    $this->kleinApp->respond(
      '/te+st',
      function () {
      }
    );

    // Should match.
    $this->kleinApp->dispatch(
      MockRequestFactory::create('/te+st')
    );
    $this->assertSame(200, $this->kleinApp->response()->code());

    // Shouldn't match.
    $this->kleinApp->dispatch(
      MockRequestFactory::create('/teeeeeeeeest')
    );
    $this->assertSame(404, $this->kleinApp->response()->code());
  }

  /**
   * Test matches parentheses in paths.
   */
  public function testMatchesParenthesesInPaths() {
    $this->kleinApp->respond(
      '/test(bar)',
      function () {
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/test(bar)')
    );
    $this->assertSame(200, $this->kleinApp->response()->code());
  }

  /**
   * Test matches advacned regular expressions.
   */
  public function testMatchesAdvancedRegularExpressions() {
    $this->kleinApp->respond(
      '@^/foo.../bar$',
      function () {
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/foooom/bar')
    );
    $this->assertSame(200, $this->kleinApp->response()->code());
  }

  /**
   * Test apc dependency fails gracefully.
   */
  public function testApcDependencyFailsGracefully() {
    // Custom apc function.
    implement_custom_apc_cache_functions();

    $this->kleinApp->respond(
      '/test',
      function () {
      }
    );

    $this->kleinApp->dispatch(
      MockRequestFactory::create('/test')
    );
    $this->assertSame(200, $this->kleinApp->response()->code());
  }

  /**
   * Test route path compilation failure.
   */
  public function testRoutePathCompilationFailure() {
    $this->kleinApp->respond(
      '/users/[i:id]/friends/[i:id]/',
      function () {
        echo 'yup';
      }
    );

    $exception = NULL;

    try {
      $this->kleinApp->dispatch(
        MockRequestFactory::create('/users/1738197/friends/7828316')
      );
    }
    catch (\Exception $e) {
      $exception = $e->getPrevious();
    }

    $this->assertTrue($exception instanceof RoutePathCompilationException);
    $this->assertTrue($exception->getRoute() instanceof Route);
  }

  /**
   * Test route patch compilation failure without warnings.
   */
  public function testRoutePathCompilationFailureWithoutWarnings() {
    $old_error_val = error_reporting();
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

    $this->testRoutePathCompilationFailure();

    error_reporting($old_error_val);
  }

}
