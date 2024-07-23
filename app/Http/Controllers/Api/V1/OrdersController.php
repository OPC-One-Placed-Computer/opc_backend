<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\OrderResourceCollection;
use App\Models\Order;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class OrdersController extends BaseController
{
    public function allOrders(Request $request)
    {
        try {
            $query = Order::with('orderItems.product');

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
