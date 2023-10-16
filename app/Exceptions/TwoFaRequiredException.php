<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class TwoFaRequiredException extends Exception
{
    private ApiErrorCode $apiErrorCode = ApiErrorCode::TWO_FACTOR_AUTHENTICATION_REQUIRED;

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
                ->withHttpCode(Response::HTTP_UNAUTHORIZED)
                ->withMessage($this->getMessage() ?: $this->apiErrorCode->description())
                ->build();
        }
    }
}
