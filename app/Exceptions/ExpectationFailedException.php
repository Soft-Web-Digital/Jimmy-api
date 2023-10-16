<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class ExpectationFailedException extends Exception
{
    private ApiErrorCode $apiErrorCode = ApiErrorCode::EXPECTATION_FAILED;

    /**
     * Render the response.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return ResponseBuilder::asError($this->apiErrorCode->value)
                ->withHttpCode(Response::HTTP_EXPECTATION_FAILED)
                ->withMessage($this->getMessage() ?: $this->apiErrorCode->description())
                ->build();
        }
    }
}
