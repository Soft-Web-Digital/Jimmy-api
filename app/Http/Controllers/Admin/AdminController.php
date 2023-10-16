<?php

namespace App\Http\Controllers\Admin;

use App\DataTransferObjects\Models\AdminModelData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Admin;
use App\Services\Profile\Admin\AdminService;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Admin $admin
     */
    public function __construct(public Admin $admin)
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
        $admins = QueryBuilder::for(
            $this->admin->query()
                ->whereHas('roles', fn ($query) => $query->where('name', '!=', 'SUPERADMIN'))
                ->orWhereDoesntHave('roles')
        )
            ->allowedFields($this->admin->getQuerySelectables())
            ->allowedFilters([
                'email',
                AllowedFilter::trashed(),
                AllowedFilter::scope('blocked'),
                AllowedFilter::scope('email_verified', 'emailVerified'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('country', new IncludeSelectFields([
                    'id',
                    'name',
                    'flag_url',
                    'dialing_code',
                ])),
                'roles',
                AllowedInclude::count('giftcardCategoriesCount'),
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Admins fetched successfully')
            ->withData([
                'admins' => $admins,
            ])
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Admin\StoreAdminRequest $request
     * @param \App\Services\Profile\Admin\AdminService $adminService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(StoreAdminRequest $request, AdminService $adminService): Response
    {
        $admin = $adminService->create(
            (new AdminModelData())
                ->setCountryId($request->country_id)
                ->setFirstname($request->firstname)
                ->setLastname($request->lastname)
                ->setEmail($request->email),
            $request->login_url
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('Admin created successfully')
            ->withData([
                'admin' => $admin,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $admin
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $admin): Response
    {
        $admin = QueryBuilder::for($this->admin->query())
            ->allowedIncludes([
                AllowedInclude::custom('country', new IncludeSelectFields([
                    'id',
                    'name',
                    'flag_url',
                    'dialing_code',
                ])),
                'roles',
                AllowedInclude::custom('giftcardCategories', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
            ])
            ->findOrFail($admin);

        return ResponseBuilder::asSuccess()
            ->withMessage('Admin fetched successfully')
            ->withData([
                'admin' => $admin,
            ])
            ->build();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateAdminRequest $request
     * @param \App\Models\Admin $admin
     * @param \App\Services\Profile\Admin\AdminService $adminService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(UpdateAdminRequest $request, Admin $admin, AdminService $adminService): Response
    {
        $this->authorize('update', $admin);

        $admin = $adminService->update(
            $admin,
            (new AdminModelData())
                ->setCountryId($request->country_id)
                ->setFirstname($request->firstname)
                ->setLastname($request->lastname)
                ->setEmail($request->email)
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Admin updated successfully')
            ->withData([
                'admin' => $admin->refresh(),
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Admin $admin
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Admin $admin): JsonResponse
    {
        $this->authorize('delete', $admin);

        $admin->deleteOrFail();

        return response()->json(null, 204);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param \App\Models\Admin $admin
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function restore(Admin $admin): Response
    {
        $this->authorize('restore', $admin);

        $admin->restore();

        return ResponseBuilder::asSuccess()
            ->withMessage('Admin restored successfully')
            ->withData([
                'admin' => $admin,
            ])
            ->build();
    }

    /**
     * Toggle the blocked status of the specified resource from storage.
     *
     * @param \App\Models\Admin $admin
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toggleBlock(Admin $admin): Response
    {
        $this->authorize('update', $admin);

        $admin->toggleBlock();

        return ResponseBuilder::asSuccess()
            ->withMessage('Admin blocked status updated successfully')
            ->withData([
                'admin' => $admin,
            ])
            ->build();
    }

    /**
     * Toggle admin role.
     *
     * @param \App\Http\Requests\Admin\AssignRoleRequest $request
     * @param \App\Models\Admin $admin
     * @param \App\Services\Profile\Admin\AdminService $adminService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toggleRole(AssignRoleRequest $request, Admin $admin, AdminService $adminService): Response
    {
        $this->authorize('update', $admin);

        $adminService->toggleRole($admin, $request->role_id);

        return ResponseBuilder::asSuccess()
            ->withMessage('Admin role updated successfully.')
            ->withData([
                'admin' => $admin,
            ])
            ->build();
    }
}
