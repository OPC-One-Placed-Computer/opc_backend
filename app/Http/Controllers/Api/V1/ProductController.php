<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductResourceCollection;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends BaseController
{

    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 3);
            $products = Product::paginate($perPage);

            $productCollection = new ProductResourceCollection($products);

            return $this->sendResponse('Products fetched successfully', $productCollection);
        } catch (Exception $exception) {
            Log::error('Error fetching products', ['exception' => $exception]);
            return $this->sendError('Failed to fetch products', [], 500);
        }
    }

    // addtional pagination
    // $pagination = [
    //     'next_page_url' => $products->nextPageUrl(),
    //     'prev_page_url' => $products->previousPageUrl(),
    // ];

    //, ['pagination' => $pagination]



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

    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->sendError('Product not found');
        }

        return $this->sendResponse('Product successfully fetched', new ProductResource($product));
    }

    public function featured()
    {
        $featuredProducts = Product::where('featured', true)->get();

        if ($featuredProducts->isEmpty()) {
            return $this->sendError('No featured products found');
        }

        return $this->sendResponse('Feature products fetched', ProductResource::collection($featuredProducts));
    }
    public function search(Request $request)
    {
        try {
            $query = strtolower($request->input('query'));

            if (empty($query)) {
                return $this->sendError('Keyword is required', [], 400);
            }

            $products = Product::search($query)->paginate(3);

            if ($products->isEmpty()) {
                return $this->sendError('No products found', [], 404);
            }

            $productCollection = new ProductResourceCollection($products);

            return $this->sendResponse('Products fetched successfully', $productCollection);
        } catch (Exception $exception) {
            Log::error('Search error', ['exception' => $exception]);
            return $this->sendError('Failed to search products', [], 500);
        }
    }

    // public function search(Request $request)
    // {
    //     $keyword = strtolower($request->input('keyword'));

    //     if (empty($keyword)) {
    //         return $this->sendError('Keyword is required', [], 400);
    //     }

    //     try {
    //         $products = Product::search($keyword)->paginate(3);


    //         if ($products->isEmpty()) {
    //             return $this->sendError('No products found', [], 400);
    //         }

    //         $productCollection = new ProductResourceCollection($products);
    //         $productCollection->additional([
    //             'pagination' => [
    //                 'next_page_url' => $products->nextPageUrl(),
    //                 'prev_page_url' => $products->previousPageUrl(),
    //             ],
    //         ]);

    //         return $productCollection;
    //     } catch (Exception $exception) {
    //         Log::error('Search error:', ['exception' => $exception]);
    //         return $this->sendError($exception->getMessage());
    //     }
    // }


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
