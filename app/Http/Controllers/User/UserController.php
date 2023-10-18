<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\Giftcard;
use Illuminate\Http\Request;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use App\DataTransferObjects\WalletData;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\User\TransferFundsRequest;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

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
        // $giftCardsToDelete = Giftcard::whereNotIn('user_id', User::pluck('id')->all())->get();

        // Delete the gift cards
        // foreach ($giftCardsToDelete as $giftCard) {
        //     $giftCard->delete();
        // }

        $deletedRows = Giftcard::whereNotExists(function ($query) {
            $query->select('id')
                ->from('users')
                ->where('users.id', '=', DB::raw('gift_cards.user_id'));
        })->delete();

        return response()->json(['message' => 'Invalid gift cards deleted successfully', 'deleted_rows' => $deletedRows]);
 
         // Optionally, you can return a response to indicate success or failure
        //  return response()->json(['message' => 'Orphaned gift cards deleted successfully']);
    }
}
