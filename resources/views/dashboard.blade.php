@extends('layouts.app')

@section('title', 'Dashboard - CEO Routine Tracker')

@section('content')
    <div class="dashboard">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard Overview
            </h1>
            <p class="text-muted">Welcome back! Here's your performance summary for {{ today()->format('F j, Y') }}</p>
        </div>

        <!-- Today's Performance Stats -->
        <div class="grid grid-4 mb-6">
            <div class="stat-card">
                <div class="stat-number" id="completion-rate">{{ $todayStats['completion_rate'] }}%</div>
                <div class="stat-label">Today's Completion</div>
            </div>

            <div class="stat-card success">
                <div class="stat-number" id="tasks-completed">{{ $todayStats['completed_tasks'] }}</div>
                <div class="stat-label">Tasks Completed</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-number" id="reading-time">{{ $todayStats['reading_time'] }}</div>
                <div class="stat-label">Reading Minutes</div>
            </div>

            <div class="stat-card info">
                <div class="stat-number" id="quality-score">{{ $todayStats['average_quality'] ?: 'N/A' }}</div>
                <div class="stat-label">Avg Quality Score</div>
            </div>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="grid grid-2">
            <!-- Today's Progress -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i>
                    Today's Progress
                </div>
                <div class="card-content">
                    <!-- Completion Progress -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold">Overall Completion</span>
                            <span class="text-lg font-bold text-primary">{{ $todayStats['completion_rate'] }}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $todayStats['completion_rate'] }}%"></div>
                        </div>
                    </div>

                    <!-- Category Breakdown -->
                    <div class="category-progress">
                        <h4 class="font-semibold mb-2">Category Performance</h4>
                        @foreach ($categoryPerformance as $category)
                            <div class="mb-3">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm">{{ $category['name'] }}</span>
                                    <span class="text-sm font-semibold">{{ $category['completion_rate'] }}%</span>
                                </div>
                                <div class="progress-bar" style="height: 6px;">
                                    <div class="progress-fill"
                                        style="width: {{ $category['completion_rate'] }}%; background-color: {{ $category['color'] }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
                        <div class="flex gap-2">
                            <a href="{{ route('routine.show') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-tasks"></i> View Today's Tasks
                            </a>
                            <button onclick="refreshDashboard()" class="btn btn-secondary btn-sm">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Performance Trends -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i>
                    Weekly Trends
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="weeklyTrendsChart"></canvas>
                    </div>

                    <div class="grid grid-3 mt-4 text-center">
                        <div>
                            <div class="text-lg font-bold text-primary">{{ $weeklyStats['completion_rate'] }}%</div>
                            <div class="text-sm text-muted">Weekly Avg</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-success">{{ $weeklyStats['consistency'] }}%</div>
                            <div class="text-sm text-muted">Consistency</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-warning">{{ $weeklyStats['reading_time'] }}</div>
                            <div class="text-sm text-muted">Reading Min</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Reading Progress -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-book-open"></i>
                    Reading Progress
                    <a href="{{ route('books.index') }}" class="btn btn-sm btn-secondary ml-auto">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
                <div class="card-content">
                    @if ($readingProgress['current_books']->isNotEmpty())
                        @foreach ($readingProgress['current_books'] as $book)
                            <div class="mb-4 p-3" style="border: 1px solid var(--border-color); border-radius: 0.5rem;">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="font-semibold">{{ $book['title'] }}</h4>
                                        <p class="text-sm text-muted">by {{ $book['author'] }}</p>
                                    </div>
                                    <span class="text-sm font-bold text-primary">{{ $book['progress_percentage'] }}%</span>
                                </div>

                                <div class="progress-bar mb-2">
                                    <div class="progress-fill" style="width: {{ $book['progress_percentage'] }}%"></div>
                                </div>

                                <div class="flex justify-between text-sm text-muted">
                                    <span>{{ $book['current_page'] }} / {{ $book['total_pages'] }} pages</span>
                                    @if ($book['reading_streak'] > 0)
                                        <span><i class="fas fa-fire text-warning"></i> {{ $book['reading_streak'] }} day
                                            streak</span>
                                    @endif
                                </div>

                                @if ($book['estimated_completion'])
                                    <div class="text-sm text-muted mt-1">
                                        <i class="fas fa-calendar"></i> Est. completion:
                                        {{ $book['estimated_completion'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-book-open text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No books currently being read</p>
                            <a href="{{ route('books.create') }}" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-plus"></i> Add Book
                            </a>
                        </div>
                    @endif

                    <!-- Reading Stats -->
                    <div class="mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
                        <div class="grid grid-2 text-center">
                            <div>
                                <div class="text-lg font-bold">{{ $readingProgress['today_reading_time'] }}</div>
                                <div class="text-sm text-muted">Minutes Today</div>
                            </div>
                            <div>
                                <div class="text-lg font-bold">{{ $readingProgress['weekly_reading_time'] }}</div>
                                <div class="text-sm text-muted">Minutes This Week</div>
                            </div>
                        </div>

                        <div class="mt-2">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm">Weekly Progress</span>
                                <span class="text-sm font-semibold">{{ $readingProgress['weekly_progress'] }}%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill"
                                    style="width: {{ min($readingProgress['weekly_progress'], 100) }}%"></div>
                            </div>
                            <div class="text-sm text-muted mt-1">Target: 210 minutes/week</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Tasks -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clock"></i>
                    Upcoming Tasks
                </div>
                <div class="card-content">
                    @if ($upcomingTasks->isNotEmpty())
                        @foreach ($upcomingTasks as $task)
                            <div
                                class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-200' : '' }}">
                                <div>
                                    <div class="font-semibold">{{ $task['title'] }}</div>
                                    <div class="text-sm text-muted">
                                        {{ $task['start_time'] }} - {{ $task['end_time'] }}
                                        <span class="mx-1">•</span>
                                        {{ $task['category'] }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 text-xs rounded"
                                        style="background-color: {{ $task['is_completed'] ? 'var(--success-color)' : 'var(--warning-color)' }}; color: white;">
                                        {{ $task['is_completed'] ? 'Done' : $task['priority'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">All tasks completed for today!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Achievements -->
        @if ($recentAchievements->isNotEmpty())
            <div class="card mt-6">
                <div class="card-header">
                    <i class="fas fa-trophy"></i>
                    Recent Achievements
                </div>
                <div class="card-content">
                    <div class="grid grid-3">
                        @foreach ($recentAchievements as $achievement)
                            <div class="achievement-item p-3 text-center"
                                style="border: 1px solid var(--border-color); border-radius: 0.5rem;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">{{ $achievement['icon'] }}</div>
                                <h4 class="font-semibold">{{ $achievement['title'] }}</h4>
                                <p class="text-sm text-muted">{{ $achievement['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Energy Tracking -->
        <div class="card mt-6">
            <div class="card-header">
                <i class="fas fa-battery-three-quarters"></i>
                Energy Tracking
            </div>
            <div class="card-content">
                <div class="grid grid-2">
                    <div>
                        <label class="form-label">Morning Energy Level (1-10)</label>
                        <input type="range" min="1" max="10"
                            value="{{ $todayLog->morning_energy_level ?? 7 }}" class="energy-slider" id="morningEnergy"
                            onchange="updateEnergyLevel('morning', this.value)">
                        <div class="text-center mt-2">
                            <span class="font-bold text-lg"
                                id="morningEnergyValue">{{ $todayLog->morning_energy_level ?? 7 }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Evening Energy Level (1-10)</label>
                        <input type="range" min="1" max="10"
                            value="{{ $todayLog->evening_energy_level ?? 7 }}" class="energy-slider" id="eveningEnergy"
                            onchange="updateEnergyLevel('evening', this.value)">
                        <div class="text-center mt-2">
                            <span class="font-bold text-lg"
                                id="eveningEnergyValue">{{ $todayLog->evening_energy_level ?? 7 }}</span>
                        </div>
                    </div>
                </div>

                @if ($todayLog->energy_change !== null)
                    <div class="mt-4 text-center">
                        <div class="text-sm text-muted">Energy Change</div>
                        <div
                            class="text-lg font-bold {{ $todayLog->energy_change >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $todayLog->energy_change > 0 ? '+' : '' }}{{ $todayLog->energy_change }}
                            <i class="fas fa-{{ $todayLog->energy_change >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick CEO Reading Log -->
        <div class="card mt-6">
            <div class="card-header">
                <i class="fas fa-clock"></i>
                Quick Reading Log (2:00-2:30 PM)
            </div>
            <div class="card-content">
                @if ($readingProgress['today_reading_time'] >= 30)
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                        <p class="text-success mt-2 font-semibold">Today's reading session completed!</p>
                        <p class="text-muted">{{ $readingProgress['today_reading_time'] }} minutes logged</p>
                    </div>
                @else
                    <form id="quickReadingForm" class="grid grid-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Current Book</label>
                            <select class="form-select" name="book_id" required>
                                <option value="">Select a book...</option>
                                @foreach ($readingProgress['current_books'] as $book)
                                    <option value="{{ $book['id'] }}">{{ $book['title'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Pages Read</label>
                            <input type="number" class="form-input" name="pages_read" min="1" max="50"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Quality Rating (1-10)</label>
                            <input type="range" min="1" max="10" value="8" class="form-input"
                                name="quality_rating">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Quick Notes</label>
                            <textarea class="form-textarea" name="notes" rows="2" placeholder="Key insights or thoughts..."></textarea>
                        </div>

                        <div class="col-span-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-book"></i> Log Reading Session
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <style>
        .energy-slider {
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: var(--border-color);
            outline: none;
            -webkit-appearance: none;
        }

        .energy-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
        }

        .energy-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
            border: none;
        }

        .achievement-item {
            transition: var(--transition);
        }

        .achievement-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .col-span-2 {
            grid-column: span 2;
        }
    </style>
@endsection

@push('scripts')
    <script>
        // Initialize charts and interactions
        document.addEventListener('DOMContentLoaded', function() {
            initWeeklyTrendsChart();
            initQuickReadingForm();
        });

        // Weekly trends chart
        function initWeeklyTrendsChart() {
            const ctx = document.getElementById('weeklyTrendsChart').getContext('2d');
            const trendsData = @json($performanceTrends);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendsData.map(d => d.date),
                    datasets: [{
                        label: 'Completion Rate',
                        data: trendsData.map(d => d.completion_rate),
                        borderColor: 'rgb(37, 99, 235)',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        tension: 0.3
                    }, {
                        label: 'Quality Score',
                        data: trendsData.map(d => d.quality_score),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10
                        }
                    }
                }
            });
        }

        // Update energy levels
        function updateEnergyLevel(type, value) {
            document.getElementById(type + 'EnergyValue').textContent = value;

            fetch('/dashboard/energy', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.Laravel.csrfToken
                    },
                    body: JSON.stringify({
                        [type + '_energy']: parseInt(value)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        CEOTracker.showSuccess(`${type} energy level updated!`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    CEOTracker.showError('Failed to update energy level');
                });
        }

        // Quick reading form
        function initQuickReadingForm() {
            const form = document.getElementById('quickReadingForm');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(form);
                const data = Object.fromEntries(formData);

                fetch('/books/log-today', {
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
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            CEOTracker.showError(data.message || 'Failed to log reading session');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        CEOTracker.showError('Failed to log reading session');
                    });
            });
        }

        // Refresh dashboard data
        function refreshDashboard() {
            location.reload();
        }

        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            if (document.hidden) return; // Don't refresh if tab is not active

            fetch('/dashboard/data?type=today')
                .then(response => response.json())
                .then(data => {
                    // Update stats without full page reload
                    document.getElementById('completion-rate').textContent = data.completion_rate + '%';
                    document.getElementById('tasks-completed').textContent = data.completed_tasks;
                    document.getElementById('reading-time').textContent = data.reading_time;
                    document.getElementById('quality-score').textContent = data.average_quality || 'N/A';
                })
                .catch(error => console.error('Auto-refresh failed:', error));
        }, 300000); // 5 minutes
    </script>
@endpush
