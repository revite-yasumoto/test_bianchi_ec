<?php

declare(strict_types=1);

namespace App\Actions\Admin\Stock;

use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class BulkUpdateStocks
{
    /**
     * @param  array<int, array{id: int|string, quantity: int|string}>  $stocks
     */
    public function __invoke(array $stocks): void
    {
        DB::transaction(function () use ($stocks): void {
            foreach ($stocks as $stock) {
                Stock::query()
                    ->whereKey((int) $stock['id'])
                    ->update(['quantity' => (int) $stock['quantity']]);
            }
        });
    }
}
