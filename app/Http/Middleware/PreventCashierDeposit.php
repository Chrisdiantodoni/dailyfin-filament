<?php

namespace App\Http\Middleware;

use App\Models\CashierDeposit;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventCashierDeposit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $dealerCodes = $request->dealer_code;
        $existingSubmission = CashierDeposit::where('dealer_code', $dealerCodes)
            ->where('date_published', Carbon::today()->toDateString()) // Cek apakah sudah ada di hari ini
            ->exists(); // Cukup cek keberadaan data, tidak perlu fetch record penuh

        if ($existingSubmission) {
            return redirect()->back()->with([
                'message' => "Already Submitted Form Can't Submit Anymore",
                'alert-type' => 'error',
            ]);
        }

        return $next($request);
    }
}
