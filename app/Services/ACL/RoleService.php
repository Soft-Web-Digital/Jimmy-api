<?php

declare(strict_types=1);

namespace App\Services\ACL;

use App\DataTransferObjects\Models\RoleModelData;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * Create a new role.
     *
     * @param \App\DataTransferObjects\Models\RoleModelData $roleModelData
     * @return \App\Models\Role
     */
    public function create(RoleModelData $roleModelData): Role
    {
        DB::beginTransaction();

        try {
            /** @var \App\Models\Role $role */
            $role = Role::query()->create([
                'name' => $roleModelData->getName(),
                'description' => $roleModelData->getDescription(),
                'guard_name' => $roleModelData->getGuardName(),
            ]);

            if (!is_null($roleModelData->getPermissions())) {
                $role->syncPermissions(
                    Permission::query()
                        ->where('guard_name', $role->guard_name)
                        ->whereIn('id', $roleModelData->getPermissions())
                        ->get()
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $role->withoutRelations()->refresh();
    }

    /**
     * Update the role.
     *
     * @param \App\Models\Role $role
     * @param \App\DataTransferObjects\Models\RoleModelData $roleModelData
     * @return \App\Models\Role
     */
    public function update(Role $role, RoleModelData $roleModelData): Role
    {
        DB::beginTransaction();

        try {
            $role->updateOrFail([
                'name' => $roleModelData->getName() ?? $role->name,
                'description' => $roleModelData->getDescription() ?? $role->description,
            ]);

            if (!is_null($roleModelData->getPermissions())) {
                $role->syncPermissions(
                    Permission::query()
                        ->where('guard_name', $role->guard_name)
                        ->whereIn('id', $roleModelData->getPermissions())
                        ->get()
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $role->withoutRelations()->refresh();
    }
}
