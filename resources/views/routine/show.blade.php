@extends('layouts.app')

@section('title', 'Today\'s Routine - CEO Routine Tracker')

@section('content')
    <div class="routine-page">
        <!-- Page Header -->
        <div class="page-header mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-tasks"></i>
                        Today's Routine
                    </h1>
                    <p class="text-muted">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</p>
                </div>

                <div class="flex gap-2">
                    <input type="date" value="{{ $date }}" class="form-input" onchange="changeDate(this.value)"
                        style="width: auto;">
                    <button onclick="location.reload()" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Daily Statistics -->
        <div class="grid grid-4 mb-6">
            <div class="stat-card">
                <div class="stat-number">{{ $statistics['completion_rate'] }}%</div>
                <div class="stat-label">Completion Rate</div>
            </div>

            <div class="stat-card success">
                <div class="stat-number">{{ $statistics['completed_tasks'] }}/{{ $statistics['total_tasks'] }}</div>
                <div class="stat-label">Tasks Completed</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-number">{{ $statistics['average_quality'] ?: 'N/A' }}</div>
                <div class="stat-label">Avg Quality Score</div>
            </div>

            <div class="stat-card info">
                <div class="stat-number">{{ number_format($statistics['total_actual_time'] / 60, 1) }}h</div>
                <div class="stat-label">Time Spent</div>
            </div>
        </div>

        <!-- View Toggle -->
        <div class="card mb-6">
            <div class="card-content p-4">
                <div class="flex justify-between items-center">
                    <div class="btn-group">
                        <button onclick="switchView('category')" class="btn btn-primary view-btn active"
                            id="categoryViewBtn">
                            <i class="fas fa-th-large"></i> By Category
                        </button>
                        <button onclick="switchView('timeline')" class="btn btn-secondary view-btn" id="timelineViewBtn">
                            <i class="fas fa-clock"></i> Timeline View
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <button onclick="markAllCompleted()" class="btn btn-success btn-sm">
                            <i class="fas fa-check-double"></i> Mark All Done
                        </button>
                        <button onclick="exportData()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category View -->
        <div id="categoryView" class="routine-view active">
            @if ($categories->isNotEmpty())
                @foreach ($categories as $category)
                    @if ($category->routineTasks->isNotEmpty())
                        <div class="card mb-6">
                            <div class="card-header">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="category-icon" style="background-color: {{ $category->color }};">
                                            <i class="{{ $category->icon ?? 'fas fa-folder' }}"></i>
                                        </div>
                                        <div>
                                            <h3>{{ $category->name }}</h3>
                                            <p class="text-sm text-muted">
                                                {{ $category->routineTasks->where('taskCompletions.0.is_completed', true)->count() }}
                                                /
                                                {{ $category->routineTasks->count() }} completed
                                            </p>
                                        </div>
                                    </div>

                                    <div class="category-progress">
                                        @php
                                            $categoryCompletion =
                                                $category->routineTasks->count() > 0
                                                    ? round(
                                                        ($category->routineTasks
                                                            ->where('taskCompletions.0.is_completed', true)
                                                            ->count() /
                                                            $category->routineTasks->count()) *
                                                            100,
                                                        1,
                                                    )
                                                    : 0;
                                        @endphp
                                        <span class="text-lg font-bold">{{ $categoryCompletion }}%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-content">
                                @foreach ($category->routineTasks as $task)
                                    @php
                                        $completion = $task->taskCompletions->first();
                                    @endphp
                                    <div class="task-item {{ $completion?->is_completed ? 'completed' : '' }}"
                                        data-task-id="{{ $task->id }}" data-completion-id="{{ $completion?->id }}">

                                        <div class="task-checkbox">
                                            <input type="checkbox" {{ $completion?->is_completed ? 'checked' : '' }}
                                                onchange="toggleTaskCompletion({{ $completion?->id }}, this.checked)"
                                                class="task-checkbox-input">
                                        </div>

                                        <div class="task-content">
                                            <div class="task-header">
                                                <h4 class="task-title">{{ $task->title }}</h4>
                                                <div class="task-meta">
                                                    <span class="task-time">
                                                        <i class="fas fa-clock"></i>
                                                        {{ $task->start_time->format('H:i') }} -
                                                        {{ $task->end_time->format('H:i') }}
                                                        ({{ $task->estimated_duration }} min)
                                                    </span>
                                                    <span class="task-priority priority-{{ $task->priority }}">
                                                        {{ $task->priority_label }}
                                                    </span>
                                                </div>
                                            </div>

                                            @if ($task->description)
                                                <p class="task-description">{{ $task->description }}</p>
                                            @endif

                                            @if ($completion?->notes)
                                                <div class="task-notes">
                                                    <i class="fas fa-sticky-note"></i>
                                                    {{ $completion->notes }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="task-actions">
                                            @if ($completion?->is_completed)
                                                <!-- Completed Task Actions -->
                                                <div class="task-status completed">
                                                    <i class="fas fa-check-circle"></i>
                                                    @if ($completion->quality_score)
                                                        <span
                                                            class="quality-score">{{ $completion->quality_score }}/10</span>
                                                    @endif
                                                </div>
                                                <button onclick="editTaskCompletion({{ $completion->id }})"
                                                    class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @elseif($completion?->completion_status === 'in_progress')
                                                <!-- In Progress Actions -->
                                                <button onclick="completeTask({{ $completion->id }})"
                                                    class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Complete
                                                </button>
                                                <button onclick="showTaskActions({{ $completion->id }})"
                                                    class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                            @else
                                                <!-- Not Started Actions -->
                                                <button onclick="startTask({{ $completion->id }})"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-play"></i> Start
                                                </button>
                                                <button onclick="showTaskActions({{ $completion->id }})"
                                                    class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @else
                <!-- No Tasks Available -->
                <div class="card">
                    <div class="card-content text-center py-8">
                        <i class="fas fa-tasks text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>No Tasks Scheduled</h3>
                        <p class="text-muted mb-4">No routine tasks are scheduled for
                            {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}.</p>

                        <div class="debug-info"
                            style="background: #f8f9fa; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; text-align: left;">
                            <h4 style="margin-bottom: 0.5rem;">Debug Information:</h4>
                            <p><strong>Date:</strong> {{ $date }}</p>
                            <p><strong>Day of Week:</strong> {{ \Carbon\Carbon::parse($date)->dayOfWeek }}
                                ({{ \Carbon\Carbon::parse($date)->format('l') }})</p>
                            <p><strong>Categories loaded:</strong> {{ $categories->count() }}</p>

                            @foreach ($categories as $category)
                                <p><strong>{{ $category->name }}:</strong> {{ $category->routineTasks->count() }} tasks
                                </p>
                            @endforeach

                            <a href="/routine/debug?date={{ $date }}" class="btn btn-sm btn-secondary mt-2">
                                <i class="fas fa-bug"></i> View Debug Details
                            </a>
                        </div>

                        <div class="suggested-actions">
                            <a href="/routine?date={{ today()->toDateString() }}" class="btn btn-primary">
                                <i class="fas fa-calendar-day"></i> View Today's Tasks
                            </a>
                            <button onclick="location.reload()" class="btn btn-secondary ml-2">
                                <i class="fas fa-sync-alt"></i> Refresh Page
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Timeline View -->
        <div id="timelineView" class="routine-view">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clock"></i>
                    Daily Timeline
                </div>
                <div class="card-content">
                    <div class="timeline">
                        @foreach ($timelineView as $item)
                            <div class="timeline-item {{ $item['is_completed'] ? 'completed' : '' }}"
                                data-completion-id="{{ $item['completion_id'] }}">

                                <div class="timeline-time">
                                    {{ $item['time'] }}
                                </div>

                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h4>{{ $item['title'] }}</h4>
                                        <span class="timeline-category"
                                            style="background-color: {{ $item['category_color'] }};">
                                            {{ $item['category'] }}
                                        </span>
                                    </div>

                                    <div class="timeline-meta">
                                        <span class="duration">{{ $item['estimated_duration'] }} min</span>
                                        @if ($item['actual_duration'])
                                            <span class="actual-duration">({{ $item['actual_duration'] }} min
                                                actual)</span>
                                        @endif
                                        @if ($item['quality_score'])
                                            <span class="quality">Quality: {{ $item['quality_score'] }}/10</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="timeline-status">
                                    <span class="status-badge status-{{ $item['status'] }}">
                                        {{ ucfirst(str_replace('_', ' ', $item['status'])) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Reflection Card -->
        <div class="card mt-6">
            <div class="card-header">
                <i class="fas fa-journal-whills"></i>
                Daily Reflection
            </div>
            <div class="card-content">
                <form id="reflectionForm" class="grid grid-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">How was your day? (1-10)</label>
                        <input type="range" min="1" max="10"
                            value="{{ $dailyLog->overall_satisfaction ?? 7 }}" class="form-input satisfaction-slider"
                            name="overall_satisfaction" onchange="updateSatisfactionValue(this.value)">
                        <div class="text-center mt-1">
                            <span class="font-bold text-lg"
                                id="satisfactionValue">{{ $dailyLog->overall_satisfaction ?? 7 }}</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tomorrow's Top Priorities</label>
                        <textarea class="form-textarea" name="tomorrow_priorities" rows="3"
                            placeholder="What are your main focus areas for tomorrow?">{{ $dailyLog->tomorrow_priorities }}</textarea>
                    </div>

                    <div class="form-group col-span-2">
                        <label class="form-label">Daily Reflection</label>
                        <textarea class="form-textarea" name="daily_reflection" rows="3"
                            placeholder="What went well today? What could be improved?">{{ $dailyLog->daily_reflection }}</textarea>
                    </div>

                    <div class="col-span-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Reflection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Task Action Modal -->
    <div id="taskActionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Task Actions</h3>
                <button onclick="closeModal()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="action-buttons">
                    <button onclick="completeTaskWithDetails()" class="btn btn-success">
                        <i class="fas fa-check"></i> Complete Task
                    </button>
                    <button onclick="skipTask()" class="btn btn-warning">
                        <i class="fas fa-skip-forward"></i> Skip Task
                    </button>
                    <button onclick="postponeTask()" class="btn btn-info">
                        <i class="fas fa-clock"></i> Postpone Task
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Completion Modal -->
    <div id="taskCompletionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Complete Task</h3>
                <button onclick="closeModal()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="taskCompletionForm">
                    <div class="form-group">
                        <label class="form-label">Quality Score (1-10)</label>
                        <input type="range" min="1" max="10" value="8" class="form-input"
                            name="quality_score" onchange="updateQualityValue(this.value)">
                        <div class="text-center mt-1">
                            Score: <span class="font-bold" id="qualityValue">8</span>/10
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Energy After Task (1-10)</label>
                        <input type="range" min="1" max="10" value="7" class="form-input"
                            name="energy_after">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-textarea" name="notes" rows="3"
                            placeholder="How did it go? Any insights or obstacles?"></textarea>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Complete Task
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
        .routine-view {
            display: none;
        }

        .routine-view.active {
            display: block;
        }

        .task-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            transition: var(--transition);
        }

        .task-item:hover {
            background-color: var(--secondary-color);
        }

        .task-item.completed {
            background-color: #f0fdf4;
            border-color: var(--success-color);
        }

        .task-checkbox-input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .task-content {
            flex: 1;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .task-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .task-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .task-time {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .task-priority {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: white;
        }

        .priority-low {
            background-color: var(--success-color);
        }

        .priority-medium {
            background-color: var(--warning-color);
        }

        .priority-high {
            background-color: #f97316;
        }

        .priority-critical {
            background-color: var(--danger-color);
        }

        .task-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .task-notes {
            background-color: #fef3c7;
            padding: 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            color: #92400e;
        }

        .task-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .task-status.completed {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--success-color);
        }

        .quality-score {
            background-color: var(--success-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .category-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .timeline {
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 60px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: var(--border-color);
        }

        .timeline-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 59px;
            top: 8px;
            width: 4px;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 50%;
        }

        .timeline-item.completed::before {
            background-color: var(--success-color);
        }

        .timeline-time {
            font-weight: 600;
            font-size: 0.875rem;
            width: 50px;
            color: var(--text-secondary);
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .timeline-category {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            color: white;
            font-weight: 500;
        }

        .timeline-meta {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-not_started {
            background-color: #f3f4f6;
            color: #374151;
        }

        .status-in_progress {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-skipped {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-postponed {
            background-color: #fed7aa;
            color: #9a3412;
        }

        .btn-group {
            display: flex;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .btn-group .btn {
            border-radius: 0;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-group .btn:last-child {
            border-right: none;
        }

        .btn-group .btn:first-child {
            border-top-left-radius: var(--border-radius);
            border-bottom-left-radius: var(--border-radius);
        }

        .btn-group .btn:last-child {
            border-top-right-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
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

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .action-buttons .btn {
            justify-content: flex-start;
        }

        .col-span-2 {
            grid-column: span 2;
        }

        .satisfaction-slider {
            width: 100%;
        }
    </style>
@endsection

@push('scripts')
    <script>
        let currentTaskCompletionId = null;

        // Change date
        function changeDate(date) {
            window.location.href = `/routine?date=${date}`;
        }

        // Switch between views
        function switchView(view) {
            document.querySelectorAll('.routine-view').forEach(v => v.classList.remove('active'));
            document.querySelectorAll('.view-btn').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-secondary');
            });

            document.getElementById(view + 'View').classList.add('active');
            document.getElementById(view + 'ViewBtn').classList.remove('btn-secondary');
            document.getElementById(view + 'ViewBtn').classList.add('btn-primary');
        }

        // Toggle task completion
        function toggleTaskCompletion(completionId, isCompleted) {
            const data = {
                is_completed: isCompleted
            };

            if (isCompleted) {
                // If marking as completed, ask for quality score
                currentTaskCompletionId = completionId;
                showTaskCompletionModal();
                // Revert checkbox until user completes the form
                event.target.checked = false;
                return;
            }

            updateTaskCompletion(completionId, data);
        }

        // Start task
        function startTask(completionId) {
            fetch(`/routine/tasks/${completionId}/start`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.Laravel.csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        CEOTracker.showSuccess('Task started!');
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    CEOTracker.showError('Failed to start task');
                });
        }

        // Complete task
        function completeTask(completionId) {
            currentTaskCompletionId = completionId;
            showTaskCompletionModal();
        }

        // Show task actions modal
        function showTaskActions(completionId) {
            currentTaskCompletionId = completionId;
            document.getElementById('taskActionModal').style.display = 'flex';
        }

        // Show task completion modal
        function showTaskCompletionModal() {
            document.getElementById('taskCompletionModal').style.display = 'flex';
        }

        // Close modal
        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
            currentTaskCompletionId = null;
        }

        // Update task completion
        function updateTaskCompletion(completionId, data) {
            fetch(`/routine/tasks/${completionId}/update`, {
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
                        CEOTracker.showSuccess('Task updated!');
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    CEOTracker.showError('Failed to update task');
                });
        }

        // Complete task with details
        function completeTaskWithDetails() {
            if (!currentTaskCompletionId) return;

            currentTaskCompletionId = currentTaskCompletionId;
            closeModal();
            showTaskCompletionModal();
        }

        // Skip task
        function skipTask() {
            if (!currentTaskCompletionId) return;

            const notes = prompt('Why are you skipping this task?');

            fetch(`/routine/tasks/${currentTaskCompletionId}/skip`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.Laravel.csrfToken
                    },
                    body: JSON.stringify({
                        notes
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        CEOTracker.showSuccess('Task skipped');
                        closeModal();
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    CEOTracker.showError('Failed to skip task');
                });
        }

        // Postpone task
        function postponeTask() {
            if (!currentTaskCompletionId) return;

            const notes = prompt('Why are you postponing this task?');

            fetch(`/routine/tasks/${currentTaskCompletionId}/postpone`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.Laravel.csrfToken
                    },
                    body: JSON.stringify({
                        notes
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        CEOTracker.showSuccess('Task postponed');
                        closeModal();
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    CEOTracker.showError('Failed to postpone task');
                });
        }

        // Update quality value display
        function updateQualityValue(value) {
            document.getElementById('qualityValue').textContent = value;
        }

        // Update satisfaction value display
        function updateSatisfactionValue(value) {
            document.getElementById('satisfactionValue').textContent = value;
        }

        // Mark all tasks as completed
        function markAllCompleted() {
            if (!confirm('Are you sure you want to mark all tasks as completed?')) return;

            // This would need to be implemented with a bulk update endpoint
            CEOTracker.showError('Bulk completion not yet implemented');
        }

        // Export data
        function exportData() {
            window.open('/routine/export?date={{ $date }}', '_blank');
        }

        // Initialize forms
        document.addEventListener('DOMContentLoaded', function() {
            // Task completion form
            document.getElementById('taskCompletionForm').addEventListener('submit', function(e) {
                e.preventDefault();

                if (!currentTaskCompletionId) return;

                const formData = new FormData(this);
                const data = Object.fromEntries(formData);

                fetch(`/routine/tasks/${currentTaskCompletionId}/complete`, {
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
                        CEOTracker.showError('Failed to complete task');
                    });
            });

            // Reflection form
            document.getElementById('reflectionForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = Object.fromEntries(formData);

                fetch('/dashboard/reflection', {
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
                            CEOTracker.showSuccess('Reflection saved!');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        CEOTracker.showError('Failed to save reflection');
                    });
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });
    </script>
@endpush
