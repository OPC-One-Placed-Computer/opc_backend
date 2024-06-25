<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends BaseController
{
    public function index()
    {
        return $this->sendResponse('Products successfully fetch', ProductResource::collection(Product::all()));
    }

     public function store(Request $request)
     {
        $validatedData = $request->validate([
            'product_name' => 'nullable|string',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'quantity' => 'nullable|integer',
            'category' => 'nullable|string',
            'brand' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle image upload
        $imagePath = $request->file('image')->store('public/images');
        $imageName = basename($imagePath);

        // Create new product
        $product = Product::create([
            'product_name' => $validatedData['product_name'],
            'price' => $validatedData['price'],
            'description' => $validatedData['description'],
            'quantity' => $validatedData['quantity'],
            'category' => $validatedData['category'],
            'brand' => $validatedData['brand'],
            'image_name' => $imageName,
            'image_path' => Storage::url($imagePath),
        ]);
        
        return $this->sendResponse('Product successfully added', new ProductResource($product));

     }

     public function show(Product $product)
     {
         return $this->sendResponse('Product successfully fetch', new ProductResource($product), [
         ]);
      }

     public function update(Request $request, Product $product)
     {
        $validatedData = $request->validate([
            'image_name' => 'nullable|string',
            'image_path' => 'nullable|string',
            'brand' => 'required|string',
            'product_name' => 'required|string',
            'category' => 'required|string',
            'quantity' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
        ]);

        $product->update($validatedData);

        return $this->sendResponse('Product updated successfully', new ProductResource($product));

     }

     public function destroy(Product $product)
     {
        $product->delete();

        return $this->sendResponse('Product deleted successfully');
     }
}
