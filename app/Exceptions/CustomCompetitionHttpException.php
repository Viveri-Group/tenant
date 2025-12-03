<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class CustomCompetitionHttpException extends HttpException
{
    public function __construct(
        int $statusCode,
        string $message,
        protected array $parameters = [],
        Throwable $previous = null
    ) {
        parent::__construct($statusCode, $message, $previous, [], $statusCode);
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $this->getMessage(),
            'details' => $this->parameters,
        ], $this->getStatusCode());
    }
}
