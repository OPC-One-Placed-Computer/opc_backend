<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderResourceCollection;
use App\Models\Order;
use Illuminate\Http\Request;
use Exception;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\DB;

class OrderController extends BaseController
{
    public function index(Request $request)
    {

        try {
            $query = Order::where('user_id', auth()->user()->id)
                ->with('orderItems.product');

            $query->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->input('status');

                if ($status === 'active') {
                    return $q->whereIn('status', ['pending', 'paid']);
                } elseif ($status === 'inactive') {
                    return $q->whereIn('status', ['completed', 'cancelled', 'refunded']);
                } else {
                    return $q->where('status', $request->input('status'));
                }
            });

            $query->when($request->filled('sort_order'), function ($q) use ($request) {
                return $q->orderBy('created_at', $request->input('sort_order'));
            }, function ($q) {
                return $q->orderBy('created_at', 'desc');
            });

            $perPage = $request->input('per_page', 20);
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
            return $this->sendError($exception->getMessage(), [], 500);
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
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }

    public function cancelOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $orderId = $request->input('order_id');
        $order = Order::where('id', $orderId)->first();

        if (!$order) {
            return $this->sendError('Order not found', [], 404);
        }

        if ($order->status === 'cancelled') {
            return $this->sendError('Order is already cancelled', [], 400);
        }

        DB::beginTransaction();
        try {
            if ($order->payment_method === 'stripe') {
                $sessionId = $order->stripe_session_id;

                if (!$sessionId) {
                    return $this->sendError('Session ID is required', [], 400);
                }

                $apiKey = config('cashier.secret');
                Stripe::setApiKey($apiKey);

                $session = Session::retrieve($sessionId);

                if ($session->payment_status === 'paid') {
                    $refund = Refund::create([
                        'payment_intent' => $session->payment_intent,
                    ]);

                    $order->status = 'refunded';
                } else {
                    $order->status = 'cancelled';
                }
            } elseif ($order->payment_method === 'cod') {
                $order->status = 'cancelled';
            }
            $order->save();
            DB::commit();
            return $this->sendResponse('Order cancelled successfully', new OrderResource($order));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }
}
