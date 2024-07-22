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
use Illuminate\Support\Facades\Log;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;

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
        $cartItems = CartItem::whereIn('id', $cartItemIds)->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return $this->sendError('Selected cart items not found or do not belong to the user', [], 400);
        }

        DB::beginTransaction();
        try {
            $orderStatus = $validatedData['payment_method'] === 'stripe' ? 'awaiting payment' : 'pending';

            $order = Order::create([
                'user_id' => $user->id,
                'full_name' => $validatedData['full_name'],
                'shipping_address' => $validatedData['shipping_address'],
                'total' => $total,
                'status' => $orderStatus,
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

            if ($validatedData['payment_method'] === 'stripe') {
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

                $checkoutSession = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => $lineItems,
                    'mode' => 'payment',
                    'success_url' => url('/api/v1/payment/success?session_id={CHECKOUT_SESSION_ID}'),
                    'cancel_url' => url('/api/v1/payment/cancel?session_id={CHECKOUT_SESSION_ID}'),
                    'metadata' => [
                        'order_id' => $order->id,
                    ],
                ]);

                $order->stripe_session_id = $checkoutSession->id;
                $order->save();

                DB::commit();

                return $this->sendResponse('Proceed to payment', [
                    'url' => $checkoutSession->url,
                ]);
            }

            DB::commit();
            $order->load('orderItems.product');

            return $this->sendResponse('Order placed successfully', new OrderResource($order));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage());
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $order = Order::where('stripe_session_id', $session->id)->first();
            if ($order) {
                $order->status = 'paid';
                $order->save();
            }
        } elseif ($event->type === 'checkout.session.expired') {
            $session = $event->data->object;

            $order = Order::where('stripe_session_id', $session->id)->first();
            if ($order) {
                $order->status = 'no payment received';
                $order->save();
            }
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function checkoutSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return $this->sendError('Session ID is required', [], 400);
        }

        $apiKey = config('cashier.secret');
        Stripe::setApiKey($apiKey);

        try {
            $session = Session::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return $this->sendError('Invalid or unpaid session', [], 400);
            }

            $order = Order::where('stripe_session_id', $session->id)->with('orderItems.product')->first();
            if (!$order) {
                return $this->sendError('Order not found', [], 404);
            }

            return $this->sendResponse('Order fetched successfully', new OrderResource($order));
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }


    public function checkoutCancel(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            Log::error('Checkout Cancel: Session ID is missing', ['request' => $request->all()]);
            return $this->sendError('Session ID is required', [], 400);
        }

        $apiKey = config('cashier.secret');
        Stripe::setApiKey($apiKey);

        try {
            $session = Session::retrieve($sessionId);

            $order = Order::where('stripe_session_id', $session->id)->with('orderItems.product')->first();
            if (!$order) {
                return $this->sendError('Order not found', [], 404);
            }

            if ($session->payment_status === 'paid') {
                $refund = Refund::create([
                    'payment_intent' => $session->payment_intent,
                ]);

                $order->status = 'refunded';
                $order->save();

                return $this->sendResponse('Payment refunded successfully', new OrderResource($order));
            } else {
                $order->status = 'cancelled';
                $order->save();

                return $this->sendResponse('Order cancelled successfully', new OrderResource($order));
            }
        } catch (Exception $exception) {
            Log::error('Checkout Cancel Error', ['exception' => $exception]);
            return $this->sendError($exception->getMessage());
        }
    }
}
