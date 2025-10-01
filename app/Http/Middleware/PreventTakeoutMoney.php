<?php

namespace App\Http\Middleware;

use App\Models\cashier_takeout_money;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventTakeoutMoney
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $dealer_code = $request->dealer_code;
        $lastSubmission = cashier_takeout_money::where('dealer_code', $dealer_code)->latest()->first();
        if ($lastSubmission) {
            $date_published = Carbon::parse($lastSubmission->date_published);
            if ($date_published->isToday()) {
                $notification = array(
                    'message' => "Already Submitted Form Can't Submit Anymore",
                    'alert-type' => 'error'
                );
                return redirect()->back()->with($notification);
            }
        }
        return $next($request);
    }
}
