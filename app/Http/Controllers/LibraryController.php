<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Author;
use App\Models\Genre;
use App\Models\BookLocation;
use App\Services\LibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller orchestrating library operations and book circulation.
 * Delegates catalog searching, issuance validation, and returns to LibraryService.
 */
class LibraryController extends Controller
{
    public function __construct(
        protected LibraryService $libraryService
    ) {}

    // ─── Dashboard ───
    public function index()
    {
        $metrics = $this->libraryService->getDashboardMetrics();
        return view('library.index', $metrics);
    }

    // ─── Books CRUD ───
    public function books(Request $request)
    {
        $books = $this->libraryService->searchBooks($request);
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
        $issues = $this->libraryService->searchIssuedBooks($request);
        return view('library.issued_books', compact('issues'));
    }

    public function issueBook()
    {
        $books = Book::with('activeIssues')->orderBy('title')->get()->filter(fn($b) => $b->is_available);
        return view('library.issue_book', compact('books'));
    }

    public function searchBorrower(Request $request)
    {
        $results = $this->libraryService->searchBorrowers($request->type ?? 'student', $request->search ?? '');
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

        $result = $this->libraryService->issueBook(
            (int) $request->book_id,
            $request->borrower_type,
            (int) $request->borrower_id,
            $request->due_date,
            $request->remarks,
            Auth::id()
        );

        if (!$result['success']) {
            return back()->withInput()->with('error', $result['message']);
        }

        return redirect()->route('library.issued_books')->with('success', 'Book issued successfully.');
    }

    public function returnBook(Request $request, BookIssue $issue)
    {
        $request->validate([
            'fine_amount' => 'nullable|numeric|min:0',
        ]);

        $this->libraryService->returnBook(
            $issue,
            (float) ($request->fine_amount ?? 0),
            Auth::id()
        );

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
        $request->validate(['name' => 'required|string|max:255', 'name_urdu' => 'nullable|string|max:255']);
        Genre::create($request->only('name', 'name_urdu'));
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
        $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        BookLocation::create($request->only('name', 'description'));
        return back()->with('success', 'Location added.');
    }

    public function destroyLocation(BookLocation $location)
    {
        $location->delete();
        return back()->with('success', 'Location deleted.');
    }
}
