<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function store(Request $request, $albumId)
    {
        $request->validate([
            'photos' => 'required',
            'photos.*' => 'image|max:2048', // Max 2MB per photo
        ]);

        $album = \App\Models\Album::findOrFail($albumId);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('albums/' . $album->id, 'public');
                
                \App\Models\Photo::create([
                    'album_id' => $album->id,
                    'path' => $path,
                    'title' => $photo->getClientOriginalName(),
                    'date' => $album->date,
                    'status' => 1,
                ]);
            }
        }

        return redirect()->route('albums.show', $album->id)->with('success', 'Photos uploaded successfully.');
    }

    public function destroy($id)
    {
        $photo = \App\Models\Photo::findOrFail($id);
        $albumId = $photo->album_id;

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($photo->path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->path);
        }

        $photo->delete();

        return redirect()->route('albums.show', $albumId)->with('success', 'Photo deleted successfully.');
    }
}
