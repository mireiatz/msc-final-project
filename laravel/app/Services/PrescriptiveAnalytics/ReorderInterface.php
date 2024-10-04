<?php

namespace App\Services\PrescriptiveAnalytics;

use App\Models\Category;
use App\Models\Provider;

interface ReorderInterface
{
    /**
     * Get reorder suggestions for a provider and category.
     *
     * @param Provider $provider
     * @param Category $category
     * @return array
     */
    public function getReorderSuggestions(Provider $provider, Category $category): array;
}
