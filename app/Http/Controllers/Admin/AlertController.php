<?php

namespace App\Http\Controllers\Admin;

use App\DataTransferObjects\Models\AlertModelData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAlertRequest;
use App\Http\Requests\Admin\UpdateAlertRequest;
use App\Models\Alert;
use App\Services\Alert\AlertService;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class AlertController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Alert $alert
     */
    public function __construct(public Alert $alert)
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
        $alerts = QueryBuilder::for(
            $this->alert->select([
                'id',
                'title',
                'status',
                'target_user',
                'target_user_count',
                'dispatched_at',
                'creator_id',
                'created_at',
                'updated_at',
            ])
        )
            ->allowedFields($this->alert->getQuerySelectables())
            ->allowedFilters([
                'status',
                'target_user',
                AllowedFilter::scope('dispatch_date'),
                AllowedFilter::trashed(),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('creator', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                ])),
                AllowedInclude::count('usersCount'),
            ])
            ->defaultSort('-dispatched_at')
            ->allowedSorts([
                'status',
                'target_user',
                'target_user_count',
                'dispatched_at',
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Alerts fetched successfully')
            ->withData([
                'alerts' => $alerts,
            ])
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Admin\StoreAlertRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(StoreAlertRequest $request, AlertService $alertService): Response
    {
        $alert = $alertService->create(
            (new AlertModelData())
                ->setTitle($request->title)
                ->setBody($request->body)
                ->setTargetUser($request->target_user)
                ->setChannels(array_unique($request->channels))
                ->setDispatchDatetime($request->dispatch_datetime)
                ->setUsers($request->users)
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('Alert created successfully')
            ->withData([
                'alert' => $alert,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $alert
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $alert): Response
    {
        $alert = QueryBuilder::for($this->alert->query())
            ->allowedFields($this->alert->getQuerySelectables())
            ->allowedIncludes([
                AllowedInclude::custom('creator', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                ])),
                AllowedInclude::custom('users', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                ])),
            ])
            ->findOrFail($alert);

        return ResponseBuilder::asSuccess()
            ->withMessage('Alert fetched successfully')
            ->withData([
                'alert' => $alert,
            ])
            ->build();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateAlertRequest $request
     * @param \App\Models\Alert $alert
     * @param \App\Services\Alert\AlertService $alertService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(UpdateAlertRequest $request, Alert $alert, AlertService $alertService): Response
    {
        $alert = $alertService->update(
            $alert,
            (new AlertModelData())
                ->setTitle($request->title)
                ->setBody($request->body)
                ->setTargetUser($request->target_user)
                ->setChannels($request->channels ? array_unique($request->channels) : null)
                ->setDispatchDatetime($request->dispatch_datetime)
                ->setUsers($request->users)
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Alert updated successfully')
            ->withData([
                'alert' => $alert,
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Alert $alert
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Alert $alert): Response
    {
        $alert->deleteOrFail();

        return response()->json(null, 204);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param \App\Models\Alert $alert
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function restore(Alert $alert): Response
    {
        $alert->restore();

        return ResponseBuilder::asSuccess()
            ->withMessage('Alert restored successfully')
            ->withData([
                'alert' => $alert,
            ])
            ->build();
    }

    /**
     * Dispatch the specified resource.
     *
     * @param \App\Models\Alert $alert
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatchAlert(Alert $alert): Response
    {
        $alert->markAsOngoing();

        return ResponseBuilder::asSuccess()
            ->withMessage('Alert dispatched successfully')
            ->withData([
                'alert' => $alert->refresh(),
            ])
            ->build();
    }
}
