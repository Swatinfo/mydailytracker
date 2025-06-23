@extends('layouts.app')

@section('title', $book->title . ' - CEO Routine Tracker')

@section('content')
    <div class="book-detail-page">
        <!-- Breadcrumb -->
        <nav class="breadcrumb mb-4">
            <a href="{{ route('books.index') }}" class="breadcrumb-link">
                <i class="fas fa-book"></i> Library
            </a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{ $book->title }}</span>
        </nav>

        <!-- Book Header -->
        <div class="book-header mb-6">
            <div class="grid grid-2 gap-6">
                <div class="book-cover-section">
                    <div class="book-cover-large">
                        @if ($book->cover_image_url)
                            <img src="{{ $book->cover_image_url }}" alt="{{ $book->title }}" class="cover-image">
                        @else
                            <div class="cover-placeholder">
                                <i class="fas fa-book"></i>
                            </div>
                        @endif
                    </div>

                    @if ($book->status === 'currently_reading')
                        <div class="reading-progress-large">
                            <div class="progress-header">
                                <span class="progress-label">Reading Progress</span>
                                <span class="progress-percentage">{{ $book->progress_percentage }}%</span>
                            </div>
                            <div class="progress-bar-large">
                                <div class="progress-fill" style="width: {{ $book->progress_percentage }}%"></div>
                            </div>
                            <div class="progress-details">
                                Page {{ number_format($book->current_page) }} of {{ number_format($book->total_pages) }}
                                @if ($book->pages_remaining > 0)
                                    • {{ number_format($book->pages_remaining) }} pages remaining
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="book-info-section">
                    <div class="book-status mb-4">
                        <span class="status-badge status-{{ $book->status }}"
                            style="background-color: {{ $book->status_color }};">
                            {{ $book->status_label }}
                        </span>
                        @if ($book->priority >= 4)
                            <span class="priority-badge high-priority">
                                <i class="fas fa-star"></i> High Priority
                            </span>
                        @endif
                    </div>

                    <h1 class="book-title">{{ $book->title }}</h1>
                    <h2 class="book-author">by {{ $book->author }}</h2>

                    @if ($book->description)
                        <div class="book-description">
                            {{ $book->description }}
                        </div>
                    @endif

                    <div class="book-metadata">
                        <div class="metadata-grid">
                            <div class="metadata-item">
                                <span class="metadata-label">Category</span>
                                <span class="metadata-value">{{ $book->category_label }}</span>
                            </div>
                            <div class="metadata-item">
                                <span class="metadata-label">Format</span>
                                <span class="metadata-value">{{ ucfirst($book->format) }}</span>
                            </div>
                            <div class="metadata-item">
                                <span class="metadata-label">Pages</span>
                                <span class="metadata-value">{{ number_format($book->total_pages) }}</span>
                            </div>
                            @if ($book->isbn)
                                <div class="metadata-item">
                                    <span class="metadata-label">ISBN</span>
                                    <span class="metadata-value">{{ $book->isbn }}</span>
                                </div>
                            @endif
                            @if ($book->started_date)
                                <div class="metadata-item">
                                    <span class="metadata-label">Started</span>
                                    <span class="metadata-value">{{ $book->started_date->format('M j, Y') }}</span>
                                </div>
                            @endif
                            @if ($book->completed_date)
                                <div class="metadata-item">
                                    <span class="metadata-label">Completed</span>
                                    <span class="metadata-value">{{ $book->completed_date->format('M j, Y') }}</span>
                                </div>
                            @endif
                            @if ($book->rating)
                                <div class="metadata-item">
                                    <span class="metadata-label">Rating</span>
                                    <span class="metadata-value">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i
                                                class="fas fa-star {{ $i <= $book->rating / 2 ? 'text-warning' : 'text-muted' }}"></i>
                                        @endfor
                                        {{ $book->rating }}/10
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="book-actions">
                        @if ($book->status === 'want_to_read')
                            <button onclick="startReading()" class="btn btn-primary">
                                <i class="fas fa-play"></i> Start Reading
                            </button>
                        @elseif($book->status === 'currently_reading')
                            <button onclick="showSessionModal()" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Log Reading Session
                            </button>
                            <button onclick="updateProgress()" class="btn btn-secondary">
                                <i class="fas fa-bookmark"></i> Update Progress
                            </button>
                        @endif

                        @if ($book->status !== 'completed')
                            <button onclick="markCompleted()" class="btn btn-success">
                                <i class="fas fa-check"></i> Mark Completed
                            </button>
                        @endif

                        <a href="{{ route('books.edit', $book) }}" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Edit Book
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-4 mb-6">
            <div class="stat-card">
                <div class="stat-number">{{ $stats['total_sessions'] }}</div>
                <div class="stat-label">Reading Sessions</div>
            </div>

            <div class="stat-card info">
                <div class="stat-number">{{ number_format($stats['total_reading_time'] / 60, 1) }}h</div>
                <div class="stat-label">Total Reading Time</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-number">{{ $stats['average_session_duration'] }}m</div>
                <div class="stat-label">Avg Session</div>
            </div>

            <div class="stat-card success">
                <div class="stat-number">{{ $stats['current_streak'] }}</div>
                <div class="stat-label">Day Streak</div>
            </div>
        </div>

        <!-- Progress Chart and Recent Sessions -->
        <div class="grid grid-2 mb-6">
            @if (!empty($progressData))
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i>
                        Reading Progress (Last 30 Days)
                    </div>
                    <div class="card-content">
                        <div class="chart-container">
                            <canvas id="progressChart"></canvas>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i>
                    Recent Reading Sessions
                    @if ($book->status === 'currently_reading')
                        <button onclick="showSessionModal()" class="btn btn-sm btn-primary ml-auto">
                            <i class="fas fa-plus"></i> Add Session
                        </button>
                    @endif
                </div>
                <div class="card-content">
                    @if ($recentSessions->isNotEmpty())
                        <div class="sessions-list">
                            @foreach ($recentSessions->take(5) as $session)
                                <div class="session-item">
                                    <div class="session-header">
                                        <div class="session-date">
                                            {{ $session->session_date->format('M j, Y') }}
                                        </div>
                                        <div class="session-stats">
                                            {{ $session->duration_minutes }}m • {{ $session->pages_read }} pages
                                        </div>
                                    </div>

                                    @if ($session->session_notes)
                                        <div class="session-notes">
                                            {{ Str::limit($session->session_notes, 100) }}
                                        </div>
                                    @endif

                                    <div class="session-meta">
                                        <span class="session-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            {{ $session->location_label }}
                                        </span>
                                        @if ($session->quality_score)
                                            <span class="session-quality">
                                                <i class="fas fa-star"></i>
                                                {{ $session->quality_score }}/10
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($recentSessions->count() > 5)
                            <div class="text-center mt-4">
                                <button onclick="showAllSessions()" class="btn btn-secondary btn-sm">
                                    View All {{ $recentSessions->count() }} Sessions
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="empty-sessions">
                            <i class="fas fa-book-reader text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No reading sessions recorded yet</p>
                            @if ($book->status === 'currently_reading')
                                <button onclick="showSessionModal()" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-plus"></i> Log First Session
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Key Insights and Notes -->
        @if ($book->key_insights || $book->action_items || $book->review || $book->notes)
            <div class="grid grid-2 mb-6">
                @if ($book->key_insights)
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-lightbulb"></i>
                            Key Insights
                        </div>
                        <div class="card-content">
                            <ul class="insights-list">
                                @foreach ($book->key_insights as $insight)
                                    <li>{{ $insight }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if ($book->action_items)
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-tasks"></i>
                            Action Items
                        </div>
                        <div class="card-content">
                            <ul class="action-items-list">
                                @foreach ($book->action_items as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if ($book->review || $book->notes)
            <div class="card mb-6">
                <div class="card-header">
                    <i class="fas fa-sticky-note"></i>
                    {{ $book->review ? 'Review & Notes' : 'Notes' }}
                </div>
                <div class="card-content">
                    @if ($book->review)
                        <div class="book-review">
                            <h4>Review</h4>
                            <p>{{ $book->review }}</p>
                        </div>
                    @endif

                    @if ($book->notes)
                        <div class="book-notes {{ $book->review ? 'mt-4' : '' }}">
                            @if ($book->review)
                                <h4>Notes</h4>
                            @endif
                            <p>{{ $book->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Reading Insights -->
        @if ($insights)
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i>
                    Reading Insights
                </div>
                <div class="card-content">
                    <div class="insights-grid">
                        @foreach ($insights as $insight)
                            <div class="insight-item insight-{{ $insight['type'] }}">
                                <div class="insight-icon">
                                    @if ($insight['type'] === 'positive')
                                        <i class="fas fa-thumbs-up"></i>
                                    @elseif($insight['type'] === 'milestone')
                                        <i class="fas fa-flag"></i>
                                    @else
                                        <i class="fas fa-info-circle"></i>
                                    @endif
                                </div>
                                <div class="insight-content">
                                    <h4>{{ $insight['title'] }}</h4>
                                    <p>{{ $insight['message'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Reading Session Modal -->
    <div id="sessionModal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Log Reading Session</h3>
                <button onclick="closeModal()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="sessionForm" class="grid grid-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Session Date</label>
                        <input type="date" class="form-input" name="session_date"
                            value="{{ today()->toDateString() }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Session Type</label>
                        <select class="form-select" name="session_type" required>
                            <option value="scheduled">Scheduled (2-3 PM)</option>
                            <option value="bonus">Bonus Session</option>
                            <option value="catchup">Catch-up</option>
                            <option value="review">Review</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="time" class="form-input" name="start_time" value="14:00" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="time" class="form-input" name="end_time" value="14:30" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pages Read</label>
                        <input type="number" class="form-input" name="pages_read" min="1" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <select class="form-select" name="location">
                            <option value="office">Office</option>
                            <option value="home">Home</option>
                            <option value="commute">Commute</option>
                            <option value="cafe">Cafe</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Focus Level (1-10)</label>
                        <input type="range" min="1" max="10" value="8" class="form-input"
                            name="focus_level">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Comprehension (1-10)</label>
                        <input type="range" min="1" max="10" value="8" class="form-input"
                            name="comprehension_level">
                    </div>

                    <div class="form-group col-span-2">
                        <label class="form-label">Session Notes</label>
                        <textarea class="form-textarea" name="session_notes" rows="3"
                            placeholder="Key insights, thoughts, or highlights from this session..."></textarea>
                    </div>

                    <div class="form-group col-span-2">
                        <label class="form-label">Key Insights</label>
                        <textarea class="form-textarea" name="key_insights" rows="2" placeholder="Important takeaways or quotes..."></textarea>
                    </div>

                    <div class="col-span-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Session
                        </button>
                        <button type="button" onclick="closeModal()" class="btn btn-secondary ml-2">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Progress Update Modal -->
    <div id="progressModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Reading Progress</h3>
                <button onclick="closeModal()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="progressForm">
                    <div class="form-group">
                        <label class="form-label">Current Page</label>
                        <input type="number" class="form-input" name="current_page" value="{{ $book->current_page }}"
                            min="0" max="{{ $book->total_pages }}" required>
                        <div class="form-help">
                            Total pages: {{ number_format($book->total_pages) }}
                        </div>
                    </div>

                    <div class="progress-preview">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressPreview"
                                style="width: {{ $book->progress_percentage }}%"></div>
                        </div>
                        <div class="progress-text" id="progressText">{{ $book->progress_percentage }}%</div>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Progress
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
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .breadcrumb-link {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb-separator {
            color: var(--text-muted);
        }

        .book-cover-large {
            width: 100%;
            max-width: 300px;
            height: 400px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            margin-bottom: 1.5rem;
        }

        .book-cover-large .cover-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-cover-large .cover-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
        }

        .reading-progress-large {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .progress-label {
            font-weight: 600;
            color: var(--text-primary);
        }

        .progress-percentage {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .progress-bar-large {
            height: 12px;
            background-color: var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .progress-bar-large .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #3b82f6);
            border-radius: 6px;
            transition: width 0.3s ease;
        }

        .progress-details {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .book-status {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .high-priority {
            background-color: var(--warning-color);
            color: white;
        }

        .book-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            line-height: 1.3;
        }

        .book-author {
            font-size: 1.25rem;
            color: var(--text-secondary);
            font-style: italic;
            margin-bottom: 1.5rem;
        }

        .book-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .metadata-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .metadata-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .metadata-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .metadata-value {
            font-weight: 500;
            color: var(--text-primary);
        }

        .book-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .sessions-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .session-item {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            background-color: #f8fafc;
        }

        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .session-date {
            font-weight: 600;
            color: var(--text-primary);
        }

        .session-stats {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .session-notes {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            font-style: italic;
        }

        .session-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .empty-sessions {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .insights-list {
            list-style: none;
            padding: 0;
        }

        .insights-list li {
            padding: 0.75rem;
            border-left: 4px solid var(--primary-color);
            background-color: #f0f9ff;
            margin-bottom: 0.75rem;
            border-radius: 0 0.5rem 0.5rem 0;
        }

        .action-items-list {
            list-style: none;
            padding: 0;
        }

        .action-items-list li {
            padding: 0.75rem;
            border-left: 4px solid var(--success-color);
            background-color: #f0fdf4;
            margin-bottom: 0.75rem;
            border-radius: 0 0.5rem 0.5rem 0;
            position: relative;
        }

        .action-items-list li::before {
            content: '✓';
            position: absolute;
            left: -12px;
            top: 50%;
            transform: translateY(-50%);
            background-color: var(--success-color);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .book-review h4,
        .book-notes h4 {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .insights-grid {
            display: grid;
            gap: 1rem;
        }

        .insight-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            border-left: 4px solid;
        }

        .insight-positive {
            background-color: #f0fdf4;
            border-color: var(--success-color);
        }

        .insight-milestone {
            background-color: #fef3c7;
            border-color: var(--warning-color);
        }

        .insight-item .insight-icon {
            font-size: 1.25rem;
            margin-top: 0.25rem;
        }

        .insight-positive .insight-icon {
            color: var(--success-color);
        }

        .insight-milestone .insight-icon {
            color: var(--warning-color);
        }

        .insight-content h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .modal-lg {
            max-width: 800px;
        }

        .col-span-2 {
            grid-column: span 2;
        }

        .form-help {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .progress-preview {
            margin: 1rem 0;
        }

        .progress-text {
            text-align: center;
            margin-top: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .book-header .grid {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .book-cover-large {
                max-width: 250px;
                height: 320px;
                margin: 0 auto 1.5rem;
            }

            .book-actions {
                justify-content: center;
            }

            .metadata-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        // Initialize progress chart
        document.addEventListener('DOMContentLoaded', function() {
            @if (!empty($progressData))
                initProgressChart();
            @endif

            // Auto-calculate pages for session form
            const pagesInput = document.querySelector('input[name="pages_read"]');
            if (pagesInput) {
                pagesInput.value = Math.ceil(30 / 2); // Estimate 2 minutes per page for 30-min session
            }

            // Update progress preview
            const currentPageInput = document.querySelector('input[name="current_page"]');
            if (currentPageInput) {
                currentPageInput.addEventListener('input', function() {
                    const percentage = Math.round((this.value / {{ $book->total_pages }}) * 100);
                    document.getElementById('progressPreview').style.width = percentage + '%';
                    document.getElementById('progressText').textContent = percentage + '%';
                });
            }
        });

        // Progress chart
        function initProgressChart() {
            const ctx = document.getElementById('progressChart').getContext('2d');
            const data = @json($progressData);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.date),
                    datasets: [{
                        label: 'Pages Read',
                        data: data.map(d => d.pages_read),
                        borderColor: 'rgb(37, 99, 235)',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Start reading
        function startReading() {
            fetch(`/books/{{ $book->id }}/start`, {
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

        // Show session modal
        function showSessionModal() {
            document.getElementById('sessionModal').style.display = 'flex';
        }

        // Show progress modal
        function updateProgress() {
            document.getElementById('progressModal').style.display = 'flex';
        }

        // Mark as completed
        function markCompleted() {
            // This could open a completion modal or redirect to edit page
            window.location.href = `{{ route('books.edit', $book) }}#completion`;
        }

        // Close modals
        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        }

        // Show all sessions
        function showAllSessions() {
            // This could be implemented as a separate page or expandable section
            CEOTracker.showError('View all sessions feature coming soon!');
        }

        // Initialize forms
        document.addEventListener('DOMContentLoaded', function() {
            // Session form
            const sessionForm = document.getElementById('sessionForm');
            if (sessionForm) {
                sessionForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData);

                    // Calculate start_page and end_page
                    data.start_page = {{ $book->current_page }} + 1;
                    data.end_page = parseInt(data.start_page) + parseInt(data.pages_read) - 1;

                    fetch(`/books/{{ $book->id }}/sessions`, {
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
                            CEOTracker.showError('Failed to save reading session');
                        });
                });
            }

            // Progress form
            const progressForm = document.getElementById('progressForm');
            if (progressForm) {
                progressForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData);

                    fetch(`/books/{{ $book->id }}/progress`, {
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
                                CEOTracker.showSuccess('Progress updated!');
                                closeModal();
                                location.reload();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            CEOTracker.showError('Failed to update progress');
                        });
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
