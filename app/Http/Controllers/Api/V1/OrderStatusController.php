<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\OrderResource;
use App\Models\Order;
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
}
