<?php

use App\Http\Controllers\Api\AdminCourseController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course:slug}', [CourseController::class, 'show']);

    // Auth
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Signed video routes (no auth middleware — signature validates access)
    Route::get('/videos/{video}/stream/{file?}', [VideoController::class, 'stream'])
        ->where('file', '.*')
        ->name('videos.stream');
    Route::get('/videos/{video}/thumbnail', [VideoController::class, 'thumbnail'])
        ->name('videos.thumbnail');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/my-courses', [CourseController::class, 'myCourses']);

        Route::get('/lessons/{lesson}', [LessonController::class, 'show']);
        Route::post('/lessons/{lesson}/complete', [LessonController::class, 'markComplete']);
        Route::post('/lessons/{lesson}/progress', [LessonController::class, 'updateProgress']);

        Route::get('/videos/{video}', [VideoController::class, 'show']);
        Route::get('/videos/{video}/signed-url', [VideoController::class, 'signedUrl']);

        Route::post('/checkout/{course:slug}', [CheckoutController::class, 'createSession']);
        Route::post('/checkout/verify', [CheckoutController::class, 'verify']);

        // Admin API
        Route::middleware(EnsureUserIsAdmin::class)->prefix('admin')->group(function () {
            Route::get('/courses', [AdminCourseController::class, 'index']);
            Route::post('/courses', [AdminCourseController::class, 'store']);
            Route::put('/courses/{course}', [AdminCourseController::class, 'update']);
            Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy']);
            Route::post('/courses/{course}/sections', [AdminCourseController::class, 'storeSection']);
            Route::post('/sections/{section}/lessons', [AdminCourseController::class, 'storeLesson']);
            Route::post('/sections/{section}/reorder', [AdminCourseController::class, 'reorderLessons']);
            Route::post('/videos/upload', [VideoController::class, 'upload']);
        });
    });
});
