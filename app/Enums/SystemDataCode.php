<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum SystemDataCode: string
{
    use EnumTrait;

    case GIFTCARD_SELL_SERVICE_CHARGE = 'GCSSC';
    case IOS_VERSION_UPDATE = 'IOSVU';
    case ANDROID_VERSION_UPDATE = 'ANDVU';
    case CRYPTO_BUY_SERVICE_CHARGE = 'CRBSC';
    case CRYPTO_SELL_SERVICE_CHARGE = 'CRSSC';
    case REFERRAL_MINIMUM_AMOUNT = 'REFMA';
    case REFERRAL_REWARD_AMOUNT = 'REFRA';

    /**
     * Get the title.
     *
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::GIFTCARD_SELL_SERVICE_CHARGE => 'Giftcard: Sell Service Charge',
            self::IOS_VERSION_UPDATE => 'iOS Version Update',
            self::ANDROID_VERSION_UPDATE => 'Android Version Update',
            self::CRYPTO_BUY_SERVICE_CHARGE => 'Crypto: Buy Service Charge',
            self::CRYPTO_SELL_SERVICE_CHARGE => 'Crypto: Sell Service Charge',
            self::REFERRAL_MINIMUM_AMOUNT => 'Referral: Minimum Amount',
            self::REFERRAL_REWARD_AMOUNT => 'Referral: Reward Amount',
        };
    }

    /**
     * Get the hint.
     *
     * @return string
     */
    public function hint(): string
    {
        return match ($this) {
            self::GIFTCARD_SELL_SERVICE_CHARGE => 'Giftcard sales percentage',
            self::IOS_VERSION_UPDATE => 'Most current iOS version of the app in the App Store',
            self::ANDROID_VERSION_UPDATE => 'Most current Android version of the app in the Play Store',
            self::CRYPTO_BUY_SERVICE_CHARGE => 'Crypto purchase percentage',
            self::CRYPTO_SELL_SERVICE_CHARGE => 'Crypto sale percentage',
            self::REFERRAL_MINIMUM_AMOUNT => 'Minimum trade amount to earn reward',
            self::REFERRAL_REWARD_AMOUNT => 'Reward amount after REFMA has been satisfied',
        };
    }

    /**
     * Get the default content.
     *
     * @return string
     */
    public function defaultContent(): string
    {
        return match ($this) {
            self::GIFTCARD_SELL_SERVICE_CHARGE,
            self::ANDROID_VERSION_UPDATE,
            self::IOS_VERSION_UPDATE,
            self::CRYPTO_BUY_SERVICE_CHARGE,
            self::CRYPTO_SELL_SERVICE_CHARGE => '0',
            self::REFERRAL_MINIMUM_AMOUNT => '100000',
            self::REFERRAL_REWARD_AMOUNT => '1000',
        };
    }

    /**
     * Get the datatype.
     *
     * @return DatatypeEnum
     */
    public function datatype(): DatatypeEnum
    {
        return match ($this) {
            self::IOS_VERSION_UPDATE,
            self::ANDROID_VERSION_UPDATE => DatatypeEnum::TEXT,
            self::GIFTCARD_SELL_SERVICE_CHARGE,
            self::CRYPTO_BUY_SERVICE_CHARGE,
            self::CRYPTO_SELL_SERVICE_CHARGE => DatatypeEnum::PERCENTAGE,
            self::REFERRAL_MINIMUM_AMOUNT,
            self::REFERRAL_REWARD_AMOUNT => DatatypeEnum::NUMERIC,
        };
    }

    /**
     * Get the obsolete system data codes.
     *
     * @return array<int, string>
     */
    public static function obsolete(): array
    {
        return [
            'CRBAC',
        ];
    }
}
