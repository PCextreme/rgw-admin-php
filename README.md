[![github-readme_header](https://cloud.githubusercontent.com/assets/2406615/17754363/6e205280-64d4-11e6-946d-e7e7aedb2e30.png)](https://www.pcextreme.nl)

# Rados Gateway API Client

PHP client for the Rados Gateway admin operations api.

## Requirements

* PHP 5.6
* PHP 7.0
* PHP 7.1

## Installation

### What type of installation should I choose?

It's very simple, choose for an express installation if your application already contains a HTTP library such 
as GuzzleHTTP, otherwise just perform an quick installation.

### Quick installation

For an quick installation we recommend you to install the `php-http/curl-client` package. This package is
a small abstraction around native php curl api. 

```bash
$ composer require php-http/curl-client guzzlehttp/psr7
```

After that you are ready to install this package:

```bash
$ composer require pcextreme/rgw-admin-client
```

### Express installation

If your application already contains a compatible HTTP client, just install the correct HTTP client 
adapter before using this package. For a list of compatible 
adapters [click here](http://docs.php-http.org/en/latest/clients.html).

For example if your application depends on `guzzlehttp/guzzle` version 6 you need to install the guzzle 6 adapter package.

```bash
$ composer require php-http/guzzle6-adapter
```

After the installation of a client adapter you are ready to install this package:

```bash
$ composer require pcextreme/rgw-admin-client
```

## Usage

### Client configuration

Before you can use the api client you need to provided the `apiUrl`, `apiKey` and `secretKey`. You need to provided
them when creating an instance of the client class.

```php
$client = new Client([
    'apiUrl'    => 'https://',
    'apiKey'    => '',
    'secretKey' => '',
]);
```

### Create and execute a request

There are two ways to interact with the rados api via this package, you can manually create the 
request and send them afterwards. See the code below for an example.

```php
$request = $client->createRequest('user', 'get', ['uid' => 'user-id']);

$response = $client->sendRequest($request);

var_dump($response);
```

You can also use the preferred short syntax. 

```php
$response = $client->get('user', ['uid' => 'user-id']);

var_dump($response);
```

See the [api docs](http://docs.ceph.com/docs/master/radosgw/adminops) for more information about the available api resources.

## Credits

- [Niels Tholenaar](https://github.com/nielstholenaar)
- [All Contributors](https://github.com/pcextreme/rgw-admin-php/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
