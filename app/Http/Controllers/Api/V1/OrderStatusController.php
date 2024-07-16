<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderStatusController extends BaseController
{
    public function __construct()
    {
        $this->middleware('permission:update order status')->only('updateStatus');
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,completed,cancelled',
        ]);

        $status = $request->input('status');
        $order = Order::with('orderItems')->find($id);

        if (!$order) {
            return $this->sendError('Order not found', [], 404);
        }

        if ($order->status === $status) {
            return $this->sendError('Order status is already ' . $status, [], 400);
        }

        if ($order->status !== 'pending' && $status === 'pending') {
            return $this->sendError('Cannot revert to pending status from ' . $order->status, [], 400);
        }

        DB::beginTransaction();
        try {
            if ($order->status === 'pending' && in_array($status, ['processing', 'shipped', 'completed'])) {
                foreach ($order->orderItems as $orderItem) {
                    $product = Product::find($orderItem->product_id);
                    if ($product) {
                        $product->quantity -= $orderItem->quantity;
                        if ($product->quantity < 0) {
                            return $this->sendError('Not enough stock for product ID: ' . $orderItem->product_id, [], 400);
                        }
                        $product->save();
                    }
                }
            }

            $order->status = $status;
            $order->save();
            DB::commit();

            return $this->sendResponse('Order status updated successfully', new OrderResource($order));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError('Failed to update order status: ' . $exception->getMessage(), [], 500);
        }
    }

    public function cancelledOrders()
    {
        $orders = Order::where('status', 'cancelled')->with('orderItems.product')->get();

        if ($orders->isEmpty()) {
            return $this->sendError('No cancelled orders found', [], 404);
        }

        return $this->sendResponse('Cancelled orders fetched successfully', OrderResource::collection($orders));
    }
}
