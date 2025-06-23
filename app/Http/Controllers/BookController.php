<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadingSession;
use App\Models\DailyLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookController extends Controller
{
    /**
     * Display a listing of books.
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $category = $request->get('category');
        $search = $request->get('search');

        $query = Book::with(['readingSessions' => function ($q) {
            $q->orderBy('session_date', 'desc')->limit(3);
        }]);

        // Apply filters
        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        $books = $query->orderBy('priority', 'desc')
            ->orderBy('started_date', 'desc')
            ->paginate(12);

        // Get statistics
        $stats = [
            'total_books' => Book::count(),
            'currently_reading' => Book::currentlyReading()->count(),
            'completed_this_year' => Book::completed()
                ->whereYear('completed_date', now()->year)
                ->count(),
            'total_pages_read' => Book::completed()->sum('total_pages'),
            'avg_rating' => round(Book::completed()->whereNotNull('rating')->avg('rating'), 1),
        ];

        // Get categories for filter
        $categories = Book::select('category')
            ->distinct()
            ->pluck('category')
            ->map(function ($cat) {
                return [
                    'value' => $cat,
                    'label' => ucfirst(str_replace('_', ' ', $cat))
                ];
            });

        return view('books.index', compact('books', 'stats', 'categories', 'filter', 'category', 'search'));
    }

    /**
     * Show the form for creating a new book.
     */
    public function create()
    {
        return view('books.create');
    }

    /**
     * Store a newly created book.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'category' => 'required|in:business,technical,personal_development,leadership,strategy,biography,fiction,other',
            'total_pages' => 'required|integer|min:1',
            'priority' => 'required|integer|min:1|max:5',
            'cover_image_url' => 'nullable|url',
            'purchase_url' => 'nullable|url',
            'price' => 'nullable|numeric|min:0',
            'format' => 'required|in:physical,ebook,audiobook,pdf',
            'notes' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $data = $request->all();

        // Process tags
        if ($request->tags) {
            $data['tags'] = array_map('trim', explode(',', $request->tags));
        }

        $book = Book::create($data);

        return redirect()->route('books.show', $book)
            ->with('success', 'Book added successfully!');
    }

    /**
     * Display the specified book.
     */
    public function show(Book $book)
    {
        $book->load(['readingSessions' => function ($query) {
            $query->orderBy('session_date', 'desc');
        }]);

        // Get reading statistics
        $stats = [
            'total_sessions' => $book->readingSessions->count(),
            'total_reading_time' => $book->total_reading_time,
            'average_session_duration' => $book->average_session_duration,
            'reading_speed' => $book->getAverageReadingSpeed(),
            'current_streak' => $book->getCurrentReadingStreak(),
            'days_since_started' => $book->days_since_started,
            'estimated_completion' => $book->estimated_completion_date,
        ];

        // Get recent reading sessions
        $recentSessions = $book->readingSessions()
            ->orderBy('session_date', 'desc')
            ->limit(10)
            ->get();

        // Get reading progress chart data (last 30 days)
        $progressData = $this->getReadingProgressData($book, 30);

        // Get insights
        $insights = $this->getBookInsights($book);

        return view('books.show', compact('book', 'stats', 'recentSessions', 'progressData', 'insights'));
    }

    /**
     * Show the form for editing the specified book.
     */
    public function edit(Book $book)
    {
        return view('books.edit', compact('book'));
    }

    /**
     * Update the specified book.
     */
    public function update(Request $request, Book $book)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'category' => 'required|in:business,technical,personal_development,leadership,strategy,biography,fiction,other',
            'total_pages' => 'required|integer|min:1',
            'current_page' => 'required|integer|min:0|max:' . $request->total_pages,
            'status' => 'required|in:want_to_read,currently_reading,completed,paused,abandoned',
            'priority' => 'required|integer|min:1|max:5',
            'rating' => 'nullable|integer|min:1|max:10',
            'review' => 'nullable|string',
            'cover_image_url' => 'nullable|url',
            'purchase_url' => 'nullable|url',
            'price' => 'nullable|numeric|min:0',
            'format' => 'required|in:physical,ebook,audiobook,pdf',
            'notes' => 'nullable|string',
            'tags' => 'nullable|string',
            'key_insights' => 'nullable|string',
            'action_items' => 'nullable|string',
        ]);

        $data = $request->all();

        // Process arrays
        if ($request->tags) {
            $data['tags'] = array_map('trim', explode(',', $request->tags));
        }

        if ($request->key_insights) {
            $data['key_insights'] = array_map('trim', explode("\n", $request->key_insights));
        }

        if ($request->action_items) {
            $data['action_items'] = array_map('trim', explode("\n", $request->action_items));
        }

        // Handle status changes
        if ($request->status === 'currently_reading' && $book->status !== 'currently_reading') {
            $data['started_date'] = today();
        }

        if ($request->status === 'completed' && $book->status !== 'completed') {
            $data['completed_date'] = today();
            $data['current_page'] = $request->total_pages;
        }

        $book->update($data);

        return redirect()->route('books.show', $book)
            ->with('success', 'Book updated successfully!');
    }

    /**
     * Remove the specified book.
     */
    public function destroy(Book $book)
    {
        $book->delete();

        return redirect()->route('books.index')
            ->with('success', 'Book deleted successfully!');
    }

    /**
     * Add a reading session.
     */
    public function addSession(Request $request, Book $book)
    {
        $request->validate([
            'session_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'pages_read' => 'required|integer|min:0',
            'start_page' => 'required|integer|min:1',
            'end_page' => 'required|integer|min:' . $request->start_page,
            'session_type' => 'required|in:scheduled,bonus,catchup,review',
            'location' => 'required|in:office,home,commute,cafe,other',
            'focus_level' => 'nullable|integer|min:1|max:10',
            'comprehension_level' => 'nullable|integer|min:1|max:10',
            'enjoyment_level' => 'nullable|integer|min:1|max:10',
            'session_notes' => 'nullable|string',
            'key_insights' => 'nullable|string',
            'quotes' => 'nullable|string',
        ]);

        // Calculate duration
        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);
        $duration = $startTime->diffInMinutes($endTime);

        // Get or create daily log for the session date
        $dailyLog = DailyLog::getOrCreateForDate($request->session_date);

        $sessionData = $request->all();
        $sessionData['book_id'] = $book->id;
        $sessionData['daily_log_id'] = $dailyLog->id;
        $sessionData['duration_minutes'] = $duration;

        $session = ReadingSession::create($sessionData);

        // Update book progress
        if ($request->end_page > $book->current_page) {
            $book->updateProgress($request->end_page);
        }

        // Start reading if this is the first session
        if ($book->status === 'want_to_read') {
            $book->startReading();
        }

        return response()->json([
            'success' => true,
            'session' => $session,
            'book' => $book->fresh(),
            'message' => 'Reading session added successfully!'
        ]);
    }

    /**
     * Update reading progress.
     */
    public function updateProgress(Request $request, Book $book)
    {
        $request->validate([
            'current_page' => 'required|integer|min:0|max:' . $book->total_pages,
        ]);

        $book->updateProgress($request->current_page);

        return response()->json([
            'success' => true,
            'book' => $book->fresh(),
            'progress_percentage' => $book->progress_percentage,
        ]);
    }

    /**
     * Start reading a book.
     */
    public function startReading(Book $book)
    {
        $book->startReading();

        return response()->json([
            'success' => true,
            'book' => $book->fresh(),
            'message' => 'Started reading "' . $book->title . '"!'
        ]);
    }

    /**
     * Mark book as completed.
     */
    public function markCompleted(Request $request, Book $book)
    {
        $request->validate([
            'rating' => 'nullable|integer|min:1|max:10',
            'review' => 'nullable|string',
            'key_insights' => 'nullable|string',
            'action_items' => 'nullable|string',
        ]);

        $insights = $request->key_insights ?
            array_map('trim', explode("\n", $request->key_insights)) : null;

        $actions = $request->action_items ?
            array_map('trim', explode("\n", $request->action_items)) : null;

        $book->update([
            'status' => 'completed',
            'completed_date' => today(),
            'current_page' => $book->total_pages,
            'rating' => $request->rating,
            'review' => $request->review,
            'key_insights' => $insights,
            'action_items' => $actions,
        ]);

        return response()->json([
            'success' => true,
            'book' => $book->fresh(),
            'message' => 'Congratulations on completing "' . $book->title . '"!'
        ]);
    }

    /**
     * Get reading statistics for dashboard widget.
     */
    public function getReadingStats()
    {
        $currentBooks = Book::currentlyReading()->get();
        $todayReading = ReadingSession::today()->sum('duration_minutes');
        $weeklyReading = ReadingSession::thisWeek()->sum('duration_minutes');
        $monthlyBooks = Book::completed()
            ->whereMonth('completed_date', now()->month)
            ->count();

        return response()->json([
            'current_books' => $currentBooks->count(),
            'today_reading_time' => $todayReading,
            'weekly_reading_time' => $weeklyReading,
            'monthly_books_completed' => $monthlyBooks,
            'books' => $currentBooks->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'progress' => $book->progress_percentage,
                ];
            }),
        ]);
    }

    /**
     * Quick log today's CEO reading session.
     */
    public function logTodaySession(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'pages_read' => 'required|integer|min:1',
            'quality_rating' => 'required|integer|min:1|max:10',
            'notes' => 'nullable|string|max:500',
            'insights' => 'nullable|string|max:500',
        ]);

        $book = Book::find($request->book_id);
        $dailyLog = DailyLog::getOrCreateToday();

        // Check if session already exists for today
        $existingSession = ReadingSession::where('book_id', $book->id)
            ->where('session_date', today())
            ->first();

        if ($existingSession) {
            return response()->json([
                'success' => false,
                'message' => 'Reading session already logged for today!'
            ], 422);
        }

        // Create the 30-minute CEO reading session (2:00-2:30 PM)
        $session = ReadingSession::create([
            'book_id' => $book->id,
            'daily_log_id' => $dailyLog->id,
            'session_date' => today(),
            'start_time' => '14:00',
            'end_time' => '14:30',
            'duration_minutes' => 30,
            'pages_read' => $request->pages_read,
            'start_page' => $book->current_page + 1,
            'end_page' => $book->current_page + $request->pages_read,
            'session_type' => 'scheduled',
            'location' => 'office',
            'focus_level' => $request->quality_rating,
            'comprehension_level' => $request->quality_rating,
            'enjoyment_level' => $request->quality_rating,
            'session_notes' => $request->notes,
            'key_insights' => $request->insights,
        ]);

        // Update book progress
        $book->updateProgress($book->current_page + $request->pages_read);

        return response()->json([
            'success' => true,
            'session' => $session,
            'book' => $book->fresh(),
            'message' => 'Today\'s reading session logged successfully!'
        ]);
    }

    /**
     * Get reading progress data for charts.
     */
    private function getReadingProgressData(Book $book, $days = 30)
    {
        $sessions = $book->readingSessions()
            ->where('session_date', '>=', now()->subDays($days))
            ->orderBy('session_date')
            ->get();

        $data = [];
        $cumulativePages = $book->current_page - $book->total_pages_read;

        foreach ($sessions as $session) {
            $cumulativePages += $session->pages_read;
            $data[] = [
                'date' => $session->session_date->format('M j'),
                'pages_read' => $session->pages_read,
                'cumulative_pages' => $cumulativePages,
                'duration' => $session->duration_minutes,
                'quality' => $session->quality_score,
            ];
        }

        return $data;
    }

    /**
     * Get book insights and recommendations.
     */
    private function getBookInsights(Book $book)
    {
        $sessions = $book->readingSessions;

        if ($sessions->isEmpty()) {
            return ['message' => 'Start reading to see insights!'];
        }

        $insights = [];

        // Reading pace insights
        $avgPagesPerSession = $sessions->avg('pages_read');
        $avgSessionDuration = $sessions->avg('duration_minutes');

        if ($avgPagesPerSession > 15) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Great Reading Pace!',
                'message' => "You're reading an average of " . round($avgPagesPerSession, 1) . " pages per session."
            ];
        }

        // Consistency insights
        $recentSessions = $sessions->where('session_date', '>=', now()->subDays(7))->count();
        if ($recentSessions >= 5) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Excellent Consistency!',
                'message' => "You've had {$recentSessions} reading sessions this week."
            ];
        }

        // Quality insights
        $avgQuality = $sessions->where('focus_level', '>', 0)->avg('focus_level');
        if ($avgQuality >= 8) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'High Quality Sessions',
                'message' => "Your average focus level is " . round($avgQuality, 1) . "/10."
            ];
        }

        // Progress insights
        if ($book->progress_percentage >= 50) {
            $insights[] = [
                'type' => 'milestone',
                'title' => 'Halfway There!',
                'message' => "You've completed {$book->progress_percentage}% of this book."
            ];
        }

        return $insights;
    }
}