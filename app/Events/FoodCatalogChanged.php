<?php

namespace App\Events;

use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A food of the catalog was created, updated or retired in the application (US-4.2/AC-7).
 */
class FoodCatalogChanged
{
    use Dispatchable, SerializesModels;

    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const RETIRED = 'retired';

    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes  field of the CSV => [before, after].
     *                                                             Foods are not patient data: values are kept.
     */
    public function __construct(
        public Food $food,
        public User $actor,
        public string $action,
        public array $changes = [],
    ) {}
}
