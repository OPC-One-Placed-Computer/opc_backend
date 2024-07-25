<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class CheckoutController extends Controller
{
    public function success()
    {
        return view('checkout.success');
    }

    public function cancel()
    {
        return view('checkout.cancel');
    }
}