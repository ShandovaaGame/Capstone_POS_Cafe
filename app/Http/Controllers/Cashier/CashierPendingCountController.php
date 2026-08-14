<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Order;

class CashierPendingCountController extends Controller
{
    public function __invoke()
    {
        return response()->json(['count' => Order::cashierPendingCount()])
            ->header('Cache-Control', 'no-store');
    }
}
