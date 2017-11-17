# API

## Overview

The API consists of 5 functions in
the `\phormio\Psr7Cookies` namespace.
They are:

| Name | Description |
| --- | --- |
| `with_cookie_set` | Set a cookie |
| `with_cookie_unset` | Delete a cookie in the HTTP client |
| `with_cookie_list_set` | Set a list of cookies |
| `with_cookie_list_unset` | Delete a list of cookies in the HTTP client |
| `change_client_cookies` | Allows setting and deleting cookies |

Each function:

  * has a first argument and return value of type
  `\Psr\Http\Message\MessageInterface`;

  * leaves all its arguments unchanged.

## Design note: missing features

The following features, which the package lacks, should probably
be considered
as gaps in its functionality:

  * Deletion of cookies from the HTTP message object (not
  the HTTP client)
  * Extraction of cookies from a HTTP request object

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
| `http_only` | mixed, but coerced to bool |
| `max_age` | integer |
| `path` | string |
| `secure` | mixed, but coerced to bool |

There are no mandatory keys.

Types are not strict.  For example, a "string" can be a PHP integer;
an "integer" can be a PHP string that looks like an integer.

### expire

If `expire` is an integer,
or a scalar that looks like an integer,
it is treated as a Unix timestamp.

Note that a value of zero for `expire` is not treated
as a special case; it just means
the start of the Unix epoch
(1970-01-01T00:00:00+0000).
This behaviour is different from that of PHP's
[setcookie](https://secure.php.net/manual/en/function.setcookie.php) function,
which *does* treat this as a special case.

### max_age

`max_age` has no effect if the browser is
Microsoft Internet Explorer
or
Microsoft Edge.
More information [here](max-age-in-ms-browsers.md).

### path

Every character in `path`:

  * must be in [ASCII](https://en.wikipedia.org/wiki/ASCII);
  * must be a printable character;
  * must not be a semicolon.

## with_cookie_unset

This function deletes the cookie on the client side.
More formally, it
puts a `Set-Cookie` field in the HTTP header that will
cause the HTTP client to delete its cookie.

Here is the function's signature:

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
Following are two blocks of code that have the same semantics.
Each block sets three cookies.
The first block makes do without `with_cookie_list_set`; the second
uses it to improve readability.

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

## with_cookie_list_unset

As `with_cookie_list_set` is to `with_cookie_set`,
so `with_cookie_list_unset` is to `with_cookie_unset`.

### Example

```php
$response = with_cookie_list_unset(
  $response,
  ['city'],
  [
    'country',
    ['domain' => 'example.com']
  ]
);
```

## change_client_cookies

Here is an incomplete signature:

```php
/**
 * @param \Psr\Http\Message\MessageInterface $message
 * @return \Psr\Http\Message\MessageInterface;
 * @throws \phormio\Psr7Cookies\Exception
 */
function change_client_cookies(MessageInterface $message) {
}
```

After the `$message` argument come zero or more
arrays.

The semantics of this function are
best explained
with an example.

```php
$response = change_client_cookies(
  $response,
  ['+city', 'London', ['domain' => 'x.com']],
  ['-shopping_cart', ['domain' => 'store.x.com']],
  ['+country', 'UK']
  ['-session']
);
```

The first member of each array begins with "+" or "-".
This is required.

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

## Exceptions

All exceptions thrown by this package inherit from
`\phormio\Psr7Cookies\ExceptionInterface`.

## Order

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

## Reference

[RFC 6265][] is the specification for HTTP cookies.

[RFC 6265]: https://tools.ietf.org/html/rfc6265
