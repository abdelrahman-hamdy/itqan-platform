<?php

/*
|--------------------------------------------------------------------------
| Lesson & Course Learning Routes
|--------------------------------------------------------------------------
| Lesson viewing, progress tracking, bookmarks, notes, and course learning.
| These routes MUST COME BEFORE general course routes to avoid conflicts.
*/

use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\RecordedCourseController;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Lesson Viewing & Progress Routes (ID-based)
    |--------------------------------------------------------------------------
    */

    // Specific routes first to avoid conflicts
    Route::get('/courses/{courseId}/lessons/{lessonId}/progress', [LessonController::class, 'getProgress'])->name('lessons.progress.get')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/progress', [LessonController::class, 'updateProgress'])->name('lessons.progress.update')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/complete', [LessonController::class, 'markComplete'])->name('lessons.complete')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::get('/courses/{courseId}/lessons/{lessonId}', [LessonController::class, 'show'])->name('lessons.show')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);

    /*
    |--------------------------------------------------------------------------
    | Lesson Interactions
    |--------------------------------------------------------------------------
    */

    Route::post('/courses/{courseId}/lessons/{lessonId}/bookmark', [LessonController::class, 'addBookmark'])->name('lessons.bookmark')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::delete('/courses/{courseId}/lessons/{lessonId}/bookmark', [LessonController::class, 'removeBookmark'])->name('lessons.unbookmark')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/notes', [LessonController::class, 'addNote'])->name('lessons.notes.add')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::get('/courses/{courseId}/lessons/{lessonId}/notes', [LessonController::class, 'getNotes'])->name('lessons.notes.get')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/rate', [LessonController::class, 'rate'])->name('lessons.rate')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);

    /*
    |--------------------------------------------------------------------------
    | Lesson Resources
    |--------------------------------------------------------------------------
    */

    Route::get('/courses/{courseId}/lessons/{lessonId}/transcript', [LessonController::class, 'getTranscript'])->name('lessons.transcript')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::get('/courses/{courseId}/lessons/{lessonId}/materials', [LessonController::class, 'downloadMaterials'])->name('lessons.materials')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::get('/courses/{courseId}/lessons/{lessonId}/video', [LessonController::class, 'serveVideo'])->name('lessons.video')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::options('/courses/{courseId}/lessons/{lessonId}/video', [LessonController::class, 'serveVideoOptions'])->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);

    /*
    |--------------------------------------------------------------------------
    | Course Progress Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/courses/{courseId}/progress', [RecordedCourseController::class, 'getProgress'])
        ->name('courses.progress')
        ->where('courseId', '[0-9]+');

    /*
    |--------------------------------------------------------------------------
    | API Progress Routes (Web Middleware for Session Auth)
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth')->prefix('api')->group(function () {
        Route::get('/courses/{courseId}/progress', [ProgressController::class, 'getCourseProgress']);
        Route::get('/courses/{courseId}/lessons/{lessonId}/progress', [ProgressController::class, 'getLessonProgress']);
        Route::post('/courses/{courseId}/lessons/{lessonId}/progress', [ProgressController::class, 'updateLessonProgress']);
        Route::post('/courses/{courseId}/lessons/{lessonId}/complete', [ProgressController::class, 'markLessonComplete']);
    });
});
