<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Country;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        activity()->disableLogging();

        DB::beginTransaction();

        try {
            $admin = Admin::query()->updateOrCreate(
                [
                    'email' => 'softwebdigital@gmail.com',
                ],
                [
                    'country_id' => Country::query()->where('alpha2_code', 'NG')->value('id'),
                    'firstname' => 'Admin',
                    'lastname' => config('app.name'),
                    'password' => bcrypt('password'),
                ]
            );

            $role = Role::query()->updateOrCreate(
                [
                    'name' => 'SUPERADMIN',
                    'description' => 'Superpowered admin',
                    'guard_name' => 'api_admin',
                ],
                [
                    'name' => 'SUPERADMIN',
                    'description' => 'Superpowered admin',
                    'guard_name' => 'api_admin',
                ]
            );

            $admin->assignRole($role);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
