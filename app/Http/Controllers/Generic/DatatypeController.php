<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
use App\Models\Datatype;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class DatatypeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Datatype $datatype
     */
    public function __construct(public Datatype $datatype)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(): Response
    {
        $datatypes = $this->datatype->select(['id', 'name', 'hint', 'developer_hint'])->orderBy('name')->get();

        return ResponseBuilder::asSuccess()
            ->withMessage('Datatypes fetched successfully.')
            ->withData([
                'datatypes' => $datatypes,
            ])
            ->build();
    }
}
