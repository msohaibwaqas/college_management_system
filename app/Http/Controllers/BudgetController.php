<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Expenditure;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index()
    {
        $budgets = Budget::all();
        return view('admin.budgets.index', compact('budgets'));
    }

    public function expenditures($budget_id)
    {
        $budget = Budget::findOrFail($budget_id);
        $expenditures = $budget->expenditures()->orderBy('date', 'desc')->get();
        return view('admin.budgets.expenditures', compact('budget', 'expenditures'));
    }

    public function storeExpenditure(Request $request, $budget_id)
    {
        $data = $request->validate([
            'amount' => 'required|numeric',
            'date' => 'required|date',
            'description' => 'required',
        ]);

        $data['budget_id'] = $budget_id;
        Expenditure::create($data);
        return back()->with('success', 'Expenditure recorded successfully.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_name' => 'required',
            'allocated_amount' => 'required|numeric',
            'fiscal_year' => 'required',
        ]);

        Budget::create($data);
        return back()->with('success', 'Budget allocated successfully.');
    }
}
