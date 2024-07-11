<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductResourceCollection;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductsController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 25);
            $products = Product::paginate($perPage);

            $productCollection = new ProductResourceCollection($products);

            return $this->sendResponse('Products fetched successfully', $productCollection);
        } catch (Exception $exception) {
            Log::error('Error fetching products', ['exception' => $exception]);
            return $this->sendError('Failed to fetch products', [], 500);
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

//     public function featured()
// {
//     // Fetch featured products
//     $featuredProducts = Product::where('featured', 1)->get();

//     // Debugging: output the raw query and results
//     $debugInfo = [
//         'query' => Product::where('featured', 1)->toSql(),
//         'results' => $featuredProducts
//     ];

//     if ($featuredProducts->isEmpty()) {
//         return $this->sendError('No featured products found', $debugInfo);
//     }

//     return $this->sendResponse('Featured products fetched', [
//         'products' => ProductResource::collection($featuredProducts),
//         'debug' => $debugInfo
//     ]);
// }

    
    public function search(Request $request)
    {
        try {
            $query = strtolower($request->input('query'));

            if (empty($query)) {
                return $this->sendError('Keyword is required', [], 400);
            }

            $products = Product::search($query)->paginate(25);

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


}
