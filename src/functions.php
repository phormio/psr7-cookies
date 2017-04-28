<?php
namespace phormio\Psr7Cookies;

use DateTime;
use DateTimeZone;
use Psr\Http\Message\MessageInterface;

/**
  * @return \Psr\Http\Message\MessageInterface
  * @throws \LogicException
  */
function change_client_cookies(MessageInterface $message) {
  $list = array_slice(func_get_args(), 1);
  check_change_list($list);

  /*<
    Design:

      Using an assertion, instead of the above, was considered, and
      rejected, because if an assertion is used, it does not seem easy to
      write code which:

        * tells the user (i.e. the programmer) which array element has the
        problem;
        * works in PHP 5 and PHP 7;
        * is not excessively verbose.
  */

  $result = $message;

  foreach ($list as $array) {
    $func =
      __NAMESPACE__ .
      '\\' .
      ($array[0] === '+'? 'with_cookie_set': 'with_cookie_unset');
    $result = call_user_func_array(
      $func,
      array($result) + $array
    );
  }

  return $result;
}

/**
  * @return \Psr\Http\Message\MessageInterface
  */
function with_cookie_list_set(MessageInterface $message) {
  return with_cookie_list_x(
    __NAMESPACE__ . "\\with_cookie_set",
    func_get_args()
  );
}

/**
  * @return \Psr\Http\Message\MessageInterface
  */
function with_cookie_list_unset(MessageInterface $message) {
  return with_cookie_list_x(
    __NAMESPACE__ . "\\with_cookie_unset",
    func_get_args()
  );
}

/**
  * @param string $cookie_name
  * @param string $cookie_value
  * @param array<string, mixed> $cookie_attribs
  * @return \Psr\Http\Message\MessageInterface
  */
function with_cookie_set
  (MessageInterface $message, $cookie_name, $cookie_value,
    array $cookie_attribs = array())
  /*<
    ‘attribs’ (attributes) is the right term, because
    <https://tools.ietf.org/html/rfc6265> says:

      The portions of the set-cookie-string produced by the cookie-av term
      are known as attributes.
  */
{
  assert(valid_cookie_name($cookie_name));
  assert(valid_cookie_value($cookie_name));
  if (array_key_exists('domain', $cookie_attribs)) {
    assert(valid_domain($cookie_attribs['domain']));
  }
  if (array_key_exists('expires', $cookie_attribs)) {
    assert(valid_expires($cookie_attribs['expires']));
  }
  if (array_key_exists('max_age', $cookie_attribs)) {
    assert(valid_max_age($cookie_attribs['max_age']));
  }
  if (array_key_exists('path', $cookie_attribs)) {
    assert(valid_path($cookie_attribs['path']));
  }

  $field_value = $cookie_name . '=' . $cookie_value;

  if (array_key_exists('domain', $cookie_attribs)) {
    $field_value .= '; Domain=' . $cookie_attribs['domain'];
  }

  if (array_key_exists('expires', $cookie_attribs)) {
    $expires = $cookie_attribs['expires'];
    if (is_object($expires)) {
      $time = clone($expires);
    } else {
      $time = new DateTime('@' . $expires);
    }
    $time->setTimeZone(new DateTimeZone('UTC'));
    $field_value .=
      '; Expires=' . $time->format('D, d M Y H:i:s') . ' GMT';
    /*<
      We are not using DateTime::RFC1123 as the format because that
      produces a numeric timezone, not "GMT".
    */
  }

  if (array_key_exists('http_only', $cookie_attribs) &&
    $cookie_attribs['http_only'])
  {
    $field_value .= '; HttpOnly';
  }

  if (array_key_exists('max_age', $cookie_attribs)) {
    $field_value .= '; Max-Age=' . $cookie_attribs['max_age'];
  }

  if (array_key_exists('path', $cookie_attribs)) {
    $field_value .= '; Path=' . $cookie_attribs['path'];
  }

  if (array_key_exists('secure', $cookie_attribs) &&
    $cookie_attribs['secure'])
  {
    $field_value .= '; Secure';
  }

  return $message->withAddedHeader('Set-Cookie', $field_value);
}

/**
  * @param string $cookie_name
  * @param array<string, mixed> $cookie_attribs
  * @return \Psr\Http\Message\MessageInterface
  */
function with_cookie_unset
  (MessageInterface $message, $cookie_name, array $cookie_attribs = array())
{
  $tmp = array('expires' => 0);
  foreach (array('domain', 'path') as $key) {
    if (array_key_exists($key, $cookie_attribs)) {
      $tmp[$key] = $cookie_attribs[$key];
    }
  }
  $new_cookie_attribs = $tmp;

  return with_cookie_set($message, $cookie_name, '', $new_cookie_attribs);
}
