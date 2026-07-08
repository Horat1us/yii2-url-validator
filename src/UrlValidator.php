<?php

declare(strict_types=1);

namespace Horat1us\Yii;

use yii\base\InvalidConfigException;
use yii\validators\UrlValidator as YiiUrlValidator;

class UrlValidator extends YiiUrlValidator
{
    public const MULTI_LABEL_HOST = '(\.[A-Z0-9][A-Z0-9_-]*)+';

    public const SINGLE_LABEL_HOST = '(\.[A-Z0-9][A-Z0-9_-]*)*';

    public bool $allowSingleLabelHost = false;

    public function init(): void
    {
        if ($this->allowSingleLabelHost && $this->pattern === static::getYiiDefaultPattern()) {
            $pattern = str_replace(self::MULTI_LABEL_HOST, self::SINGLE_LABEL_HOST, $this->pattern);
            if ($pattern === $this->pattern) {
                throw new InvalidConfigException(
                    'Unable to enable allowSingleLabelHost: installed yii2 version '
                    . 'uses an unrecognized default UrlValidator::$pattern.'
                );
            }
            $this->pattern = $pattern;
        }

        parent::init();
    }

    protected static function getYiiDefaultPattern(): string
    {
        /** @var array{pattern: string} $defaults */
        $defaults = (new \ReflectionClass(YiiUrlValidator::class))->getDefaultProperties();

        return $defaults['pattern'];
    }
}
