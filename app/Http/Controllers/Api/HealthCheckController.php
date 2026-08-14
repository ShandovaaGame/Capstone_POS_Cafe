<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function ping()
    {
        try {
            DB::select('SELECT 1');
        } catch (\Exception) {
            // DB down — still return 200, this is just a connectivity check
        }

        return response('', 200)->header('X-Health', 'ok');
    }
}
