<?php

namespace App\Http\Controllers;

use App\Models\FeeHead;
use App\Models\SubjectGroup;
use Illuminate\Http\Request;

class FeeHeadController extends Controller
{
    public function index()
    {
        $feeHeads = FeeHead::all();
        return view('fee-heads.index', compact('feeHeads'));
    }

    public function create()
    {
        $subjectGroups = SubjectGroup::all();
        return view('fee-heads.create', compact('subjectGroups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_frequency' => 'required|in:one_time,yearly,semester,monthly',
            'is_refundable' => 'boolean',
            'refund_percentage' => 'nullable|numeric|min:0|max:100',
            'belongs_to_practical' => 'boolean',
            'is_computer_fund' => 'boolean',
            'program_type' => 'required|in:All,Inter,ADP,BS,DIT',
            'applicable_gender' => 'required|in:All,Male,Female',
            'applicable_group' => 'nullable|string|max:255',
        ]);

        $validated['is_refundable'] = $request->has('is_refundable');
        $validated['belongs_to_practical'] = $request->has('belongs_to_practical');
        $validated['is_computer_fund'] = $request->has('is_computer_fund');

        FeeHead::create($validated);

        return redirect()->route('fee-heads.index')->with('success', 'Fee Head created successfully.');
    }

    public function edit(FeeHead $feeHead)
    {
        $subjectGroups = SubjectGroup::all();
        return view('fee-heads.edit', compact('feeHead', 'subjectGroups'));
    }

    public function update(Request $request, FeeHead $feeHead)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_frequency' => 'required|in:one_time,yearly,semester,monthly',
            'is_refundable' => 'boolean',
            'refund_percentage' => 'nullable|numeric|min:0|max:100',
            'belongs_to_practical' => 'boolean',
            'is_computer_fund' => 'boolean',
            'program_type' => 'required|in:All,Inter,ADP,BS,DIT',
            'applicable_gender' => 'required|in:All,Male,Female',
        ]);

        $validated['is_refundable'] = $request->has('is_refundable');
        $validated['belongs_to_practical'] = $request->has('belongs_to_practical');
        $validated['is_computer_fund'] = $request->has('is_computer_fund');

        $feeHead->update($validated);

        return redirect()->route('fee-heads.index')->with('success', 'Fee Head updated successfully.');
    }

    public function destroy(FeeHead $feeHead)
    {
        $feeHead->delete();
        return redirect()->route('fee-heads.index')->with('success', 'Fee Head deleted successfully.');
    }
}
