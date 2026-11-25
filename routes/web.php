<?php

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BroadcastUnsubscribeController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobsController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\SelectionController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\Account;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ForcedPasswordController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\TwoFactorChallengeController;
use App\Http\Controllers\TrialController;
use App\Http\Middleware\FeatureOpen;
use Illuminate\Support\Facades\Route;

// Beranda
Route::get('/', HomeController::class)->name('home');

// Akun
Route::get('/dashboard', [AccountsController::class, 'index'])->middleware('auth')->name('dashboard.index');

Route::middleware('auth')->prefix('dashboard')->name('account.')->group(function () {
    Route::get('/simulasi', [Account\ExamsController::class, 'index'])->name('exams');
    Route::get('/riwayat', [Account\HistoryController::class, 'index'])->name('history');
    Route::get('/seleksi', [SelectionController::class, 'account'])->name('selections');
    Route::get('/rencana', [Account\StudyController::class, 'index'])->middleware(\App\Http\Middleware\RequirePremium::class.':plan')->name('plan');
    Route::put('/rencana', [Account\StudyController::class, 'update'])->middleware(\App\Http\Middleware\RequirePremium::class.':plan')->name('plan.update');
    Route::put('/rencana/pengingat', [Account\StudyController::class, 'reminder'])->middleware(\App\Http\Middleware\RequirePremium::class.':plan')->name('plan.reminder');
    Route::get('/lamaran', [Account\ApplicationController::class, 'index'])->name('applications');
    Route::post('/lamaran', [Account\ApplicationController::class, 'store'])->middleware('throttle:60,1')->name('applications.store');
    Route::patch('/lamaran/{application}', [Account\ApplicationController::class, 'update'])->whereNumber('application')->name('applications.update');
    Route::delete('/lamaran/{application}', [Account\ApplicationController::class, 'destroy'])->whereNumber('application')->name('applications.destroy');
    Route::post('/lamaran/alert', [Account\ApplicationController::class, 'alertStore'])->middleware('throttle:20,1')->name('alerts.store');
    Route::patch('/lamaran/alert/{alert}', [Account\ApplicationController::class, 'alertToggle'])->whereNumber('alert')->name('alerts.toggle');
    Route::delete('/lamaran/alert/{alert}', [Account\ApplicationController::class, 'alertDestroy'])->whereNumber('alert')->name('alerts.destroy');
    Route::get('/wawancara', [Account\InterviewController::class, 'index'])->middleware(\App\Http\Middleware\RequirePremium::class.':interview')->name('interview');
    Route::post('/wawancara', [Account\InterviewController::class, 'start'])->middleware('throttle:10,1')->middleware(\App\Http\Middleware\RequirePremium::class.':interview')->name('interview.start');
    Route::get('/wawancara/{session}', [Account\InterviewController::class, 'show'])->whereNumber('session')->middleware(\App\Http\Middleware\RequirePremium::class.':interview')->name('interview.show');
    Route::post('/wawancara/{session}/jawab', [Account\InterviewController::class, 'answer'])->whereNumber('session')->middleware('throttle:20,1')->middleware(\App\Http\Middleware\RequirePremium::class.':interview')->name('interview.answer');
    Route::post('/wawancara/{session}/akhiri', [Account\InterviewController::class, 'finish'])->whereNumber('session')->middleware('throttle:10,1')->middleware(\App\Http\Middleware\RequirePremium::class.':interview')->name('interview.finish');
    Route::post('/wawancara/{session}/nilai', [Account\InterviewController::class, 'grade'])->whereNumber('session')->middleware('throttle:8,1')->middleware(\App\Http\Middleware\RequirePremium::class.':interview')->name('interview.grade');
    Route::redirect('/cv-surat', '/cv', 301); // alamat lama menu CV & surat lamaran
    Route::get('/belajar', [Account\LearningController::class, 'index'])->name('learning');
    Route::get('/karier', [Account\CareerController::class, 'index'])->name('career');
    Route::get('/asisten', [Account\ChatController::class, 'index'])->name('chat');
    Route::post('/asisten', [Account\ChatController::class, 'send'])->middleware('throttle:20,1')->name('chat.send');
    Route::delete('/asisten', [Account\ChatController::class, 'clear'])->name('chat.clear');
    Route::get('/asisten/riwayat', [Account\ChatController::class, 'days'])->name('chat.days');
    Route::get('/asisten/riwayat/{day}', [Account\ChatController::class, 'day'])->where('day', '\d{4}-\d{2}-\d{2}')->name('chat.day');
    Route::delete('/asisten/riwayat/{day}', [Account\ChatController::class, 'clearDay'])->where('day', '\d{4}-\d{2}-\d{2}')->name('chat.day.clear');
    Route::get('/langganan', [Account\SubscriptionController::class, 'index'])->name('subscription');
    Route::get('/pengaturan', [Account\SettingsController::class, 'index'])->name('settings');
    Route::put('/pengaturan/profil', [Account\SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/pengaturan/karier', [Account\SettingsController::class, 'updateCareer'])->name('settings.career');
    Route::put('/pengaturan/preferensi/{group}', [Account\SettingsController::class, 'updatePreferences'])->where('group', '[a-z_]+')->name('settings.preferences');
    Route::put('/pengaturan/sandi', [Account\SettingsController::class, 'updatePassword'])->middleware('not.impersonating')->name('settings.password');
    Route::post('/pengaturan/perangkat/keluar', [Account\SettingsController::class, 'logoutOthers'])->middleware(['throttle:6,1', 'not.impersonating'])->name('settings.devices.logout');
    Route::post('/pengaturan/2fa/mulai', [Account\TwoFactorController::class, 'begin'])->middleware(['throttle:10,1', 'not.impersonating'])->name('settings.2fa.begin');
    Route::post('/pengaturan/2fa/konfirmasi', [Account\TwoFactorController::class, 'confirm'])->middleware(['throttle:10,1', 'not.impersonating'])->name('settings.2fa.confirm');
    Route::post('/pengaturan/2fa/pemulihan', [Account\TwoFactorController::class, 'recovery'])->middleware(['throttle:6,1', 'not.impersonating'])->name('settings.2fa.recovery');
    Route::delete('/pengaturan/2fa', [Account\TwoFactorController::class, 'disable'])->middleware(['throttle:6,1', 'not.impersonating'])->name('settings.2fa.disable');
    Route::get('/pengaturan/data/unduh', [Account\SettingsController::class, 'export'])->middleware(['throttle:6,1', 'not.impersonating'])->name('settings.export');
    Route::delete('/pengaturan/data/chat', [Account\SettingsController::class, 'clearChat'])->name('settings.chat.clear');
    Route::delete('/pengaturan/data/cv', [Account\SettingsController::class, 'clearCv'])->name('settings.cv.clear');
    Route::delete('/pengaturan/akun', [Account\SettingsController::class, 'destroy'])->middleware(['throttle:5,1', 'not.impersonating'])->name('settings.destroy');
});

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');

    // Masuk dengan Google (OAuth). Callback harus sama persis dengan Redirect URI di Google Cloud Console.
    Route::get('/masuk/google', [GoogleAuthController::class, 'redirect'])->middleware('throttle:20,1')->name('login.google');
    Route::get('/masuk/google/callback', [GoogleAuthController::class, 'callback'])->middleware('throttle:20,1')->name('login.google.callback');

    // Langkah kedua masuk (kode 2 langkah) dan lupa/atur ulang kata sandi.
    Route::get('/masuk/2fa', [TwoFactorChallengeController::class, 'show'])->name('login.2fa');
    // Setelah daftar manual: halaman "Cek emailmu" dan kirim ulang link verifikasi (belum masuk).
    Route::get('/verifikasi-email/terkirim', [EmailVerificationController::class, 'sent'])->name('verification.sent');
    Route::post('/verifikasi-email/kirim-ulang', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.resend');
    Route::post('/masuk/2fa', [TwoFactorChallengeController::class, 'store'])->middleware('throttle:10,1')->name('login.2fa.store');
    Route::get('/lupa-sandi', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/lupa-sandi', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/atur-ulang-sandi/{token}', [PasswordResetController::class, 'form'])->name('password.reset');
    Route::post('/atur-ulang-sandi', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Link verifikasi dari email: boleh dibuka tanpa masuk (bertanda tangan + hash email).
Route::get('/verifikasi-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->whereNumber('id')->name('verification.verify');

// Verifikasi email, wajib ganti sandi (diminta admin), dan keluar dari mode "masuk sebagai pengguna".
Route::middleware('auth')->group(function () {
    Route::get('/verifikasi-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/verifikasi-email/kirim', [EmailVerificationController::class, 'send'])->middleware('throttle:3,1')->name('verification.send');
    Route::get('/ganti-sandi', [ForcedPasswordController::class, 'show'])->name('password.force');
    Route::put('/ganti-sandi', [ForcedPasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.force.update');
    Route::post('/impersonasi/berhenti', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
});

// Karier: katalog digabung ke menu Simulasi (/simulasi#karier); tautan lama diarahkan ke sana.
Route::redirect('/karier', '/simulasi#karier');
Route::get('/karier/{career:slug}', [CareerController::class, 'show'])->name('careers.show');
Route::post('/karier/{career:slug}/target', [CareerController::class, 'target'])->middleware('auth')->name('careers.target');
Route::get('/arah-karier', [CareerController::class, 'quiz'])->name('careers.quiz');
Route::post('/arah-karier/hasil', [CareerController::class, 'analyze'])->middleware('throttle:30,1')->name('careers.analyze');

Route::middleware('auth')->prefix('cv')->group(function () {
    Route::get('/', [CvController::class, 'index'])->name('cv.index');
    Route::post('/', [CvController::class, 'store'])->middleware('throttle:10,1')->name('cv.store');
    Route::post('/cocokkan', [CvController::class, 'matchJob'])->middleware('throttle:20,1')->name('cv.match');
    Route::get('/laporan', [CvController::class, 'report'])->name('cv.report');
    Route::get('/halaman/{n}', [CvController::class, 'page'])->whereNumber('n')->name('cv.page');
    Route::delete('/', [CvController::class, 'destroy'])->name('cv.destroy');
});

// Simulasi ujian
Route::prefix('simulasi')->group(function () {
    Route::get('/', [ExamController::class, 'index'])->name('exam.index');
    Route::get('/peringkat', [ExamController::class, 'leaderboard'])->name('exam.leaderboard');
    // Menjaga sesi tetap hidup saat ujian (sesi 15 menit tanpa aktivitas); dipanggil berkala dari ruang ujian.
    Route::post('/ping', fn () => response()->noContent())->middleware('throttle:30,1')->name('exam.ping');

    // Coba simulasi umum 5 menit (tampilan soal saja). Taruh sebelum rute berparameter kode.
    Route::get('/coba', [TrialController::class, 'show'])->name('exam.trial');
    Route::post('/coba', [TrialController::class, 'submit'])->middleware('throttle:30,1')->name('exam.trial.submit');

    Route::get('/ujian/{attempt}', [ExamController::class, 'room'])->whereNumber('attempt')->name('exam.room');
    Route::post('/ujian/{attempt}/jawab', [ExamController::class, 'answer'])->whereNumber('attempt')->middleware('throttle:240,1')->name('exam.answer');
    Route::post('/ujian/{attempt}/selesai', [ExamController::class, 'finish'])->whereNumber('attempt')->name('exam.finish');
    Route::get('/hasil/{attempt}', [ExamController::class, 'result'])->whereNumber('attempt')->name('exam.result');
    Route::post('/hasil/{attempt}/ulangi', [ExamController::class, 'retake'])->whereNumber('attempt')->middleware('throttle:20,1')->name('exam.retake');
    Route::post('/hasil/{attempt}/ai/ringkasan', [ExamController::class, 'aiSummary'])->whereNumber('attempt')->middleware('throttle:12,1')->name('exam.ai.summary');
    Route::post('/hasil/{attempt}/ai/tanya', [ExamController::class, 'aiAsk'])->whereNumber('attempt')->middleware('throttle:20,1')->name('exam.ai.ask');
    Route::post('/hasil/{attempt}/ai/pembahasan', [ExamController::class, 'aiExplain'])->whereNumber('attempt')->middleware('throttle:30,1')->name('exam.ai.explain');
    Route::post('/hasil/{attempt}/bagikan', [ShareController::class, 'store'])->whereNumber('attempt')->middleware('throttle:20,1')->name('exam.share');
    Route::delete('/hasil/{attempt}/bagikan', [ShareController::class, 'destroy'])->whereNumber('attempt')->middleware('throttle:20,1')->name('exam.share.revoke');

    // Rute berparameter kode taruh paling bawah.
    Route::get('/{type:code}', [ExamController::class, 'show'])->name('exam.show');
    Route::post('/{type:code}/mulai', [ExamController::class, 'start'])->middleware('throttle:20,1')->name('exam.start');
});

// Kartu hasil yang dibagikan pemiliknya (tautan acak, bisa dimatikan) dan info seleksi.
Route::get('/bagikan/{token}', [ShareController::class, 'show'])->where('token', '[A-Za-z0-9]{20,40}')->middleware('throttle:60,1')->name('share.show');
Route::get('/info-seleksi', [SelectionController::class, 'index'])->name('selection.index');

// Belajar (selama LEARNING_OPEN belum true, semua alamat di sini menampilkan halaman Segera Hadir)
Route::prefix('belajar')->middleware(FeatureOpen::class.':learning')->group(function () {
    Route::get('/', [LearningController::class, 'index'])->name('learning.index');
    Route::get('/{course:slug}', [LearningController::class, 'show'])->name('learning.show');
    Route::get('/{course:slug}/{lesson}', [LearningController::class, 'lesson'])->whereNumber('lesson')->name('learning.lesson');
    Route::post('/{course:slug}/{lesson}/selesai', [LearningController::class, 'complete'])->whereNumber('lesson')->middleware('auth')->name('learning.complete');
});

// Lowongan
Route::prefix('lowongan')->group(function () {
    Route::get('/', [JobsController::class, 'index'])->name('jobs.index');
    Route::post('/cari', [JobsController::class, 'search'])->middleware('throttle:12,1')->name('jobs.search');
    Route::get('/status/{id}', [JobsController::class, 'status'])->middleware('throttle:240,1')->name('jobs.status');
    Route::post('/cv', [JobsController::class, 'cv'])->middleware(['auth', 'throttle:10,1'])->name('jobs.cv');
});

// Harga dan tagihan (Premium bisa dibeli tanpa akun; kepemilikan tagihan dicek lewat sesi)
Route::prefix('harga')->group(function () {
    Route::get('/', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/langganan', [BillingController::class, 'buyPlan'])->middleware(['verified', 'throttle:10,1'])->name('billing.plan');
    Route::get('/tagihan/{order:invoice_no}/status', [BillingController::class, 'status'])->middleware('throttle:40,1')->name('billing.status');
    Route::get('/tagihan/{order:invoice_no}', [BillingController::class, 'invoice'])->name('billing.invoice');
    Route::post('/tagihan/{order:invoice_no}/konfirmasi', [BillingController::class, 'claim'])->middleware('throttle:10,1')->name('billing.claim');
    Route::post('/tagihan/{order:invoice_no}/simulasi-lunas', [BillingController::class, 'simulatePaid'])->name('billing.simulate');
    // Tiket simulasi boleh dibeli tanpa daftar (cukup email); akses langsung tertaut ke akun dari email itu.
    Route::post('/beli', [BillingController::class, 'buy'])->middleware('throttle:10,1')->name('billing.buy');
});

// Berhenti menerima email info (footer email broadcast). Tautan bertanda tangan; konfirmasi dulu baru berhenti.
Route::get('/berhenti-info/{user}', [BroadcastUnsubscribeController::class, 'show'])->middleware(['signed', 'throttle:30,1'])->whereNumber('user')->name('broadcast.unsubscribe');
Route::post('/berhenti-info/{user}', [BroadcastUnsubscribeController::class, 'update'])->middleware(['signed', 'throttle:10,1'])->whereNumber('user')->name('broadcast.unsubscribe.update');

// Pratinjau halaman error saat pengembangan (hanya APP_ENV=local): buka /_error/404, /_error/429, /_error/503, dst.
if (app()->environment('local')) {
    Route::get('/_error/{code}', fn (int $code) => abort($code, '', $code === 429 ? ['Retry-After' => 20] : []))
        ->whereIn('code', [400, 401, 403, 404, 405, 408, 410, 413, 419, 422, 429, 500, 502, 503, 504]);
}
