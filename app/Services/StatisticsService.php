<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Dashboard statistics.
 */
class StatisticsService
{
    /**
     * Records created per month of the given year, always 12 entries.
     *
     * @param  class-string<Model>  $model
     * @return Collection<int, array{month: int, count: int}>
     */
    public function monthlyCounts(string $model, int $year): Collection
    {
        // One query; grouped in PHP so it works on MySQL and SQLite alike.
        $perMonth = $model::query()
            ->whereYear('created_at', $year)
            ->pluck('created_at')
            ->countBy(fn ($date) => $date->month);

        return collect(range(1, 12))->map(fn (int $month) => [
            'month' => $month,
            'count' => $perMonth->get($month, 0),
        ]);
    }
}
