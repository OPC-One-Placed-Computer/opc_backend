<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AnalyticsController extends BaseController
{
    public function salesReport(Request $request)
    {
        try {
            $query = Order::query();

            $status = $request->query('status');
            $year = $request->query('year');
            $month = $request->query('month');
            $day = $request->query('day');
            $type = $request->query('type', 'yearly');

            if ($status) {
                $validStatuses = ['pending', 'confirmed', 'awaiting_payment', 'paid', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'];
                if (in_array($status, $validStatuses)) {
                    $query->where('status', $status);
                }else {
                    return $this->sendError('Invalid status provided', [
                        'query' => [
                            'year' => $year,
                            'month' => $month,
                            'day' => $day,
                            'type' => $type,
                            'status' => $status
                        ]
                    ], 400);
                }
            }

            if ($year) {
                $query->whereYear('created_at', $year);
            }

            if ($month) {
                $query->whereMonth('created_at', $month);
            }

            if ($day) {
                $query->whereDay('created_at', $day);
            }

            switch ($type) {
                case 'daily':
                    $query->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total_sales'))
                        ->groupBy('date')
                        ->orderBy('date', 'desc');
                    $label = 'Daily Sales Report';
                    break;

                case 'weekly':
                    $query->select(DB::raw('YEARWEEK(created_at) as week'), DB::raw('SUM(total) as total_sales'))
                        ->groupBy('week')
                        ->orderBy('week', 'desc');
                    $label = 'Weekly Sales Report';
                    break;

                case 'monthly':
                    $query->select(DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total) as total_sales'))
                        ->groupBy('year', 'month')
                        ->orderBy('year', 'desc')
                        ->orderBy('month', 'desc');
                    $label = 'Monthly Sales Report';
                    break;

                case 'yearly':
                    $query->select(DB::raw('YEAR(created_at) as year'), DB::raw('SUM(total) as total_sales'))
                        ->groupBy('year')
                        ->orderBy('year', 'desc');
                    $label = 'Yearly Sales Report';
                    break;

                default:
                    $query->select(DB::raw('YEAR(created_at) as year'), DB::raw('SUM(total) as total_sales'))
                        ->groupBy('year')
                        ->orderBy('year', 'desc');
                    $label = 'Yearly Sales Report';
                    break;
            }

            $sales = $query->get();

            if ($sales->isEmpty()) {
                return $this->sendError(
                    'No data available for this report',
                    [
                        'query' => [
                            'year' => $year,
                            'month' => $month,
                            'day' => $day,
                            'type' => $type,
                            'status' => $status
                        ],
                    ]
                );
            }

            return $this->sendResponse($label, $sales);
        } catch (Exception $exception) {
            return $this->sendError('Failed to generate sales report', ['error' => $exception->getMessage()], 500);
        }
    }

    public function bestSellingProducts()
    {
        $products = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.product_name', DB::raw('SUM(order_items.quantity) as total_units_sold'), DB::raw('SUM(order_items.subtotal) as total_revenue'))
            ->groupBy('products.product_name')
            ->orderBy('total_units_sold', 'desc')
            ->get();

        return $this->sendResponse('Best Selling Products', $products);
    }

    public function orderStatistics()
    {
        $statuses = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'confirmed_orders' => Order::where('status', 'confirmed')->count(),
            'awaiting_payment_orders' => Order::where('status', 'awaiting_payment')->count(),
            'paid_orders' => Order::where('status', 'paid')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'refunded_orders' => Order::where('status', 'refunded')->count(),
        ];

        return $this->sendResponse('Order Statistics', $statuses);
    }

    public function revenueStatistics()
    {
        $totalRevenue = Order::sum('total');
        $averageOrderValue = Order::avg('total');
        $revenuePerCustomer = Order::select('user_id', DB::raw('SUM(total) as total_spent'))
            ->groupBy('user_id')
            ->pluck('total_spent')
            ->avg();

        $data = [
            'total_revenue' => $totalRevenue,
            'average_order_value' => $averageOrderValue,
            'revenue_per_customer' => $revenuePerCustomer,
        ];

        return $this->sendResponse('Revenue Analytics', $data);
    }

    public function customerAnalytics()
    {
        $newCustomers = User::whereDate('created_at', '>', Carbon::now()->subMonth())->count();
        $returningCustomers = Order::select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
        $customerLifetimeValue = Order::select('user_id', DB::raw('SUM(total) as total_spent'))
            ->groupBy('user_id')
            ->pluck('total_spent')
            ->avg();

        $data = [
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
            'customer_lifetime_value' => $customerLifetimeValue,
        ];

        return $this->sendResponse('Customer Analytics', $data);
    }


    public function productPerformance()
    {
        $inventoryLevels = Product::select('product_name', 'quantity')->get();
        $lowStockProducts = Product::where('quantity', '<', 10)->get();
        $outOfStockProducts = Product::where('quantity', '=', 0)->get();

        $data = [
            'inventory_levels' => $inventoryLevels,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
        ];

        return $this->sendResponse('Product Performance', $data);
    }

    public function paymentMethodsBreakdown()
    {
        $paymentMethods = Order::select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        return $this->sendResponse('Payment Methods Breakdown', $paymentMethods);
    }
}
