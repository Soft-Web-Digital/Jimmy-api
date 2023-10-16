<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetTransactionController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\Auth\TwoFactorLoginController;
use App\Http\Controllers\Admin\Auth\VerificationController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\GiftcardCategoryController;
use App\Http\Controllers\Admin\GiftcardController;
use App\Http\Controllers\Admin\GiftcardProductController;
use App\Http\Controllers\Admin\NetworkController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StatController;
use App\Http\Controllers\Admin\SystemBankAccountController;
use App\Http\Controllers\Admin\SystemDataController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WalletTransactionController;
use Illuminate\Support\Facades\Route;

Route::post('login', [LoginController::class, 'login']);

// Password reset
Route::post('password/forgot', [ResetPasswordController::class, 'forgot']);
Route::post('password/verify', [ResetPasswordController::class, 'verify']);
Route::post('password/reset', [ResetPasswordController::class, 'reset']);

Route::middleware('auth:api_admin')->group(function () {
    Route::get('/', [ProfileController::class, 'index']);

    Route::post('logout', [LoginController::class, 'logout']);
    Route::post('logout-others', [LoginController::class, 'logoutOtherDevices']);

    // Two-FA Authentication
    Route::middleware('auth.two_fa:0')->group(function () {
        Route::post('verify-two-fa', [TwoFactorLoginController::class, 'verify']);
        Route::post('resend-two-fa', [TwoFactorLoginController::class, 'resend'])->middleware('throttle:resend');
    });

    // Email verification
    Route::post('email/verify', [VerificationController::class, 'verify']);
    Route::post('email/resend', [VerificationController::class, 'resend'])->middleware('throttle:resend');

    // Full Authentication
    Route::middleware(['auth.two_fa', 'unblocked'])->group(function () {
        Route::post('profile/password', [ProfileController::class, 'updatePassword']);
        Route::post('profile/two-fa', [ProfileController::class, 'updateTwoFa']);

        // Complete security
        Route::middleware(['password.secure'])->group(function () {
            Route::get('my-permissions', [ProfileController::class, 'getPermissions']);
            Route::patch('profile', [ProfileController::class, 'updateProfile']);

            // Notifications
            Route::prefix('notifications')->group(function () {
                Route::get('', [NotificationController::class, 'index']);
                Route::post('read', [NotificationController::class, 'markAsRead']);
            });

            // Countries
            Route::prefix('countries')->middleware('can:' . Permission::MANAGE_COUNTRIES->value)->group(function () {
                Route::get('', [CountryController::class, 'index']);
                Route::get('{country}', [CountryController::class, 'show']);
                Route::patch('{country}/registration', [CountryController::class, 'toggleRegistrationActivation']);
                Route::patch('{country}/giftcard', [CountryController::class, 'toggleGiftcardActivation']);
            });

            // Admins
            Route::prefix('admins')->middleware('can:' . Permission::MANAGE_ADMINS->value)->group(function () {
                Route::get('', [AdminController::class, 'index']);
                Route::post('', [AdminController::class, 'store']);
                Route::prefix('{admin}')->group(function () {
                    Route::get('', [AdminController::class, 'show']);
                    Route::patch('', [AdminController::class, 'update']);
                    Route::delete('', [AdminController::class, 'destroy']);
                    Route::patch('restore', [AdminController::class, 'restore'])->withTrashed();
                    Route::patch('block', [AdminController::class, 'toggleBlock']);
                    Route::patch('role', [AdminController::class, 'toggleRole']);
                });
            });

            // Roles & permissions
            Route::middleware('can:' . Permission::MANAGE_ACCESS_CONTROL_LIST->value)->group(function () {
                Route::get('permissions', PermissionController::class);

                Route::prefix('roles')->group(function () {
                    Route::get('', [RoleController::class, 'index']);
                    Route::post('', [RoleController::class, 'store']);
                    Route::prefix('{role}')->group(function () {
                        Route::get('', [RoleController::class, 'show']);
                        Route::patch('', [RoleController::class, 'update']);
                        Route::delete('', [RoleController::class, 'destroy']);
                    });
                });
            });

            // Alerts
            Route::prefix('alerts')->middleware('can:' . Permission::MANAGE_ALERTS->value)->group(function () {
                Route::get('', [AlertController::class, 'index']);
                Route::post('', [AlertController::class, 'store']);
                Route::prefix('{alert}')->group(function () {
                    Route::get('', [AlertController::class, 'show']);
                    Route::patch('', [AlertController::class, 'update']);
                    Route::delete('', [AlertController::class, 'destroy']);
                    Route::patch('restore', [AlertController::class, 'restore'])->withTrashed();
                    Route::post('dispatch', [AlertController::class, 'dispatchAlert']);
                });
            });

            // Users
            Route::prefix('users')->middleware('can:' . Permission::MANAGE_USERS->value)->group(function () {
                Route::get('', [UserController::class, 'index']);
                Route::get('export', [UserController::class, 'export'])->withTrashed();
                Route::prefix('{user}')->group(function () {
                    Route::get('', [UserController::class, 'show'])->withTrashed();
                    Route::patch('block', [UserController::class, 'toggleBlock']);
                    Route::patch('restore', [UserController::class, 'restore'])->withTrashed();
                    Route::post('finance/{type}', [UserController::class, 'finance'])
                        ->middleware('can:' . Permission::FINANCE_USERS->value);
                });
            });

            // System data
            Route::prefix('system-data')
                ->middleware('can:' . Permission::MANAGE_SYSTEM_DATA->value)
                ->group(function () {
                    Route::get('', [SystemDataController::class, 'index']);
                    Route::get('{systemData}', [SystemDataController::class, 'show']);
                    Route::patch('{systemData}', [SystemDataController::class, 'update']);
                });

            Route::prefix('currencies')
                ->middleware('can:' . Permission::MANAGE_CURRENCIES->value)
                ->group(function () {
                    Route::patch('{currency}', [CurrencyController::class, 'update']);
                });

            // Giftcard categories
            Route::prefix('giftcard-categories')
                ->middleware('can:' . Permission::MANAGE_GIFTCARD_CATEGORIES->value)
                ->group(function () {
                    Route::get('', [GiftcardCategoryController::class, 'index']);
                    Route::post('', [GiftcardCategoryController::class, 'store']);
                    Route::prefix('{giftcardCategory}')->group(function () {
                        Route::get('', [GiftcardCategoryController::class, 'show']);
                        Route::patch('', [GiftcardCategoryController::class, 'update']);
                        Route::delete('', [GiftcardCategoryController::class, 'destroy']);
                        Route::patch('restore', [GiftcardCategoryController::class, 'restore'])->withTrashed();
                        Route::patch('sale-activation', [GiftcardCategoryController::class, 'toggleSaleActivation']);
                        Route::patch(
                            'purchase-activation',
                            [GiftcardCategoryController::class, 'togglePurchaseActivation']
                        );
                    });
                });

            // Giftcard products
            Route::prefix('giftcard-products')
                ->middleware('can:' . Permission::MANAGE_GIFTCARD_PRODUCTS->value)
                ->group(function () {
                    Route::get('', [GiftcardProductController::class, 'index']);
                    Route::post('', [GiftcardProductController::class, 'store']);
                    Route::prefix('{giftcardProduct}')->group(function () {
                        Route::get('', [GiftcardProductController::class, 'show']);
                        Route::patch('', [GiftcardProductController::class, 'update']);
                        Route::delete('', [GiftcardProductController::class, 'destroy']);
                        Route::patch('restore', [GiftcardProductController::class, 'restore'])->withTrashed();
                        Route::patch('activation', [GiftcardProductController::class, 'toggleActivation']);
                    });
                });

            // Giftcards
            Route::prefix('giftcards')->middleware('can:' . Permission::MANAGE_GIFTCARDS->value)->group(function () {
                Route::get('', [GiftcardController::class, 'index']);
                Route::get('export', [GiftcardController::class, 'export']);
                Route::get('traders', [GiftcardController::class, 'topTraders']);
                Route::prefix('{giftcard}')->group(function () {
                    Route::get('', [GiftcardController::class, 'show']);
                    Route::patch('decline/{multiple?}', [GiftcardController::class, 'decline']);
                    Route::patch('approve/{multiple?}', [GiftcardController::class, 'approve']);
                });
            });

            // Networks
            Route::prefix('networks')->middleware('can:' . Permission::MANAGE_NETWORKS->value)->group(function () {
                Route::get('', [NetworkController::class, 'index']);
                Route::post('', [NetworkController::class, 'store']);
                Route::prefix('{network}')->group(function () {
                    Route::get('', [NetworkController::class, 'show']);
                    Route::patch('', [NetworkController::class, 'update']);
                    Route::delete('', [NetworkController::class, 'destroy']);
                    Route::patch('restore', [NetworkController::class, 'restore'])->withTrashed();
                });
            });

            // Assets
            Route::prefix('assets')->middleware('can:' . Permission::MANAGE_ASSETS->value)->group(function () {
                Route::get('', [AssetController::class, 'index']);
                Route::post('', [AssetController::class, 'store']);
                Route::prefix('{asset}')->group(function () {
                    Route::get('', [AssetController::class, 'show']);
                    Route::patch('', [AssetController::class, 'update']);
                    Route::delete('', [AssetController::class, 'destroy']);
                    Route::patch('restore', [AssetController::class, 'restore'])->withTrashed();
                });
            });

            // Asset transactions
            Route::prefix('asset-transactions')
                ->middleware('can:' . Permission::MANAGE_ASSET_TRANSACTIONS->value)
                ->group(function () {
                    Route::get('', [AssetTransactionController::class, 'index']);
                    Route::get('export', [AssetTransactionController::class, 'export']);
                    Route::get('traders', [AssetTransactionController::class, 'topTraders']);
                    Route::prefix('{assetTransaction}')->group(function () {
                        Route::get('', [AssetTransactionController::class, 'show']);
                        Route::patch('decline', [AssetTransactionController::class, 'decline']);
                        Route::patch('approve', [AssetTransactionController::class, 'approve']);
                    });
                });

            // Wallet transactions
            Route::prefix('wallet-transactions')
                ->middleware('can:' . Permission::MANAGE_WALLET_TRANSACTIONS->value)
                ->group(function () {
                    Route::get('', [WalletTransactionController::class, 'index']);
                    Route::prefix('{walletTransaction}')->group(function () {
                        Route::get('', [WalletTransactionController::class, 'show']);
                        Route::patch('decline', [WalletTransactionController::class, 'decline']);
                        Route::patch('approve', [WalletTransactionController::class, 'approve']);
                    });
                });

            // Banners
            Route::prefix('banners')
                ->middleware('can:' . Permission::MANAGE_BANNERS->value)
                ->group(function () {
                    Route::get('', [BannerController::class, 'index']);
                    Route::post('', [BannerController::class, 'store']);
                    Route::prefix('{banner}')->group(function () {
                        Route::get('', [BannerController::class, 'show']);
                        Route::patch('activation', [BannerController::class, 'toggleActivation']);
                        Route::delete('', [BannerController::class, 'destroy']);
                    });
                });

            // System bank accounts
            Route::prefix('system-bank-accounts')
                ->middleware('can:' . Permission::MANAGE_SYSTEM_BANK_ACCOUNTS->value)
                ->group(function () {
                    Route::get('', [SystemBankAccountController::class, 'index']);
                    Route::post('', [SystemBankAccountController::class, 'store']);
                    Route::prefix('{systemBankAccount}')->group(function () {
                        Route::get('', [SystemBankAccountController::class, 'show']);
                        Route::put('', [SystemBankAccountController::class, 'update']);
                        Route::delete('', [SystemBankAccountController::class, 'destroy']);
                    });
                });

            // Referrals
            Route::prefix('referrals')->group(function () {
                Route::get('', [ReferralController::class, 'index']);
                Route::get('{referral}', [ReferralController::class, 'show']);
            });

            Route::get('transactions', [StatController::class, 'transactions']);
        });
    });
});
