<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum Permission: string
{
    use EnumTrait;

    // Guards
    private const GUARD_ADMIN = 'api_admin';
    // private const GUARD_USER = 'api_user';

    // Groups
    private const GROUP_MANAGEMENT = 'management';
    private const GROUP_CONFIG = 'config';
    private const GROUP_SECURITY = 'security';
    private const GROUP_ALERT = 'alert';
    private const GROUP_USER = 'user';
    private const GROUP_GIFTCARD = 'giftcard';
    private const GROUP_CRYPTO = 'crypto';
    private const GROUP_NOTIFICATION = 'notification';
    private const GROUP_WALLET = 'wallet';
    private const GROUP_BANNER = 'banner';

    // Permissions
    case MANAGE_ADMINS = 'manage_admins';
    case MANAGE_COUNTRIES = 'manage_countries';
    case MANAGE_CURRENCIES = 'manage_currencies';
    case MANAGE_ACCESS_CONTROL_LIST = 'manage_access_control_list';
    case MANAGE_ALERTS = 'manage_alerts';
    case MANAGE_USERS = 'manage_users';
    case FINANCE_USERS = 'finance_users';
    case MANAGE_SYSTEM_DATA = 'manage_system_data';
    case MANAGE_GIFTCARD_CATEGORIES = 'manage_giftcard_categories';
    case MANAGE_GIFTCARD_PRODUCTS = 'manage_giftcard_products';
    case MANAGE_GIFTCARDS = 'manage_giftcards';
    case MANAGE_NETWORKS = 'manage_networks';
    case MANAGE_ASSETS = 'manage_assets';
    case RECEIVE_NOTIFICATIONS = 'receive_notifications';
    case MANAGE_ASSET_TRANSACTIONS = 'manage_asset_transactions';
    case MANAGE_WALLET_TRANSACTIONS = 'manage_wallet_transactions';
    case MANAGE_BANNERS = 'manage_banners';
    case MANAGE_SYSTEM_BANK_ACCOUNTS = 'manage_system_bank_accounts';

    /**
     * Get the permission's description.
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::MANAGE_ADMINS => 'enable admin to manage admins',
            self::MANAGE_COUNTRIES => 'enable admin to manage countries',
            self::MANAGE_CURRENCIES => 'enable admin to manage currencies',
            self::MANAGE_ACCESS_CONTROL_LIST => 'enable admin to manage access control list (roles & permissions)',
            self::MANAGE_ALERTS => 'enable admin to manage alerts',
            self::MANAGE_USERS => 'enable admin to manage users',
            self::FINANCE_USERS => 'enable admin to finance users',
            self::MANAGE_SYSTEM_DATA => 'enable admin to manage system data',
            self::MANAGE_GIFTCARD_CATEGORIES => 'enable admin to manage giftcard categories',
            self::MANAGE_GIFTCARD_PRODUCTS => 'enable admin to manage giftcard products',
            self::MANAGE_GIFTCARDS => 'enable admin to manage giftcard transactions',
            self::MANAGE_NETWORKS => 'enable admin to manage crypto networks',
            self::MANAGE_ASSETS => 'enable admin to manage crypto assets',
            self::RECEIVE_NOTIFICATIONS => 'enable admin to receive notifications on application usage',
            self::MANAGE_ASSET_TRANSACTIONS => 'enable admin to manage asset transactions',
            self::MANAGE_WALLET_TRANSACTIONS => 'enable admin to manage wallet transactions',
            self::MANAGE_BANNERS => 'enable admin to manage banners',
            self::MANAGE_SYSTEM_BANK_ACCOUNTS => 'enable admin to manage system bank accounts',
        };
    }

    /**
     * Get the permission's AUTH guard(s).
     *
     * @return string|array<int, string>
     */
    public function guards(): string|array
    {
        return match ($this) {
            self::MANAGE_ADMINS => self::GUARD_ADMIN,
            self::MANAGE_COUNTRIES => self::GUARD_ADMIN,
            self::MANAGE_CURRENCIES => self::GUARD_ADMIN,
            self::MANAGE_ACCESS_CONTROL_LIST => self::GUARD_ADMIN,
            self::MANAGE_ALERTS => self::GUARD_ADMIN,
            self::MANAGE_USERS => self::GUARD_ADMIN,
            self::FINANCE_USERS => self::GUARD_ADMIN,
            self::MANAGE_SYSTEM_DATA => self::GUARD_ADMIN,
            self::MANAGE_GIFTCARD_CATEGORIES => self::GUARD_ADMIN,
            self::MANAGE_GIFTCARD_PRODUCTS => self::GUARD_ADMIN,
            self::MANAGE_GIFTCARDS => self::GUARD_ADMIN,
            self::MANAGE_NETWORKS => self::GUARD_ADMIN,
            self::MANAGE_ASSETS => self::GUARD_ADMIN,
            self::RECEIVE_NOTIFICATIONS => self::GUARD_ADMIN,
            self::MANAGE_ASSET_TRANSACTIONS => self::GUARD_ADMIN,
            self::MANAGE_WALLET_TRANSACTIONS => self::GUARD_ADMIN,
            self::MANAGE_BANNERS => self::GUARD_ADMIN,
            self::MANAGE_SYSTEM_BANK_ACCOUNTS => self::GUARD_ADMIN,
        };
    }

    /**
     * Get the permission's guards.
     *
     * @return string
     */
    public function group(): string
    {
        return match ($this) {
            self::MANAGE_ADMINS => self::GROUP_MANAGEMENT,
            self::MANAGE_COUNTRIES => self::GROUP_CONFIG,
            self::MANAGE_CURRENCIES => self::GROUP_CONFIG,
            self::MANAGE_ACCESS_CONTROL_LIST => self::GROUP_SECURITY,
            self::MANAGE_ALERTS => self::GROUP_ALERT,
            self::MANAGE_USERS => self::GROUP_USER,
            self::FINANCE_USERS => self::GROUP_USER,
            self::MANAGE_SYSTEM_DATA => self::GROUP_CONFIG,
            self::MANAGE_GIFTCARD_CATEGORIES => self::GROUP_GIFTCARD,
            self::MANAGE_GIFTCARD_PRODUCTS => self::GROUP_GIFTCARD,
            self::MANAGE_GIFTCARDS => self::GROUP_GIFTCARD,
            self::MANAGE_NETWORKS => self::GROUP_CRYPTO,
            self::MANAGE_ASSETS => self::GROUP_CRYPTO,
            self::RECEIVE_NOTIFICATIONS => self::GROUP_NOTIFICATION,
            self::MANAGE_ASSET_TRANSACTIONS => self::GROUP_CRYPTO,
            self::MANAGE_WALLET_TRANSACTIONS => self::GROUP_WALLET,
            self::MANAGE_BANNERS => self::GROUP_BANNER,
            self::MANAGE_SYSTEM_BANK_ACCOUNTS => self::GROUP_CONFIG,
        };
    }

    /**
     * Get the obsolete permissions.
     *
     * @return array<string, string> key:permission_name => value:guard_names
     */
    public static function obsolete(): array
    {
        return [
            'manage_withdrawal_requests' => self::GUARD_ADMIN,
            'manage system data' => self::GUARD_ADMIN,
            'manage giftcard categories' => self::GUARD_ADMIN,
            'manage giftcard products' => self::GUARD_ADMIN,
            'manage giftcards' => self::GUARD_ADMIN,
            'manage networks' => self::GUARD_ADMIN,
            'manage assets' => self::GUARD_ADMIN,
            'receive notifications' => self::GUARD_ADMIN,
            'manage asset transactions' => self::GUARD_ADMIN,
            'manage wallet transactions' => self::GUARD_ADMIN,
        ];
    }
}
