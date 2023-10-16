<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
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
     * Display the specified resource.
     *
     * @param mixed $code
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(mixed $code): Response
    {
        $systemData = $this->systemData->query()
            ->select(['code', 'title', 'content', 'updated_at'])
            ->where('code', $code)
            ->firstOrFail();

        return ResponseBuilder::asSuccess()
            ->withMessage('System data fetched successfully.')
            ->withData([
                'system_data' => $systemData,
            ])
            ->build();
    }
}
