<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

use function App\Helpers\parseTemplate;

class DashboardController extends Controller
{
    public function index()
    {

        $all_months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];

        $transactions = Transaction::select(
            DB::raw("MONTHNAME(created_at) as month"),
            DB::raw("SUM(CASE WHEN type = 'increment' THEN amount ELSE 0 END) as increment_total"),
            DB::raw("SUM(CASE WHEN type = 'decrement' THEN amount ELSE 0 END) as decrement_total")
        )
            ->where('status', 'success')
            ->groupBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    strtolower($item->month) => [
                        'increment' => number_format($item->increment_total, 2),
                        'decrement' => number_format($item->decrement_total, 2)
                    ]
                ];
            });

        $formatted_data = collect($all_months)->mapWithKeys(function ($month) use ($transactions) {
            return [
                $month => $transactions->get($month, ['increment' => '0.00', 'decrement' => '0.00'])
            ];
        });

        file_put_contents(public_path('transactions/' . auth('web')->user()->slug . '.json'), json_encode($formatted_data));

        return view('backend.layouts.dashboard');
    }
}
