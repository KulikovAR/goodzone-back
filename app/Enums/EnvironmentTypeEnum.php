<?php

namespace App\Enums;

enum EnvironmentTypeEnum: string
{
    case DEV = 'dev';
    case DEVELOPMENT = 'development';
    case LOCAL = 'local';
    case TESTING = 'testing';
    case PROD = 'prod';
    case PRODUCTION = 'production';

    public static function notProductEnv(): array
    {
        return [
            self::DEV->value,
            self::DEVELOPMENT->value,
            self::LOCAL->value,
            self::TESTING->value,
        ];
    }

    public static function productEnv(): array
    {
        return [
            self::PROD->value,
            self::PRODUCTION->value,
        ];
    }
}
