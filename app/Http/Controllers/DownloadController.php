<?php

namespace App\Http\Controllers;

use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    /**
     * Display a listing of downloads for admin panel
     */
    public function index()
    {
        $downloads = Download::with('uploader')
            ->orderBy('published_date', 'desc')
            ->paginate(20);

        return view('downloads.index', compact('downloads'));
    }

    /**
     * Show the form for creating a new download
     */
    public function create()
    {
        return view('downloads.create');
    }

    /**
     * Store a newly created download
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240', // 10MB max
            'published_date' => 'nullable|date',
        ]);

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            
            // Generate unique filename
            $filename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
            
            // Debug logging
            \Log::info('File upload attempt', [
                'original_name' => $originalName,
                'filename' => $filename,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);
            
            // Store file on public disk
            $path = $file->storeAs('downloads', $filename, 'public');
            
            \Log::info('File stored', ['path' => $path, 'disk' => 'public']);
            
            // Verify file exists on public disk
            if (!Storage::disk('public')->exists($path)) {
                \Log::error('File was not saved to storage', ['path' => $path, 'disk' => 'public']);
                return back()->with('error', 'File upload failed - file not saved to storage.');
            }
            
            // Create download record
            Download::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => 'downloads/' . $filename,
                'published_date' => $request->published_date ?? now(),
                'uploaded_by' => Auth::id(),
                'file_size' => $file->getSize(),
                'download_count' => 0,
            ]);

            return redirect()->route('downloads.index')
                ->with('success', 'File uploaded successfully!');
        }

        return back()->with('error', 'File upload failed.');
    }

    /**
     * Remove the specified download
     */
    public function destroy($id)
    {
        $download = Download::findOrFail($id);
        
        // Delete file from storage (public disk)
        if (Storage::disk('public')->exists($download->file_path)) {
            Storage::disk('public')->delete($download->file_path);
        }
        
        // Delete record
        $download->delete();

        return redirect()->route('downloads.index')
            ->with('success', 'File deleted successfully!');
    }

    /**
     * Public download endpoint
     */
    public function download($id)
    {
        $download = Download::findOrFail($id);
        
        // Increment download counter
        $download->incrementDownloadCount();
        
        // Get potential locations for the file
        $potentialPaths = [
            Storage::disk('public')->path($download->file_path),
            storage_path('app/public/' . $download->file_path),
            base_path('storage/app/public/' . $download->file_path),
            base_path('../public_html/storage/' . $download->file_path),
        ];
        
        $filePath = null;
        foreach ($potentialPaths as $path) {
            if (file_exists($path)) {
                $filePath = $path;
                break;
            }
        }
        
        if (!$filePath) {
            \Log::error("File not found for Download record #{$id}", [
                'expected_path' => $download->file_path,
                'checked_locations' => $potentialPaths
            ]);
            abort(404, 'File not found on server.');
        }
        
        return response()->download($filePath, $download->original_filename);
    }

    /**
     * Display downloads on public homepage
     */
    public function publicIndex()
    {
        $downloads = Download::orderBy('published_date', 'desc')
            ->limit(10)
            ->get();

        return view('home', compact('downloads'));
    }
}
