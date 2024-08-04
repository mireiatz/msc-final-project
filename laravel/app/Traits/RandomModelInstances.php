<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

trait RandomModelInstances
{
    /**
     * Get a random instance of a model, creating one if none exist.
     *
     * @param string $modelClass
     * @return mixed
     */
    protected function getRandomModelInstance(string $modelClass): mixed
    {
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class {$modelClass} does not exist.");
        }

        return $modelClass::inRandomOrder()->first() ?? $modelClass::factory()->create();
    }

    /**
     * Get multiple random instances of a model, creating new ones if needed.
     *
     * @param string $modelClass
     * @param int $amount
     * @return Collection
     */
    protected function getRandomModelInstances(string $modelClass, int $amount = 1): Collection
    {
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class {$modelClass} does not exist.");
        }

        $instances = $modelClass::inRandomOrder()->take($amount)->get();

        if ($instances->count() < $amount) {
            $needed = $amount - $instances->count();
            $newInstances = $modelClass::factory()->count($needed)->create();
            $instances = $instances->merge($newInstances);
        }

        return $instances;
    }
}
