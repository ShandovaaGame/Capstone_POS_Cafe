<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Cashier\CashierOrderController;
use App\Http\Controllers\Cashier\CashierPendingCountController;
use App\Http\Controllers\Cashier\CashierPesananAktifController;
use App\Http\Controllers\Cashier\CashierPesananBaruController;
use App\Http\Controllers\Cashier\CashierDashboardController;
use App\Http\Controllers\Cashier\CashierRiwayatController;
use App\Http\Controllers\Customer\CustomerMenuController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Customer\CustomerPaymentController;
use App\Http\Controllers\Kitchen\KitchenController;
use App\Http\Controllers\ReceiptController;
use App\Models\Order;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Cashier auth
Route::get('/kasir/login', [AuthController::class, 'showLogin'])->name('kasir.login');
Route::post('/kasir/login', [AuthController::class, 'login'])->name('kasir.login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Kasir pages
Route::prefix('kasir')->middleware(['auth:web', 'role:cashier,admin'])->group(function () {
    Route::get('/pesanan-baru', [CashierPesananBaruController::class, 'index'])->name('kasir.pesanan-baru');
    Route::post('/pesanan-baru', [CashierPesananBaruController::class, 'store'])->name('kasir.pesanan-baru.simpan');
    Route::get('/pesanan-aktif', [CashierPesananAktifController::class, 'index'])->name('kasir.pesanan-aktif');
    Route::get('/riwayat-pesanan', [CashierRiwayatController::class, 'index'])->name('kasir.riwayat-pesanan');
    Route::get('/pesanan/{order}', [CashierOrderController::class, 'show'])->name('kasir.pesanan.detail');
    Route::patch('/pesanan/{order}/status', [CashierOrderController::class, 'updateStatus'])->name('kasir.pesanan.status');
    Route::patch('/pesanan/{order}/konfirmasi-tunai', [CashierOrderController::class, 'confirmCash'])->name('kasir.pesanan.konfirmasi-tunai');
    Route::patch('/pesanan/{order}/konfirmasi-bayar', [CashierOrderController::class, 'confirmPayment'])->name('kasir.pesanan.konfirmasi-bayar');
    Route::patch('/pesanan/{order}/konfirmasi-qris', [CashierOrderController::class, 'confirmQris'])->name('kasir.pesanan.konfirmasi-qris');
    Route::patch('/pesanan/{order}/tolak-qris', [CashierOrderController::class, 'rejectQris'])->name('kasir.pesanan.tolak-qris');
    Route::post('/pesanan/{order}/qris/accept', [CashierOrderController::class, 'acceptQrisProof'])->name('kasir.pesanan.qris.accept');
    Route::post('/pesanan/{order}/qris/reject', [CashierOrderController::class, 'rejectQrisProof'])->name('kasir.pesanan.qris.reject');
    Route::post('/pesanan/{order}/qris/resubmit', [CashierOrderController::class, 'requestQrisResubmit'])->name('kasir.pesanan.qris.resubmit');

    Route::get('/dashboard', [CashierDashboardController::class, 'index'])->name('kasir.dashboard');
    Route::patch('/pesanan/{order}/cancel', [CashierOrderController::class, 'cancel'])->name('kasir.pesanan.cancel');
    Route::get('/profil', function () { return Inertia::render('Kasir/Profil', ['user' => auth()->user()]); })->name('kasir.profil');

    Route::get('/pesanan-menunggu', CashierPendingCountController::class)->name('kasir.pesanan-menunggu');
});

// Kitchen auth
Route::get('/dapur/login', [AuthController::class, 'showKitchenLogin'])->name('dapur.login');
Route::post('/dapur/login', [AuthController::class, 'login'])->name('dapur.login.attempt');

// Kitchen
Route::middleware(['auth:kitchen', 'role:kitchen,admin'])->group(function () {
    Route::get('/dapur', [KitchenController::class, 'index'])->name('dapur.beranda');
    Route::get('/dapur/riwayat-pesanan', [KitchenController::class, 'riwayat'])->name('dapur.riwayat-pesanan');
    Route::patch('/dapur/pesanan/{order}/proses', [KitchenController::class, 'bump'])->name('dapur.proses');
});

// Customer — entry point via QR scan
Route::get('/pesan', [CustomerMenuController::class, 'showIdentitas'])->name('pelanggan.identitas');

// Customer pages
Route::prefix('pelanggan')->group(function () {
    Route::get('/menu', [CustomerMenuController::class, 'index'])->name('pelanggan.menu');
    Route::get('/keranjang', fn () => Inertia::render('Pelanggan/Cart/Index', []))->name('pelanggan.keranjang');
    Route::get('/pesanan/{code}/status', [CustomerOrderController::class, 'status'])->name('pelanggan.pesanan.status');

    // Payment flow
    Route::get('/bayar/{order:order_code}/pilih', [CustomerPaymentController::class, 'showChoose'])->name('pelanggan.bayar.pilih');
    Route::get('/bayar/{order:order_code}/tunai-status', [CustomerPaymentController::class, 'showCashStatus'])->name('pelanggan.bayar.tunai-status');
    Route::get('/bayar/{order:order_code}/qris', [CustomerPaymentController::class, 'showQrisUpload'])->name('pelanggan.bayar.qris-upload');
    Route::get('/bayar/{order:order_code}/qris-status', [CustomerPaymentController::class, 'showQrisStatus'])->name('pelanggan.bayar.qris-status');
});

// Customer routes
Route::prefix('customer')->group(function () {
    Route::get('/login', [AuthController::class, 'showCustomerLogin'])->name('customer.login');
    Route::post('/login', [AuthController::class, 'customerLogin'])->name('customer.login.attempt');
});

Route::prefix('customer')->middleware('auth:web')->group(function () {
    Route::get('/menu', [CustomerMenuController::class, 'index'])->name('customer.menu');
    Route::get('/identitas', [CustomerMenuController::class, 'showIdentitas'])->name('customer.identitas');
    Route::post('/identitas', [CustomerMenuController::class, 'submitIdentitas'])->name('customer.identitas.submit');
    Route::post('/order/store', [CustomerOrderController::class, 'store'])->name('customer.order.store');
    Route::get('/order/{order}/payment', [CustomerPaymentController::class, 'showChoose'])->name('customer.payment.choose');
    Route::post('/order/{order}/payment/choose', [CustomerPaymentController::class, 'choose'])->name('customer.payment.choose.post');
    Route::get('/order/{order}/payment/qris', [CustomerPaymentController::class, 'showQris'])->name('customer.payment.qris');
    Route::post('/order/{order}/payment/qris', [CustomerPaymentController::class, 'uploadQris'])->name('customer.payment.qris.upload');
    Route::get('/order/{order}/status', [CustomerOrderController::class, 'showStatus'])->name('customer.order.status');
    Route::get('/riwayat', [CustomerOrderController::class, 'riwayat'])->name('customer.riwayat');
});

// Receipt (public — no auth required)
Route::get('/receipt/{order:code}', fn (Order $order) => redirect()->route('receipt.show-by-uuid', ['order' => $order->uuid], 301));
Route::get('/struk-pesanan/{order:uuid}', [ReceiptController::class, 'showByUuid'])->name('receipt.show-by-uuid');
