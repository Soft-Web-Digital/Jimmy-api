<?php

use App\Models\Bank;
use App\Models\User;
use App\Models\UserBankAccount;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'user', 'bank-account');





it('can fetch user bank accounts', function () {
    sanctumLogin(User::factory()->create(), ['*'], 'api_user');

    getJson('/api/user/bank-accounts')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Bank accounts fetched successfully.')
                    ->has('data.bank_accounts', 0)
        );
});





it('can verify bank accounts', function () {
    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/bank-accounts/verify', [
        'bank_id' => Bank::factory()->create(['code' => '057'])->id,
        'account_number' => '0000000000'
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Bank account verified successfully.')
                    ->has(
                        'data.bank_account',
                        fn (AssertableJson $json) =>
                            $json->hasAll(['bank', 'account_number', 'account_name'])
                    )
        );
});





it('can store bank accounts', function () {
    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/bank-accounts', [
        'bank_id' => Bank::factory()->create(['code' => '057'])->id,
        'account_number' => '0000000000'
    ])->assertCreated();
});





it('can delete bank accounts', function () {
    $user = User::factory()->create();

    $userBankAccount = UserBankAccount::factory()->for($user)->create();

    sanctumLogin($user, ['*'], 'api_user');

    deleteJson("/api/user/bank-accounts/{$userBankAccount->id}")->assertNoContent();

    expect($user->bankAccounts()->count())->toBe(0);
});
