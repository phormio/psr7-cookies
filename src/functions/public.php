<?php
namespace phormio\Psr7Cookies;

use DateTime;
use DateTimeZone;
use Psr\Http\Message\MessageInterface;

/**
  * @return \Psr\Http\Message\MessageInterface
  * @throws \phormio\Psr7Cookies\Exception
  */
function change_client_cookies(MessageInterface $message) {
  $list = array_slice(func_get_args(), 1);
  check_change_list($list);

  $result = $message;

  foreach ($list as $array) {
    $func =
      __NAMESPACE__ .
      '\\' .
      ($array[0]{0} === '+'? 'with_cookie_set': 'with_cookie_unset');
    $cookie_name = substr($array[0], 1);
    $args = array_merge(
      array($result, $cookie_name),
      array_slice($array, 1)
    );
    $result = call_user_func_array($func, $args);
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
  if (!valid_cookie_name($cookie_name)) {
    throw exception('Invalid cookie name', $cookie_name);
  }

  if (!valid_cookie_value($cookie_name)) {
    throw exception('Invalid cookie value', $cookie_value);
  }

  if (array_key_exists('domain', $cookie_attribs)) {
    if (!valid_domain($cookie_attribs['domain'])) {
      throw exception(
        'Invalid cookie domain', $cookie_attribs['domain']
      );
    }
  }

  if (array_key_exists('expires', $cookie_attribs)) {
    if (!valid_expires($cookie_attribs['expires'])) {
      throw exception(
        "Invalid 'expires' value", $cookie_attribs['expires']
      );
    }
  }

  if (array_key_exists('max_age', $cookie_attribs)) {
    if (!valid_max_age($cookie_attribs['max_age'])) {
      throw exception(
        "Invalid 'max_age' value", $cookie_attribs['max_age']
      );
    }
  }

  if (array_key_exists('path', $cookie_attribs)) {
    if (!valid_path($cookie_attribs['path'])) {
      throw exception(
        'Invalid cookie path', $cookie_attribs['path']
      );
    }
  }

  $header_field_value = $cookie_name . '=' . $cookie_value;

  if (array_key_exists('domain', $cookie_attribs)) {
    $header_field_value .= '; Domain=' . $cookie_attribs['domain'];
  }

  if (array_key_exists('expires', $cookie_attribs)) {
    $expires = $cookie_attribs['expires'];
    if (is_object($expires)) {
      $time = clone($expires);
    } else {
      $time = new DateTime('@' . $expires);
    }
    $time->setTimeZone(new DateTimeZone('UTC'));
    $header_field_value .=
      '; Expires=' . $time->format('D, d M Y H:i:s') . ' GMT';
  }

  if (array_key_exists('http_only', $cookie_attribs) &&
    $cookie_attribs['http_only'])
  {
    $header_field_value .= '; HttpOnly';
  }

  if (array_key_exists('max_age', $cookie_attribs)) {
    $header_field_value .= '; Max-Age=' . $cookie_attribs['max_age'];
  }

  if (array_key_exists('path', $cookie_attribs)) {
    $header_field_value .= '; Path=' . $cookie_attribs['path'];
  }

  if (array_key_exists('secure', $cookie_attribs) &&
    $cookie_attribs['secure'])
  {
    $header_field_value .= '; Secure';
  }

  return $message->withAddedHeader('Set-Cookie', $header_field_value);
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
