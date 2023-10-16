<?php

namespace Database\Seeders;

use App\Enums\Permission as EnumsPermission;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        DB::beginTransaction();

        try {
            // Create permissions
            foreach (EnumsPermission::cases() as $permission) {
                $permissionModel = Permission::query()->firstOrNew([
                    'name' => $permission->value,
                ]);
                $permissionModel->group_name = $permission->group();
                $permissionModel->description = $permission->description();

                $guards = $permission->guards();

                if (is_array($guards)) {
                    foreach ($guards as $guardName) {
                        $permissionModel->where('guard_name', $guardName);
                        $permissionModel->guard_name = $guardName;
                        $permissionModel->save();

                        $this->updateSuperadminPermissions($guardName, $permissionModel);
                    }
                } else {
                    $permissionModel->where('guard_name', $guards);
                    $permissionModel->guard_name = $guards;
                    $permissionModel->save();

                    $this->updateSuperadminPermissions($guards, $permissionModel);
                }
            }

            // Delete permissions
            foreach (EnumsPermission::obsolete() as $permission => $guards) {
                $permission = Permission::query()->where('name', $permission);

                if (is_array($guards)) {
                    foreach ($guards as $guard) {
                        $permission->where('guard_name', $guard)->delete();
                    }
                } else {
                    $permission->where('guard_name', $guards)->delete();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update superadmin permissions.
     *
     * @param string $guardName
     * @param \App\Models\Permission $permission
     * @return void
     */
    private function updateSuperadminPermissions(string $guardName, Permission $permission)
    {
        $superAdminRoles = Role::where('name', 'SUPERADMIN')->where('guard_name', $guardName)->get();

        foreach ($superAdminRoles as $superAdminRole) {
            $superAdminRole->permissions()->syncWithoutDetaching($permission->id);
        }
    }
}
