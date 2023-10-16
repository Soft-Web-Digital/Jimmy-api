<?php

namespace App\Http\Controllers\User;

use App\DataTransferObjects\Models\UserModelData;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Profile\DeleteProfileRequest;
use App\Http\Requests\User\Profile\ProfileUpdateRequest;
use App\Http\Requests\User\Profile\UpdatePasswordRequest;
use App\Services\Profile\ProfilePasswordService;
use App\Services\Profile\ProfileTwoFaService;
use App\Services\Profile\User\UserProfileService;
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
        /** @var \App\Models\Admin $user */
        $user = $request->user()->load('country:id,name,flag_url,dialing_code');

        return ResponseBuilder::asSuccess()
            ->withMessage('Account fetched successfully')
            ->withData([
                'user' => $user,
                'requires_two_fa' => !$user->tokenCan('*') && $user->tokenCan('two_fa'),
            ])
            ->build();
    }

    /**
     * Update profile password.
     *
     * @param \App\Http\Requests\User\Profile\UpdatePasswordRequest $request
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
        /** @var \App\Models\User $user */
        $user = $request->user();

        $status = $profileTwoFaService->toggle($user);

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
     * @param \App\Http\Requests\User\Profile\ProfileUpdateRequest $request
     * @param \App\Services\Profile\User\UserProfileService $userProfileService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateProfile(ProfileUpdateRequest $request, UserProfileService $userProfileService): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $user = $userProfileService->update(
            $user,
            (new UserModelData())
                ->setCountryId($request->country_id)
                ->setFirstname($request->firstname)
                ->setLastname($request->lastname)
                ->setEmail($request->email)
                ->setPhoneNumber($request->phone_number)
                ->setAvatar($request->file('avatar'))
                ->setUsername($request->username)
                ->setPhoneNumber($request->phone_number)
                ->setDateOfBirth($request->date_of_birth)
                ->setFcmToken($request->fcm_token)
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Profile updated successfully')
            ->withData([
                'user' => $user,
            ])
            ->build();
    }

    /**
     * Delete profile.
     *
     * @param \App\Http\Requests\User\Profile\DeleteProfileRequest $request
     * @param \App\Services\Profile\User\UserProfileService $userProfileService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function destroy(DeleteProfileRequest $request, UserProfileService $userProfileService): Response
    {
        $userProfileService->delete($request->user(), $request->reason);

        return response('', Response::HTTP_NO_CONTENT);
    }
}
