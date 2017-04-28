<?php
namespace phormio\Psr7Cookies;

use DateTime;
use Phake;

class FunctionsTest extends \PHPUnit_Framework_TestCase {
  const MESSAGE_INTERFACE_FQCN = '\Psr\Http\Message\MessageInterface';

  public function test__with_cookie_set__basic() {
    #> Given

    $message = Phake::mock(self::MESSAGE_INTERFACE_FQCN);

    #> When

    with_cookie_set(
      $message,
      'city',
      'London'
    );

    #> Then

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      'city=London'
    );
  }

  public function test__with_cookie_set__all_attributes() {
    #> Given

    $message = Phake::mock(self::MESSAGE_INTERFACE_FQCN);

    #> When

    with_cookie_set(
      $message,
      'city',
      'London',
      array(
        'domain' => 'example.com',
        'expires' => new DateTime('2000-01-01 12:00 +0600'),
        'max_age' => 100,
        'path' => '/a/b',
      )
    );

    #> Then

    $expected_header_field_value =
      'city=London; Domain=example.com; ' .
      'Expires=Sat, 01 Jan 2000 06:00:00 GMT; Max-Age=100; Path=/a/b';

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      $expected_header_field_value
    );
  }

  /** @dataProvider provideInvalidAttributes */
  public function test__with_cookie_set__invalid_attributes
    (array $attributes)
  {
    $message = Phake::mock(self::MESSAGE_INTERFACE_FQCN);

    try {
      with_cookie_set(
        $message,
        'city',
        'London',
        $attributes
      );
    } catch (\PHPUnit_Framework_Error_Warning $exception) {}

    $this->assertNotEmpty($exception);
    $this->assertTrue(self::traceContainsAssert($exception));
  }

  public function provideInvalidAttributes() {
    return array(
      array(array('domain' => '')),
      array(array('domain' => 'a/b')),

      array(array('expires' => new \StdClass)),
      array(array('expires' => new \DateInterval('P1D'))),
      array(array('expires' => 'x')),
      array(array('expires' => NAN)),
      array(array('expires' => INF)),

      array(array('max_age' => new \StdClass)),
      array(array('max_age' => 'x')),
      array(array('max_age' => NAN)),
      array(array('max_age' => INF)),

      array(array('path' => "x\ty")),
      array(array('path' => ';')),
      array(array('path' => "\x01")),
    );
  }

  public function test__with_cookie_list_set() {
    #> Given

    $message = Phake::mock(self::MESSAGE_INTERFACE_FQCN);

    Phake::when($message)
      ->withAddedHeader(Phake::anyParameters())
      ->thenReturn($message)
      ->thenReturn($message);

    #> When

    with_cookie_list_set(
      $message,
      array('city', 'London'),
      array(
        'country',
        'UK',
        array(
          'domain' => 'example.com',
        )
      )
    );

    #> Then

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      'city=London'
    );

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      'country=UK; Domain=example.com'
    );
  }

  public function test__with_cookie_unset__basic() {
    #> Given

    $message = Phake::mock(self::MESSAGE_INTERFACE_FQCN);

    #> When

    with_cookie_unset(
      $message,
      'city'
    );

    #> Then

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      'city=; Expires=Thu, 01 Jan 1970 00:00:00 GMT'
    );
  }

  public function test__with_cookie_unset__with_attributes() {
    #> Given

    $message = Phake::mock(self::MESSAGE_INTERFACE_FQCN);

    #> When

    with_cookie_unset(
      $message,
      'city',
      array(
        'domain' => 'x.com',
        'path' => '/y',
        'unrecognized_attribute' => 'x',
      )
    );

    #> Then

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      'city=; Domain=x.com; Expires=Thu, 01 Jan 1970 00:00:00 GMT' .
        '; Path=/y'
    );
  }

  public function test__with_cookie_list_unset() {
    #> Given

    $message = Phake::mock(self::MESSAGE_INTERFACE_FQCN);

    Phake::when($message)
      ->withAddedHeader(Phake::anyParameters())
      ->thenReturn($message)
      ->thenReturn($message);

    #> When

    with_cookie_list_unset(
      $message,
      array('city'),
      array(
        'country',
        array(
          'domain' => 'example.com',
          'path' => '/x',
          #> The next two will not be used (if they are, it's a bug).
          'expires' => new \DateTime('+ 1 year'),
          'unrecognized_attribute' => 1,
        )
      )
    );

    #> Then

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      'city=; Expires=Thu, 01 Jan 1970 00:00:00 GMT'
    );

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      'country=; Domain=example.com; ' .
        'Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/x'
    );
  }

  public function test__change_client_cookies() {
    #> Given

    $message = Phake::mock(self::MESSAGE_INTERFACE_FQCN);

    Phake::when($message)
      ->withAddedHeader(Phake::anyParameters())
      ->thenReturn($message)
      ->thenReturn($message)
      ->thenReturn($message);

    #> When

    change_client_cookies(
      $message,
      array('+', 'city', 'London', array('domain' => 'x.com')),
      array('-', 'spice', array('domain' => 'y.com')),
      array(
        '-',
        'jewel',
        array('domain' => 'y.com', 'path' => '/z'),
      )
    );

    #> Then

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      'city=London; Domain=x.com'
    );

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      'spice=; Domain=y.com; Expires=Thu, 01 Jan 1970 00:00:00 GMT'
    );

    Phake::verify($message)->withAddedHeader(
      'Set-Cookie',
      'jewel=; Domain=y.com; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/z'
    );
  }

  /** @dataProvider provide__invalid_input_for_change_client_cookies */
  public function test__change_client_cookies__invalid_input
    ($invalid_input)
  {
    $message = Phake::mock(self::MESSAGE_INTERFACE_FQCN);

    $this->setExpectedException('LogicException');

    change_client_cookies(
      $message,
      $invalid_input
    );
  }

  public function provide__invalid_input_for_change_client_cookies() {
    return array(
      array(1),
      array(array(1, 2)),
      array(array('+', 'city', 'London', 'not-an-array')),
      array(array('-', 'city', 'not-an-array')),
      array(array('-')),
    );
  }

  /** @dataProvider provide__func_and_bad_args */
  public function test__with_cookie_list_x__error
    ($function_under_test, $bad_args)
  {
    #> Given

    $message = Phake::mock(self::MESSAGE_INTERFACE_FQCN);

    Phake::when($message)
      ->withAddedHeader(Phake::anyParameters())
      ->thenReturn($message);

    #> When

    $call_function_under_test = function ()
        use ($function_under_test, $message, $bad_args)
      {
        call_user_func(
          __NAMESPACE__ . '\\' . $func,
          $message,
          $bad_args
        );
      };

    if (interface_exists('Throwable')) {
      try {
        call_user_func($call_function_under_test);
      } catch (\Throwable $throwable) {}
    } else {
      try {
        call_user_func($call_function_under_test);
      } catch (\Exception $throwable) {}
    }

    #> Then

    $this->assertNotEmpty($throwable);
  }

  public function provide__func_and_bad_args() {
    return array(
      array(
        'with_cookie_list_set',
        array(),
      ),
      array(
        'with_cookie_list_set',
        array('x'),
      ),
      array(
        'with_cookie_list_set',
        array('x', 'y', 'z'),
      ),
      array(
        'with_cookie_list_unset',
        array(),
      ),
      array(
        'with_cookie_list_unset',
        array('x', 'y'),
      ),
    );
  }

  /**
    * @return bool TRUE iff the trace for $exception contains a call to PHP's built-in `assert' function.
    */
  private static function traceContainsAssert(\Exception $exception) {
    $result = FALSE;

    foreach ($exception->getTrace() as $frame) {
      $has_assert =
        $frame['function'] === 'assert' &&
          !array_key_exists('class', $frame);

      if ($has_assert) {
        $result = TRUE;
        break;
      }
    }

    return $result;
  }
}
