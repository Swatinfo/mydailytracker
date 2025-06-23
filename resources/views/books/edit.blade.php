@extends('layouts.app')

@section('title', 'Edit ' . $book->title . ' - CEO Routine Tracker')

@section('content')
    <div class="edit-book-page">
        <!-- Breadcrumb -->
        <nav class="breadcrumb mb-4">
            <a href="{{ route('books.index') }}" class="breadcrumb-link">
                <i class="fas fa-book"></i> Library
            </a>
            <span class="breadcrumb-separator">/</span>
            <a href="{{ route('books.show', $book) }}" class="breadcrumb-link">{{ $book->title }}</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Edit</span>
        </nav>

        <!-- Page Header -->
        <div class="page-header mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-edit"></i>
                        Edit Book
                    </h1>
                    <p class="text-muted">Update book information and reading progress</p>
                </div>

                <div class="book-status-indicator">
                    <span class="status-badge status-{{ $book->status }}"
                        style="background-color: {{ $book->status_color }};">
                        {{ $book->status_label }}
                    </span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('books.update', $book) }}" id="editBookForm">
            @csrf
            @method('PUT')

            <div class="grid grid-2 gap-6">
                <!-- Main Form -->
                <div class="book-form-section">

                    <!-- Basic Information -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </div>
                        <div class="card-content">
                            <!-- Title -->
                            <div class="form-group">
                                <label class="form-label required">Title</label>
                                <input type="text" name="title" class="form-input @error('title') error @enderror"
                                    value="{{ old('title', $book->title) }}" required>
                                @error('title')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Author -->
                            <div class="form-group">
                                <label class="form-label required">Author</label>
                                <input type="text" name="author" class="form-input @error('author') error @enderror"
                                    value="{{ old('author', $book->author) }}" required>
                                @error('author')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- ISBN -->
                            <div class="form-group">
                                <label class="form-label">ISBN</label>
                                <input type="text" name="isbn" class="form-input @error('isbn') error @enderror"
                                    value="{{ old('isbn', $book->isbn) }}">
                                @error('isbn')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-textarea @error('description') error @enderror" rows="4">{{ old('description', $book->description) }}</textarea>
                                @error('description')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Reading Progress -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-bookmark"></i>
                            Reading Progress
                        </div>
                        <div class="card-content">
                            <div class="grid grid-2 gap-4">
                                <!-- Status -->
                                <div class="form-group">
                                    <label class="form-label required">Status</label>
                                    <select name="status" class="form-select @error('status') error @enderror" required
                                        onchange="updateStatusFields()">
                                        <option value="want_to_read"
                                            {{ old('status', $book->status) === 'want_to_read' ? 'selected' : '' }}>Want to
                                            Read</option>
                                        <option value="currently_reading"
                                            {{ old('status', $book->status) === 'currently_reading' ? 'selected' : '' }}>
                                            Currently Reading</option>
                                        <option value="completed"
                                            {{ old('status', $book->status) === 'completed' ? 'selected' : '' }}>Completed
                                        </option>
                                        <option value="paused"
                                            {{ old('status', $book->status) === 'paused' ? 'selected' : '' }}>Paused
                                        </option>
                                        <option value="abandoned"
                                            {{ old('status', $book->status) === 'abandoned' ? 'selected' : '' }}>Abandoned
                                        </option>
                                    </select>
                                    @error('status')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Current Page -->
                                <div class="form-group">
                                    <label class="form-label required">Current Page</label>
                                    <input type="number" name="current_page"
                                        class="form-input @error('current_page') error @enderror"
                                        value="{{ old('current_page', $book->current_page) }}" min="0"
                                        max="{{ $book->total_pages }}" onchange="updateProgressPreview()" required>
                                    @error('current_page')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Progress Preview -->
                            <div class="progress-preview">
                                <div class="progress-header">
                                    <span class="progress-label">Progress</span>
                                    <span class="progress-percentage"
                                        id="progressPercentage">{{ $book->progress_percentage }}%</span>
                                </div>
                                <div class="progress-bar-large">
                                    <div class="progress-fill" id="progressFill"
                                        style="width: {{ $book->progress_percentage }}%"></div>
                                </div>
                                <div class="progress-details" id="progressDetails">
                                    {{ number_format($book->current_page) }} of {{ number_format($book->total_pages) }}
                                    pages
                                    @if ($book->pages_remaining > 0)
                                        • {{ number_format($book->pages_remaining) }} pages remaining
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Book Details -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-cogs"></i>
                            Book Details
                        </div>
                        <div class="card-content">
                            <div class="grid grid-3 gap-4">
                                <!-- Category -->
                                <div class="form-group">
                                    <label class="form-label required">Category</label>
                                    <select name="category" class="form-select @error('category') error @enderror" required>
                                        <option value="business"
                                            {{ old('category', $book->category) === 'business' ? 'selected' : '' }}>
                                            Business</option>
                                        <option value="technical"
                                            {{ old('category', $book->category) === 'technical' ? 'selected' : '' }}>
                                            Technical</option>
                                        <option value="personal_development"
                                            {{ old('category', $book->category) === 'personal_development' ? 'selected' : '' }}>
                                            Personal Development</option>
                                        <option value="leadership"
                                            {{ old('category', $book->category) === 'leadership' ? 'selected' : '' }}>
                                            Leadership</option>
                                        <option value="strategy"
                                            {{ old('category', $book->category) === 'strategy' ? 'selected' : '' }}>
                                            Strategy</option>
                                        <option value="biography"
                                            {{ old('category', $book->category) === 'biography' ? 'selected' : '' }}>
                                            Biography</option>
                                        <option value="fiction"
                                            {{ old('category', $book->category) === 'fiction' ? 'selected' : '' }}>Fiction
                                        </option>
                                        <option value="other"
                                            {{ old('category', $book->category) === 'other' ? 'selected' : '' }}>Other
                                        </option>
                                    </select>
                                    @error('category')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Total Pages -->
                                <div class="form-group">
                                    <label class="form-label required">Total Pages</label>
                                    <input type="number" name="total_pages"
                                        class="form-input @error('total_pages') error @enderror"
                                        value="{{ old('total_pages', $book->total_pages) }}" min="1"
                                        onchange="updateProgressPreview()" required>
                                    @error('total_pages')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Priority -->
                                <div class="form-group">
                                    <label class="form-label required">Priority</label>
                                    <select name="priority" class="form-select @error('priority') error @enderror"
                                        required>
                                        <option value="1"
                                            {{ old('priority', $book->priority) == '1' ? 'selected' : '' }}>1 - Very Low
                                        </option>
                                        <option value="2"
                                            {{ old('priority', $book->priority) == '2' ? 'selected' : '' }}>2 - Low
                                        </option>
                                        <option value="3"
                                            {{ old('priority', $book->priority) == '3' ? 'selected' : '' }}>3 - Medium
                                        </option>
                                        <option value="4"
                                            {{ old('priority', $book->priority) == '4' ? 'selected' : '' }}>4 - High
                                        </option>
                                        <option value="5"
                                            {{ old('priority', $book->priority) == '5' ? 'selected' : '' }}>5 - Very High
                                        </option>
                                    </select>
                                    @error('priority')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-2 gap-4">
                                <!-- Format -->
                                <div class="form-group">
                                    <label class="form-label required">Format</label>
                                    <select name="format" class="form-select @error('format') error @enderror" required>
                                        <option value="physical"
                                            {{ old('format', $book->format) === 'physical' ? 'selected' : '' }}>Physical
                                            Book</option>
                                        <option value="ebook"
                                            {{ old('format', $book->format) === 'ebook' ? 'selected' : '' }}>E-book
                                        </option>
                                        <option value="audiobook"
                                            {{ old('format', $book->format) === 'audiobook' ? 'selected' : '' }}>Audiobook
                                        </option>
                                        <option value="pdf"
                                            {{ old('format', $book->format) === 'pdf' ? 'selected' : '' }}>PDF</option>
                                    </select>
                                    @error('format')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Cover Image -->
                                <div class="form-group">
                                    <label class="form-label">Cover Image URL</label>
                                    <input type="url" name="cover_image_url"
                                        class="form-input @error('cover_image_url') error @enderror"
                                        value="{{ old('cover_image_url', $book->cover_image_url) }}"
                                        onchange="previewCover(this.value)">
                                    @error('cover_image_url')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Tags -->
                            <div class="form-group">
                                <label class="form-label">Tags</label>
                                <input type="text" name="tags" class="form-input @error('tags') error @enderror"
                                    value="{{ old('tags', $book->tags ? implode(', ', $book->tags) : '') }}"
                                    placeholder="Separate tags with commas">
                                @error('tags')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Completion Details (shown when status is completed) -->
                    <div class="card" id="completionSection"
                        style="display: {{ $book->status === 'completed' ? 'block' : 'none' }};">
                        <div class="card-header">
                            <i class="fas fa-trophy"></i>
                            Completion Details
                        </div>
                        <div class="card-content">
                            <div class="form-group">
                                <label class="form-label">Rating (1-10)</label>
                                <input type="range" min="1" max="10"
                                    value="{{ old('rating', $book->rating ?? 8) }}" class="form-input rating-slider"
                                    name="rating" onchange="updateRatingValue(this.value)">
                                <div class="text-center mt-2">
                                    Rating: <span class="font-bold text-lg"
                                        id="ratingValue">{{ $book->rating ?? 8 }}</span>/10
                                    <div class="rating-stars" id="ratingStars"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Review</label>
                                <textarea name="review" class="form-textarea @error('review') error @enderror" rows="4"
                                    placeholder="What did you think of this book?">{{ old('review', $book->review) }}</textarea>
                                @error('review')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label">Key Insights</label>
                                <textarea name="key_insights" class="form-textarea @error('key_insights') error @enderror" rows="4"
                                    placeholder="Enter each insight on a new line">{{ old('key_insights', $book->key_insights ? implode("\n", $book->key_insights) : '') }}</textarea>
                                <div class="form-help">Enter each insight on a separate line</div>
                                @error('key_insights')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label">Action Items</label>
                                <textarea name="action_items" class="form-textarea @error('action_items') error @enderror" rows="4"
                                    placeholder="Enter each action item on a new line">{{ old('action_items', $book->action_items ? implode("\n", $book->action_items) : '') }}</textarea>
                                <div class="form-help">Enter each action item on a separate line</div>
                                @error('action_items')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-sticky-note"></i>
                            Additional Information
                        </div>
                        <div class="card-content">
                            <div class="grid grid-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Purchase URL</label>
                                    <input type="url" name="purchase_url"
                                        class="form-input @error('purchase_url') error @enderror"
                                        value="{{ old('purchase_url', $book->purchase_url) }}">
                                    @error('purchase_url')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Price</label>
                                    <input type="number" name="price"
                                        class="form-input @error('price') error @enderror"
                                        value="{{ old('price', $book->price) }}" step="0.01" min="0">
                                    @error('price')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-textarea @error('notes') error @enderror" rows="3">{{ old('notes', $book->notes) }}</textarea>
                                @error('notes')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Book
                        </button>
                        <a href="{{ route('books.show', $book) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Book
                        </a>
                        <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Book
                        </button>
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="book-preview-section">
                    <div class="card sticky">
                        <div class="card-header">
                            <i class="fas fa-eye"></i>
                            Book Preview
                        </div>
                        <div class="card-content">
                            <div class="book-preview">
                                <div class="preview-cover" id="previewCover">
                                    @if ($book->cover_image_url)
                                        <img src="{{ $book->cover_image_url }}" alt="{{ $book->title }}"
                                            class="cover-image">
                                    @else
                                        <div class="cover-placeholder">
                                            <i class="fas fa-book"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="preview-info">
                                    <h3 class="preview-title" id="previewTitle">{{ $book->title }}</h3>
                                    <p class="preview-author" id="previewAuthor">by {{ $book->author }}</p>

                                    <div class="preview-status">
                                        <span class="status-badge" id="previewStatus"
                                            style="background-color: {{ $book->status_color }};">
                                            {{ $book->status_label }}
                                        </span>
                                    </div>

                                    <div class="preview-meta">
                                        <span class="preview-category"
                                            id="previewCategory">{{ $book->category_label }}</span>
                                        <span class="preview-pages"
                                            id="previewPages">{{ number_format($book->total_pages) }} pages</span>
                                        <span class="preview-format"
                                            id="previewFormat">{{ ucfirst($book->format) }}</span>
                                    </div>

                                    <div class="preview-priority" id="previewPriority">
                                        Priority: <span class="priority-value">{{ $book->priority_label }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reading Statistics -->
                    @if ($book->readingSessions->isNotEmpty())
                        <div class="card mt-4">
                            <div class="card-header">
                                <i class="fas fa-chart-bar"></i>
                                Reading Statistics
                            </div>
                            <div class="card-content">
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="stat-number">{{ $book->readingSessions->count() }}</div>
                                        <div class="stat-label">Sessions</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number">{{ number_format($book->total_reading_time / 60, 1) }}h
                                        </div>
                                        <div class="stat-label">Total Time</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number">{{ $book->average_session_duration }}m</div>
                                        <div class="stat-label">Avg Session</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number">{{ $book->getCurrentReadingStreak() }}</div>
                                        <div class="stat-label">Day Streak</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Book</h3>
                <button onclick="closeDeleteModal()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong>"{{ $book->title }}"</strong>?</p>
                <p class="text-muted">This action cannot be undone. All reading sessions and progress will be lost.</p>

                <div class="flex gap-2 mt-4">
                    <form method="POST" action="{{ route('books.destroy', $book) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Yes, Delete Book
                        </button>
                    </form>
                    <button onclick="closeDeleteModal()" class="btn btn-secondary">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .book-preview-section .card.sticky {
            position: sticky;
            top: 100px;
        }

        .progress-preview {
            margin-top: 1rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .progress-label {
            font-weight: 600;
            color: var(--text-primary);
        }

        .progress-percentage {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .progress-bar-large {
            height: 10px;
            background-color: var(--border-color);
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .progress-bar-large .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #3b82f6);
            border-radius: 5px;
            transition: width 0.3s ease;
        }

        .progress-details {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-align: center;
        }

        .rating-slider {
            width: 100%;
        }

        .rating-stars {
            margin-top: 0.5rem;
        }

        .rating-stars i {
            font-size: 1.25rem;
            margin: 0 0.125rem;
        }

        .preview-status {
            margin-bottom: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            text-align: center;
        }

        .stat-item {
            padding: 0.75rem;
            background-color: var(--secondary-color);
            border-radius: 0.5rem;
        }

        .stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .book-preview-section .card.sticky {
                position: static;
            }

            .form-actions {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            updateRatingStars();
            updateStatusFields();
        });

        // Update progress preview
        function updateProgressPreview() {
            const currentPage = parseInt(document.querySelector('input[name="current_page"]').value) || 0;
            const totalPages = parseInt(document.querySelector('input[name="total_pages"]').value) ||
                {{ $book->total_pages }};

            const percentage = totalPages > 0 ? Math.round((currentPage / totalPages) * 100) : 0;
            const remaining = Math.max(0, totalPages - currentPage);

            document.getElementById('progressPercentage').textContent = percentage + '%';
            document.getElementById('progressFill').style.width = percentage + '%';
            document.getElementById('progressDetails').textContent =
                `${currentPage.toLocaleString()} of ${totalPages.toLocaleString()} pages` +
                (remaining > 0 ? ` • ${remaining.toLocaleString()} pages remaining` : '');
        }

        // Update rating value and stars
        function updateRatingValue(value) {
            document.getElementById('ratingValue').textContent = value;
            updateRatingStars(value);
        }

        function updateRatingStars(rating = null) {
            const ratingValue = rating || document.querySelector('input[name="rating"]').value;
            const starsContainer = document.getElementById('ratingStars');
            const starCount = Math.ceil(ratingValue / 2); // Convert 1-10 to 1-5 stars

            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                const starClass = i <= starCount ? 'fas fa-star text-warning' : 'far fa-star text-muted';
                starsHtml += `<i class="${starClass}"></i>`;
            }
            starsContainer.innerHTML = starsHtml;
        }

        // Update status-dependent fields
        function updateStatusFields() {
            const status = document.querySelector('select[name="status"]').value;
            const completionSection = document.getElementById('completionSection');

            if (status === 'completed') {
                completionSection.style.display = 'block';
                // Set current page to total pages if marking as completed
                const totalPages = document.querySelector('input[name="total_pages"]').value;
                document.querySelector('input[name="current_page"]').value = totalPages;
                updateProgressPreview();
            } else {
                completionSection.style.display = 'none';
            }

            // Update preview status
            updatePreviewStatus(status);
        }

        // Update preview status
        function updatePreviewStatus(status) {
            const statusBadge = document.getElementById('previewStatus');
            const statusColors = {
                'want_to_read': '#6b7280',
                'currently_reading': '#3b82f6',
                'completed': '#10b981',
                'paused': '#f59e0b',
                'abandoned': '#ef4444'
            };

            const statusLabels = {
                'want_to_read': 'Want to Read',
                'currently_reading': 'Currently Reading',
                'completed': 'Completed',
                'paused': 'Paused',
                'abandoned': 'Abandoned'
            };

            statusBadge.style.backgroundColor = statusColors[status] || '#6b7280';
            statusBadge.textContent = statusLabels[status] || status;
        }

        // Preview cover image
        function previewCover(url) {
            const previewCover = document.getElementById('previewCover');

            if (url) {
                const img = new Image();
                img.onload = function() {
                    previewCover.innerHTML = `<img src="${url}" alt="Book Cover" class="cover-image">`;
                };
                img.onerror = function() {
                    previewCover.innerHTML = `
                <div class="cover-placeholder">
                    <i class="fas fa-book"></i>
                </div>
            `;
                };
                img.src = url;
            } else {
                previewCover.innerHTML = `
            <div class="cover-placeholder">
                <i class="fas fa-book"></i>
            </div>
        `;
            }
        }

        // Real-time preview updates
        document.querySelector('input[name="title"]').addEventListener('input', function() {
            document.getElementById('previewTitle').textContent = this.value || '{{ $book->title }}';
        });

        document.querySelector('input[name="author"]').addEventListener('input', function() {
            document.getElementById('previewAuthor').textContent = 'by ' + (this.value || '{{ $book->author }}');
        });

        document.querySelector('select[name="category"]').addEventListener('change', function() {
            const categoryLabel = this.options[this.selectedIndex].text;
            document.getElementById('previewCategory').textContent = categoryLabel;
        });

        document.querySelector('input[name="total_pages"]').addEventListener('input', function() {
            const pages = parseInt(this.value) || 0;
            document.getElementById('previewPages').textContent = pages.toLocaleString() + ' pages';
            updateProgressPreview();
        });

        document.querySelector('select[name="format"]').addEventListener('change', function() {
            const format = this.options[this.selectedIndex].text;
            document.getElementById('previewFormat').textContent = format;
        });

        document.querySelector('select[name="priority"]').addEventListener('change', function() {
            const priority = this.options[this.selectedIndex].text.replace(/^\d+ - /, '');
            document.querySelector('.priority-value').textContent = priority;
        });

        document.querySelector('input[name="current_page"]').addEventListener('input', updateProgressPreview);

        // Delete confirmation
        function confirmDelete() {
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Form validation
        document.getElementById('editBookForm').addEventListener('submit', function(e) {
            const currentPage = parseInt(document.querySelector('input[name="current_page"]').value);
            const totalPages = parseInt(document.querySelector('input[name="total_pages"]').value);

            if (currentPage > totalPages) {
                e.preventDefault();
                CEOTracker.showError('Current page cannot be greater than total pages');
                document.querySelector('input[name="current_page"]').focus();
                return;
            }

            // Auto-update status to completed if reading all pages
            if (currentPage === totalPages && document.querySelector('select[name="status"]').value !==
                'completed') {
                if (confirm('You\'ve read all pages. Mark this book as completed?')) {
                    document.querySelector('select[name="status"]').value = 'completed';
                    updateStatusFields();
                }
            }
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeDeleteModal();
            }
        });
    </script>
@endpush
