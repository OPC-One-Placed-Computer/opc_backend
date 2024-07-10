<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\OrderResource;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderController extends BaseController
{
    public function __construct()
    {
        $this->middleware('permission:update order status')->only('updateStatus');
    }

    public function index(Request $request)
    {
        $orders = Order::where('user_id', auth()->user()->id)->with('orderItems')->get();

        if ($orders->isEmpty()) {
            return $this->sendError('Order is empty', [], 404);
        }

        return $this->sendResponse('Orders fetched successfully', OrderResource::collection($orders));
    }

    public function placeOrder(Request $request)
    {

        $validatedData = $request->validate([
            'full_name' => 'required|string',
            'shipping_address' => 'required|string',
            'total' => 'required|numeric|min:0',
            'cart_items' => 'required|array',
            'cart_items.*.id' => 'required|exists:cart_items,id,user_id,' . auth()->user()->id,
        ]);

        $total = $validatedData['total'];
        $cartItemIds = collect($validatedData['cart_items'])->pluck('id');
        $cartItems = CartItem::whereIn('id', $cartItemIds)->where('user_id', auth()->user()->id)->get();

        if ($cartItems->isEmpty()) {
            return $this->sendError('Selected cart items not found or do not belong to the user', [], 400);
        }

        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'user_id' => auth()->user()->id,
                'full_name' => $validatedData['full_name'],
                'shipping_address' => $validatedData['shipping_address'],
                'total' => $total,
                'status' => 'pending', // Default status
            ]);

            // Create order items for selected cart items
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;

                // Ensure the product exists and fetch the price
                if ($product) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $cartItem->product_id,
                        'quantity' => $cartItem->quantity,
                        'subtotal' => $product->price * $cartItem->quantity,
                    ]);
                }

                $cartItem->delete();
            }

            // Eager load order items relationship with product details
            $order->load('orderItems.product');

            DB::commit();
            return $this->sendResponse('Order placed successfully', new OrderResource($order));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage());
        }
    }

    public function updateStatus(Request $request, int $id)
    {

        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $status = $request->input('status');

        $order = Order::find($id);

        if (!$order) {
            return $this->sendError('Order not found', [], 404);
        }

        if ($order->status === $status) {
            return $this->sendError('Order status is already ' . $status, [], 400);
        }

        $order->status = $status;

        DB::beginTransaction();
        try {
            $order->save();
            DB::commit();
            return $this->sendResponse('Order status updated successfully', new OrderResource($order));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError('Failed to update order status: ' . $exception->getMessage(), [], 500);
        }
    }

    public function cancelOrder(Request $request)
    {
        // Validate request data
        $request->validate([
            'order_id' => 'required|exists:orders,id,user_id,' . auth()->user()->id,
        ]);

        $orderId = $request->input('order_id');

        $order = Order::where('id', $orderId)->where('user_id', auth()->user()->id)->first();

        if (!$order) {
            return $this->sendError('Order not found or does not belong to the user', [], 404);
        }

        if ($order->status === 'cancelled') {
            return $this->sendError('Order is already cancelled', [], 400);
        }

        $order->status = 'cancelled';

        DB::beginTransaction();
        try {
            $order->save();
            DB::commit();
            return $this->sendResponse('Order cancelled successfully', new OrderResource($order));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError('Failed to cancel order: ' . $exception->getMessage(), [], 500);
        }
    }

    public function allOrders()
    {
        $orders = Order::with('orderItems.product')->get();

        if ($orders->isEmpty()) {
            return $this->sendError('No orders found', [], 404);
        }

        return $this->sendResponse('All orders fetched successfully', OrderResource::collection($orders));
    }
}
