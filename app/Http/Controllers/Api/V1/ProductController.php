<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseController
{
    public function __construct()
    {
        $this->middleware('role:admin')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'product_name' => 'required|string',
            'price' => 'required|numeric',
            'description' => 'required|string',
            'quantity' => 'required|integer',
            'category' => 'required|string',
            'brand' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::beginTransaction();
        try {

            $imageName = null;
            $imagePath = null;

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $fileName = $this->normalizeFileName($image->getClientOriginalName());
                $imagePath = '/storage/product_images/' . $fileName;

                $image->storeAs('product_images', $fileName, 'public');

                $imageName = basename($imagePath);
            }

            // Create new product
            $product = Product::create([
                'product_name' => $validatedData['product_name'],
                'price' => $validatedData['price'],
                'description' => $validatedData['description'],
                'quantity' => $validatedData['quantity'],
                'category' => $validatedData['category'],
                'brand' => $validatedData['brand'],
                'image_name' => $imageName,
                'image_path' => $imagePath,
            ]);

            DB::commit();
            return $this->sendResponse('Product successfully added', new ProductResource($product));
        } catch (Exception $exeption) {
            DB::rollBack();
            $this->sendError($exeption);
        }
    }

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $validatedData = $request->validate([
            'product_name' => 'nullable|string',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'quantity' => 'nullable|integer',
            'category' => 'nullable|string',
            'brand' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::beginTransaction();
        try {

            $imageName = $product->image_name;
            $imagePath = $product->image_path;

            // Handle image update
            if ($request->hasFile('image')) {
                if ($product->image_path) {
                    Storage::disk('local')->delete($product->image_path);
                }

                $image = $request->file('image');
                $fileName = $this->normalizeFileName($image->getClientOriginalName());
                $imagePath = '/storage/product_images/' . $fileName;

                $image->storeAs('product_images', $fileName, 'public');

                $imageName = basename($imagePath);
            }

            // Update product
            $product->update([
                'product_name' => $validatedData['product_name'],
                'price' => $validatedData['price'],
                'description' => $validatedData['description'],
                'quantity' => $validatedData['quantity'],
                'category' => $validatedData['category'],
                'brand' => $validatedData['brand'],
                'image_name' => $imageName,
                'image_path' => $imagePath,
            ]);

            DB::commit();
            return $this->sendResponse('Product updated successfully', new ProductResource($product));
        } catch (Exception $exeption) {
            DB::rollBack();
            $this->sendError($exeption);
        }
    }
    
    public function destroy(int $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return $this->sendResponse('Product deleted successfully');
        } catch (Exception $exeption) {
            $this->sendError($exeption);
        }
    }
}
