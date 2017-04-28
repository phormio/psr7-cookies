<?php
namespace phormio\Psr7Cookies;

use DateTime;

/**
  * @internal
  * @param array[] $change_list
  * @return void
  * @throws \LogicException
  */
function check_change_list(array $change_list) {
  foreach ($change_list as $index => $member) {
    if (!valid_change($member)) {
      throw new \LogicException(
        "Invalid cookie change list: change at index $index is invalid"
      );
    }
  }
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
    return is_scalar($x) && preg_match('@\A-?\d+\z@', $x) === 1;
  }
}

/**
  * @internal
  * @return bool
  * @throws \LogicException
  */
function valid_change($change) {
  $result = FALSE;

  if (is_array($change) && count($change) >= 1) {
    $tail = array_slice($change, 1);

    switch ($change[0]) {
      case '+':
        $result =
          count($tail) == 2 ||
          (count($tail) == 3 && is_array($tail[2]));
      break;

      case '-':
        $result =
          count($tail) == 1 ||
          (count($tail) == 2 && is_array($tail[1]));
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
      function ($char) {
        return preg_quote($char, '@');
      },
      $chars
    );
    $regex = '@\A(' . implode('|', $tmp) . ')+\z@';
  }

  return preg_match($regex, $name) === 1;
}

/**
  * @internal
  * @return bool
  */
function valid_cookie_value($value) {
  $regex = '@\A[\x21\x23-\x2B\x2D-\x3A\x3C-\x5B\x5D-\x7E]+\z@';
  return preg_match($regex, $value) === 1;
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

  return preg_match($regex, $domain) === 1;
}

/**
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
  return preg_match($regex, $path) === 1;
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
