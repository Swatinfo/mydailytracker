<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Dashboard Routes
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

// Dashboard AJAX Routes
Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/data', [DashboardController::class, 'getData'])->name('data');
    Route::post('/energy', [DashboardController::class, 'updateEnergyLevels'])->name('energy');
    Route::post('/reflection', [DashboardController::class, 'updateReflection'])->name('reflection');
});

// Routine Management Routes
Route::prefix('routine')->name('routine.')->group(function () {
    Route::get('/', [RoutineController::class, 'show'])->name('show');
    Route::get('/data', [RoutineController::class, 'getRoutineData'])->name('data');
    Route::get('/export', [RoutineController::class, 'exportData'])->name('export');
    Route::get('/debug', [RoutineController::class, 'debugTasks'])->name('debug');

    // Task Completion Routes
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::post('/{taskCompletion}/update', [RoutineController::class, 'updateTaskCompletion'])->name('update');
        Route::post('/{taskCompletion}/start', [RoutineController::class, 'startTask'])->name('start');
        Route::post('/{taskCompletion}/complete', [RoutineController::class, 'completeTask'])->name('complete');
        Route::post('/{taskCompletion}/skip', [RoutineController::class, 'skipTask'])->name('skip');
        Route::post('/{taskCompletion}/postpone', [RoutineController::class, 'postponeTask'])->name('postpone');
        Route::post('/bulk-update', [RoutineController::class, 'bulkUpdateTasks'])->name('bulk-update');
    });
});

// Analytics Routes
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    Route::get('/data', [AnalyticsController::class, 'getData'])->name('data');
    Route::get('/export', [AnalyticsController::class, 'exportAnalytics'])->name('export');
});

// Book Management Routes
Route::resource('books', BookController::class);

// Additional Book Routes
Route::prefix('books')->name('books.')->group(function () {
    // Reading Session Management
    Route::post('/{book}/sessions', [BookController::class, 'addSession'])->name('sessions.add');
    Route::post('/{book}/progress', [BookController::class, 'updateProgress'])->name('progress.update');
    Route::post('/{book}/start', [BookController::class, 'startReading'])->name('start');
    Route::post('/{book}/complete', [BookController::class, 'markCompleted'])->name('complete');

    // Quick Actions for CEO Reading
    Route::post('/log-today', [BookController::class, 'logTodaySession'])->name('log-today');
    Route::get('/stats', [BookController::class, 'getReadingStats'])->name('stats');
});

// API Routes for AJAX calls
Route::prefix('api')->name('api.')->group(function () {
    // Dashboard API
    Route::get('/dashboard/{type}', [DashboardController::class, 'getData'])->name('dashboard.data');

    // Routine API
    Route::get('/routine/today', function () {
        return app(RoutineController::class)->show(request());
    })->name('routine.today');

    // Analytics API
    Route::get('/analytics/{type}', [AnalyticsController::class, 'getData'])->name('analytics.data');

    // Books API
    Route::get('/books/current', [BookController::class, 'getReadingStats'])->name('books.current');
});

// Additional utility routes
Route::prefix('utils')->name('utils.')->group(function () {
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'version' => '1.0.0',
        ]);
    })->name('health');

    // Clear cache (for development)
    Route::get('/clear-cache', function () {
        if (app()->environment('local')) {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            return response()->json(['message' => 'Cache cleared successfully']);
        }
        return response()->json(['message' => 'Not allowed in production'], 403);
    })->name('clear-cache');
});

// Redirect old URLs for backward compatibility
Route::redirect('/home', '/dashboard');
Route::redirect('/routine/today', '/routine');
Route::redirect('/tasks', '/routine');

// Catch-all route for SPA-like behavior (optional)
Route::fallback(function () {
    return redirect()->route('dashboard');
});