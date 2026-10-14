<?php

namespace App\Http\Controllers;

use App\Models\SalaryStructure;
use App\Models\SalaryComponent;
use Illuminate\Http\Request;

class SalaryStructureController extends Controller
{
    public function index()
    {
        $structures = SalaryStructure::withCount('components', 'staff')->get();
        return view('admin.payroll.structures.index', compact('structures'));
    }

    public function create()
    {
        return view('admin.payroll.structures.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_salary' => 'required|numeric|min:0',
        ]);

        $structure = SalaryStructure::create($validated);

        return redirect()->route('salary-structures.edit', $structure->id)
            ->with('success', 'Salary structure created. Now add components (Earnings/Deductions).');
    }

    public function edit($id)
    {
        $structure = SalaryStructure::with('components')->findOrFail($id);
        return view('admin.payroll.structures.edit', compact('structure'));
    }

    public function update(Request $request, $id)
    {
        $structure = SalaryStructure::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_salary' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $structure->update($validated);

        return redirect()->route('salary-structures.index')->with('success', 'Salary structure updated.');
    }

    public function addComponent(Request $request, $id)
    {
        $structure = SalaryStructure::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:earning,deduction',
            'amount_type' => 'required|in:fixed,percentage',
            'amount' => 'required|numeric|min:0'
        ]);

        $structure->components()->create($validated);

        return back()->with('success', 'Salary component added.');
    }

    public function removeComponent($id)
    {
        $component = SalaryComponent::findOrFail($id);
        $component->delete();
        return back()->with('success', 'Salary component removed.');
    }
}
