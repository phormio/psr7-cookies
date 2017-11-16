<?php
namespace phormio\Psr7Cookies;

use DateTime;

/**
  * @internal
  * @param array[] $change_list
  * @return void
  * @throws \phormio\Psr7Cookies\Exception
  */
function check_change_list(array $change_list) {
  foreach ($change_list as $index => $member) {
    if (!valid_change($member)) {
      throw new Exception(
        "Invalid cookie change list: change at index $index is invalid"
      );
    }
  }
}

/**
  * @internal
  * @param string $message
  * @param mixed $subject
  * @return \phormio\Psr7Cookies\Exception
  */
function exception($message, $subject) {
  $msg2 = $message;
  if (stringifiable($subject)) {
    $msg2 .= ': ' . $subject;
  }
  return new Exception($msg2);
}

/**
  * @internal
  * @param mixed $x
  * @return bool
  */
function is_math_integer($x) {
  if (is_float($x)) {
    return fmod($x, 1) == 0;
  } else {
    return
      is_integer($x) ||
      is_numeric($x) && preg_match('@\.\d*[1-9]@', $x) === 0;
  }
}

/**
  * @internal
  * @param mixed $x
  * @return bool
  */
function stringifiable($x) {
  return
    is_scalar($x) || is_object($x) && method_exists($x, '__toString');
}

/**
  * @internal
  * @return bool
  */
function valid_change($change) {
  $result = FALSE;

  if (is_array($change) && count($change) >= 1 && strlen($change[0]) >= 2) {
    $tail = array_slice($change, 1);

    switch ($change[0]{0}) {
      case '+':
        $result =
          count($tail) == 1 ||
          (count($tail) == 2 && is_array($tail[1]));
      break;

      case '-':
        $result =
          count($tail) == 0 ||
          (count($tail) == 1 && is_array($tail[0]));
      break;
    }
  }

  return $result;
}

/**
  * @internal
  * @return bool
  */
function valid_cookie_name($name) {
  /*>
    This could be optimised (e.g. by having a static regular expression),
    but as it stands, it has the merit of being easy to check against the
    RFCs.
  */

  static $regex;

  if ($regex === NULL) {
    $chars = array_diff(
      array_map('chr', range(32, 126)),
      str_split('()<>@,;:\\"/[]?={}'),
      array(' ', "\t")
    );
    $tmp = array_map(
      function ($c) {
        return preg_quote($c, '@');
      },
      $chars
    );
    $regex = '@\A(' . implode('|', $tmp) . ')+\z@';
  }

  return stringifiable($name) && preg_match($regex, $name) === 1;
}

/**
  * @internal
  * @return bool
  */
function valid_cookie_value($value) {
  $regex = '@\A[\x21\x23-\x2B\x2D-\x3A\x3C-\x5B\x5D-\x7E]+\z@';
  return stringifiable($value) && preg_match($regex, $value) === 1;
}

/**
  * @internal
  * @return bool
  */
function valid_domain($domain) {
  /*
    Our reference:

      https://en.wikipedia.org/w/index.php?title=Domain_Name_System&oldid=776136018#Domain_name_syntax
  */

  $regex = '@
    \A
    [A-Za-z0-9-]+
    (\.[A-Za-z0-9-]+)*
    \z
  @x';

  return stringifiable($domain) && preg_match($regex, $domain) === 1;
}

/**
  * TRUE if and only if $expires is a valid expiry time.
  *
  * Negative values are accepted.
  *
  * @internal
  * @return bool
  */
function valid_expires($expires) {
  return
    $expires instanceof DateTimeInterface ||
    $expires instanceof DateTime ||
    is_math_integer($expires);

  /*<
    Older versions of PHP lack DateTimeInterface.  It was probably first
    introduced in PHP 5.5.0, along with DateTimeImmutable.
    <https://secure.php.net/ChangeLog-5.php#5.5.0>.
  */
}

/**
  * TRUE if and only if $expires is a valid Max-Age.
  *
  * Negative values are accepted, as per the RFC.  See
  * <https://tools.ietf.org/html/rfc6265#section-5.2.2>.
  *
  * @internal
  * @return bool
  */
function valid_max_age($max_age) {
  return is_math_integer($max_age);
}

/**
  * @internal
  * @return bool
  */
function valid_path($path) {
  $regex = '@
    \A
    [\x20-\x3A\x3C-\x7E]*
    \z
  @x';
  return stringifiable($path) && preg_match($regex, $path) === 1;
}

/**
  * @internal
  * @param callable $func
  * @param mixed[] $orig_args
  * @return \Psr\Http\Message\MessageInterface
  */
function with_cookie_list_x($func, array $orig_args) {
  $result = $orig_args[0];
  foreach (array_slice($orig_args, 1) as $array) {
    $tmp = $array;
    array_unshift($tmp, $result);
    $result = call_user_func_array($func, $tmp);
  }
  return $result;
}
