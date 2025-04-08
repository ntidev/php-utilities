<?php

namespace ntidev\Utilities\Http;

use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;

class ApiResponse extends SymfonyJsonResponse
{
    protected function __construct(
        array $data,
        int $status = 200,
        array $headers = [],
        bool $json = false
    ) {
        parent::__construct($data, $status, $headers, $json);
    }

    public static function success(
        string $message,
        mixed $data,
        ?array $pagination = null,
        int $status = 200,
        array $headers = []
    ): self {
        $responseData = [
            'hasError' => false,
            'additionalErrors' => [],
            'message' => $message,
            'result' => [
                'data' => $data,
                'pagination' => $pagination ?? []
            ],
            'redirect' => ''
        ];

        return new self($responseData, $status, $headers);
    }

    public static function error(
        string $message,
        array $additionalErrors = [],
        int $status = 400,
        array $headers = []
    ): self {
        $responseData = [
            'hasError' => true,
            'additionalErrors' => $additionalErrors,
            'message' => $message,
            'result' => null,
            'redirect' => ''
        ];

        return new self($responseData, $status, $headers);
    }

    public static function redirect(
        string $message,
        string $redirectUrl,
        mixed $data = null,
        int $status = 302,
        array $headers = []
    ): self {
        $responseData = [
            'hasError' => false,
            'additionalErrors' => [],
            'message' => $message,
            'result' => $data ? [
                'data' => $data,
                'pagination' => []
            ] : null,
            'redirect' => $redirectUrl
        ];

        return new self($responseData, $status, $headers);
    }
} 