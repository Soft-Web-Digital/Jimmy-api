<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ReadNotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $notifications = QueryBuilder::for($user->notifications()->reOrder())
            ->allowedFilters(
                AllowedFilter::callback('read', function ($query, $value) {
                    $query->when((bool) $value, fn ($query) => $query->read(), fn ($query) => $query->unread());
                }),
            )
            ->defaultSort('created_at')
            ->allowedSorts([
                'read_at',
                'created_at',
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Notifications fetched successfully.')
            ->withData([
                'notifications' => $notifications
            ])
            ->build();
    }

    /**
     * Mark notifications as read.
     *
     * @param \App\Http\Requests\User\ReadNotificationRequest $request
     * @param \App\Services\NotificationService $notificationService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function markAsRead(ReadNotificationRequest $request, NotificationService $notificationService): Response
    {
        $notificationService->markAsRead($request->user(), $request->notifications);

        return ResponseBuilder::asSuccess()
            ->withMessage('Notification(s) marked as read')
            ->build();
    }
}
