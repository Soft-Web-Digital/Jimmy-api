<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Generic\BankController;
use App\Http\Controllers\Generic\AssetController;
use App\Http\Controllers\Generic\UploadSignature;
use App\Http\Controllers\Generic\BannerController;
use App\Http\Controllers\Generic\CountryController;
use App\Http\Controllers\Generic\NetworkController;
use App\Http\Controllers\Generic\CurrencyController;
use App\Http\Controllers\Generic\DatatypeController;
use App\Http\Controllers\Generic\SystemDataController;
use App\Http\Controllers\Generic\GiftcardProductController;
use App\Http\Controllers\Generic\GiftcardCategoryController;
use App\Http\Controllers\Generic\SystemBankAccountController;

Route::get('countries', CountryController::class);
Route::get('banks', BankController::class);
Route::get('currencies', CurrencyController::class);
Route::get('giftcard-categories', GiftcardCategoryController::class);
Route::get('giftcard-products', GiftcardProductController::class);
Route::get('datatypes', DatatypeController::class);
Route::get('system-data/{code}', SystemDataController::class);
Route::get('networks', NetworkController::class);
Route::get('assets', AssetController::class);
Route::get('banners', BannerController::class);
Route::get('system-bank-accounts', SystemBankAccountController::class);

Route::get('image/signature', [UploadSignature::class, 'generateSignature']);

