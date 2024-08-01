<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Models\Order;
use App\Models\Product;
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
            return $this->sendError($exception->getMessage(), [], 500);
        }
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
}
