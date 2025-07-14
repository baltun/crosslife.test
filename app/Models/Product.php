<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock_quantity',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)
            ->using(ProductReservation::class)
            ->as('product_reservation')
            ->withPivot('id', 'price', 'stock_quantity', 'product_id', 'customer_id')
            ->withTimestamps();
    }
}
