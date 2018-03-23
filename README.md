# Laravel Daraja

[![Build Status](https://travis-ci.org/starnerz/laravel-daraja.svg?branch=master)](https://travis-ci.org/starnerz/laravel-daraja)
[![styleci](https://styleci.io/repos/126376478/shield)](https://styleci.io/repos/126376478)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/starnerz/laravel-daraja/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/starnerz/laravel-daraja/?branch=master)

[![Packagist](https://img.shields.io/packagist/v/starnerz/laravel-daraja.svg)](https://packagist.org/packages/starnerz/laravel-daraja)
[![Packagist](https://poser.pugx.org/starnerz/laravel-daraja/d/total.svg)](https://packagist.org/packages/starnerz/laravel-daraja)
[![Packagist](https://img.shields.io/packagist/l/starnerz/laravel-daraja.svg)](https://packagist.org/packages/starnerz/laravel-daraja)

This package provides you with a simple tool to make requests to Safaricom Daraja APIs so that you can focus on the development of your awesome applications instead of all the set up involved.

## Installation

Install via composer
```bash
composer require starnerz/laravel-daraja
```

### Register Service Provider

**Note! This and next step are optional if you use laravel>=5.5 with package
auto discovery feature.**

Add service provider to `config/app.php` in `providers` section
```php
Starnerz\LaravelDaraja\LaravelDarajaServiceProvider::class,
```

### Register Facade

Register package facade in `config/app.php` in `aliases` section
```php
Starnerz\LaravelDaraja\Facades\MpesaApi::class,
```

### Publish Configuration File

```bash
php artisan vendor:publish --provider="Starnerz\LaravelDaraja\LaravelDarajaServiceProvider" --tag="config"
```

## Usage

If you have not created your Safaricom API application yet you can create one at [Safaricom Developer][link-safaricom-developer]

Each Safaricom API except Oauth has been implemented as a class on its own which you can use in your code.


``` php
$STK = new STK();
$STK->push('254727123456','10000','New Purchase');
```

If you prefer using the facade

``` php
MpesaApi::STK()->push('254727123456','10000','New Purchase');
```

If you will be using the C2B Api you can easily register the validation and confirmation URLs through artisan.

``` bash
# php artisan daraja:register-urls
```

## Security

If you discover any security related issues, please email stanleykimathi@gmail.com
instead of using the issue tracker.

[link-safaricom-developer]: https://developer.safaricom.co.ke/
