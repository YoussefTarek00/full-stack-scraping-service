<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'price', 'image_url'])]
class Product extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }
}
