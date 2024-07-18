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
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PaymentController extends BaseController
{
    public function placeOrder(Request $request)
    {
        $validatedData = $request->validate([
            'full_name' => 'required|string',
            'shipping_address' => 'required|string',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|in:stripe,cod',
            'cart_items' => 'required|array',
        ]);

        $user = auth()->user();
        $total = $validatedData['total'];
        $cartItemIds = collect($validatedData['cart_items']['id'])->toArray();
        // dd($cartItemIds);
        $cartItems = CartItem::whereIn('id', $cartItemIds)->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return $this->sendError('Selected cart items not found or do not belong to the user', [], 400);
        }

        if ($validatedData['payment_method'] === 'stripe') {
            try {

                $apiKey = config('cashier.secret');
                Stripe::setApiKey($apiKey);

                $lineItems = [];
                foreach ($cartItems as $cartItem) {
                    $product = $cartItem->product;
                    if ($product) {
                        $lineItems[] = [
                            'price_data' => [
                                'currency' => 'php',
                                'product_data' => [
                                    'name' => $product->product_name,
                                ],
                                'unit_amount' => $product->price * 100,
                            ],
                            'quantity' => $cartItem->quantity,
                        ];
                    }
                }

                $checkout_session = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => $lineItems,
                    'mode' => 'payment',
                    'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('checkout.cancel'),
                ]);

                return $this->sendResponse('Order placed successfully', [
                    'url' => $checkout_session->url,
                ]);
            } catch (Exception $exception) {
                return $this->sendError($exception->getMessage());
            }
        } else {
            DB::beginTransaction();
            try {
                $order = Order::create([
                    'user_id' => auth()->user()->id,
                    'full_name' => $validatedData['full_name'],
                    'shipping_address' => $validatedData['shipping_address'],
                    'total' => $total,
                    'status' => 'pending',
                    'payment_method' => $validatedData['payment_method'],
                ]);

                foreach ($cartItems as $cartItem) {
                    $product = $cartItem->product;

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

                $order->load('orderItems.product');

                DB::commit();
                return $this->sendResponse('Order placed successfully', new OrderResource($order));
            } catch (Exception $exception) {
                DB::rollBack();
                return $this->sendError($exception->getMessage());
            }
        }
    }

    public function checkoutSuccess(Request $request)
    {
        $session_id = $request->get('session_id');
        $user = auth()->user();
    
        if (!$user) {
            return $this->sendError('User not authenticated', [], 401);
        }
    
        $apiKey = config('cashier.secret');
        Stripe::setApiKey($apiKey);
        
        try {
            $checkout_session = Session::retrieve($session_id);
            $cartItems = CartItem::where('user_id', $user->id)->get();
            $totalAmount = $checkout_session->amount_total / 100;
    
            DB::beginTransaction();
            
            $order = Order::create([
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'shipping_address' => $user->shipping_address,
                'total' => $totalAmount,
                'status' => 'processing',
                'payment_method' => 'stripe',
            ]);
    
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
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
    
            DB::commit();
            return $this->sendResponse('Order placed successfully', new OrderResource($order));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage());
        }
    }    

    public function checkoutCancel()
    {
        return $this->sendError('Checkout canceled by the user', [], 400);
    }
}
