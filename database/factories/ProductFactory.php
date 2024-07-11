<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    
    protected $model = Product::class;

    public function definition()
    {
       
        $categories = ['PC', 'Laptop'];
        $companies = ['ACER', 'LENOVO', 'ASUS', 'MSI'];

        // Generate image file
        $imageFileName = Str::random(10) . '.png';
        $imagePath = Storage::disk('local')->putFile('product_images', $this->faker->image());
        $imageName = basename($imagePath);

        return [
            'product_name' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 100, 100000),
            'description' => $this->faker->sentence,
            'quantity' => $this->faker->numberBetween(1, 50),
            'category' => $this->faker->randomElement($categories),
            'brand' => $this->faker->randomElement($companies),
            'image_name' => $imageName,
            'image_path' => 'product_images/' . $imageName,
        ];
    }
}
