<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderResourceCollection;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
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
            return $this->sendResponse('Orders fetched successfully', new OrderResource($order));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError('Failed to cancel order: ' . $exception->getMessage(), [], 500);
        }
    }

    public function cancelledOrders()
    {
        $cancelledOrders = Order::where('user_id', auth()->user()->id)->where('status', 'cancelled')->with('orderItems.product')->get();

        if ($cancelledOrders->isEmpty()) {
            return $this->sendError('No cancelled orders found', [], 404);
        }

        return $this->sendResponse('Cancelled orders fetched successfully', OrderResource::collection($cancelledOrders));
    }

    public function allOrders(Request $request)
    {
        try {
            $query = Order::with('orderItems.product');

            $query->when($request->filled('status'), function ($q) use ($request) {
                if ($request->input('status') === 'active') {
                    return $q->whereNotIn('status', ['pending', 'completed', 'cancelled']);
                } else {
                    return $q->where('status', $request->input('status'));
                }
            });

            $query->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
                $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                return $q->whereBetween('created_at', [$startDate, $endDate]);
            });

            $query->when($request->filled('today'), function ($q) {
                return $q->whereDate('created_at', Carbon::today());
            });

            $query->when($request->filled('this_week'), function ($q) {
                return $q->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            });

            $query->when($request->filled('this_month'), function ($q) {
                return $q->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month);
            });

            $query->when($request->filled('year'), function ($q) use ($request) {
                return $q->whereYear('created_at', $request->input('year'));
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
}
