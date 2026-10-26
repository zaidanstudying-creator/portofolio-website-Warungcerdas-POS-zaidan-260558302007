<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ToppingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');

    // Password Reset
    Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->middleware('throttle:forgot-password')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'updatePassword'])->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/welcome');
    Route::view('/welcome', 'welcome')->name('welcome');
    Route::get('/store-logo', [SettingController::class, 'storeLogo'])->name('settings.store-logo');
    Route::get('/store-status', [SettingController::class, 'status'])->name('store.status');

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/qris-image', [PosController::class, 'qrisImage'])->name('pos.qris-image');
    Route::get('/toppings/{topping}/image', [ToppingController::class, 'image'])->name('toppings.image');
    Route::get('/products/{product}/image', [ProductController::class, 'image'])->name('products.image');

    Route::post('/promo/validate', [PromoController::class, 'validatePromo'])->middleware('throttle:promo-validate')->name('promo.validate');

    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:orders-store')->name('orders.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('throttle:orders-status')->name('orders.status');
    Route::get('/orders/{order}/struk', [OrderController::class, 'receipt'])->name('orders.receipt');

    Route::middleware(['auth', 'active', 'role:admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');

        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('products', ProductController::class)->except('show');
        Route::resource('toppings', ToppingController::class)->except('show');
        Route::resource('promos', PromoController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');

        Route::get('/settings/qris', [SettingController::class, 'edit'])->name('settings.qris.edit');
        Route::put('/settings/qris', [SettingController::class, 'update'])->name('settings.qris.update');

        Route::get('/settings/store-profile', [SettingController::class, 'editStoreProfile'])->name('settings.store-profile.edit');
        Route::put('/settings/store-profile', [SettingController::class, 'updateStoreProfile'])->name('settings.store-profile.update');
        Route::put('/settings/store-profile/open', [SettingController::class, 'openStore'])->name('settings.store-profile.open');
        Route::put('/settings/store-profile/close', [SettingController::class, 'closeStore'])->name('settings.store-profile.close');

        // Finance Module
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('/finance/export', [FinanceController::class, 'export'])->name('finance.export');

        Route::resource('finance/kategori', FinanceController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->names([
                'index' => 'finance.kategori.index',
                'create' => 'finance.kategori.create',
                'store' => 'finance.kategori.store',
                'edit' => 'finance.kategori.edit',
                'update' => 'finance.kategori.update',
                'destroy' => 'finance.kategori.destroy',
            ])
            ->parameters(['finance/kategori' => 'kategori_pengeluaran']);

        Route::get('/finance/pengeluaran', [FinanceController::class, 'pengeluaranIndex'])->name('finance.pengeluaran.index');
        Route::get('/finance/pengeluaran/create', [FinanceController::class, 'pengeluaranCreate'])->name('finance.pengeluaran.create');
        Route::post('/finance/pengeluaran', [FinanceController::class, 'pengeluaranStore'])->name('finance.pengeluaran.store');
        Route::get('/finance/pengeluaran/{pengeluaran}/edit', [FinanceController::class, 'pengeluaranEdit'])->name('finance.pengeluaran.edit');
        Route::put('/finance/pengeluaran/{pengeluaran}', [FinanceController::class, 'pengeluaranUpdate'])->name('finance.pengeluaran.update');
        Route::delete('/finance/pengeluaran/{pengeluaran}', [FinanceController::class, 'pengeluaranDestroy'])->name('finance.pengeluaran.destroy');

        Route::get('/finance/pemasukan-lain', [FinanceController::class, 'pemasukanLainIndex'])->name('finance.pemasukan-lain.index');
        Route::get('/finance/pemasukan-lain/create', [FinanceController::class, 'pemasukanLainCreate'])->name('finance.pemasukan-lain.create');
        Route::post('/finance/pemasukan-lain', [FinanceController::class, 'pemasukanLainStore'])->name('finance.pemasukan-lain.store');
        Route::get('/finance/pemasukan-lain/{pemasukanLain}/edit', [FinanceController::class, 'pemasukanLainEdit'])->name('finance.pemasukan-lain.edit');
        Route::put('/finance/pemasukan-lain/{pemasukanLain}', [FinanceController::class, 'pemasukanLainUpdate'])->name('finance.pemasukan-lain.update');
        Route::delete('/finance/pemasukan-lain/{pemasukanLain}', [FinanceController::class, 'pemasukanLainDestroy'])->name('finance.pemasukan-lain.destroy');

        // Archive Module
        Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
        Route::get('/archive/preview', [ArchiveController::class, 'preview'])->name('archive.preview');
        Route::post('/archive', [ArchiveController::class, 'create'])->name('archive.create');
        Route::get('/archive/{arsip}/download', [ArchiveController::class, 'download'])->name('archive.download');
        Route::delete('/archive/{arsip}', [ArchiveController::class, 'destroy'])->name('archive.destroy');
    });
});
