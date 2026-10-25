<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Author;
use App\Models\Genre;
use App\Models\BookLocation;
use App\Models\Student;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service managing library catalog, copy inventory, borrower lookups,
 * and book issue/return lifecycles.
 */
class LibraryService
{
    /**
     * Retrieves aggregated library dashboard statistics.
     */
    public function getDashboardMetrics(): array
    {
        $totalBooks = Book::count();
        $totalCopies = Book::sum('volumes');
        $issuedCount = BookIssue::where('status', 'issued')->count();
        $overdueCount = BookIssue::where('status', 'issued')->where('due_date', '<', now())->count();
        $availableCopies = $totalCopies - $issuedCount;
        $recentIssues = BookIssue::with('book')->latest()->limit(10)->get();
        $totalAuthors = Author::count();
        $totalGenres = Genre::count();

        return compact(
            'totalBooks',
            'totalCopies',
            'issuedCount',
            'overdueCount',
            'availableCopies',
            'recentIssues',
            'totalAuthors',
            'totalGenres'
        );
    }

    /**
     * Filters, searches, and paginates library books catalog.
     */
    public function searchBooks(Request $request, int $perPage = 25): LengthAwarePaginator
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

        return $query->withCount(['activeIssues'])->latest()->paginate($perPage)->withQueryString();
    }

    /**
     * Queries borrowers (either student or staff) for autocomplete/select lookup.
     */
    public function searchBorrowers(string $type, string $search): array
    {
        if ($type === 'student') {
            return Student::where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            })->limit(10)->get()->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->full_name,
                'detail' => $s->admission_no . ' — ' . $s->current_class,
            ])->toArray();
        }

        return Staff::where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('id', $search);
        })->limit(10)->get()->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->first_name . ' ' . ($s->last_name ?? ''),
            'detail' => $s->designation->name ?? 'Staff',
        ])->toArray();
    }

    /**
     * Verifies inventory availability and issues a book copy to a borrower.
     */
    public function issueBook(int $bookId, string $borrowerType, int $borrowerId, string $dueDate, ?string $remarks, int $issuedBy): array
    {
        $book = Book::withCount('activeIssues')->findOrFail($bookId);
        if ($book->active_issues_count >= $book->volumes) {
            return [
                'success' => false,
                'message' => 'No copies available for this book.'
            ];
        }

        $issue = BookIssue::create([
            'book_id' => $bookId,
            'issued_to_type' => $borrowerType,
            'issued_to_id' => $borrowerId,
            'issued_by' => $issuedBy,
            'issued_at' => now(),
            'due_date' => $dueDate,
            'remarks' => $remarks,
            'status' => 'issued',
        ]);

        return [
            'success' => true,
            'issue' => $issue
        ];
    }

    /**
     * Records return of an issued book and calculates/persists any overdue fine.
     */
    public function returnBook(BookIssue $issue, float $fineAmount, int $acceptedBy): void
    {
        $issue->update([
            'returned_at' => now(),
            'return_accepted_by' => $acceptedBy,
            'fine_amount' => $fineAmount,
            'status' => 'returned',
        ]);
    }

    /**
     * Filters issued books list.
     */
    public function searchIssuedBooks(Request $request, int $perPage = 25): LengthAwarePaginator
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

        return $query->latest('issued_at')->paginate($perPage)->withQueryString();
    }
}
