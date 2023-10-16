<?php

namespace App\Http\Controllers\Admin;

use App\DataTransferObjects\Models\AdminModelData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Profile\ProfileUpdateRequest;
use App\Http\Requests\Admin\Profile\UpdatePasswordRequest;
use App\Services\Profile\Admin\AdminProfileService;
use App\Services\Profile\ProfilePasswordService;
use App\Services\Profile\ProfileTwoFaService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    /**
     * Get account data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\Admin $admin */
        $admin = $request->user()->load('country:id,name,flag_url,dialing_code');

        return ResponseBuilder::asSuccess()
            ->withMessage('Account fetched successfully')
            ->withData([
                'admin' => $admin,
                'requires_two_fa' => !$admin->tokenCan('*') && $admin->tokenCan('two_fa'),
            ])
            ->build();
    }

    /**
     * Get the admin permissions.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPermissions(Request $request): Response
    {
        /** @var \App\Models\Admin $admin */
        $admin = $request->user();

        return ResponseBuilder::asSuccess()
            ->withMessage('Admin permissions fetched successfully.')
            ->withData([
                'permissions' => $admin->getAllPermissions()
                    ->map(fn ($permission) => $permission->only(['id', 'name', 'group_name', 'description'])),
            ])
            ->build();
    }

    /**
     * Update profile password.
     *
     * @param \App\Http\Requests\Admin\Profile\UpdatePasswordRequest $request
     * @param \App\Services\Profile\ProfilePasswordService $profilePasswordService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updatePassword(
        UpdatePasswordRequest $request,
        ProfilePasswordService $profilePasswordService
    ): Response {
        $profilePasswordService->update($request->user(), $request->new_password);

        return ResponseBuilder::asSuccess()
            ->withMessage('Profile password updated successfully')
            ->build();
    }

    /**
     * Toggle Two-FA status.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\Profile\ProfileTwoFaService $profileTwoFaService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateTwoFa(Request $request, ProfileTwoFaService $profileTwoFaService): Response
    {
        /** @var \App\Models\Admin $admin */
        $admin = $request->user();

        $status = $profileTwoFaService->toggle($admin);

        return ResponseBuilder::asSuccess()
            ->withMessage('Two-FA status updated successfully')
            ->withData([
                'status' => $status,
            ])
            ->build();
    }

    /**
     * Update profile.
     *
     * @param \App\Http\Requests\Admin\Profile\ProfileUpdateRequest $request
     * @param \App\Services\Profile\Admin\AdminProfileService $adminProfileService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateProfile(ProfileUpdateRequest $request, AdminProfileService $adminProfileService): Response
    {
        /** @var \App\Models\Admin $admin */
        $admin = $request->user();

        $admin = $adminProfileService->update(
            $admin,
            (new AdminModelData())
                ->setCountryId($request->country_id)
                ->setFirstname($request->firstname)
                ->setLastname($request->lastname)
                ->setEmail($request->email)
                ->setPhoneNumber($request->phone_number)
                ->setAvatar($request->file('avatar'))
                ->setFcmToken($request->fcm_token)
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Profile updated successfully')
            ->withData([
                'admin' => $admin,
            ])
            ->build();
    }
}
