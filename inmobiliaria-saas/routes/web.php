<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Asset2Controller;
use App\Http\Controllers\Asset2TypeController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\Provider2Controller;
use App\Http\Controllers\Provider2TypeController;
use App\Http\Controllers\AssetMediaController;
use App\Http\Controllers\AssetNoveltyController;
use App\Http\Controllers\AssetNoveltyTypeController;
use App\Http\Controllers\AssetTypeController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseAttachmentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductCatalogController;
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProviderTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
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
    Route::get('companies/{company}/logo', [CompanyController::class, 'logo'])->name('companies.logo');
    Route::post('companies/{company}/logo', [CompanyController::class, 'storeLogo'])->name('companies.logo.store');
    Route::resource('companies', CompanyController::class);

    Route::patch('projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status');
    Route::resource('projects', ProjectController::class);

    Route::patch('providers2/{provider2}/status', [Provider2Controller::class, 'updateStatus'])->name('providers2.status');
    Route::resource('providers2', Provider2Controller::class)->except('show')->parameters(['providers2' => 'provider2']);
    Route::get('provider2-types', [Provider2TypeController::class, 'index'])->name('provider2-types.index');
    Route::post('provider2-types', [Provider2TypeController::class, 'store'])->name('provider2-types.store');
    Route::patch('provider2-types/{provider2Type}', [Provider2TypeController::class, 'update'])->name('provider2-types.update');
    Route::delete('provider2-types/{provider2Type}', [Provider2TypeController::class, 'destroy'])->name('provider2-types.destroy');

    Route::patch('providers/{provider}/status', [ProviderController::class, 'updateStatus'])->name('providers.status');
    Route::resource('providers', ProviderController::class)->except('show');

    Route::get('provider-types', [ProviderTypeController::class, 'index'])->name('provider-types.index');
    Route::post('provider-types', [ProviderTypeController::class, 'store'])->name('provider-types.store');
    Route::patch('provider-types/{providerType}', [ProviderTypeController::class, 'update'])->name('provider-types.update');
    Route::patch('provider-types/{providerType}/status', [ProviderTypeController::class, 'updateStatus'])->name('provider-types.status');
    Route::delete('provider-types/{providerType}', [ProviderTypeController::class, 'destroy'])->name('provider-types.destroy');

    Route::get('maestras/productos', [ProductCatalogController::class, 'index'])->name('product-catalog.index');
    Route::post('maestras/productos/grupos', [ProductCatalogController::class, 'storeGroup'])->name('product-catalog.groups.store');
    Route::patch('maestras/productos/grupos/{productGroup}', [ProductCatalogController::class, 'updateGroup'])->name('product-catalog.groups.update');
    Route::patch('maestras/productos/grupos/{productGroup}/status', [ProductCatalogController::class, 'statusGroup'])->name('product-catalog.groups.status');
    Route::delete('maestras/productos/grupos/{productGroup}', [ProductCatalogController::class, 'destroyGroup'])->name('product-catalog.groups.destroy');
    Route::post('maestras/productos/subgrupos', [ProductCatalogController::class, 'storeSubgroup'])->name('product-catalog.subgroups.store');
    Route::patch('maestras/productos/subgrupos/{productSubgroup}', [ProductCatalogController::class, 'updateSubgroup'])->name('product-catalog.subgroups.update');
    Route::patch('maestras/productos/subgrupos/{productSubgroup}/status', [ProductCatalogController::class, 'statusSubgroup'])->name('product-catalog.subgroups.status');
    Route::delete('maestras/productos/subgrupos/{productSubgroup}', [ProductCatalogController::class, 'destroySubgroup'])->name('product-catalog.subgroups.destroy');
    Route::post('maestras/productos/productos', [ProductCatalogController::class, 'storeProduct'])->name('product-catalog.products.store');
    Route::patch('maestras/productos/productos/{product}', [ProductCatalogController::class, 'updateProduct'])->name('product-catalog.products.update');
    Route::patch('maestras/productos/productos/{product}/status', [ProductCatalogController::class, 'statusProduct'])->name('product-catalog.products.status');
    Route::delete('maestras/productos/productos/{product}', [ProductCatalogController::class, 'destroyProduct'])->name('product-catalog.products.destroy');

    Route::resource('assets2', Asset2Controller::class)->except('show')->parameters(['assets2' => 'asset2']);
    Route::get('asset2-types', [Asset2TypeController::class, 'index'])->name('asset2-types.index');
    Route::post('asset2-types', [Asset2TypeController::class, 'store'])->name('asset2-types.store');
    Route::patch('asset2-types/{asset2Type}', [Asset2TypeController::class, 'update'])->name('asset2-types.update');
    Route::delete('asset2-types/{asset2Type}', [Asset2TypeController::class, 'destroy'])->name('asset2-types.destroy');

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
    Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status');
    Route::post('invoices/{invoice}/attachments', [InvoiceController::class, 'storeAttachment'])->name('invoices.attachments.store');
    Route::get('invoices/{invoice}/attachments/{attachment}/preview', [InvoiceController::class, 'previewAttachment'])->name('invoices.attachments.preview');
    Route::delete('invoices/{invoice}/attachments/{attachment}', [InvoiceController::class, 'destroyAttachment'])->name('invoices.attachments.destroy');
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

    Route::patch('purchases/{purchase}/status', [PurchaseController::class, 'updateStatus'])->name('purchases.status');
    Route::resource('purchases', PurchaseController::class)->except('show');

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
