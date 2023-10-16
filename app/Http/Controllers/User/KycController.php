<?php

namespace App\Http\Controllers\User;

use App\Enums\KycAttribute;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SubmitKycRequest;
use App\Models\UserKyc;
use App\Services\Profile\UserKycService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class KycController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\UserKyc $userKyc
     */
    public function __construct(public UserKyc $userKyc)
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
        $kyc = $this->userKyc->query()
            ->whereMorphedTo('user', $request->user())
            ->first();

        return ResponseBuilder::asSuccess()
            ->withMessage('User KYC fetched successfully')
            ->withData([
                'kyc' => $kyc,
            ])
            ->build();
    }

    /**
     * Verify KYC.
     *
     * @param \App\Http\Requests\User\SubmitKycRequest $request
     * @param \App\Enums\KycAttribute $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function verify(SubmitKycRequest $request, KycAttribute $type, UserKycService $userKycService): Response
    {
        $kyc = $userKycService->verify($request->user(), $type, $request->value);

        return ResponseBuilder::asSuccess()
            ->withMessage('KYC verification is initiated successfully')
            ->withData([
                'kyc' => $kyc,
            ])
            ->build();
    }
}
