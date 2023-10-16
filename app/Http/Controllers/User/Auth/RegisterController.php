<?php

namespace App\Http\Controllers\User\Auth;

use App\DataTransferObjects\Models\UserModelData;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\RegisterRequest;
use App\Services\Profile\User\UserService;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    /**
     * Register a new user.
     *
     * @param \App\Http\Requests\User\Auth\RegisterRequest $request
     * @param \App\Services\Profile\User\UserService $userService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(RegisterRequest $request, UserService $userService): Response
    {
        $user = $userService->create(
            userModelData: (new UserModelData())
                ->setCountryId($request->country_id)
                ->setFirstname($request->firstname)
                ->setLastname($request->lastname)
                ->setEmail($request->email)
                ->setPassword($request->password)
                ->setUsername($request->username)
                ->setPhoneNumber($request->phone_number)
                ->setDateOfBirth($request->date_of_birth)
                ->setRefCode($request->ref),
            authenticate: true
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage($user->getApiMessage())
            ->withData([
                'user' => $user->getUser(),
                'token' => $user->getToken(),
            ])
            ->build();
    }
}
