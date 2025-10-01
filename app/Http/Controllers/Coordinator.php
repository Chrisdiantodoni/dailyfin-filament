<?php

namespace App\Http\Controllers;

use App\Models\CashierDepositMutate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Coordinator extends Controller
{
    public function index(Request $request)
    {
        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        $query = CashierDepositMutate::getReportQuery($start, $end);

        // kalau mau paginate
        $data = $query->paginate(20);

        // atau kalau mau semua
        // $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
