<?php

namespace App\Http\Controllers\User;

use App\DataTransferObjects\WalletData;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\TransferFundsRequest;
use App\Models\Giftcard;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\User $user
     */
    public function __construct(public User $user)
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
        $users = QueryBuilder::for(
            $this->user->select([
                'id',
                'firstname',
                'lastname',
                'email',
            ])->where('id', '!=', $request->user()->id)
        )
            ->allowedFilters([
                'email',
            ]);

        if ((bool) $request->do_not_paginate) {
            $users = $users->get();
        } else {
            $users = $users->paginate((int) $request->per_page)->withQueryString();
        }

        return ResponseBuilder::asSuccess()
            ->withMessage('Users fetched successfully.')
            ->withData([
                'users' => $users,
            ])
            ->build();
    }

    /**
     * Transfer funds to user.
     *
     * @param \App\Http\Requests\User\TransferFundsRequest $request
     * @param \App\Models\User $user
     * @param \App\Services\WalletService $walletService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function transfer(TransferFundsRequest $request, User $user, WalletService $walletService): Response
    {
        /** @var \App\Models\User $sender */
        $sender = $request->user();

        $walletData = (new WalletData())
            ->setAmount($request->amount)
            ->setReceipt($request->file('receipt'));

        $walletService->transfer($sender, $user, $walletData);

        return ResponseBuilder::asSuccess()
            ->withMessage('Fund transferred successfully.')
            ->build();
    }

    public function hardDelete() :Response
    {
        // Find and delete orphaned gift cards
        // Giftcard::whereDoesntHave('user', function ($query) {
        //     $query->withTrashed()->whereNotNull('deleted_at');
        // })->delete();

        // return ResponseBuilder::asSuccess()
        //     ->withMessage('Giftcard deleted successfully.')
        //     ->build();

         // Get a list of gift cards with no associated user
        //  $orphanedGiftCards = Giftcard::whereNull('user_id')->get();

        //  // Delete the orphaned gift cards
        //  foreach ($orphanedGiftCards as $giftCard) {
        //      $giftCard->delete();
        //  }

        $giftCards = Giftcard::with('user') // Eager load the user relationship
            ->whereHas('user', function ($query) {
                // Check if firstname and lastname are both "null"
                $query->where('firstname', 'null')->where('lastname', 'null');
            })->get();

        foreach ($giftCards as $giftCard) {
            $giftCard->delete();
        }
 
         // Optionally, you can return a response to indicate success or failure
         return response()->json(['message' => 'Orphaned gift cards deleted successfully']);
    }
}
