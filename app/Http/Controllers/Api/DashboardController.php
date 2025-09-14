<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentBill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getKpiData()
    {
        $monthlyIncome = Payment::whereMonth('payment_date', now()->month)
                                ->whereYear('payment_date', now()->year)
                                ->sum('amount_paid');
        $totalArrears = StudentBill::where('status', '!=', 'Lunas')->sum('remaining_amount');
        $activeStudents = Student::where('status', 'Aktif')->count();

        return response()->json([
            'monthly_income' => (float) $monthlyIncome,
            'total_arrears' => (float) $totalArrears,
            'active_students' => $activeStudents,
        ]);
    }

    public function getIncomeChartData(Request $request)
    {
        $months = $request->validate(['months' => 'sometimes|integer|min:1|max:24'])['months'] ?? 12;
        $incomeData = Payment::select(
            DB::raw('DATE_FORMAT(payment_date, "%Y-%m") as month'),
            DB::raw('SUM(amount_paid) as total')
        )
        ->where('payment_date', '>=', now()->subMonths($months))
        ->groupBy('month')->orderBy('month', 'asc')->get();

        return response()->json($incomeData);
    }

    public function getIncomeCompositionData()
    {
        return Payment::join('student_bills', 'payments.student_bill_id', '=', 'student_bills.id')
            ->join('cost_items', 'student_bills.cost_item_id', '=', 'cost_items.id')
            ->select('cost_items.name', DB::raw('SUM(payments.amount_paid) as total'))
            ->groupBy('cost_items.name')->get();
    }

    public function getTopArrears()
    {
        return StudentBill::with('student:id,name,enrollment_year')
            ->select('student_id', DB::raw('SUM(remaining_amount) as total_arrears'))
            ->where('status', '!=', 'Lunas')->whereNotNull('student_id')
            ->groupBy('student_id')->orderBy('total_arrears', 'desc')
            ->limit(5)->get();
    }

    public function getInventorySummary()
    {
        return InventoryItem::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->get();
    }
}
