<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class NotAllowedException extends Exception
{
    private ApiErrorCode $apiErrorCode = ApiErrorCode::NOT_ALLOWED;

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
                ->withHttpCode(Response::HTTP_NOT_ACCEPTABLE)
                ->withMessage($this->getMessage() ?: $this->apiErrorCode->description())
                ->build();
        }
    }

    /**
     * Report the exception.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function report(Request $request)
    {
        //
    }
}
