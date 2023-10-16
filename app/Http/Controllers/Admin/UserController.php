<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApiErrorCode;
use App\Enums\WalletTransactionType;
use App\Exports\DataExport;
use App\Exports\UserSheet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinanceUserRequest;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param User $user
     */
    public function __construct(public User $user)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $users = QueryBuilder::for($this->user->query())
            ->allowedFields($this->user->getQuerySelectables())
            ->allowedFilters([
                AllowedFilter::trashed(),
                'email',
                AllowedFilter::exact('country_id'),
                AllowedFilter::scope('name'),
                AllowedFilter::scope('email_verified'),
                AllowedFilter::scope('registration_date'),
            ])
            ->allowedIncludes([
                'country',
            ])
            ->defaultSort('-updated_at')
            ->allowedSorts([
                'firstname',
                'blocked_at',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Users fetched successfully.')
            ->withData([
                'users' => $users,
                'active_users' => $this->user->query()->whereNull(['deleted_at', 'blocked_at'])->count(),
                'blocked_users' => $this->user->query()->whereNotNull('blocked_at')->count(),
                'inactive_users' => $this->user->withTrashed()->whereNotNull('deleted_at')->count()
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $user
     * @return Response
     */
    public function show(string $user): Response
    {
        $user = QueryBuilder::for($this->user->query())
            ->allowedFields($this->user->getQuerySelectables())
            ->allowedIncludes([
                'country',
            ])
            ->where('id', $user)
            ->orWhere('email', $user)
            ->firstOrFail();

        return ResponseBuilder::asSuccess()
            ->withMessage('User fetched successfully.')
            ->withData([
                'user' => $user,
            ])
            ->build();
    }

    /**
     * Toggle the blocked status of the specified resource.
     *
     * @param User $user
     * @return Response
     */
    public function toggleBlock(User $user): Response
    {
        $user->toggleBlock();

        return ResponseBuilder::asSuccess()
            ->withMessage('User blocked status updated successfully.')
            ->withData([
                'user' => $user,
            ])
            ->build();
    }

    /**
     * Finance the specified user.
     *
     * @param FinanceUserRequest $request
     * @param User $user
     * @param WalletTransactionType $type
     * @return Response
     */
    public function finance(
        FinanceUserRequest $request,
        User $user,
        WalletTransactionType $type,
        WalletService $walletService
    ): Response {
        $user = $walletService->finance(
            $user,
            $type,
            $request->user(),
            $request->amount,
            $request->note,
            $request->file('receipt')
        );

        return ResponseBuilder::asSuccess()
            ->withMessage("NGN {$request->amount} {$type->sentenceTerm()} user successfully.")
            ->withData([
                'user' => $user,
            ])
            ->build();
    }

    /**
     * Restore the specified resource.
     *
     * @param User $user
     * @return Response
     */
    public function restore(User $user): Response
    {
        $user->restore();

        return ResponseBuilder::asSuccess()
            ->withMessage('User restored successfully.')
            ->withData([
                'user' => $user,
            ])
            ->build();
    }

    /**
     * Export users to a spreadsheet file.
     *
     * @return Response
     */
    public function export(Request $request): Response
    {
        $total = User::query()->count();
        $limit = $request->query('limit') ?? 5000;
        $offset = $request->query('offset') ?? 0;
        $excel = new DataExport(User::class, $total, UserSheet::class, $offset, $limit);
        $path = 'exports/users.xlsx';
        if (Excel::store($excel, $path, 'public')) {
            return ResponseBuilder::asSuccess()
                ->withMessage('User exported successfully.')
                ->withData([
                    'path' => asset("storage/{$path}")
                ])
                ->build();
        }

        return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
            ->withMessage('Unable to export users.')
            ->build();
    }
}
