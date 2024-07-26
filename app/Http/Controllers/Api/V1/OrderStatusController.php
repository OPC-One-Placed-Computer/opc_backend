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
            'status' => 'required',
        ]);

        $status = $request->input('status');
        $order = Order::with('orderItems')->find($id);

        if (!$order) {
            return $this->sendError('Order not found', [], 404);
        }

        if ($order->status === $status) {
            return $this->sendError('Order status is already ' . $status, [], 400);
        }

        if (($order->status === 'pending' || $order->status === 'paid') && $status === 'confirmed') {
            DB::beginTransaction();
            try {

                $quantityChanges = [];

                foreach ($order->orderItems as $orderItem) {
                    $product = Product::find($orderItem->product_id);
                    if ($product) {
                        $previousQuantity = $product->quantity;
                        $product->quantity -= $orderItem->quantity;
                        if ($product->quantity < 0) {
                            throw new Exception('Not enough stock for product ID: ' . $orderItem->product_id);
                        }
                        $product->save();

                        $quantityChanges[] = [
                            'product_id' => $product->id,
                            'previous_quantity' => $previousQuantity,
                            'new_quantity' => $product->quantity,
                        ];
                    }
                }

                $order->status = $status;
                $order->save();
                DB::commit();

                return $this->sendResponse('Order status updated to confirmed successfully', [
                    'order' => new OrderResource($order),
                    'quantity_changes' => $quantityChanges,
                ]);
            } catch (Exception $exception) {
                DB::rollBack();
                return $this->sendError('Failed to update order status to confirmed: ' . $exception->getMessage(), [], 500);
            }
        } else {
            $validStatuses = ['processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'];

            if (in_array($status, $validStatuses)) {
                $order->status = $status;
                $order->save();

                return $this->sendResponse('Order status updated successfully', [
                    'order' => new OrderResource($order),
                ]);
            }

            return $this->sendError('Invalid status transition', [], 400);
        }
    }
}
