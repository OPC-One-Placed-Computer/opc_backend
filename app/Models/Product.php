<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, Searchable;
    protected $fillable = [
        'image_name',
        'image_path',
        'brand',
        'product_name',
        'featured',
        'category',
        'quantity',
        'description',
        'price',
    ];

    public function toSearchableArray()
    {
        $array = $this->only([
            'product_name',
            'brand',
            'category',
            'description'
        ]);
        
        $array = array_map('strtolower', $array);

        return $array;
    }

    /**
     * Product can have multiple order items associated
     * 
     * @return HasMany
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
