<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    
    protected $model = Product::class;

    public function definition()
    {
        return [
            'image_name' => $this->faker->word . '.jpg', // Example for generating random image names
            'image_path' => '/storage/images/' . $this->faker->image('public/storage/images', 400, 300, null, false),
            'brand' => $this->faker->company,
            'product_name' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 100, 1000),
        ];
    }
}
