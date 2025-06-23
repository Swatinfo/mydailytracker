<!DOCTYPE html>
<html>

<head>
    <title>Task Debug Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .debug-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }

        .task-item {
            margin: 5px 0;
            padding: 10px;
            background: white;
            border-left: 3px solid #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .json {
            font-family: monospace;
            background: #f0f0f0;
            padding: 5px;
        }
    </style>
</head>

<body>
    <h1>Task Debug Information</h1>

    <div class="debug-section">
        <h2>Date Information</h2>
        <p><strong>Requested Date:</strong> {{ $date ?? 'Not provided' }}</p>
        <p><strong>Parsed Date:</strong> {{ $parsedDate ?? 'Not available' }}</p>
        <p><strong>Day of Week (Number):</strong> {{ $dayOfWeek ?? 'Not calculated' }}</p>
        <p><strong>Day Name:</strong> {{ $dayName ?? 'Not available' }}</p>
    </div>

    <div class="debug-section">
        <h2>All Active Categories</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Active</th>
                    <th>Sort Order</th>
                    <th>Total Tasks</th>
                </tr>
            </thead>
            <tbody>
                @if (isset($allCategories))
                    @foreach ($allCategories as $category)
                        <tr>
                            <td>{{ $category['id'] }}</td>
                            <td>{{ $category['name'] }}</td>
                            <td>{{ $category['is_active'] ? 'Yes' : 'No' }}</td>
                            <td>{{ $category['sort_order'] }}</td>
                            <td>{{ $category['task_count'] }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5">No categories data available</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="debug-section">
        <h2>All Active Tasks</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Days of Week</th>
                    <th>Active</th>
                    <th>Scheduled Today?</th>
                </tr>
            </thead>
            <tbody>
                @if (isset($allTasks))
                    @foreach ($allTasks as $task)
                        <tr style="background-color: {{ $task['scheduled_today'] ? '#e8f5e8' : '#f8f8f8' }}">
                            <td>{{ $task['id'] }}</td>
                            <td>{{ $task['title'] }}</td>
                            <td>{{ $task['category'] }}</td>
                            <td class="json">{{ json_encode($task['days_of_week']) }}</td>
                            <td>{{ $task['is_active'] ? 'Yes' : 'No' }}</td>
                            <td>{{ $task['scheduled_today'] ? 'YES' : 'No' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6">No tasks data available</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="debug-section">
        <h2>Tasks Scheduled for {{ $dayName ?? 'This Day' }}</h2>
        @if (isset($scheduledTasks) && count($scheduledTasks) > 0)
            @foreach ($scheduledTasks as $task)
                <div class="task-item">
                    <h4>{{ $task['title'] }}</h4>
                    <p><strong>Category:</strong> {{ $task['category'] }}</p>
                    <p><strong>Time:</strong> {{ $task['start_time'] ?? 'N/A' }} - {{ $task['end_time'] ?? 'N/A' }}
                    </p>
                    <p><strong>Days of Week:</strong> <span
                            class="json">{{ json_encode($task['days_of_week']) }}</span></p>
                    <p><strong>Active:</strong> {{ $task['is_active'] ? 'Yes' : 'No' }}</p>
                </div>
            @endforeach
        @else
            <p>No tasks are scheduled for this day.</p>
        @endif
    </div>

    <div class="debug-section">
        <h2>Existing Task Completions</h2>
        @if (isset($existingCompletions) && count($existingCompletions) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Completion ID</th>
                        <th>Task</th>
                        <th>Completed</th>
                        <th>Status</th>
                        <th>Quality Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($existingCompletions as $completion)
                        <tr>
                            <td>{{ $completion['id'] }}</td>
                            <td>{{ $completion['task_title'] }}</td>
                            <td>{{ $completion['is_completed'] ? 'Yes' : 'No' }}</td>
                            <td>{{ $completion['status'] }}</td>
                            <td>{{ $completion['quality_score'] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No task completions found for this date.</p>
        @endif
    </div>

    <div class="debug-section">
        <h2>Recommendations</h2>
        @if (isset($scheduledTasks) && count($scheduledTasks) === 0)
            <div style="background-color: #fff3cd; padding: 10px; border: 1px solid #ffeaa7;">
                <h3>No tasks scheduled for this day</h3>
                <p>Possible issues:</p>
                <ul>
                    <li>Tasks might not be seeded in the database</li>
                    <li>Day of week calculation might be incorrect</li>
                    <li>JSON format in days_of_week column might be wrong</li>
                    <li>Tasks might be set to inactive (is_active = false)</li>
                </ul>

                <h4>Try these solutions:</h4>
                <ol>
                    <li>Run the seeder: <code>php artisan db:seed --class=RoutineSeeder</code></li>
                    <li>Check if tasks exist: Go to your database and verify the routine_tasks table has data</li>
                    <li>Verify day of week: Today is day {{ $dayOfWeek ?? 'unknown' }} (0=Sunday, 1=Monday, etc.)</li>
                    <li>Check JSON format: days_of_week should contain [1,2,3,4,5] for weekdays</li>
                </ol>
            </div>
        @endif
    </div>

    <div style="margin-top: 30px;">
        <a href="/routine"
            style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">←
            Back to Routine</a>
        <a href="/routine?date={{ today()->toDateString() }}"
            style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">View
            Today</a>
    </div>
</body>

</html>
