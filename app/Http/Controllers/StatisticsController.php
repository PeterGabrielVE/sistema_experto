<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonthlyCountResource;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * JSON series for the dashboard charts. ?year=YYYY, defaults to the current year.
 */
class StatisticsController extends Controller
{
    public function __construct(private StatisticsService $statistics)
    {
    }

    public function diagnoses(Request $request): AnonymousResourceCollection
    {
        return $this->series(Diagnosis::class, $request);
    }

    public function patients(Request $request): AnonymousResourceCollection
    {
        return $this->series(Patient::class, $request);
    }

    public function users(Request $request): AnonymousResourceCollection
    {
        return $this->series(User::class, $request);
    }

    private function series(string $model, Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate(['year' => ['nullable', 'integer', 'between:2000,2100']]);
        $year = (int) ($validated['year'] ?? now()->year);

        return MonthlyCountResource::collection($this->statistics->monthlyCounts($model, $year))
            ->additional(['meta' => ['year' => $year]]);
    }
}
