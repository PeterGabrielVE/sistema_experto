<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One month of a dashboard series: {"month": 1, "label": "ENE", "count": 4}
 *
 * @property array{month: int, count: int} $resource
 */
class MonthlyCountResource extends JsonResource
{
    private const LABELS = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

    public function toArray(Request $request): array
    {
        return [
            'month' => $this->resource['month'],
            'label' => self::LABELS[$this->resource['month'] - 1],
            'count' => $this->resource['count'],
        ];
    }
}
