<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApiErrorCode;
use App\Enums\GiftcardStatus;
use App\Exports\DataExport;
use App\Exports\GiftcardSheet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveGiftcardRequest;
use App\Http\Requests\Admin\DeclineGiftcardRequest;
use App\Models\Giftcard;
use App\Models\User;
use App\Services\Giftcard\GiftcardService;
use App\Spatie\QueryBuilder\IncludeRelationCallback;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
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
        $giftcardsQuery = $this->giftcard
            ->withCount('children')
            ->with('children')
            ->withTrashed();

        if (!$request->user()->hasRole('SUPERADMIN')) { // @phpstan-ignore-line
            $giftcardsQuery->where(function ($q) use ($request) {
                $q->where('reviewed_by', auth()->id())
                ->orWhereHas(
                    'giftcardProduct',
                    fn ($query) => $query->whereIn(
                        'giftcard_category_id',
                        $request->user()->giftcardCategories()->pluck('id')->toArray() // @phpstan-ignore-line
                    )
                );
            });
        }

        $giftcards = QueryBuilder::for($giftcardsQuery)
            ->allowedFields($this->giftcard->getQuerySelectables())
            ->allowedFilters([
                'trade_type',
                'card_type',
                'status',
                'reference',
                AllowedFilter::exact('user_id'),
                AllowedFilter::scope('creation_date'),
                AllowedFilter::scope('giftcard_categories'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('giftcardProduct', new IncludeRelationCallback(
                    fn ($query) => $query->with([
                        'giftcardCategory:id,name,icon',
                        'country:id,name,flag_url',
                        'currency:id,name,code',
                    ])
                )),
                AllowedInclude::custom('user', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email'
                ])),
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
                fn ($query) => $query->whereNull('parent_id')
            )
            ->when(
                $request->status,
                fn ($q) => $q->where(function ($q) use ($request) {
                    $q->where(function ($q) use ($request) {
                        $q->whereNull('parent_id')
                            ->where('status', 'LIKE', "{$request->status}%");
                    })->orWhereHas(
                        'children',
                        fn ($q) => $q->where('status', 'LIKE', "{$request->status}%")
                    );
                })
            )
            ->paginate((int)$request->per_page)
            ->withQueryString();

        $giftcards->getCollection()->transform(function ($giftcard) {
            if ($giftcard->children_count > 0) {
                $giftcard->amount += $giftcard->children->sum('amount');
                $giftcard->status = 'multiple';
            }
            unset($giftcard->children);
            return $giftcard;
        });

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcards fetched successfully')
            ->withData([
                'giftcards' => $giftcards,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $giftcard
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $giftcard): Response
    {
        $giftcard = QueryBuilder::for(
            $this->giftcard->withTrashed()->with('media', 'legacyCards')
        )
            ->allowedIncludes([
                AllowedInclude::custom('giftcardProduct', new IncludeRelationCallback(
                    fn ($query) => $query->with([
                        'giftcardCategory:id,name,icon',
                        'country:id,name,flag_url',
                        'currency:id,name,code',
                    ])
                )),
                AllowedInclude::custom('user', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email'
                ])),
                AllowedInclude::custom('reviewer', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email'
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
                    AllowedInclude::custom('user', new IncludeSelectFields([
                        'id',
                        'firstname',
                        'lastname',
                        'email'
                    ])),
                    AllowedInclude::custom('reviewer', new IncludeSelectFields([
                        'id',
                        'firstname',
                        'lastname',
                        'email'
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
     * Decline the specified resource from storage.
     *
     * @param \App\Http\Requests\Admin\DeclineGiftcardRequest $request
     * @param \App\Models\Giftcard $giftcard
     * @param \App\Services\Giftcard\GiftcardService $giftcardService
     * @param string|null $multiple
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function decline(
        DeclineGiftcardRequest $request,
        Giftcard $giftcard,
        GiftcardService $giftcardService,
        ?string $multiple = null
    ): Response {
        $this->authorize('manage', $giftcard);

        $giftcard = $giftcardService->decline(
            $giftcard,
            $request->user('api_admin'), // @phpstan-ignore-line
            $request->review_note,
            $request->review_proof,
            (bool) $multiple
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard declined successfully')
            ->withData([
                'giftcard' => $giftcard,
            ])
            ->build();
    }

    /**
     * Approve the specified resource from storage.
     *
     * @param \App\Http\Requests\Admin\ApproveGiftcardRequest $request
     * @param \App\Models\Giftcard $giftcard
     * @param \App\Services\Giftcard\GiftcardService $giftcardService
     * @param string|null $multiple
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function approve(
        ApproveGiftcardRequest $request,
        Giftcard $giftcard,
        GiftcardService $giftcardService,
        ?string $multiple = null
    ): Response {
        $this->authorize('manage', $giftcard);

        $giftcard = $giftcardService->approve(
            $giftcard,
            $request->user('api_admin'), // @phpstan-ignore-line
            (bool) $request->complete_approval,
            $request->review_amount,
            $request->review_note,
            $request->review_proof,
            (bool) $multiple
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard approved successfully')
            ->withData([
                'giftcard' => $giftcard,
            ])
            ->build();
    }

    /**
     * Export asset transactions to a spreadsheet file.
     *
     * @return Response
     */
    public function export(Request $request): Response
    {
        $total = Giftcard::query()->count();
        $limit = $request->query('limit') ?? 5000;
        $offset = $request->query('offset') ?? 0;
        $excel = new DataExport(Giftcard::class, $total, GiftcardSheet::class, $offset, $limit);
        $path = 'exports/giftcards.xlsx';
        if (Excel::store($excel, $path, 'public')) {
            return ResponseBuilder::asSuccess()
                ->withMessage('Giftcard transactions exported successfully.')
                ->withData([
                    'path' => asset("storage/{$path}")
                ])
                ->build();
        }

        return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
            ->withMessage('Unable to export giftcard transactions.')
            ->build();
    }

    public function topTraders(Request $request): Response
    {
        $from = $request->query('from');
        $to = $request->query('to') ?? now();
        $query = User::query()
            ->withCount([
                'giftcardTransactions' => fn ($query) => $query->when(
                    $from,
                    fn () => $query->whereBetween('created_at', [$from, $to])
                )->whereIn('status', [GiftcardStatus::APPROVED, GiftcardStatus::PARTIALLYAPPROVED])
            ])
            ->withSum([
                'giftcardTransactions' => fn ($query) => $query->when(
                    $from,
                    fn () => $query->whereBetween('created_at', [$from, $to])
                )->whereIn('status', [GiftcardStatus::APPROVED, GiftcardStatus::PARTIALLYAPPROVED])
            ], 'amount')
            ->withSum([
                'giftcardTransactions' => fn ($query) => $query->when(
                    $from,
                    fn () => $query->whereBetween('created_at', [$from, $to])
                )->whereIn('status', [GiftcardStatus::APPROVED, GiftcardStatus::PARTIALLYAPPROVED])
            ], 'payable_amount')
            ->having('giftcard_transactions_count', '>', 0)
            ->orderByDesc('giftcard_transactions_sum_payable_amount');

        return ResponseBuilder::asSuccess()
            ->withMessage('Top traders fetched successfully.')
            ->withData([
                'users' => $query->limit($request->query('limit') ?? 10)
                    ->get($request->query('select') ? explode(',', $request->query('select')) : ['*']),
                'total_traders' => $query->count(),
                'total_trades' => (int) $query->sum('giftcard_transactions_count'),
                'total_traded_usd' => (int) $query->sum('giftcard_transactions_sum_amount'),
                'total_traded_ngn' => (int) $query->sum('giftcard_transactions_sum_payable_amount'),
            ])
            ->build();
    }
}
