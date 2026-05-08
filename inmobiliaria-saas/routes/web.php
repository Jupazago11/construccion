<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseAttachmentController;
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'active.user'])
    ->name('dashboard');

Route::middleware(['auth', 'active.user'])->group(function () {
    Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::patch('companies/{company}/status', [CompanyController::class, 'updateStatus'])->name('companies.status');
    Route::resource('companies', CompanyController::class);

    Route::patch('projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status');
    Route::resource('projects', ProjectController::class);

    Route::patch('providers/{provider}/status', [ProviderController::class, 'updateStatus'])->name('providers.status');
    Route::resource('providers', ProviderController::class)->except('show');

    Route::patch('expenses/{expense}/status', [ExpenseController::class, 'updateStatus'])->name('expenses.status');
    Route::resource('expenses', ExpenseController::class)->except('show');

    Route::prefix('expenses/{expense}')->name('expenses.')->group(function () {
        Route::get('attachments', [ExpenseAttachmentController::class, 'index'])->name('attachments.index');
        Route::post('attachments', [ExpenseAttachmentController::class, 'store'])->name('attachments.store');
        Route::get('attachments/{attachment}/download', [ExpenseAttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('attachments/{attachment}', [ExpenseAttachmentController::class, 'destroy'])->name('attachments.destroy');
    });

    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        Route::get('categories', [ProjectCategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/copy', [ProjectCategoryController::class, 'createCopy'])->name('categories.copy.create');
        Route::post('categories/copy', [ProjectCategoryController::class, 'storeCopy'])->name('categories.copy.store');

        Route::get('categories/create', [ProjectCategoryController::class, 'createCategory'])->name('categories.create');
        Route::post('categories', [ProjectCategoryController::class, 'storeCategory'])->name('categories.store');
        Route::get('categories/{category}/edit', [ProjectCategoryController::class, 'editCategory'])->name('categories.edit');
        Route::patch('categories/{category}', [ProjectCategoryController::class, 'updateCategory'])->name('categories.update');
        Route::patch('categories/{category}/status', [ProjectCategoryController::class, 'updateCategoryStatus'])->name('categories.status');
        Route::delete('categories/{category}', [ProjectCategoryController::class, 'destroyCategory'])->name('categories.destroy');

        Route::get('subcategories/create', [ProjectCategoryController::class, 'createSubcategory'])->name('subcategories.create');
        Route::post('subcategories', [ProjectCategoryController::class, 'storeSubcategory'])->name('subcategories.store');
        Route::get('subcategories/{subcategory}/edit', [ProjectCategoryController::class, 'editSubcategory'])->name('subcategories.edit');
        Route::patch('subcategories/{subcategory}', [ProjectCategoryController::class, 'updateSubcategory'])->name('subcategories.update');
        Route::patch('subcategories/{subcategory}/status', [ProjectCategoryController::class, 'updateSubcategoryStatus'])->name('subcategories.status');
        Route::delete('subcategories/{subcategory}', [ProjectCategoryController::class, 'destroySubcategory'])->name('subcategories.destroy');

        Route::get('auxiliaries/create', [ProjectCategoryController::class, 'createAuxiliary'])->name('auxiliaries.create');
        Route::post('auxiliaries', [ProjectCategoryController::class, 'storeAuxiliary'])->name('auxiliaries.store');
        Route::get('auxiliaries/{auxiliary}/edit', [ProjectCategoryController::class, 'editAuxiliary'])->name('auxiliaries.edit');
        Route::patch('auxiliaries/{auxiliary}', [ProjectCategoryController::class, 'updateAuxiliary'])->name('auxiliaries.update');
        Route::patch('auxiliaries/{auxiliary}/status', [ProjectCategoryController::class, 'updateAuxiliaryStatus'])->name('auxiliaries.status');
        Route::delete('auxiliaries/{auxiliary}', [ProjectCategoryController::class, 'destroyAuxiliary'])->name('auxiliaries.destroy');
    });

    Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
    Route::resource('users', UserController::class);
});

require __DIR__.'/auth.php';
