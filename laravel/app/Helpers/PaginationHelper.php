<?php

namespace App\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaginationHelper
{
    /**
     * Paginate a standard Laravel Collection or array.
     *
     * @param array|Collection $items
     * @param int $perPage
     * @param int|null $page
     * @param string|null $path
     * @return LengthAwarePaginator
     */
    public static function paginate(array|Collection $items, int $perPage = 15, int $page = null, string $path = null): LengthAwarePaginator
    {
        $page = $page ?: (LengthAwarePaginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        $paginatedItems = $items->slice(($page - 1) * $perPage, $perPage)->values();
        $path = $path ?: request()->url();

        return new LengthAwarePaginator(
            $paginatedItems,
            $items->count(),
            $perPage,
            $page,
            ['path' => $path]
        );
    }
}
