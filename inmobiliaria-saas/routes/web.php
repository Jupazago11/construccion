<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetMediaController;
use App\Http\Controllers\AssetNoveltyController;
use App\Http\Controllers\AssetNoveltyTypeController;
use App\Http\Controllers\AssetTypeController;
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

    Route::resource('assets', AssetController::class)->except('show');
    Route::get('asset-types', [AssetTypeController::class, 'index'])->name('asset-types.index');
    Route::post('asset-types', [AssetTypeController::class, 'store'])->name('asset-types.store');
    Route::patch('asset-types/{assetType}', [AssetTypeController::class, 'update'])->name('asset-types.update');
    Route::delete('asset-types/{assetType}', [AssetTypeController::class, 'destroy'])->name('asset-types.destroy');
    Route::get('asset-novelty-types', [AssetNoveltyTypeController::class, 'index'])->name('asset-novelty-types.index');
    Route::post('asset-novelty-types', [AssetNoveltyTypeController::class, 'store'])->name('asset-novelty-types.store');
    Route::patch('asset-novelty-types/{assetNoveltyType}', [AssetNoveltyTypeController::class, 'update'])->name('asset-novelty-types.update');
    Route::delete('asset-novelty-types/{assetNoveltyType}', [AssetNoveltyTypeController::class, 'destroy'])->name('asset-novelty-types.destroy');

    Route::prefix('assets/{asset}')->name('assets.')->group(function () {
        Route::get('media', [AssetMediaController::class, 'index'])->name('media.index');
        Route::post('media', [AssetMediaController::class, 'store'])->name('media.store');
        Route::get('media/{media}/preview', [AssetMediaController::class, 'preview'])->name('media.preview');
        Route::delete('media/{media}', [AssetMediaController::class, 'destroy'])->name('media.destroy');

        Route::get('novelties/create', [AssetNoveltyController::class, 'create'])->name('novelties.create');
        Route::post('novelties', [AssetNoveltyController::class, 'store'])->name('novelties.store');
        Route::get('novelties/{novelty}/edit', [AssetNoveltyController::class, 'edit'])->name('novelties.edit');
        Route::patch('novelties/{novelty}', [AssetNoveltyController::class, 'update'])->name('novelties.update');
        Route::delete('novelties/{novelty}', [AssetNoveltyController::class, 'destroy'])->name('novelties.destroy');
    });

    Route::patch('expenses/{expense}/status', [ExpenseController::class, 'updateStatus'])->name('expenses.status');
    Route::resource('expenses', ExpenseController::class)->except('show');

    Route::prefix('expenses/{expense}')->name('expenses.')->group(function () {
        Route::get('attachments', [ExpenseAttachmentController::class, 'index'])->name('attachments.index');
        Route::post('attachments', [ExpenseAttachmentController::class, 'store'])->name('attachments.store');
        Route::get('attachments/{attachment}/preview', [ExpenseAttachmentController::class, 'preview'])->name('attachments.preview');
        Route::get('attachments/{attachment}/download', [ExpenseAttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('attachments/{attachment}', [ExpenseAttachmentController::class, 'destroy'])->name('attachments.destroy');
    });

    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        Route::get('categories', [ProjectCategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/copy', [ProjectCategoryController::class, 'createCopy'])->name('categories.copy.create');
        Route::post('categories/copy', [ProjectCategoryController::class, 'storeCopy'])->name('categories.copy.store');

        Route::patch('categories/reorder', [ProjectCategoryController::class, 'reorderCategories'])->name('categories.reorder');
        Route::get('categories/create', [ProjectCategoryController::class, 'createCategory'])->name('categories.create');
        Route::post('categories', [ProjectCategoryController::class, 'storeCategory'])->name('categories.store');
        Route::get('categories/{category}/edit', [ProjectCategoryController::class, 'editCategory'])->name('categories.edit');
        Route::patch('categories/{category}', [ProjectCategoryController::class, 'updateCategory'])->name('categories.update');
        Route::patch('categories/{category}/status', [ProjectCategoryController::class, 'updateCategoryStatus'])->name('categories.status');
        Route::delete('categories/{category}', [ProjectCategoryController::class, 'destroyCategory'])->name('categories.destroy');

        Route::patch('subcategories/reorder', [ProjectCategoryController::class, 'reorderSubcategories'])->name('subcategories.reorder');
        Route::get('subcategories/create', [ProjectCategoryController::class, 'createSubcategory'])->name('subcategories.create');
        Route::post('subcategories', [ProjectCategoryController::class, 'storeSubcategory'])->name('subcategories.store');
        Route::get('subcategories/{subcategory}/edit', [ProjectCategoryController::class, 'editSubcategory'])->name('subcategories.edit');
        Route::patch('subcategories/{subcategory}', [ProjectCategoryController::class, 'updateSubcategory'])->name('subcategories.update');
        Route::patch('subcategories/{subcategory}/status', [ProjectCategoryController::class, 'updateSubcategoryStatus'])->name('subcategories.status');
        Route::delete('subcategories/{subcategory}', [ProjectCategoryController::class, 'destroySubcategory'])->name('subcategories.destroy');

        Route::patch('auxiliaries/reorder', [ProjectCategoryController::class, 'reorderAuxiliaries'])->name('auxiliaries.reorder');
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
