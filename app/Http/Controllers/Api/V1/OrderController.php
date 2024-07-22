<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderResourceCollection;
use App\Models\Order;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderController extends BaseController
{
    public function index(Request $request)
    {

        try {
            $query = Order::where('user_id', auth()->user()->id)
                ->with('orderItems.product');

            $query->when($request->filled('status'), function ($q) use ($request) {
                if ($request->input('status') === 'active') {
                    return $q->whereNotIn('status', ['pending', 'completed', 'cancelled']);
                } else {
                    return $q->where('status', $request->input('status'));
                }
            });

            $query->when($request->filled('sort_order'), function ($q) use ($request) {
                return $q->orderBy('created_at', $request->input('sort_order'));
            }, function ($q) {
                return $q->orderBy('created_at', 'desc');
            });

            $perPage = $request->input('per_page', 25);
            $orders = $query->paginate($perPage);

            if ($orders->isEmpty()) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'No orders found matching the provided filters.',
                    'filters' => $request->all(),
                ], 404);
            }

            $orderCollection = new OrderResourceCollection($orders);

            return $this->sendResponse('Orders fetched successfully', $orderCollection);
        } catch (Exception $exception) {
            return $this->sendError('Failed to fetch orders', [], 500);
        }
    }

    public function show(int $orderId)
    {
        try {
            $order = Order::where('id', $orderId)->with('orderItems.product')->first();

            if (!$order) {
                return $this->sendError('Order not found', [], 404);
            }

            return $this->sendResponse('Order fetched successfully', new OrderResource($order));
        } catch (Exception $exception) {
            return $this->sendError('Failed to fetch order', [], 500);
        }
    }

    public function cancelOrder(Request $request)
    {
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
            return $this->sendResponse('Cancel order successfully', new OrderResource($order));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError('Failed to cancel order: ' . $exception->getMessage(), [], 500);
        }
    }
}
