<?php

namespace Modules\CallCenterModule\Support;

use Illuminate\Http\JsonResponse;

trait RespondsWithCallCenterApi
{
    protected function ok(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    protected function created(mixed $data): JsonResponse
    {
        return response()->json($data, 201);
    }

    protected function notFound(string $code, string $message): JsonResponse
    {
        return $this->apiError($code, $message, 404);
    }

    protected function validationError(array $errors): JsonResponse
    {
        $first = collect($errors)->flatten()->first();

        return $this->apiError('validation_error', (string) ($first ?: 'Validation failed.'), 422, [
            'fields' => $errors,
        ]);
    }

    protected function apiError(string $code, string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $extra), $status);
    }

    protected function paginatedMeta(int $total, int $page, int $perPage): array
    {
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }
}
