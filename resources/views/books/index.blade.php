@extends('layouts.app')

@section('title', 'Reading Library - CEO Routine Tracker')

@section('content')
    <div class="books-page">
        <!-- Page Header -->
        <div class="page-header mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-book"></i>
                        Reading Library
                    </h1>
                    <p class="text-muted">Manage your reading collection and track progress</p>
                </div>

                <a href="{{ route('books.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Book
                </a>
            </div>
        </div>

        <!-- Reading Statistics -->
        <div class="grid grid-4 mb-6">
            <div class="stat-card">
                <div class="stat-number">{{ $stats['total_books'] }}</div>
                <div class="stat-label">Total Books</div>
            </div>

            <div class="stat-card info">
                <div class="stat-number">{{ $stats['currently_reading'] }}</div>
                <div class="stat-label">Currently Reading</div>
            </div>

            <div class="stat-card success">
                <div class="stat-number">{{ $stats['completed_this_year'] }}</div>
                <div class="stat-label">Completed This Year</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-number">{{ number_format($stats['total_pages_read']) }}</div>
                <div class="stat-label">Pages Read</div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="card mb-6">
            <div class="card-content p-4">
                <form method="GET" class="filters-form">
                    <div class="grid grid-4 gap-4">
                        <div>
                            <label class="form-label">Filter by Status</label>
                            <select name="filter" class="form-select" onchange="this.form.submit()">
                                <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All Books</option>
                                <option value="want_to_read" {{ $filter === 'want_to_read' ? 'selected' : '' }}>Want to Read
                                </option>
                                <option value="currently_reading" {{ $filter === 'currently_reading' ? 'selected' : '' }}>
                                    Currently Reading</option>
                                <option value="completed" {{ $filter === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="paused" {{ $filter === 'paused' ? 'selected' : '' }}>Paused</option>
                                <option value="abandoned" {{ $filter === 'abandoned' ? 'selected' : '' }}>Abandoned
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat['value'] }}"
                                        {{ $category === $cat['value'] ? 'selected' : '' }}>
                                        {{ $cat['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Search</label>
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="Search books or authors..." class="form-input">
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            @if ($search || $category || $filter !== 'all')
                                <a href="{{ route('books.index') }}" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Books Grid -->
        @if ($books->count() > 0)
            <div class="books-grid">
                @foreach ($books as $book)
                    <div class="book-card">
                        <div class="book-cover">
                            @if ($book->cover_image_url)
                                <img src="{{ $book->cover_image_url }}" alt="{{ $book->title }}" class="cover-image">
                            @else
                                <div class="cover-placeholder">
                                    <i class="fas fa-book"></i>
                                </div>
                            @endif

                            <div class="book-status">
                                <span class="status-badge status-{{ $book->status }}"
                                    style="background-color: {{ $book->status_color }};">
                                    {{ $book->status_label }}
                                </span>
                            </div>

                            @if ($book->status === 'currently_reading' && $book->progress_percentage > 0)
                                <div class="book-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: {{ $book->progress_percentage }}%"></div>
                                    </div>
                                    <span class="progress-text">{{ $book->progress_percentage }}%</span>
                                </div>
                            @endif
                        </div>

                        <div class="book-info">
                            <h3 class="book-title">
                                <a href="{{ route('books.show', $book) }}">{{ $book->title }}</a>
                            </h3>
                            <p class="book-author">by {{ $book->author }}</p>

                            <div class="book-meta">
                                <span class="book-category">{{ $book->category_label }}</span>
                                <span class="book-pages">{{ number_format($book->total_pages) }} pages</span>
                                @if ($book->rating)
                                    <span class="book-rating">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i
                                                class="fas fa-star {{ $i <= $book->rating / 2 ? 'text-warning' : 'text-muted' }}"></i>
                                        @endfor
                                        {{ $book->rating }}/10
                                    </span>
                                @endif
                            </div>

                            @if ($book->status === 'currently_reading')
                                <div class="reading-stats">
                                    <div class="reading-stat">
                                        <i class="fas fa-bookmark"></i>
                                        Page {{ number_format($book->current_page) }} of
                                        {{ number_format($book->total_pages) }}
                                    </div>

                                    @if ($book->readingSessions->isNotEmpty())
                                        <div class="reading-stat">
                                            <i class="fas fa-clock"></i>
                                            Last read {{ $book->readingSessions->first()->session_date->diffForHumans() }}
                                        </div>
                                    @endif

                                    @if ($book->getCurrentReadingStreak() > 0)
                                        <div class="reading-stat">
                                            <i class="fas fa-fire text-warning"></i>
                                            {{ $book->getCurrentReadingStreak() }} day streak
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if ($book->status === 'completed' && $book->completed_date)
                                <div class="completion-info">
                                    <i class="fas fa-check-circle text-success"></i>
                                    Completed {{ $book->completed_date->format('M j, Y') }}
                                </div>
                            @endif

                            <div class="book-actions">
                                @if ($book->status === 'want_to_read')
                                    <button onclick="startReading({{ $book->id }})" class="btn btn-primary btn-sm">
                                        <i class="fas fa-play"></i> Start Reading
                                    </button>
                                @elseif($book->status === 'currently_reading')
                                    <a href="{{ route('books.show', $book) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-book-open"></i> Continue Reading
                                    </a>
                                @else
                                    <a href="{{ route('books.show', $book) }}" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                @endif

                                <div class="book-actions-dropdown">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle"
                                        onclick="toggleDropdown({{ $book->id }})">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" id="dropdown-{{ $book->id }}">
                                        <a href="{{ route('books.edit', $book) }}" class="dropdown-item">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        @if ($book->status !== 'completed')
                                            <button onclick="markCompleted({{ $book->id }})" class="dropdown-item">
                                                <i class="fas fa-check"></i> Mark as Completed
                                            </button>
                                        @endif
                                        <div class="dropdown-divider"></div>
                                        <button onclick="deleteBook({{ $book->id }})"
                                            class="dropdown-item text-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="pagination-wrapper mt-6">
                {{ $books->appends(request()->query())->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <h3>No books found</h3>
                @if ($search || $category || $filter !== 'all')
                    <p>Try adjusting your filters or search terms.</p>
                    <a href="{{ route('books.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                @else
                    <p>Start building your reading library by adding your first book.</p>
                    <a href="{{ route('books.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Your First Book
                    </a>
                @endif
            </div>
        @endif
    </div>

    <!-- Quick Actions Modal -->
    <div id="quickActionsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Mark as Completed</h3>
                <button onclick="closeModal()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="completionForm">
                    <div class="form-group">
                        <label class="form-label">Rating (1-10)</label>
                        <input type="range" min="1" max="10" value="8" class="form-input"
                            name="rating" onchange="updateRatingValue(this.value)">
                        <div class="text-center mt-1">
                            Rating: <span class="font-bold" id="ratingValue">8</span>/10
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Review</label>
                        <textarea class="form-textarea" name="review" rows="3" placeholder="What did you think of this book?"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Key Insights (one per line)</label>
                        <textarea class="form-textarea" name="key_insights" rows="3" placeholder="Main takeaways from this book..."></textarea>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Mark as Completed
                        </button>
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .book-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
            position: relative;
        }

        .book-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .book-cover {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .cover-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cover-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .book-status {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .book-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem;
        }

        .book-progress .progress-bar {
            height: 4px;
            margin-bottom: 0.25rem;
        }

        .progress-text {
            font-size: 0.75rem;
            font-weight: 500;
        }

        .book-info {
            padding: 1.5rem;
        }

        .book-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .book-title a {
            color: var(--text-primary);
            text-decoration: none;
        }

        .book-title a:hover {
            color: var(--primary-color);
        }

        .book-author {
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
            font-style: italic;
        }

        .book-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .book-category {
            background-color: var(--secondary-color);
            color: var(--text-primary);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 500;
        }

        .book-pages {
            color: var(--text-muted);
        }

        .book-rating {
            color: var(--text-secondary);
        }

        .reading-stats {
            background-color: var(--secondary-color);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .reading-stat {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .reading-stat:last-child {
            margin-bottom: 0;
        }

        .completion-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }

        .book-actions {
            display: flex;
            gap: 0.5rem;
            position: relative;
        }

        .book-actions .btn {
            flex: 1;
        }

        .book-actions-dropdown {
            position: relative;
        }

        .dropdown-toggle {
            background: none;
            border: 1px solid var(--border-color);
            padding: 0.5rem;
            border-radius: 0.5rem;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            z-index: 100;
            min-width: 150px;
            display: none;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.875rem;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background-color: var(--secondary-color);
        }

        .dropdown-item.text-danger {
            color: var(--danger-color);
        }

        .dropdown-divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 0.5rem 0;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .filters-form .grid {
            align-items: end;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .books-grid {
                grid-template-columns: 1fr;
            }

            .filters-form .grid {
                grid-template-columns: 1fr;
            }

            .book-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        let currentBookId = null;

        // Start reading a book
        function startReading(bookId) {
            fetch(`/books/${bookId}/start`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.Laravel.csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        CEOTracker.showSuccess(data.message);
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    CEOTracker.showError('Failed to start reading');
                });
        }

        // Mark book as completed
        function markCompleted(bookId) {
            currentBookId = bookId;
            document.getElementById('quickActionsModal').style.display = 'flex';
        }

        // Delete book
        function deleteBook(bookId) {
            if (!confirm('Are you sure you want to delete this book? This action cannot be undone.')) {
                return;
            }

            fetch(`/books/${bookId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': window.Laravel.csrfToken
                    }
                })
                .then(response => {
                    if (response.ok) {
                        CEOTracker.showSuccess('Book deleted successfully');
                        location.reload();
                    } else {
                        CEOTracker.showError('Failed to delete book');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    CEOTracker.showError('Failed to delete book');
                });
        }

        // Toggle dropdown
        function toggleDropdown(bookId) {
            const dropdown = document.getElementById(`dropdown-${bookId}`);

            // Close all other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu.id !== `dropdown-${bookId}`) {
                    menu.classList.remove('show');
                }
            });

            dropdown.classList.toggle('show');
        }

        // Close modal
        function closeModal() {
            document.getElementById('quickActionsModal').style.display = 'none';
            currentBookId = null;
        }

        // Update rating value display
        function updateRatingValue(value) {
            document.getElementById('ratingValue').textContent = value;
        }

        // Initialize completion form
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('completionForm').addEventListener('submit', function(e) {
                e.preventDefault();

                if (!currentBookId) return;

                const formData = new FormData(this);
                const data = Object.fromEntries(formData);

                fetch(`/books/${currentBookId}/complete`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.Laravel.csrfToken
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            CEOTracker.showSuccess(data.message);
                            closeModal();
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        CEOTracker.showError('Failed to mark book as completed');
                    });
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.book-actions-dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });
    </script>
@endpush
