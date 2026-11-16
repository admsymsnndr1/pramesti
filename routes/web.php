<?php

use App\Http\Controllers\AdminProjectDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliverableController;
use App\Http\Controllers\Employee\TaskController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WbsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::controller(AuthController::class)->middleware('loggedin')->group(function () {
    Route::get('login', 'loginView')->name('login');
    Route::post('login', 'login')->name('login.check');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // ---------------------------
    // Dashboards
    // ---------------------------
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard-project', [AdminProjectDashboardController::class, 'index'])
        ->name('dashboard.admin-project');

    // ---------------------------
    // Projects
    // ---------------------------
    Route::resource('projects', ProjectController::class);

    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        Route::get('wbs', [ProjectController::class, 'wbs'])->name('wbs');
        Route::get('timeline', [ProjectController::class, 'timeline'])->name('timeline');
        Route::get('deliverables', [ProjectController::class, 'deliverables'])->name('deliverables');
        Route::get('report', [ProjectController::class, 'report'])->name('report');
    });

    // ---------------------------
    // WBS
    // ---------------------------
    Route::controller(WbsController::class)->prefix('wbs')->name('wbs.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{id}/edit', 'edit')->name('edit');
        Route::put('{id}', 'update')->name('update');
        Route::delete('{id}', 'destroy')->name('destroy');
    });

    // ---------------------------
    // Users
    // ---------------------------
    Route::controller(UsersController::class)->prefix('users')->name('users.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{user}/edit', 'edit')->name('edit');
        Route::put('{user}', 'update')->name('update');
        Route::delete('{user}', 'destroy')->name('destroy');
        Route::post('{user}/login-as', 'loginAs')->name('loginAs');
        Route::post('logout-as', 'logoutAs')->name('logoutAs');
    });

    // ---------------------------
    // Roles & permissions
    // ---------------------------
    Route::get('roles/export', [RoleController::class, 'export'])->name('roles.export');
    Route::resource('roles', RoleController::class)->except('show');

    // ---------------------------
    // Deliverables
    // ---------------------------
    Route::prefix('tasks/{task}')->name('deliverables.')->group(function () {
        Route::get('deliverables/create', [DeliverableController::class, 'create'])->name('create');
        Route::post('deliverables', [DeliverableController::class, 'store'])->name('store');
    });

    Route::prefix('deliverables')->name('deliverables.')->group(function () {
        Route::get('{deliverable}/edit', [DeliverableController::class, 'edit'])->name('edit');
        Route::put('{deliverable}', [DeliverableController::class, 'update'])->name('update');
        Route::delete('{deliverable}', [DeliverableController::class, 'destroy'])->name('destroy');
        Route::post('{deliverable}/upload', [DeliverableController::class, 'uploadFile'])->name('upload');
    });

    // ---------------------------
    // Tasks
    // ---------------------------
    Route::controller(TaskController::class)->prefix('employee/tasks')->name('employee.tasks.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('project/{project}', 'detail')->name('detail');
        Route::get('task/{task}', 'detailTask')->name('detail-task');
        Route::get('{task}', 'show')->name('show');
        Route::post('{task}/assign', 'assign')->name('assign');
    });
});
