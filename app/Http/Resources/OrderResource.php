<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'order_id' => $this->id,
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'shipping_address' => $this->shipping_address,
            'total' => $this->total,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'stripe_session_id' => $this->stripe_session_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}
