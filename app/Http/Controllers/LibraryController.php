<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Author;
use App\Models\Genre;
use App\Models\BookLocation;
use App\Models\Student;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LibraryController extends Controller
{
    // ─── Dashboard ───
    public function index()
    {
        $totalBooks = Book::count();
        $totalCopies = Book::sum('volumes');
        $issuedCount = BookIssue::where('status', 'issued')->count();
        $overdueCount = BookIssue::where('status', 'issued')->where('due_date', '<', now())->count();
        $availableCopies = $totalCopies - $issuedCount;
        $recentIssues = BookIssue::with('book')->latest()->limit(10)->get();
        $totalAuthors = Author::count();
        $totalGenres = Genre::count();

        return view('library.index', compact(
            'totalBooks', 'totalCopies', 'issuedCount', 'overdueCount',
            'availableCopies', 'recentIssues', 'totalAuthors', 'totalGenres'
        ));
    }

    // ─── Books CRUD ───
    public function books(Request $request)
    {
        $query = Book::with(['author', 'genre', 'location']);

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('title_urdu', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%")
                  ->orWhere('ref_no', 'like', "%{$search}%")
                  ->orWhere('acc_no', 'like', "%{$search}%")
                  ->orWhereHas('author', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->genre_id) $query->where('genre_id', $request->genre_id);
        if ($request->author_id) $query->where('author_id', $request->author_id);
        if ($request->language) $query->where('language', $request->language);

        $books = $query->withCount(['activeIssues'])->latest()->paginate(25)->withQueryString();
        $genres = Genre::orderBy('name')->get();
        $authors = Author::orderBy('name')->get();

        return view('library.books', compact('books', 'genres', 'authors'));
    }

    public function createBook()
    {
        $authors = Author::orderBy('name')->get();
        $genres = Genre::orderBy('name')->get();
        $locations = BookLocation::orderBy('name')->get();
        return view('library.create_book', compact('authors', 'genres', 'locations'));
    }

    public function storeBook(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'title_urdu' => 'nullable|string|max:255',
            'ref_no' => 'nullable|string|max:100',
            'language' => 'required|string|max:50',
            'author_id' => 'nullable|exists:authors,id',
            'book_type' => 'nullable|string|max:100',
            'genre_id' => 'nullable|exists:genres,id',
            'isbn' => 'nullable|string|max:20',
            'pages' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'acc_no' => 'nullable|string|max:100',
            'location_id' => 'nullable|exists:book_locations,id',
            'volumes' => 'required|integer|min:1',
            'publication_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'remarks' => 'nullable|string',
        ]);

        Book::create($request->only([
            'title', 'title_urdu', 'ref_no', 'language', 'author_id', 'book_type',
            'genre_id', 'isbn', 'pages', 'price', 'acc_no', 'location_id',
            'volumes', 'publication_year', 'remarks',
        ]));

        return redirect()->route('library.books')->with('success', 'Book added successfully.');
    }

    public function editBook(Book $book)
    {
        $authors = Author::orderBy('name')->get();
        $genres = Genre::orderBy('name')->get();
        $locations = BookLocation::orderBy('name')->get();
        return view('library.edit_book', compact('book', 'authors', 'genres', 'locations'));
    }

    public function updateBook(Request $request, Book $book)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'title_urdu' => 'nullable|string|max:255',
            'ref_no' => 'nullable|string|max:100',
            'language' => 'required|string|max:50',
            'author_id' => 'nullable|exists:authors,id',
            'book_type' => 'nullable|string|max:100',
            'genre_id' => 'nullable|exists:genres,id',
            'isbn' => 'nullable|string|max:20',
            'pages' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'acc_no' => 'nullable|string|max:100',
            'location_id' => 'nullable|exists:book_locations,id',
            'volumes' => 'required|integer|min:1',
            'publication_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'remarks' => 'nullable|string',
        ]);

        $book->update($request->only([
            'title', 'title_urdu', 'ref_no', 'language', 'author_id', 'book_type',
            'genre_id', 'isbn', 'pages', 'price', 'acc_no', 'location_id',
            'volumes', 'publication_year', 'remarks',
        ]));

        return redirect()->route('library.books')->with('success', 'Book updated successfully.');
    }

    public function destroyBook(Book $book)
    {
        $book->delete();
        return back()->with('success', 'Book deleted.');
    }

    // ─── Issue / Return ───
    public function issuedBooks(Request $request)
    {
        $query = BookIssue::with('book');

        if ($request->status === 'overdue') {
            $query->where('status', 'issued')->where('due_date', '<', now());
        } elseif ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->whereHas('book', fn($q) => $q->where('title', 'like', "%{$request->search}%"));
        }

        $issues = $query->latest('issued_at')->paginate(25)->withQueryString();
        return view('library.issued_books', compact('issues'));
    }

    public function issueBook()
    {
        $books = Book::with('activeIssues')->orderBy('title')->get()->filter(fn($b) => $b->is_available);
        return view('library.issue_book', compact('books'));
    }

    public function searchBorrower(Request $request)
    {
        $type = $request->type;
        $search = $request->search;

        if ($type === 'student') {
            $results = Student::where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            })->limit(10)->get()->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->full_name,
                'detail' => $s->admission_no . ' — ' . $s->current_class,
            ]);
        } else {
            $results = Staff::where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            })->limit(10)->get()->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->first_name . ' ' . ($s->last_name ?? ''),
                'detail' => $s->designation->name ?? 'Staff',
            ]);
        }

        return response()->json($results);
    }

    public function storeIssue(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'borrower_type' => 'required|in:student,staff',
            'borrower_id' => 'required|integer',
            'due_date' => 'required|date|after:today',
            'remarks' => 'nullable|string',
        ]);

        $book = Book::withCount('activeIssues')->findOrFail($request->book_id);
        if ($book->active_issues_count >= $book->volumes) {
            return back()->withInput()->with('error', 'No copies available for this book.');
        }

        BookIssue::create([
            'book_id' => $request->book_id,
            'issued_to_type' => $request->borrower_type,
            'issued_to_id' => $request->borrower_id,
            'issued_by' => Auth::id(),
            'issued_at' => now(),
            'due_date' => $request->due_date,
            'remarks' => $request->remarks,
            'status' => 'issued',
        ]);

        return redirect()->route('library.issued_books')->with('success', 'Book issued successfully.');
    }

    public function returnBook(Request $request, BookIssue $issue)
    {
        $request->validate([
            'fine_amount' => 'nullable|numeric|min:0',
        ]);

        $issue->update([
            'returned_at' => now(),
            'return_accepted_by' => Auth::id(),
            'fine_amount' => $request->fine_amount ?? 0,
            'status' => 'returned',
        ]);

        return back()->with('success', 'Book returned successfully.');
    }

    // ─── Authors CRUD ───
    public function authors()
    {
        $authors = Author::withCount('books')->orderBy('name')->paginate(50);
        return view('library.authors', compact('authors'));
    }

    public function storeAuthor(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'name_urdu' => 'nullable|string|max:255']);
        Author::create($request->only('name', 'name_urdu'));
        return back()->with('success', 'Author added.');
    }

    public function destroyAuthor(Author $author)
    {
        $author->delete();
        return back()->with('success', 'Author deleted.');
    }

    // ─── Genres CRUD ───
    public function genres()
    {
        $genres = Genre::withCount('books')->orderBy('name')->paginate(50);
        return view('library.genres', compact('genres'));
    }

    public function storeGenre(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        Genre::create($request->only('name'));
        return back()->with('success', 'Genre added.');
    }

    public function destroyGenre(Genre $genre)
    {
        $genre->delete();
        return back()->with('success', 'Genre deleted.');
    }

    // ─── Locations CRUD ───
    public function locations()
    {
        $locations = BookLocation::withCount('books')->orderBy('name')->paginate(50);
        return view('library.locations', compact('locations'));
    }

    public function storeLocation(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100', 'description' => 'nullable|string|max:255']);
        BookLocation::create($request->only('name', 'description'));
        return back()->with('success', 'Location added.');
    }

    public function destroyLocation(BookLocation $location)
    {
        $location->delete();
        return back()->with('success', 'Location deleted.');
    }

    // ─── Import / Export ───
    public function importExport()
    {
        return view('library.import_export');
    }

    public function exportBooks()
    {
        $books = Book::with(['author', 'genre', 'location'])->get();
        $filename = 'library_books_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($books) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Title', 'Title (Urdu)', 'Ref#', 'Language', 'Author', 'Book Type',
                'Genre', 'ISBN', 'Pages', 'Price', 'Acc#', 'Location',
                'Volumes/Copies', 'Publication Year', 'Remarks',
            ]);

            foreach ($books as $book) {
                fputcsv($file, [
                    $book->title, $book->title_urdu, $book->ref_no, $book->language,
                    $book->author->name ?? '', $book->book_type, $book->genre->name ?? '',
                    $book->isbn, $book->pages, $book->price, $book->acc_no,
                    $book->location->name ?? '', $book->volumes, $book->publication_year,
                    $book->remarks,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importBooks(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $header = array_shift($data); // Remove header row

        $imported = 0;
        foreach ($data as $row) {
            if (count($row) < 5) continue;

            // Find or create author
            $authorId = null;
            if (!empty($row[4])) {
                $author = Author::firstOrCreate(['name' => trim($row[4])]);
                $authorId = $author->id;
            }

            // Find or create genre
            $genreId = null;
            if (!empty($row[6])) {
                $genre = Genre::firstOrCreate(['name' => trim($row[6])]);
                $genreId = $genre->id;
            }

            // Find or create location
            $locationId = null;
            if (!empty($row[11])) {
                $location = BookLocation::firstOrCreate(['name' => trim($row[11])]);
                $locationId = $location->id;
            }

            Book::create([
                'title' => $row[0] ?? 'Untitled',
                'title_urdu' => $row[1] ?? null,
                'ref_no' => $row[2] ?? null,
                'language' => $row[3] ?? 'English',
                'author_id' => $authorId,
                'book_type' => $row[5] ?? null,
                'genre_id' => $genreId,
                'isbn' => $row[7] ?? null,
                'pages' => is_numeric($row[8] ?? null) ? (int)$row[8] : null,
                'price' => is_numeric($row[9] ?? null) ? (float)$row[9] : null,
                'acc_no' => $row[10] ?? null,
                'location_id' => $locationId,
                'volumes' => is_numeric($row[12] ?? null) ? (int)$row[12] : 1,
                'publication_year' => is_numeric($row[13] ?? null) ? (int)$row[13] : null,
                'remarks' => $row[14] ?? null,
            ]);
            $imported++;
        }

        return back()->with('success', "{$imported} books imported successfully.");
    }
}
