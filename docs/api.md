# API

## Overview

The API consists of 5 functions in
the `phormio\Psr7Cookies` namespace.
They are:

| Name | Description |
| --- | --- |
| `with_cookie_set` | Set a cookie |
| `with_cookie_unset` | Unset a cookie |
| `with_cookie_list_set` | Set a list of cookies |
| `with_cookie_list_unset` | Unset a list of cookies |
| `change_client_cookies` | Allows setting and unsetting cookies |

The first argument and return type of each
function are of type
`\Psr\Http\Message\MessageInterface`.

All of the functions leave all of their arguments unchanged.

## with_cookie_set

```php
/**
 * @param \Psr\Http\Message\MessageInterface $message
 * @param string $cookie_name
 * @param string $cookie_value
 * @param array<string, mixed> $cookie_attribs
 * @return \Psr\Http\Message\MessageInterface;
 */
function with_cookie_set
  (MessageInterface $message, $cookie_name, $cookie_value,
    array $cookie_attribs = array())
{
  # ...
}
```

Here are the possible keys for `$cookie_attribs`,
together with the possible types for the associated
value.

| Key | Types |
| --- | --- |
| `domain` | string |
| `expire` | integer, `DateTime`, `DateTimeInterface` |
| `max_age` | integer |
| `path` | string |

There are no mandatory keys.

Types are not strict.  For example, a "string" can be a PHP integer;
an "integer" can be a PHP string that looks like an integer.

If `expire` is an integer,
it is treated as a Unix timestamp.

Note that a value of zero for `expire` is not treated
as a special case; it just means
the start of the Unix epoch
(in other words, 1970-01-01T00:00:00+0000).
The behaviour here is different from
the behaviour of PHP's
[setcookie](https://secure.php.net/manual/en/function.setcookie.php),
which *does* treat this as a special case.

Note that `max_age` has no effect if the browser is
Microsoft Internet Explorer
or
Microsoft Edge.
More information [here](max-age-in-ms-browsers.md).

## with_cookie_unset

This function
puts a `Set-Cookie` field in the HTTP header that will
erase the cookie on the client side.
Here is its signature:

```php
/**
 * @param \Psr\Http\Message\MessageInterface $message
 * @param string $cookie_name
 * @param array<string, mixed> $cookie_attribs
 * @return \Psr\Http\Message\MessageInterface;
 */
function with_cookie_unset
  (MessageInterface $message, $cookie_name, array $cookie_attribs = array())
{
  # ...
}
```

The keys that are recognised in `$cookie_attribs` are:

* `domain`
* `path`

## with_cookie_list_set

Here is an incomplete signature:

```php
/**
 * @return \Psr\Http\Message\MessageInterface
 */
function with_cookie_list_set(MessageInterface $message) {
  # ...
}
```

After the `$message` argument come zero or more arrays.

The semantics of this function are
best explained
with an example.
Here are two blocks of code that have the same semantics:

```php
$response = with_cookie_list_set(
  $response,
  ['city', 'London'],
  [
    'country',
    'UK',
    [
      'domain' => 'example.com',
      'expire' => new \DateTime('+ 1 week'),
    ]
  ]
  [
    'continent',
    'Europe',
    ['path' => '/'],
  ]
);
```

```php
$response = with_cookie($response, 'city', 'London');

$response = with_cookie(
  $response,
  'country',
  'UK',
  [
    'domain' => 'example.com',
    'expire' => new \DateTime('+ 1 week'),
  ]
);

$response = with_cookie(
  $response,
  'continent',
  'Europe',
  ['path' => '/']
);
```

## with_cookie_list_unset

    'with_cookie_list_unset' is to 'with_cookie_unset'
                              as
    'with_cookie_list_set'   is to 'with_cookie_set'.

## change_client_cookies

Here is an incomplete signature:

```php
/**
 * @param \Psr\Http\Message\MessageInterface $message
 * @return \Psr\Http\Message\MessageInterface;
 * @throws \LogicException
 */
function change_client_cookies(MessageInterface $message) {
}
```

After the `$message` argument come zero or more
arrays.

The semantics of this function are
best explained
with an example.
Consider the following:

```php
$response = change_client_cookies(
  $response,
  ['+', 'city', 'London', ['domain' => 'x.com']],
  ['-', 'shopping_cart', ['domain' => 'store.x.com']],
  ['+', 'country', 'UK']
  ['-', 'session']
);
```

Note that each array argument begins with "+" or "-";
this is required.

The above code is equivalent to:

```php
$response = with_cookie(
  $response,
  'city',
  'London',
  ['domain' => 'x.com']
);

$response = without_cookie(
  $response,
  'shopping_cart',
  ['domain' => 'store.x.com']
);

$response = with_cookie(
  $response,
  'country',
  'UK'
);

$response = without_cookie(
  $response,
  'session'
);
```

## Order

Here is a boring detail that you probably don't care about
but which is documented here for completeness.

For
`with_cookie_list_set`,
`with_cookie_list_unset`,
and
`change_client_cookies`,
each array argument corresponds
to a call to
`MessageInterface#withAddedHeader` that adds a `Set-Cookie`
header field.
It is guaranteed that these header fields are added in
the order in which the arrays are passed to the function.

This behaviour can be observed in the above examples.

## 'assert' is used

The library calls `assert` to set function
[preconditions](https://en.wikipedia.org/w/index.php?title=Precondition&oldid=713416011).
Consequently, if you are using this library in an application, you should do
one of the following:

  * make sure you don't violate the preconditions;
  * turn on assertions and make sure that a failed assertion
  either terminates the program or throws an exception.

If you choose the latter, and
your application needs to support PHP 5,
then the following is one possible implementation:

```php
ini_set('assert.active', 1);
ini_set('assert.callback', 'handle_assertion_failure');

function handle_assert_failure($file, $line, $code) {
  $msg = "'assert' failed in $file on line $line";

  if (count(func_num_args()) == 4) {
    $msg .= ': ' . func_get_arg(3);
    # This is the $description argument.
  }

  if ($code !== '') {
    $msg .= '.  Assertion was: ' . $code;
  }

  throw new \LogicException($msg);
}
```

`assert.callback` must be a string.
A string such as `SomeClass::someMethod` is valid.

### Assertions in library code

If you are surprised to see assertions used in
library code, consider the words of
PHP's [Expectations RFC](https://wiki.php.net/rfc/expectations),
created in 2013:

> Library code should not shy away from deploying Assertions *everywhere*

Admittedly, this advice is subject to a vague qualification:

> prefix [...] with "when deployed and configured properly".
