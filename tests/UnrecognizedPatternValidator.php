<?php

declare(strict_types=1);

namespace Horat1us\Yii\Tests;

use Horat1us\Yii\UrlValidator;

class UnrecognizedPatternValidator extends UrlValidator
{
    public $pattern = '/^{schemes}:\/\/nope$/i';

    protected static function getYiiDefaultPattern(): string
    {
        return '/^{schemes}:\/\/nope$/i';
    }
}
