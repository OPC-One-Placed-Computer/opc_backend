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
    public function __construct()
    {
        $this->middleware('role:user')->only(['index', 'store', 'update', 'destroy']);
    }

    public function index()
    {
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
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        $validatedData = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {

            $userId = auth()->user()->id;
            $quantity = $validatedData['quantity'];

            $cartItem = CartItem::where('user_id', $userId)->findOrFail($id);

            if ($quantity == 0) {
                $cartItem->delete();
                DB::commit();
                return $this->sendResponse('Cart item deleted successfully');
            }

            $cartItem->quantity = $quantity;
            $cartItem->subtotal = $cartItem->product->price * $quantity;
            $cartItem->save();

            DB::commit();
            return $this->sendResponse('Cart item updated successfully', new CartItemResource($cartItem));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }

    public function destroy(int $id)
    {
        $cartItem = CartItem::where('user_id', auth()->user()->id)->findOrFail($id);

        $cartItem->delete();

        return $this->sendResponse('Cart item removed successfully');
    }
}
