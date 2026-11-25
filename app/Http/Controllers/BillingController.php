<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Plan;
use App\Services\Billing\EntitlementService;
use App\Services\Billing\OrderSettler;
use App\Services\Billing\PurchasePassword;
use App\Services\Billing\Promo;
use App\Models\ExamType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function index(Request $request, EntitlementService $entitlements, Promo $promo)
    {
        $user = $request->user();
        $types = ExamType::where('is_active', true)->orderBy('position')->orderBy('id')->get();

        return view('billing.index', [
            // Tiket 1x dijual untuk semua ujian aktif: seleksi resmi dan simulasi karier.
            'types' => $types,
            'ticketOptions' => array_filter([
                'Seleksi resmi' => $types->where('kind', 'selection')->pluck('name', 'id')->all(),
                'Simulasi karier' => $types->where('kind', 'career')->pluck('name', 'id')->all(),
            ]),
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(),
            'promo' => $promo->current(),
            'price' => (int) config('platform.exam.pass_price'),
            'selected' => $request->query('jenis'),
            'premiumUntil' => $user && $entitlements->isPremium($user->id) ? $entitlements->premiumUntil($user->id) : null,
            'isPremium' => $user && $entitlements->isPremium($user->id),
        ]);
    }

    /**
     * Tiket simulasi: sekali beli, simulasi itu terbuka seterusnya. Tidak wajib daftar: tamu cukup mengisi email
     * (akun dibuatkan otomatis setelah bayar). Berlaku sama untuk semua simulasi, seleksi resmi maupun karier.
     */
    public function buy(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $rules = [
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:15'], // kosong = 1 tiket (form lama)
        ];
        $messages = ['exam_type_id.required' => 'Pilih simulasi dulu.', 'quantity.required' => 'Pilih jumlah tiket dulu.', 'quantity.max' => 'Maksimal 15 tiket sekali beli.'];

        if (! $request->user()) {
            $rules += [
                'email' => ['required', 'email', 'max:255'],
                'display_name' => ['nullable', 'string', 'max:40'],
                'show_in_ranking' => ['nullable', 'boolean'],
            ];
            $messages += ['email.required' => 'Email wajib diisi.', 'email.email' => 'Format email tidak valid.'];
        }

        $data = $request->validate($rules, $messages);
        $type = ExamType::where('is_active', true)->findOrFail($data['exam_type_id']);
        $qty = (int) ($data['quantity'] ?? 1);
        $meta = ['quantity' => $qty, 'item_name' => 'Tiket simulasi '.$type->name.($qty > 1 ? ' (x'.$qty.')' : '')];
        $email = null;

        if (! $request->user()) {
            $email = Str::lower(trim($data['email']));
            $name = trim(strip_tags((string) ($data['display_name'] ?? '')));
            if ($name !== '') {
                $meta['display_name'] = $name;
            }
            // Kotak "tampil di peringkat" tidak dicentang tidak terkirim: anggap ikut hanya bila form memang dikirim dengan kotaknya.
            $meta['show_in_ranking'] = $request->boolean('show_in_ranking', ! $request->has('ranking_shown'));
        }

        return $this->checkout($request, $gateway, 'exam_pass', $type->id, (int) config('platform.exam.pass_price') * $qty, $meta, $email);
    }

    /** Langganan Premium. Tidak wajib daftar: tamu cukup mengisi email, akun dibuatkan otomatis setelah bayar (sama seperti tiket). */
    public function buyPlan(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $rules = ['plan_id' => ['required', 'integer']];
        $messages = ['plan_id.required' => 'Pilih paket dulu.'];

        if (! $request->user()) {
            $rules['email'] = ['required', 'email', 'max:255'];
            $messages += ['email.required' => 'Email wajib diisi.', 'email.email' => 'Format email tidak valid.'];
        }

        $data = $request->validate($rules, $messages);
        $plan = Plan::where('is_active', true)->findOrFail($data['plan_id']);
        $email = $request->user() ? null : Str::lower(trim($data['email']));

        // Harga mengikuti diskon yang sedang berjalan; rinciannya disimpan di tagihan agar tetap benar bila diskon berakhir.
        $meta = ['item_name' => $plan->name];
        if ($plan->isDiscounted()) {
            $meta += ['original_amount' => (int) $plan->price, 'discount' => $plan->discountAmount(), 'promo_label' => $plan->discount_label ?: 'Diskon Premium'];
        }

        return $this->checkout($request, $gateway, 'plan', $plan->id, $plan->finalPrice(), $meta, $email);
    }

    private function checkout(Request $request, PaymentGateway $gateway, string $itemType, int $itemId, int $amount, array $meta, ?string $guestEmail = null): RedirectResponse
    {
        $user = $request->user();

        // Pakai tagihan yang masih menunggu untuk item dan harga yang sama, supaya tidak menumpuk (harga berubah = tagihan baru).
        $existing = Order::where('item_type', $itemType)->where('item_id', $itemId)
            ->where('status', 'pending')->where('amount', $amount)->where('expires_at', '>', now());
        if ($user) {
            $existing->where('user_id', $user->id);
        } else {
            $existing->whereIn('invoice_no', $this->guestOrders($request))->where('meta->email', $guestEmail);
        }
        $order = $existing->latest('id')->first();

        if (! $order) {
            $driver = config('platform.payment.driver');
            $order = Order::create([
                'user_id' => $user?->id,
                'invoice_no' => 'INV-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'item_type' => $itemType, 'item_id' => $itemId, 'amount' => $amount,
                'status' => 'pending', 'gateway' => $driver,
                'expires_at' => $driver === 'midtrans'
                    ? now()->addMinutes((int) config('platform.payment.midtrans.expiry_minutes', 60))
                    : now()->addHours((int) config('platform.exam.order_expires_hours', 24)),
                'meta' => $guestEmail ? $meta + ['email' => $guestEmail] : $meta,
            ]);

            if (! $user) {
                $request->session()->push('guest_orders', $order->invoice_no);
            }

            try {
                $charge = $gateway->createCharge($order->load('user'));
            } catch (\Throwable $e) {
                report($e);
                $order->delete();

                return back()->with('error', 'Pembayaran belum bisa dibuat. Coba lagi sebentar lagi atau hubungi admin.');
            }

            $order->update([
                'gateway_ref' => $charge['gateway_ref'] ?? null,
                'meta' => array_merge($order->meta ?? [], array_filter([
                    'qr_string' => $charge['qr_string'] ?? null,
                    'qr_url' => $charge['qr_url'] ?? null,
                    'pay_url' => $charge['pay_url'] ?? null,
                ])),
            ]);
        }

        return redirect()->route('billing.invoice', $order);
    }

    public function invoice(Request $request, Order $order, PaymentGateway $gateway, OrderSettler $settler, PurchasePassword $passwords)
    {
        $this->ownOrder($request, $order);

        // Pembeli yang baru kembali dari halaman bayar langsung melihat hasilnya, tanpa menunggu polling pertama.
        $this->syncWithGateway($order, $gateway, $settler);
        $settler->expireIfDue($order);
        $this->signInBuyer($request, $order);

        $qris = file_exists(public_path(config('platform.payment.qris_image'))) ? asset(config('platform.payment.qris_image')) : null;

        return view('billing.invoice', [
            'order' => $order,
            'type' => $order->item_type === 'exam_pass' ? ExamType::find($order->item_id) : null,
            'qris' => $qris,
            'dynamicQr' => ! empty($order->meta['qr_string']) || ! empty($order->meta['qr_url']),
            'payUrl' => $order->meta['pay_url'] ?? null,
            'email' => $order->meta['email'] ?? $order->user?->email,
            'accountCreated' => (bool) ($order->meta['account_created'] ?? false),
            // Sandi acak akun baru: hanya untuk pemilik akun yang sedang masuk, dan hilang begitu ia membuat sandi sendiri.
            'initialPassword' => $order->status === 'paid' ? $passwords->reveal($order, $request->user()) : null,
            'canSimulate' => config('platform.payment.driver') === 'fake' && ! app()->environment('production'),
        ]);
    }

    /** Dipanggil halaman tagihan setiap beberapa detik. */
    public function status(Request $request, Order $order, PaymentGateway $gateway, OrderSettler $settler): JsonResponse
    {
        $this->ownOrder($request, $order);

        $answered = $this->syncWithGateway($order, $gateway, $settler);

        // Cek terakhir ke Midtrans sudah dilakukan di atas; kalau batas waktu lewat dan belum lunas, tagihan ditutup.
        $settler->expireIfDue($order);

        return response()->json([
            'status' => $order->status,
            'attempt_failed' => $order->status === 'pending' && ! empty($order->meta['attempt_failed_at']),
            'gateway_ok' => $answered,
            'checked_at' => now()->timezone(config('app.timezone'))->format('H:i:s'),
        ]);
    }

    /** Tanya Midtrans untuk tagihan yang masih menunggu; aman dipanggil berulang. */
    private function syncWithGateway(Order $order, PaymentGateway $gateway, OrderSettler $settler): bool
    {
        if ($order->status !== 'pending' || $order->gateway !== 'midtrans') {
            return true;
        }

        try {
            if ($result = $gateway->fetchStatus($order)) {
                $settler->apply($order, $result);
                $order->refresh();

                return true;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return false;
    }

    /** Pembeli menandai sudah transfer (QRIS statis); */
    public function claim(Request $request, Order $order): RedirectResponse
    {
        $this->ownOrder($request, $order);

        if ($order->status === 'pending') {
            $order->update(['meta' => array_merge($order->meta ?? [], ['claimed_at' => now()->toIso8601String()])]);
        }

        return back()->with('status', 'Terima kasih. Pembayaran kamu akan diverifikasi admin, biasanya kurang dari 1 jam di jam kerja.');
    }

    /** Hanya untuk development (PAYMENT_DRIVER=fake dan bukan production). */
    public function simulatePaid(Request $request, Order $order, EntitlementService $entitlements): RedirectResponse
    {
        $this->ownOrder($request, $order);
        abort_unless(config('platform.payment.driver') === 'fake' && ! app()->environment('production'), 404);

        if ($order->status === 'pending') {
            $order->update(['status' => 'paid', 'paid_at' => now()]);
            $entitlements->grantFor($order->refresh());
        }

        return redirect()->route('billing.invoice', $order)->with('status', 'Mode pengembangan: pesanan dilunasi.');
    }

    /** Pemilik tagihan: pengguna yang masuk, atau browser (sesi) yang membuat tagihan tamu itu. */
    private function ownOrder(Request $request, Order $order): void
    {
        $mine = $request->user() && $order->user_id === $request->user()->id;
        $guest = in_array($order->invoice_no, $this->guestOrders($request), true);

        abort_unless($mine || $guest, 404);
    }

    /** @return list<string> */
    private function guestOrders(Request $request): array
    {
        return (array) $request->session()->get('guest_orders', []);
    }

    /** Setelah tagihan tamu lunas, langsung masukkan pembeli ke akun yang baru dibuat untuk */
    private function signInBuyer(Request $request, Order $order): void
    {
        if ($request->user() || $order->status !== 'paid' || ! $order->user_id || empty($order->meta['account_created'])) {
            return;
        }
        if (! in_array($order->invoice_no, $this->guestOrders($request), true)) {
            return;
        }

        Auth::login($order->user);
        $request->session()->regenerate();
        $request->session()->put('guest_orders', array_values(array_diff($this->guestOrders($request), [$order->invoice_no])));
    }
}
