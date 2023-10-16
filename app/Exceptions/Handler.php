<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use PhpOffice\PhpSpreadsheet\Exception;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $isProduction = app()->isProduction();

        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) use ($isProduction) {
            if ($request->expectsJson()) {
                if ($e instanceof \Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery) {
                    return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
                        ->withHttpCode(Response::HTTP_NOT_ACCEPTABLE)
                        ->withMessage('Invalid query')
                        ->build();
                }

                if ($e instanceof \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig) {
                    return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
                        ->withHttpCode(Response::HTTP_REQUEST_ENTITY_TOO_LARGE)
                        ->withMessage(preg_replace('(`.*`\\s)', '', $e->getMessage()))
                        ->build();
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
                        ->withHttpCode($e->getStatusCode())
                        ->withMessage($e->getMessage() ?: 'Route not found')
                        ->build();
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
                        ->withHttpCode(Response::HTTP_UNAUTHORIZED)
                        ->withMessage($e->getMessage())
                        ->build();
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                    return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
                        ->withHttpCode(Response::HTTP_FORBIDDEN)
                        ->withMessage($e->getMessage())
                        ->build();
                }

                if ($e instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
                    return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
                        ->withHttpCode(Response::HTTP_UNAUTHORIZED)
                        ->withMessage('You are not permitted to perform this action.')
                        ->build();
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
                        ->withHttpCode($e->getStatusCode())
                        ->withMessage($e->getMessage())
                        ->build();
                }

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return ResponseBuilder::asError(ApiErrorCode::EXPECTATION_FAILED->value)
                        ->withHttpCode($e->status)
                        ->withMessage($e->getMessage())
                        ->withData([
                            'errors' => $e->errors(),
                        ])
                        ->build();
                }

                if ($e instanceof \League\Flysystem\UnableToWriteFile) {
                    return ResponseBuilder::asError(ApiErrorCode::EXPECTATION_FAILED->value)
                        ->withHttpCode(Response::HTTP_BAD_REQUEST)
                        ->withMessage($e->getMessage())
                        ->build();
                }

                if ($e instanceof \Yabacon\Paystack\Exception\ApiException) {
                    return ResponseBuilder::asError(100)
                        ->withHttpCode(Response::HTTP_BAD_REQUEST)
                        ->withMessage($e->getMessage())
                        ->build();
                }

                if ($e instanceof Exception) {
                    return ResponseBuilder::asError(100)
                        ->withHttpCode(Response::HTTP_BAD_REQUEST)
                        ->withMessage($e->getMessage())
                        ->build();
                }

                return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
                    ->withHttpCode(500)
                    ->withMessage($isProduction ? 'Server Error' : $e->getMessage())
                    ->withDebugData($this->convertExceptionToArray($e))
                    ->build();
            }
        });
    }
}
