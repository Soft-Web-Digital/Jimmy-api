<?php

namespace App\Http\Controllers\User;

use App\DataTransferObjects\Models\GiftcardModelData;
use App\Enums\GiftcardTradeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\BreakdownGiftcardRequest;
use App\Http\Requests\User\StoreGiftcardSaleRequest;
use App\Models\Giftcard;
use App\Services\Giftcard\GiftcardService;
use App\Spatie\QueryBuilder\IncludeRelationCallback;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class GiftcardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Giftcard $giftcard
     */
    public function __construct(public Giftcard $giftcard)
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
        $giftcards = QueryBuilder::for(
            $this->giftcard
                ->withCount('children')
                ->withTrashed()
                ->where('user_id', $request->user()->id)
        )
            ->allowedFilters([
                'trade_type',
                'card_type',
                'status',
                'reference',
                AllowedFilter::scope('creation_date'),
                AllowedFilter::scope('payable_amount'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('giftcardProduct', new IncludeRelationCallback(
                    fn ($query) => $query->with([
                        'giftcardCategory:id,name,icon',
                        'country:id,name,flag_url',
                        'currency:id,name,code',
                    ])
                )),
                AllowedInclude::custom('bank', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
            ])
            ->defaultSort('-created_at')
            ->allowedSorts([
                'status',
                'trade_type',
                'card_type',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->when(
                $request->filter,
                fn ($query) => $query,
                fn ($query) => $query->whereNull('parent_id'),
            )
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcards fetched successfully')
            ->withData([
                'giftcards' => $giftcards,
            ])
            ->build();
    }

    /**
     * Get asset transaction stats.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\Giftcard\GiftcardService $giftcardService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getStats(Request $request, GiftcardService $giftcardService): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $stats = $giftcardService->getStats($user);

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard stats fetched successfully.')
            ->withData([
                'stats' => $stats,
            ])
            ->build();
    }

    /**
     * Get the breakdown for the giftcard transaction.
     *
     * @param \App\Http\Requests\User\BreakdownGiftcardRequest $request
     * @param \App\Services\Giftcard\GiftcardService $giftcardService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function breakdown(BreakdownGiftcardRequest $request, GiftcardService $giftcardService): Response
    {
        $breakdown = $giftcardService->breakdown(
            (new GiftcardModelData())
                ->setGiftcardProductId($request->giftcard_product_id)
                ->setTradeType($request->trade_type)
                ->setAmount((float) $request->amount)
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard transaction breakdown fetched successfully')
            ->withData([
                'breakdown' => [
                    'rate' => $breakdown->getRate(),
                    'service_charge' => $breakdown->getServiceCharge(),
                    'payable_amount' => $breakdown->getPayableAmount(),
                ],
            ])
            ->build();
    }

    /**
     * Create a giftcard sale in storage.
     *
     * @param \App\Http\Requests\User\StoreGiftcardSaleRequest $request
     * @param \App\Services\Giftcard\GiftcardService $giftcardService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function storeSale(StoreGiftcardSaleRequest $request, GiftcardService $giftcardService): Response
    {
        $giftcardModelData = (new GiftcardModelData())
            ->setGiftcardProductId($request->giftcard_product_id)
            ->setUserBankAccountId($request->user_bank_account_id)
            ->setTradeType(GiftcardTradeType::SELL)
            ->setCardType($request->card_type)
            ->setUploadType($request->upload_type)
            ->setAmount((float) $request->amount)
            ->setQuantity((int) ($request->quantity ?? 1))
            ->setGroupTag($request->group_tag)
            ->setComment($request->comment)
            ->setCodes($request->codes)
            ->setPins($request->pins)
            ->setCards($request->cards);

        $data = $giftcardService->create($giftcardModelData);

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage(
                'Giftcard sale ' . Str::of('transaction')->plural($giftcardModelData->getQuantity())->toString()
                . ' created successfully'
            )
            ->withData([
                'giftcard' => $data
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $giftcard
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(Request $request, string $giftcard): Response
    {
        $giftcard = QueryBuilder::for(
            $this->giftcard->withTrashed()->where('user_id', $request->user()->id)
        )
            ->allowedIncludes([
                AllowedInclude::custom('giftcardProduct', new IncludeRelationCallback(
                    fn ($query) => $query->with([
                        'giftcardCategory:id,name,icon',
                        'country:id,name,flag_url',
                        'currency:id,name,code',
                    ])
                )),
                AllowedInclude::custom('reviewer', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                ])),
                AllowedInclude::custom('bank', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
            ])
            ->where(
                fn ($query) => $query->where('id', $giftcard)->orWhere('reference', $giftcard)
            )
            ->firstOrFail();

        /** @var \App\Models\Giftcard $giftcard */
        $giftcard->makeVisible([
            'code',
            'pin',
        ]);
        $giftcard->cards = $giftcard->getCardMedia();

        $relatedGiftcards = $giftcard->group_tag
            ? QueryBuilder::for(
                $this->giftcard
                    ->withTrashed()
                    ->where('user_id', $request->user()->id)
                    ->where('group_tag', $giftcard->group_tag)
                    ->whereNot('id', $giftcard->id)
            )
                ->allowedIncludes([
                    AllowedInclude::custom('giftcardProduct', new IncludeRelationCallback(
                        fn ($query) => $query->with([
                            'giftcardCategory:id,name,icon',
                            'country:id,name,flag_url',
                            'currency:id,name,code',
                        ])
                    )),
                    AllowedInclude::custom('reviewer', new IncludeSelectFields([
                        'id',
                        'firstname',
                        'lastname',
                    ])),
                    AllowedInclude::custom('bank', new IncludeSelectFields([
                        'id',
                        'name',
                    ])),
                ])
                ->get()
                ->map(function ($giftcard) {
                    /** @var \App\Models\Giftcard $giftcard */
                    $giftcard->cards = $giftcard->getCardMedia();
                    return $giftcard;
                })
            : null;

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard fetched successfully')
            ->withData([
                'giftcard' => $giftcard,
                'related_giftcards' => $relatedGiftcards,
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Giftcard $giftcard
     * @param \App\Services\Giftcard\GiftcardService $giftcardService
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Giftcard $giftcard, GiftcardService $giftcardService): Response
    {
        $giftcardService->delete($giftcard);

        return response()->json(null, 204);
    }
}
