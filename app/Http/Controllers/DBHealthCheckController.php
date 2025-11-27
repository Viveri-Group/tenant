<?php

namespace App\Http\Controllers;

use App\Models\ActiveCall;
use Exception;
use Illuminate\Support\Facades\DB;

class DBHealthCheckController extends Controller
{
    public function __invoke()
    {
        try {
            DB::select('SELECT 1');

            return response()->json('databaseup');

        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }
}
