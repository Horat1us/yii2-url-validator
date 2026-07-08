# Yii2 Url Validator

[![Latest Stable Version](https://poser.pugx.org/horat1us/yii2-url-validator/v/stable)](https://packagist.org/packages/horat1us/yii2-url-validator)
[![Total Downloads](https://poser.pugx.org/horat1us/yii2-url-validator/downloads)](https://packagist.org/packages/horat1us/yii2-url-validator)
[![License](https://poser.pugx.org/horat1us/yii2-url-validator/license)](https://packagist.org/packages/horat1us/yii2-url-validator)
[![PHP Tests](https://github.com/Horat1us/yii2-url-validator/actions/workflows/php.yml/badge.svg)](https://github.com/Horat1us/yii2-url-validator/actions/workflows/php.yml)

Drop-in replacement for Yii2 `UrlValidator` that can accept single-label hosts such as
`http://localhost:8000/`, which the stock validator rejects.

## Installation

```bash
composer require horat1us/yii2-url-validator
```

## Usage

```php
use Horat1us\Yii\UrlValidator;
use yii\base;

class YourModel extends base\Model
{
    public ?string $url = null;

    public function rules(): array
    {
        return [
            ["url", "required",],
            ["url", UrlValidator::class, "allowSingleLabelHost" => true,],
        ];
    }
}
```

With `allowSingleLabelHost` enabled:

| URL | Stock Yii2 | This package |
| --- | --- | --- |
| `http://example.com/` | valid | valid |
| `http://127.0.0.1:8080/` | valid | valid |
| `http://localhost` | invalid | valid |
| `http://localhost:8000/path?q=1#f` | invalid | valid |
| `http://my-host:3000/` | invalid | valid |
| `http://user@localhost/` | invalid | invalid |
| `http://[::1]/` | invalid | invalid |
| `http://localhost:999999/` | invalid | invalid |

Leaving `allowSingleLabelHost` at its default (`false`) reproduces stock Yii2 behaviour exactly.

The option relaxes only the host part of the pattern: a hostname may consist of a single label.
It does **not** enable userinfo (`http://user@localhost/`), IPv6 literals (`http://[::1]/`),
out-of-range ports (`http://localhost:999999/`), or empty hosts (`http://`, `http:///`).

### Custom patterns

`allowSingleLabelHost` only relaxes the host part of Yii's *default* pattern. If you supply your
own `pattern`, it is used verbatim and the flag has no effect:

```php
["url", UrlValidator::class, "allowSingleLabelHost" => true, "pattern" => '/^{schemes}:\/\/internal$/i',],
```

### Client-side validation

`allowSingleLabelHost` is applied in `init()` to the `pattern` property, so `getClientOptions()`
and `clientValidateAttribute()` emit the relaxed regular expression too. Server-side and
client-side validation stay in sync with no extra configuration.

## Features

- Extends `yii\validators\UrlValidator`; all inherited options (`validSchemes`, `defaultScheme`,
  `enableIDN`, `message`, `skipOnEmpty`, ...) keep working
- Permits any single-label host, not just the literal `localhost`
- Never silently overwrites a user-supplied `pattern`
- Client-side validation supported out of the box
- Throws `InvalidConfigException` rather than silently degrading if a future Yii2 release changes
  its default pattern
- Fully tested

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would
like to change.

Please make sure to update tests as appropriate.
