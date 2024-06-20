<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends BaseController
{
    public function index()
    {
        return $this->sendResponse('Products successfully fetch', ProductResource::collection(Product::all()));
    }

    public function show(Product $product)
    {
        return $this->sendResponse('Product successfully fetch', new ProductResource($product), [
        ]);
     }
}
