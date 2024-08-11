<?php

namespace App\Http\Responses;

use App\Helpers\PaginationHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse as Json;

class JsonResponse extends Json
{
    /**
     * Return a paginated JSON response.
     *
     * @param Collection $data
     * @return Json
     */
    public static function paginate(mixed $data): Json
    {
        $paginatedData = PaginationHelper::paginate($data);

        return self::success([
            'items' => $paginatedData->items(),
            'pagination' => [
                'count' => $paginatedData->count(),
                'total_items' => $paginatedData->total(),
                'items_per_page' => $paginatedData->perPage(),
                'current_page' => $paginatedData->currentPage(),
                'total_pages' => $paginatedData->lastPage(),
            ],
        ]);
    }

    /**
     * Return a standard JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @return Json
     */
    public static function success(mixed $data, int $status = self::HTTP_OK): Json
    {
        $response = [
            'data' => $data,
            'success' => true,
            'errors' => null,
        ];

        return new parent($response, $status);
    }
}
