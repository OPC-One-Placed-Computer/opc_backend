<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\OrderResource;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends BaseController
{
    public function placeOrder(Request $request)
    {
        // Validate request
        $validatedData = $request->validate([
            'full_name' => 'required|string',
            'shipping_address' => 'required|string',
        ]);

        // Fetch cart items for the current user
        $cartItems = CartItem::where('user_id', auth()->id())->get();

        if ($cartItems->isEmpty()) {
            return $this->sendError('Cart is empty', [], 400);
        }

        // Calculate total price
        $total = 0;
        foreach ($cartItems as $cartItem) {
            $total += $cartItem->product->price * $cartItem->quantity;
        }

        // Create order
        $order = Order::create([
            'user_id' => auth()->id(),
            'full_name' => $validatedData['full_name'],
            'shipping_address' => $validatedData['shipping_address'],
            'total' => $total,
            'status' => 'pending', // Default status
        ]);

        // Create order items
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;

            // Ensure the product exists and fetch the price
            if ($product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    // 'price' => $product->price, // Store the product price
                    'subtotal' => $product->price * $cartItem->quantity,
                ]);
            }
        }

        // Clear cart items
        CartItem::where('user_id', auth()->id())->delete();

        // Eager load order items relationship with product details
        $order->load('orderItems.product');

        return $this->sendResponse('Order placed successfully', new OrderResource($order));
    }

    public function index(Request $request)
    {
        // Fetch orders for the current authenticated user
        $orders = Order::where('user_id', auth()->id())->get();

        return $this->sendResponse('Orders fetched successfully', OrderResource::collection($orders));
    }
}
