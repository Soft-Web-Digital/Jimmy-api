<?php

namespace App\Http\Controllers\Admin;

use App\DataTransferObjects\Models\RoleModelData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Role;
use App\Services\ACL\RoleService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Role $role
     */
    public function __construct(public Role $role)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request): Response
    {
        $roles = QueryBuilder::for(
            $this->role->query()->where('guard_name', 'api_admin')->where('name', '!=', 'SUPERADMIN')
        )
            ->allowedIncludes([
                'permissions',
                AllowedInclude::count('usersCount'),
            ])
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'created_at',
                'updated_at',
            ]);

        if ($request->do_not_paginate) {
            $roles = $roles->get();
        } else {
            $roles = $roles->paginate((int) $request->per_page)->withQueryString();
        }

        return ResponseBuilder::asSuccess()
            ->withMessage('Roles fetched successfully')
            ->withData([
                'roles' => $roles,
            ])
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Admin\StoreRoleRequest $request
     * @param \App\Services\ACL\RoleService $roleService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(StoreRoleRequest $request, RoleService $roleService): Response
    {
        $role = $roleService->create(
            (new RoleModelData())
                ->setName($request->name)
                ->setDescription($request->description)
                ->setGuardName('api_admin')
                ->setPermissions($request->permissions)
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('Role created successfully')
            ->withData([
                'role' => $role->refresh(),
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $role): Response
    {
        $role = QueryBuilder::for(
            Role::query()->where('guard_name', 'api_admin')->where('name', '!=', 'SUPERADMIN')
        )
            ->allowedIncludes([
                'permissions',
                AllowedInclude::count('usersCount'),
            ])
            ->findOrFail($role);

        return ResponseBuilder::asSuccess()
            ->withMessage('Role fetched successfully.')
            ->withData([
                'role' => $role,
            ])
            ->build();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateRoleRequest $request
     * @param \App\Models\Role $role
     * @param \App\Services\ACL\RoleService $roleService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(UpdateRoleRequest $request, Role $role, RoleService $roleService): Response
    {
        $role = $roleService->update(
            $role,
            (new RoleModelData())
                ->setName($request->name)
                ->setDescription($request->description)
                ->setGuardName('api_admin')
                ->setPermissions($request->permissions)
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Role updated successfully')
            ->withData([
                'role' => $role,
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Role $role): Response
    {
        $role->deleteOrFail();

        return response()->json(null, 204);
    }
}
