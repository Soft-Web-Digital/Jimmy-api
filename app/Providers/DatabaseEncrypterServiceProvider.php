<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class DatabaseEncrypterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $encryptionKey = base64_decode(Str::of(config('database.encryption_key'))->after('base64:'));

        if ($encryptionKey) {
            Model::encryptUsing(new Encrypter($encryptionKey, config('app.cipher')));
        }
    }
}
