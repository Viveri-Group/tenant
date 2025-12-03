<?php

namespace App\Services\DMB_UK\Requests;

use App\Models\DMBUKRequestLog;
use App\Services\DMB_UK\DMBUK;
use App\Services\DMB_UK\DTO\DefaultParamsDTO;
use App\Services\DMB_UK\Response\DMBUKBadRequest;
use App\Services\DMB_UK\Response\DMBUKDependencyError;
use App\Services\DMB_UK\Response\DMBUKError;
use App\Services\DMB_UK\Response\DMBUKForbidden;
use App\Services\DMB_UK\Response\DMBUKInternalServerError;
use App\Services\DMB_UK\Response\DMBUKNotFoundError;
use App\Services\DMB_UK\Response\DMBUKTooManyRequests;
use App\Services\DMB_UK\Response\DMBUKUnauthorised;
use App\Services\DMB_UK\Response\DMBUKUnprocessable;
use App\Services\DMB_UK\Response\Response as DMBUKResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

abstract class BaseRequest
{
    abstract protected function getUri(): string;

    protected function getHeaders(): array
    {
        return [];
    }

    protected function getParams(): array
    {
        return [];
    }

    protected function getFile(): ?array
    {
        return null;
    }

    protected function getRequestUrl(): string
    {
        return app(DMBUK::class)->getBaseUrl() . Str::of($this->getUri())->ltrim('/');
    }

    protected function getDefaultConnectTimeout(): int
    {
        return 8;
    }

    protected function getDefaultTimeout(): int
    {
        return 40;
    }

    protected function getRetryTimes(): int
    {
        return 3;
    }

    protected function getHttpMethod(): string
    {
        return 'get';
    }

    protected function onError(DMBUKError $response)
    {
        return $response;
    }

    public function handle()
    {
        /** @var Response $response */
        $response = Http::acceptJson()
            ->withHeaders($this->getHeaders())
            ->connectTimeout($this->getDefaultTimeout())
            ->retry($this->getRetryTimes(), 2000, function ($exception) use (&$attempts) {
                if ($this->disableRetryAttempts()) {
                    return false;
                }

                if ($exception instanceof ConnectionException || Str::startsWith($exception->response->status(), 5)) {
                    return true;
                }

                return false;
            }, throw: false)
            ->{$this->getHttpMethod()}($this->getRequestUrl(), $this->getParams());

        if ($response->successful()) {
            $this->logRequest(DMBUKResponse::class, $response);

            return (new DMBUKResponse($response->json()))->recursive();
        }

        $error = $response->json()['message'] ?? ['Unknown Error'];

        $errorReport = (match ($response->status()) {
            SymfonyResponse::HTTP_FORBIDDEN => (new DMBUKForbidden($error)),
            SymfonyResponse::HTTP_FAILED_DEPENDENCY => (new DMBUKDependencyError($error)),
            SymfonyResponse::HTTP_BAD_REQUEST => (new DMBUKBadRequest($error)),
            SymfonyResponse::HTTP_UNAUTHORIZED => (new DMBUKUnauthorised($error)),
            SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR => (new DMBUKInternalServerError($error)),
            SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY => (new DMBUKUnprocessable($error)),
            SymfonyResponse::HTTP_TOO_MANY_REQUESTS => (new DMBUKTooManyRequests($error)),
            SymfonyResponse::HTTP_NOT_FOUND => (new DMBUKNotFoundError($error)),
            default => (new DMBUKError($error)),
        })->recursive();

        $this->logRequest($errorReport::class, $response);

        return $this->onError($errorReport);
    }

    private function logRequest(string $responseClassName, Response $response): void
    {
        DMBUKRequestLog::create([
            'sms_type' => $this->smsType,
            'request_type' => get_class($this),
            'request_headers' => $this->getHeaders(),
            'request_input' => (new DefaultParamsDTO($this->getParams()))->toArrayWithoutHiddenFields(),
            'response_data' => $response->json(),
            'status_code' => $response->getStatusCode(),
            'url' => $this->getRequestUrl(),
            'http_method' => Str::of($this->getHttpMethod())->upper(),
            'response_class' => $responseClassName,
        ]);
    }

    protected function disableRetryAttempts(): bool
    {
        return false;
    }
}
