<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SpecOptionType;
use Database\Factories\SpecOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'name', 'sort_order'])]
class SpecOption extends Model
{
    /** @use HasFactory<SpecOptionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SpecOptionType::class,
        ];
    }
}
