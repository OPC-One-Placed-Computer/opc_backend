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
            $query = Product::query();

            $query->when($request->filled('search'), function ($q) use ($request) {
                $searchKeyword = $request->input('search');
                return $q->where(function ($sq) use ($searchKeyword) {
                    $sq->where('product_name', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('description', 'like', '%' . $searchKeyword . '%');
                });
            });

            $query->when($request->filled('category'), function ($q) use ($request) {
                return $q->where('category', $request->input('category'));
            });

            $query->when($request->filled('brand'), function ($q) use ($request) {
                return $q->where('brand', $request->input('brand'));
            });

            $query->when($request->filled('featured'), function ($q) use ($request) {
                $isFeatured = $request->input('featured') == 'true' ? 1 : 0;
                return $q->where('featured', $isFeatured);
            });

            $minPrice = $request->input('min_price', 0);
            $maxPrice = $request->input('max_price', 999999);
            $query->whereBetween('price', [$minPrice, $maxPrice]);

            $perPage = $request->input('per_page', 25);
            $products = $query->paginate($perPage);

            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'No products found matching the provided filters.',
                    'filters' => $request->all(),
                ], 404);
            }

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
}
