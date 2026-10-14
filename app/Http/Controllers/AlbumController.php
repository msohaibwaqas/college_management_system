<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function index()
    {
        $albums = \App\Models\Album::orderBy('date', 'desc')->paginate(10);
        return view('admin.albums.index', compact('albums'));
    }

    public function create()
    {
        return view('admin.albums.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'date' => 'required|date',
            'status' => 'required|boolean',
        ]);

        \App\Models\Album::create($request->except('_token'));

        return redirect()->route('albums.index')->with('success', 'Album created successfully.');
    }

    public function show($id)
    {
        $album = \App\Models\Album::with('photos')->findOrFail($id);
        return view('admin.albums.show', compact('album'));
    }

    public function edit($id)
    {
        $album = \App\Models\Album::findOrFail($id);
        return view('admin.albums.edit', compact('album'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'date' => 'required|date',
            'status' => 'required|boolean',
        ]);

        $album = \App\Models\Album::findOrFail($id);
        $album->update($request->except(['_token', '_method']));

        return redirect()->route('albums.index')->with('success', 'Album updated successfully.');
    }

    public function destroy($id)
    {
        $album = \App\Models\Album::findOrFail($id);
        // Delete associated photos from storage would go here
        $album->delete();

        return redirect()->route('albums.index')->with('success', 'Album deleted successfully.');
    }
}
