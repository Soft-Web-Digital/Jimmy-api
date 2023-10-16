<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request): Response
    {
        $permissions = Permission::query()
            ->where('guard_name', 'api_admin')
            ->orderBy('group_name');

        if ($request->do_not_paginate) {
            $permissions = $permissions->get();
        } else {
            $permissions = $permissions->paginate((int) $request->per_page)->withQueryString();
        }

        return ResponseBuilder::asSuccess()
            ->withMessage('Permissions fetched successfully.')
            ->withData([
                'permissions' => $permissions,
            ])
            ->build();
    }
}
