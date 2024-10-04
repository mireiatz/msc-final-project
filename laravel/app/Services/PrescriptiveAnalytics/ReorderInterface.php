<?php

namespace App\Services\PrescriptiveAnalytics;

use App\Models\Category;
use App\Models\Provider;

interface ReorderInterface
{
    public function getReorderSuggestions(Provider $provider, Category $category);
}
