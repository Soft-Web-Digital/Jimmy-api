<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemDataRequest;
use App\Models\SystemData;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class SystemDataController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\SystemData $systemData
     */
    public function __construct(public SystemData $systemData)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(): Response
    {
        $systemData = $this->systemData->query()
            ->with([
                'datatype' => fn ($query) => $query->select(['id', 'name', 'hint']),
            ])
            ->latest('updated_at')
            ->get();

        return ResponseBuilder::asSuccess()
            ->withMessage('System data fetched successfully.')
            ->withData([
                'system_data' => $systemData,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $systemData
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $systemData): Response
    {
        $systemData = $this->systemData->query()
            ->with([
                'datatype' => fn ($query) => $query->select(['id', 'name', 'hint']),
            ])
            ->findOrFail($systemData);

        return ResponseBuilder::asSuccess()
            ->withMessage('System data fetched successfully.')
            ->withData([
                'system_data' => $systemData,
            ])
            ->build();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateSystemDataRequest $request
     * @param \App\Models\SystemData $systemData
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(UpdateSystemDataRequest $request, SystemData $systemData): Response
    {
        $systemData->updateOrFail([
            'content' => $request->content,
        ]);

        return ResponseBuilder::asSuccess()
            ->withMessage('System data updated successfully.')
            ->withData([
                'system_data' => $systemData->withoutRelations()->refresh(),
            ])
            ->build();
    }
}
