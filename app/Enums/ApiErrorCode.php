<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum ApiErrorCode: int
{
    use EnumTrait;

    case GENERAL_ERROR = 100;
    case TWO_FACTOR_AUTHENTICATION_REQUIRED = 101;
    case BLOCKED_ACCESS = 102;
    case INCOMPLETE_DATA = 103;
    case NOT_ALLOWED = 104;
    case EXPECTATION_FAILED = 105;
    case ACCOUNT_DELETED_TEMPORARILY = 106;
    case ACCOUNT_DELETED_PERMANENTLY = 107;
    case INSECURE_PASSWORD = 108;
    case INSUFFICIENT_FUNDS = 109;

    /**
     * Get the description of the error.
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::GENERAL_ERROR => trans('errors.general'),
            self::TWO_FACTOR_AUTHENTICATION_REQUIRED => trans('errors.auth.two_fa'),
            self::BLOCKED_ACCESS => trans('errors.accounts.blocked'),
            self::INCOMPLETE_DATA => trans('errors.incomplete'),
            self::NOT_ALLOWED => trans('errors.disallowed'),
            self::EXPECTATION_FAILED => trans('errors.failed'),
            self::ACCOUNT_DELETED_TEMPORARILY => trans('errors.accounts.temp_delete'),
            self::ACCOUNT_DELETED_PERMANENTLY => trans('errors.accounts.delete'),
            self::INSECURE_PASSWORD => trans('errors.passwords.insecure'),
            self::INSUFFICIENT_FUNDS => trans('errors.accounts.insufficient_funds'),
        };
    }
}
