<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'image_name', 'image_path', 'brand', 'product_name','category', 'quantity', 'description', 'price',
    ];

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
