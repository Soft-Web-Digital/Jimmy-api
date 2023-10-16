<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNetworkRequest;
use App\Http\Requests\Admin\UpdateNetworkRequest;
use App\Models\Network;
use App\Services\Crypto\NetworkService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class NetworkController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Network $network
     */
    public function __construct(public Network $network)
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
        $networks = QueryBuilder::for($this->network->query())
            ->allowedFields($this->network->getQuerySelectables())
            ->allowedFilters([
                'name',
                AllowedFilter::trashed(),
            ])
            ->allowedIncludes([
                'assets',
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Networks fetched successfully')
            ->withData([
                'networks' => $networks,
            ])
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Admin\StoreNetworkRequest $request
     * @param \App\Services\Crypto\NetworkService $networkService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(StoreNetworkRequest $request, NetworkService $networkService): Response
    {
        $network = $networkService->create($request->name, $request->wallet_address, $request->comment);

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('Network created successfully')
            ->withData([
                'network' => $network,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $network
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $network): Response
    {
        $network = QueryBuilder::for($this->network->query())
            ->allowedIncludes([
                'assets',
            ])
            ->findOrFail($network);

        return ResponseBuilder::asSuccess()
            ->withMessage('Network fetched successfully')
            ->withData([
                'network' => $network,
            ])
            ->build();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateNetworkRequest $request
     * @param \App\Models\Network $network
     * @param \App\Services\Crypto\NetworkService $networkService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(UpdateNetworkRequest $request, Network $network, NetworkService $networkService): Response
    {
        $network = $networkService->update($network, $request->name, $request->wallet_address, $request->comment);

        return ResponseBuilder::asSuccess()
            ->withMessage('Network updated successfully')
            ->withData([
                'network' => $network,
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Network $network
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Network $network): Response
    {
        $network->deleteOrFail();

        return response()->json(null, 204);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param \App\Models\Network $network
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function restore(Network $network): Response
    {
        $network->restore();

        return ResponseBuilder::asSuccess()
            ->withMessage('Network restored successfully')
            ->withData([
                'network' => $network,
            ])
            ->build();
    }
}
