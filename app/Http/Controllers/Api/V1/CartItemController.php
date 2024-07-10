<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class CartItemController extends BaseController
{
    
    public function index()
    {
        
        // Fetch cart items for the current user
        $cartItems = CartItem::where('user_id', auth()->user()->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return $this->sendError('Cart is empty');
        }

        return $this->sendResponse('Cart items fetched successfully', CartItemResource::collection($cartItems));
    }

    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $userId = auth()->user()->id;
            $productId = $validatedData['product_id'];
            $quantity = $validatedData['quantity'];

            // Check if the product already exists in the cart for the current user
            $existingCartItem = CartItem::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();

            if ($existingCartItem) {
                $existingCartItem->quantity += $quantity;
                $existingCartItem->subtotal += $quantity * $existingCartItem->product->price;
                $existingCartItem->save();

                DB::commit();
                return $this->sendResponse('Product quantity updated successfully', new CartItemResource($existingCartItem));
            }

            // Product does not exist in cart, create a new cart item
            $product = Product::find($productId);
            $subtotal = $product->price * $quantity;

            $cartItem = CartItem::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ]);

            DB::commit();
            return $this->sendResponse('Product added to cart successfully', new CartItemResource($cartItem));
        } catch (Exception $exeption) {
            DB::rollBack();
            $this->sendError($exeption);
        }
    }

    public function update(Request $request, int $id)
    {
        // Validate request
        $validatedData = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {

            $userId = auth()->user()->id;
            $quantity = $validatedData['quantity'];

            // Find the cart item belonging to the current user
            $cartItem = CartItem::where('user_id', $userId)->findOrFail($id);

            if ($quantity == 0) {
                $cartItem->delete();
                DB::commit();
                return $this->sendResponse('Cart item deleted successfully');
            }

            // Update the cart item quantity and subtotal
            $cartItem->quantity = $quantity;
            $cartItem->subtotal = $cartItem->product->price * $quantity;
            $cartItem->save();

            DB::commit();
            return $this->sendResponse('Cart item updated successfully', new CartItemResource($cartItem));
        } catch (Exception $exeption) {
            DB::rollBack();
            $this->sendError($exeption);
        }
    }

    public function destroy(int $id)
    {
        // Find cart item belonging to the current user
        $cartItem = CartItem::where('user_id', auth()->user()->id)->findOrFail($id);

        $cartItem->delete();

        return $this->sendResponse('Cart item removed successfully');
    }
}
