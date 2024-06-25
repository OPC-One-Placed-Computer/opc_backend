<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartItemController extends BaseController
{
    public function index()
    {
        // Fetch cart items for the current user
        $cartItems = CartItem::where('user_id', auth()->id())->with('product')->get();
        return $this->sendResponse('Cart items fetched successfully', CartItemResource::collection($cartItems));
    }

    public function store(Request $request)
    {
        // Validate request
        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Fetch product price
        $product = Product::find($validatedData['product_id']);
        $subtotal = $product->price * $validatedData['quantity'];

        // Create cart item
        $cartItem = CartItem::create([
            'user_id' => auth()->id(), // Get the authenticated user's ID
            'product_id' => $validatedData['product_id'],
            'quantity' => $validatedData['quantity'],
            'subtotal' => $subtotal,
        ]);

        return $this->sendResponse('Product added to cart successfully', new CartItemResource($cartItem));
    }

    public function destroy($id)
    {
        // Find cart item belonging to the current user
        $cartItem = CartItem::where('user_id', auth()->id())->findOrFail($id);
        
        // Delete cart item
        $cartItem->delete();
        
        return $this->sendResponse('Cart item removed successfully');
    }
}
