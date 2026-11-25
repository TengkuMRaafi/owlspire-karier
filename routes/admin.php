<?php

use App\Http\Controllers\Admin\AttemptController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BroadcastController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\CareerAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExamSetupController;
use App\Http\Controllers\Admin\LearningAdminController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Prefix /admin, nama admin.*, middleware web + auth + staff (lihat bootstrap/app.php).
// Tiap grup dijaga `area:...`; peran yang boleh masuk dibaca dari config/roles.php.

Route::middleware('area:dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware('area:reports')->group(function () {
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/unduh', [ReportController::class, 'download'])->name('reports.download');
});

Route::middleware('area:questions')->group(function () {
    Route::get('/soal', [QuestionController::class, 'index'])->name('questions.index');
    Route::get('/soal/tambah', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/soal', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('/soal/impor', [QuestionController::class, 'importForm'])->name('questions.import');
    Route::post('/soal/impor', [QuestionController::class, 'import'])->name('questions.import.store');
    Route::get('/soal/templat/{format}', [QuestionController::class, 'template'])->whereIn('format', ['json', 'csv'])->name('questions.template');
    Route::get('/soal/{question}', [QuestionController::class, 'show'])->whereNumber('question')->name('questions.show');
    Route::get('/soal/{question}/ubah', [QuestionController::class, 'edit'])->whereNumber('question')->name('questions.edit');
    Route::put('/soal/{question}', [QuestionController::class, 'update'])->whereNumber('question')->name('questions.update');
    Route::delete('/soal/{question}', [QuestionController::class, 'destroy'])->whereNumber('question')->name('questions.destroy');
});

Route::middleware('area:exam_setup')->group(function () {
    Route::get('/jenis-ujian', [ExamSetupController::class, 'index'])->name('setup.index');
    Route::get('/jenis-ujian/tambah', [ExamSetupController::class, 'createType'])->name('setup.create');
    Route::post('/jenis-ujian', [ExamSetupController::class, 'storeType'])->name('setup.types.store');
    Route::get('/jenis-ujian/{examType}', [ExamSetupController::class, 'showType'])->whereNumber('examType')->name('setup.show');
    Route::get('/jenis-ujian/{examType}/ubah', [ExamSetupController::class, 'editType'])->whereNumber('examType')->name('setup.edit');
    Route::put('/jenis-ujian/{examType}', [ExamSetupController::class, 'updateType'])->name('setup.types.update');
    Route::delete('/jenis-ujian/{examType}', [ExamSetupController::class, 'destroyType'])->name('setup.destroy');
    Route::get('/jenis-ujian/{examType}/segmen/tambah', [ExamSetupController::class, 'createSegment'])->whereNumber('examType')->name('setup.segments.create');
    Route::post('/jenis-ujian/{examType}/segmen', [ExamSetupController::class, 'storeSegment'])->name('setup.segments.store');
    Route::get('/segmen/{segment}/ubah', [ExamSetupController::class, 'editSegment'])->whereNumber('segment')->name('setup.segments.edit');
    Route::put('/segmen/{segment}', [ExamSetupController::class, 'updateSegment'])->name('setup.segments.update');
    Route::delete('/segmen/{segment}', [ExamSetupController::class, 'destroySegment'])->name('setup.segments.destroy');
});

Route::middleware('area:careers')->group(function () {
    Route::get('/karier', [CareerAdminController::class, 'index'])->name('careers.index');
    Route::get('/karier/tambah', [CareerAdminController::class, 'create'])->name('careers.create');
    Route::post('/karier', [CareerAdminController::class, 'store'])->name('careers.store');
    Route::get('/karier/{career}', [CareerAdminController::class, 'show'])->whereNumber('career')->name('careers.show');
    Route::get('/karier/{career}/ubah', [CareerAdminController::class, 'edit'])->whereNumber('career')->name('careers.edit');
    Route::put('/karier/{career}', [CareerAdminController::class, 'update'])->whereNumber('career')->name('careers.update');
    Route::delete('/karier/{career}', [CareerAdminController::class, 'destroy'])->whereNumber('career')->name('careers.destroy');
});

Route::middleware('area:learning')->group(function () {
    Route::get('/belajar', [LearningAdminController::class, 'index'])->name('learning.index');
    Route::get('/belajar/tambah', [LearningAdminController::class, 'create'])->name('learning.create');
    Route::post('/belajar', [LearningAdminController::class, 'store'])->name('learning.store');
    Route::get('/belajar/{lesson}', [LearningAdminController::class, 'show'])->whereNumber('lesson')->name('learning.show');
    Route::get('/belajar/{lesson}/ubah', [LearningAdminController::class, 'edit'])->whereNumber('lesson')->name('learning.edit');
    Route::put('/belajar/{lesson}', [LearningAdminController::class, 'update'])->whereNumber('lesson')->name('learning.update');
    Route::post('/belajar/{lesson}/setujui', [LearningAdminController::class, 'approve'])->whereNumber('lesson')->name('learning.approve');
    Route::delete('/belajar/{lesson}', [LearningAdminController::class, 'destroy'])->whereNumber('lesson')->name('learning.destroy');
});

Route::middleware('area:attempts')->group(function () {
    Route::get('/hasil-ujian', [AttemptController::class, 'index'])->name('attempts.index');
    Route::get('/hasil-ujian/{attempt}', [AttemptController::class, 'show'])->whereNumber('attempt')->name('attempts.show');
    Route::delete('/hasil-ujian/{attempt}', [AttemptController::class, 'destroy'])->whereNumber('attempt')->name('attempts.destroy');
});

Route::middleware('area:orders')->group(function () {
    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::post('/pesanan/{order}/lunas', [OrderController::class, 'markPaid'])->name('orders.paid');
    Route::post('/pesanan/{order}/tolak', [OrderController::class, 'reject'])->name('orders.reject');
});

Route::middleware('area:broadcast')->group(function () {
    Route::get('/broadcast', [BroadcastController::class, 'index'])->name('broadcast.index');
    Route::get('/broadcast/buat', [BroadcastController::class, 'create'])->name('broadcast.create');
    Route::post('/broadcast', [BroadcastController::class, 'store'])->middleware('throttle:30,1')->name('broadcast.store');
    Route::post('/broadcast/pratinjau', [BroadcastController::class, 'preview'])->middleware('throttle:120,1')->name('broadcast.preview');
    Route::get('/broadcast/{broadcast}/ubah', [BroadcastController::class, 'edit'])->whereNumber('broadcast')->name('broadcast.edit');
    Route::put('/broadcast/{broadcast}', [BroadcastController::class, 'update'])->middleware('throttle:30,1')->whereNumber('broadcast')->name('broadcast.update');
    Route::delete('/broadcast/{broadcast}', [BroadcastController::class, 'destroy'])->whereNumber('broadcast')->name('broadcast.destroy');
});

Route::middleware('area:audit')->group(function () {
    Route::get('/log-aktivitas', [AuditLogController::class, 'index'])->name('audit.index');
});

Route::middleware('area:system')->group(function () {
    Route::put('/paket/harga', [PlanController::class, 'prices'])->name('plans.prices');
    Route::get('/paket/keuntungan', [PlanController::class, 'benefits'])->name('plans.benefits');
    Route::put('/paket/keuntungan', [PlanController::class, 'updateBenefits'])->name('plans.benefits.update');
    Route::get('/paket/{plan}/ubah', [PlanController::class, 'edit'])->whereNumber('plan')->name('plans.edit');
    Route::put('/paket/{plan}', [PlanController::class, 'update'])->whereNumber('plan')->name('plans.update');
    Route::put('/pengaturan/fitur', [SiteSettingsController::class, 'features'])->name('settings.features');
    Route::put('/pengaturan/akses-staf', [SiteSettingsController::class, 'staffAreas'])->name('settings.staff');
});

Route::middleware('area:users')->group(function () {
    Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    Route::get('/pengguna/ekspor', [UserController::class, 'export'])->middleware('throttle:10,1')->name('users.export');
    Route::post('/pengguna/massal', [UserController::class, 'bulk'])->middleware('throttle:20,1')->name('users.bulk');
    Route::get('/pengguna/tambah', [UserController::class, 'create'])->name('users.create');
    Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
    Route::get('/pengguna/{user}', [UserController::class, 'show'])->whereNumber('user')->name('users.show');
    Route::get('/pengguna/{user}/ubah', [UserController::class, 'edit'])->whereNumber('user')->name('users.edit');
    Route::put('/pengguna/{user}', [UserController::class, 'update'])->whereNumber('user')->name('users.update');
    Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->whereNumber('user')->name('users.destroy');
    Route::put('/pengguna/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
    Route::post('/pengguna/{user}/tiket', [UserController::class, 'grantTicket'])->name('users.ticket');
    Route::post('/pengguna/{user}/premium', [UserController::class, 'premium'])->whereNumber('user')->name('users.premium');
    Route::post('/pengguna/{user}/tangguhkan', [UserController::class, 'suspend'])->whereNumber('user')->name('users.suspend');
    Route::post('/pengguna/{user}/aktifkan', [UserController::class, 'activate'])->whereNumber('user')->name('users.activate');
    Route::post('/pengguna/{user}/kirim-reset', [UserController::class, 'sendReset'])->middleware('throttle:10,1')->whereNumber('user')->name('users.reset');
    Route::post('/pengguna/{user}/wajib-ganti-sandi', [UserController::class, 'forcePassword'])->whereNumber('user')->name('users.force');
    Route::post('/pengguna/{user}/reset-pemakaian', [UserController::class, 'resetUsage'])->whereNumber('user')->name('users.reset-usage');
    Route::post('/pengguna/{user}/verifikasi-email', [UserController::class, 'verifyEmail'])->whereNumber('user')->name('users.verify');
    Route::post('/pengguna/{user}/matikan-2fa', [UserController::class, 'disableTwoFactor'])->whereNumber('user')->name('users.2fa.off');
    Route::post('/pengguna/{user}/keluarkan', [UserController::class, 'logoutAll'])->whereNumber('user')->name('users.logout');
    Route::post('/pengguna/{user}/masuk-sebagai', [UserController::class, 'impersonate'])->middleware('throttle:10,1')->whereNumber('user')->name('users.impersonate');
});
// Pengaturan terbuka untuk admin dan staf; tab sistem hanya tampil untuk area `system`.
Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings');
Route::put('/pengaturan/sandi', [SettingsController::class, 'updatePassword'])->name('settings.password');

// Pratinjau halaman pemeliharaan sebagaimana dilihat pengunjung (admin dan staf biasanya melewati mode ini).
Route::get('/pratinjau-pemeliharaan', fn () => response()->view('maintenance', ['message' => app(\App\Services\Settings\SiteSettings::class)->maintenance()['message']], 503))->name('maintenance.preview');
