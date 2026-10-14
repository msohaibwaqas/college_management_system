<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::with('category')->paginate(20);
        $categories = AssetCategory::all();
        return view('admin.assets.index', compact('assets', 'categories'));
    }

    public function categories()
    {
        $categories = AssetCategory::withCount('assets')->get();
        return view('admin.assets.categories', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|exists:asset_categories,id',
            'serial_number' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric',
            'condition' => 'required|in:new,good,damaged,disposed',
            'location' => 'nullable|string',
        ]);

        Asset::create($data);
        return back()->with('success', 'Asset recorded successfully.');
    }

    public function update(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|exists:asset_categories,id',
            'serial_number' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric',
            'condition' => 'required|in:new,good,damaged,disposed',
            'location' => 'nullable|string',
        ]);

        $asset->update($data);
        return back()->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return back()->with('success', 'Asset deleted successfully.');
    }
    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:asset_categories,name',
            'description' => 'nullable',
        ]);

        AssetCategory::create($data);
        return back()->with('success', 'Category added successfully.');
    }
}
