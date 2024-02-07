<?php

use App\Enums\KycAttribute;
use App\Http\Controllers\User\AssetTransactionController;
use App\Http\Controllers\User\Auth\LoginController;
use App\Http\Controllers\User\Auth\RegisterController;
use App\Http\Controllers\User\Auth\ResetPasswordController;
use App\Http\Controllers\User\Auth\SocialAuthController;
use App\Http\Controllers\User\Auth\TwoFactorLoginController;
use App\Http\Controllers\User\Auth\VerificationController;
use App\Http\Controllers\User\GiftcardController;
use App\Http\Controllers\User\KycController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\ReferralController;
use App\Http\Controllers\User\StatController;
use App\Http\Controllers\User\TransactionPinController;
use App\Http\Controllers\User\UserBankAccountController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\WalletTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:3,1')->group(function () {
    Route::post('register', RegisterController::class);
    Route::post('login', [LoginController::class, 'login']);
    Route::post('social-auth', SocialAuthController::class);

    // Password reset
    Route::post('password/forgot', [ResetPasswordController::class, 'forgot']);
    Route::post('password/verify', [ResetPasswordController::class, 'verify']);
    Route::post('password/reset', [ResetPasswordController::class, 'reset']);
});

Route::middleware('auth:api_user')->group(function () {
    Route::get('/', [ProfileController::class, 'index']);

    Route::middleware('throttle:3,1')->group(function () {
        // Two-FA Authentication
        Route::middleware('auth.two_fa:0')->group(function () {
            Route::post('verify-two-fa', [TwoFactorLoginController::class, 'verify']);
            Route::post('resend-two-fa', [TwoFactorLoginController::class, 'resend'])->middleware('throttle:resend');
        });

        // Email verification
        Route::post('email/verify', [VerificationController::class, 'verify']);
        Route::post('email/resend', [VerificationController::class, 'resend'])->middleware('throttle:resend');
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
            Route::patch('profile', [ProfileController::class, 'updateProfile']);
            Route::delete('profile', [ProfileController::class, 'destroy']);

            // KYC
            Route::prefix('kyc')->group(function () {
                Route::get('', [KycController::class, 'index']);
                Route::post('verify/{type}', [KycController::class, 'verify'])
                    ->whereIn('type', KycAttribute::values())
                    ->middleware('throttle:resend');
            });

            // Notifications
            Route::prefix('notifications')->group(function () {
                Route::get('', [NotificationController::class, 'index']);
                Route::post('read', [NotificationController::class, 'markAsRead']);
            });

            // Referrals
            Route::prefix('referrals')->group(function () {
                Route::get('', [ReferralController::class, 'index']);
                Route::get('{referral}', [ReferralController::class, 'show']);
            });

            // Transaction PIN
            Route::prefix('transaction-pin')->group(function () {
                Route::patch('', [TransactionPinController::class, 'update']);
                Route::patch('activation', [TransactionPinController::class, 'toggleActivation']);
                Route::post('forgot', [TransactionPinController::class, 'requestReset']);
                Route::post('reset', [TransactionPinController::class, 'reset']);
            });

            // Users
            Route::prefix('users')->group(function () {
                Route::get('', [UserController::class, 'index']);

                Route::prefix('{user}')->group(function () {
                    Route::post('transfer', [UserController::class, 'transfer']);
                });
            });

            // Wallet transactions
            Route::prefix('wallet-transactions')->group(function () {
                Route::get('', [WalletTransactionController::class, 'index']);
                Route::post('withdraw', [WalletTransactionController::class, 'withdraw']);
                Route::get('{walletTransaction}', [WalletTransactionController::class, 'show']);
                Route::patch('{walletTransaction}/close', [WalletTransactionController::class, 'close']);
            });

            // Bank accounts
            Route::prefix('bank-accounts')->group(function () {
                Route::get('', [UserBankAccountController::class, 'index']);
                Route::post('', [UserBankAccountController::class, 'store']);
                Route::post('verify', [UserBankAccountController::class, 'verify']);
                Route::delete('{userBankAccount}', [UserBankAccountController::class, 'destroy']);
            });

            // Giftcards
            Route::prefix('giftcards')->group(function () {
                Route::get('', [GiftcardController::class, 'index']);
                Route::get('stats', [GiftcardController::class, 'getStats']);
                Route::post('breakdown', [GiftcardController::class, 'breakdown']);
                Route::post('sale', [GiftcardController::class, 'storeSale']);
                Route::get('{giftcard}', [GiftcardController::class, 'show']);
                Route::delete('{giftcard}', [GiftcardController::class, 'destroy']);
            });

            // Asset transactions
            Route::prefix('asset-transactions')->group(function () {
                Route::get('', [AssetTransactionController::class, 'index']);
                Route::get('stats', [AssetTransactionController::class, 'getStats']);
                Route::post('breakdown', [AssetTransactionController::class, 'breakdown']);
                Route::post('', [AssetTransactionController::class, 'store']);
                Route::get('{assetTransaction}', [AssetTransactionController::class, 'show']);
                Route::patch('{assetTransaction}/transfer', [AssetTransactionController::class, 'transfer']);
            });

            Route::get('transactions', [StatController::class, 'transactions']);
        });
    });
});
