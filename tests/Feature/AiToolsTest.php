<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\InterviewSession;
use App\Models\User;
use App\Services\Ai\JsonReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['platform.premium_only.interview' => false]); // kunci Premium diuji di PremiumGateTest
    }

    private function fakeLlm(array $replies): void
    {
        config(['services.groq.key' => 'k', 'services.groq.model' => 'm', 'services.groq.base_url' => 'https://llm.test/v1', 'services.llm.order' => 'groq']);
        $seq = Http::sequence();
        foreach ($replies as $r) {
            $seq->push(['choices' => [['message' => ['content' => $r]]]]);
        }
        Http::fake(['llm.test/*' => $seq]);
    }

    public function test_json_reply_membaca_pagar_kode_dan_kalimat_pengantar(): void
    {
        $this->assertSame(['a' => 1], JsonReply::parse("```json\n{\"a\":1}\n```"));
        $this->assertSame([1, 2], JsonReply::parse('Ini hasilnya: [1,2] semoga membantu'));
        $this->assertNull(JsonReply::parse('bukan json'));
        $this->assertNull(JsonReply::parse(null));
    }

    private function answerJson(User $u, InterviewSession $session, string $text = 'Saya mengerjakan sistem aset sekolah bersama tim kecil.')
    {
        return $this->actingAs($u)->postJson("/dashboard/wawancara/{$session->id}/jawab", ['answer' => $text]);
    }

    private function report(int ...$scores): string
    {
        return json_encode([
            'summary' => 'Kesan keseluruhan baik.',
            'strengths' => ['Jelas'],
            'improve' => ['Tambah hasil terukur'],
            'answers' => array_map(fn ($s) => ['score' => $s, 'strengths' => ['Runtut'], 'improve' => ['Tambah contoh'], 'sample' => 'Contoh jawaban kuat.'], $scores),
        ]);
    }

    public function test_wawancara_percakapan_dari_pembuka_sampai_penilaian_akhir(): void
    {
        $this->fakeLlm([
            'Ceritakan sedikit tentang dirimu.',
            'Oh, sistem aset sekolah ya. Bagian mana yang paling sulit?',
            'Menarik. Bagaimana kamu membagi tugas dengan timmu?',
            'Hmm, lalu apa hasil akhirnya?',
            "Terima kasih, saya cukup puas dengan ceritamu. Tim kami akan menghubungi kamu.\n[SELESAI]",
            $this->report(60, 70, 80, 90),
        ]);

        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Staf IT', 'kind' => 'hr', 'context' => 'PT Maju Jaya, butuh Laravel'])->assertRedirect();
        $session = InterviewSession::firstOrFail();
        $this->assertSame([['role' => 'interviewer', 'text' => 'Ceritakan sedikit tentang dirimu.']], $session->transcript);
        $this->assertSame('PT Maju Jaya, butuh Laravel', $session->context);

        $this->answerJson($u, $session)->assertOk()->assertJson(['reply' => 'Oh, sistem aset sekolah ya. Bagian mana yang paling sulit?', 'done' => false, 'count' => 1]);
        $this->answerJson($u, $session)->assertOk()->assertJson(['done' => false, 'count' => 2]);
        $this->answerJson($u, $session)->assertOk()->assertJson(['done' => false, 'count' => 3]);
        $this->answerJson($u, $session)->assertOk()->assertJson(['done' => true, 'count' => 4])
            ->assertJsonMissing(['reply' => 'Tim kami akan menghubungi kamu.[SELESAI]']);

        $session->refresh();
        $this->assertSame('done', $session->status);
        $this->assertNull($session->report);
        $turns = $session->transcript;
        $this->assertStringNotContainsString('SELESAI', end($turns)['text']);

        // Halaman hasil menawarkan penyusunan penilaian; penilaian dibuat sekali dari seluruh transkrip.
        $this->actingAs($u)->get("/dashboard/wawancara/{$session->id}")->assertOk()->assertSee('Menyusun penilaian');
        $this->actingAs($u)->postJson("/dashboard/wawancara/{$session->id}/nilai")->assertOk()->assertJsonStructure(['url']);

        $session->refresh();
        $this->assertSame(75, $session->total_score);
        $this->actingAs($u)->get("/dashboard/wawancara/{$session->id}")->assertOk()
            ->assertSee('Skor 75 dari 100', false)->assertSee('Rata-rata dari')->assertSee('Kesan keseluruhan baik.')->assertSee('Contoh jawaban kuat.');
    }

    public function test_pewawancara_menerima_riwayat_cv_dan_lowongan(): void
    {
        $this->fakeLlm(['Pertanyaan pembuka?', 'Oh, begitu. Lalu?']);
        $u = User::factory()->create();
        CvProfile::create(['user_id' => $u->id, 'raw_text' => 'Pengalaman: membangun aplikasi aset sekolah dengan Laravel.']);

        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Staf IT', 'kind' => 'teknis', 'context' => 'Lowongan di PT Maju Jaya']);
        $session = InterviewSession::firstOrFail();
        $this->answerJson($u, $session, 'Saya membangun aplikasi aset sekolah.')->assertOk();

        Http::assertSent(function ($req) {
            $msgs = $req['messages'];
            $last = end($msgs);
            $system = $msgs[0]['content'];

            return count($msgs) === 4 // sistem, penanda awal, pertanyaan pembuka, jawaban
                && $last['role'] === 'user' && $last['content'] === 'Saya membangun aplikasi aset sekolah.'
                && $msgs[2]['role'] === 'assistant' && $msgs[2]['content'] === "[EMOSI: netral]\nPertanyaan pembuka?"
                && str_contains($system, 'aset sekolah dengan Laravel') && str_contains($system, 'PT Maju Jaya')
                && abs($req["temperature"] - 0.85) < 0.001;
        });
    }

    public function test_ucapan_pewawancara_dibersihkan_dari_markdown_dan_nama(): void
    {
        $this->fakeLlm(['Pembuka?', "**Bu Rina Marlina:** Oh, **menarik** sekali.\n\n- Apa peranmu di sana?"]);
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr']);

        $this->answerJson($u, InterviewSession::firstOrFail())->assertOk()
            ->assertJson(['reply' => 'Oh, menarik sekali. Apa peranmu di sana?']);
    }

    public function test_jawaban_tidak_maju_bila_ai_gagal_dan_bisa_dikirim_ulang(): void
    {
        $this->fakeLlm(['Pembuka?']);
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr']);
        $session = InterviewSession::firstOrFail();

        Http::fake(['llm.test/*' => Http::response('gagal', 500)]);
        $this->answerJson($u, $session)->assertStatus(503);
        $this->assertCount(1, $session->fresh()->transcript); // jawaban belum tersimpan
        $this->actingAs($u)->post("/dashboard/wawancara/{$session->id}/jawab", ['answer' => 'Jawaban saya cukup panjang.'])->assertSessionHasErrors('answer');
    }

    public function test_penutupan_terlalu_dini_dari_model_diabaikan(): void
    {
        $this->fakeLlm(['Pembuka?', "Terima kasih, sampai jumpa.\n[SELESAI]"]);
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr']);
        $session = InterviewSession::firstOrFail();

        $this->answerJson($u, $session)->assertStatus(503);
        $this->assertSame('running', $session->fresh()->status);
    }

    public function test_wawancara_dipaksa_tutup_di_batas_jawaban(): void
    {
        $this->fakeLlm(['Pembuka?', 'Satu pertanyaan lagi ya, bagaimana kelanjutannya?']); // model lupa menutup
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr']);
        $session = InterviewSession::firstOrFail();

        $turns = [['role' => 'interviewer', 'text' => 'Pembuka?']];
        foreach (range(1, 7) as $i) {
            $turns[] = ['role' => 'candidate', 'text' => "Jawaban ke-{$i}"];
            $turns[] = ['role' => 'interviewer', 'text' => "Pertanyaan {$i}?"];
        }
        $session->update(['transcript' => $turns]);

        $res = $this->answerJson($u, $session, 'Jawaban ke-8 saya.')->assertOk()->assertJson(['done' => true, 'count' => 8]);
        $this->assertStringContainsString('Terima kasih', $res->json('reply'));
        $this->assertSame('done', $session->fresh()->status);
    }

    public function test_jatah_wawancara_gratis_satu_per_hari_dan_milik_orang_lain_tertutup(): void
    {
        $this->fakeLlm(['Pembuka?']);
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr'])->assertRedirect();
        $session = InterviewSession::firstOrFail();
        $session->update(['status' => 'done']);

        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr'])->assertSessionHasErrors('role');
        $this->actingAs(User::factory()->create())->get("/dashboard/wawancara/{$session->id}")->assertNotFound();
        $this->actingAs(User::factory()->create())->postJson("/dashboard/wawancara/{$session->id}/nilai")->assertNotFound();
    }

    public function test_sesi_lama_selesai_tetap_bisa_dibuka_dan_yang_berjalan_dilanjutkan(): void
    {
        $u = User::factory()->create();
        $old = InterviewSession::create(['user_id' => $u->id, 'role' => 'Analis', 'kind' => 'hr', 'status' => 'done', 'total_score' => 72,
            'questions' => ['Q1?'], 'answers' => [['answer' => 'Jawaban lama saya.', 'score' => 72, 'strengths' => ['Jelas'], 'improve' => [], 'sample' => 'Contoh lama.']]]);
        $this->actingAs($u)->get("/dashboard/wawancara/{$old->id}")->assertOk()->assertSee('Jawaban lama saya.')->assertSee('Contoh lama.');

        $old->update(['status' => 'running', 'total_score' => null,
            'questions' => ['Q1?', 'Q2?'], 'answers' => [['answer' => 'Jawaban lama saya.', 'score' => 72, 'strengths' => [], 'improve' => [], 'sample' => '', 'reaction' => 'Oke, menarik.']]]);
        $this->actingAs($u)->get("/dashboard/wawancara/{$old->id}")->assertOk()->assertSee('Oke, menarik. Q2?');
        $this->assertSame(1, $old->fresh()->answerCount());
        $turns = $old->fresh()->transcript;
        $this->assertSame('Oke, menarik. Q2?', end($turns)['text']);
    }

    public function test_akhiri_lebih_awal_lalu_penilaian_diulang_sampai_berhasil(): void
    {
        $this->fakeLlm(['Pembuka?', 'Lanjut, apa kekuatanmu?', 'Baik, dan kelemahanmu?', 'bukan json', 'masih bukan json', $this->report(55, 65)]);
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr']);
        $session = InterviewSession::firstOrFail();

        $this->actingAs($u)->post("/dashboard/wawancara/{$session->id}/akhiri")->assertSessionHasErrors('answer'); // belum ada jawaban
        $this->answerJson($u, $session)->assertOk();
        $this->answerJson($u, $session, 'Saya teliti dan suka belajar.')->assertOk();

        $this->actingAs($u)->post("/dashboard/wawancara/{$session->id}/akhiri")->assertRedirect();
        $session->refresh();
        $this->assertSame('done', $session->status);
        $this->assertSame(2, $session->answerCount());

        // Dua percobaan pertama (satu permintaan penilaian) gagal membaca JSON; permintaan berikutnya berhasil.
        $this->actingAs($u)->postJson("/dashboard/wawancara/{$session->id}/nilai")->assertStatus(503);
        $this->assertNull($session->fresh()->report);
        $this->actingAs($u)->postJson("/dashboard/wawancara/{$session->id}/nilai")->assertOk();
        $this->assertSame(60, $session->fresh()->total_score);
    }

    public function test_penilaian_menambal_jawaban_yang_dilewati_model(): void
    {
        $this->fakeLlm(['Pembuka?', 'Lanjut?', 'Lagi?', $this->report(80)]); // model hanya menilai 1 dari 2 jawaban
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr']);
        $session = InterviewSession::firstOrFail();
        $this->answerJson($u, $session)->assertOk();
        $this->answerJson($u, $session, 'Jawaban kedua saya.')->assertOk();
        $this->actingAs($u)->post("/dashboard/wawancara/{$session->id}/akhiri");

        $this->actingAs($u)->postJson("/dashboard/wawancara/{$session->id}/nilai")->assertOk();
        $report = $session->fresh()->report;
        $this->assertCount(2, $report['answers']);
        $this->assertSame(80, $report['answers'][1]['score']); // ditambal dengan rata-rata
    }

    public function test_halaman_mulai_dan_sesi_berjalan_tampil(): void
    {
        $this->fakeLlm(['Ceritakan sedikit tentang dirimu.']);
        $u = User::factory()->create();

        $this->actingAs($u)->get('/dashboard/wawancara')->assertOk()->assertSee('Lowongan atau catatan');
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Staf IT', 'kind' => 'hr']);
        $session = InterviewSession::firstOrFail();

        $this->actingAs($u)->get("/dashboard/wawancara/{$session->id}")->assertOk()
            ->assertSee('Ceritakan sedikit tentang dirimu.')->assertSee('Kirim jawaban')->assertSee('Bu Rina Marlina');
        $this->actingAs($u)->get('/dashboard/wawancara')->assertOk()->assertSee('0 jawaban terkirim');
    }

    public function test_emosi_dibaca_disimpan_dan_dikirim_ke_tampilan(): void
    {
        $this->fakeLlm(['Pembuka?', "[EMOSI: kesal]\nHmm, itu belum menjawab pertanyaan saya. Coba lagi dengan contoh nyata."]);
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'teknis']);
        $session = InterviewSession::firstOrFail();

        $this->answerJson($u, $session, 'Pokoknya bagus saja lah.')->assertOk()
            ->assertJson(['mood' => 'kesal', 'done' => false, 'reply' => 'Hmm, itu belum menjawab pertanyaan saya. Coba lagi dengan contoh nyata.']);
        $turns = $session->fresh()->transcript;
        $this->assertSame('kesal', end($turns)['mood']);

        $this->actingAs($u)->get("/dashboard/wawancara/{$session->id}")->assertOk()->assertSee('data-emo="annoyed"', false)->assertSee('Tampak kesal');
    }

    public function test_emosi_tidak_dikenal_menjadi_netral_dan_riwayat_membawa_penanda_emosi(): void
    {
        $this->fakeLlm(['Pembuka?', "[EMOSI: bahagia banget]\nOh begitu.", 'Lanjut?']);
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr']);
        $session = InterviewSession::firstOrFail();

        $this->answerJson($u, $session, 'Jawaban pertama saya.')->assertOk()->assertJson(['mood' => 'netral', 'reply' => 'Oh begitu.']);
        $this->answerJson($u, $session, 'Jawaban kedua saya.')->assertOk();
        Http::assertSent(fn ($req) => str_contains(json_encode($req['messages']), '[EMOSI: netral]\nOh begitu.'));
    }

    public function test_pewawancara_marah_boleh_mengakhiri_wawancara_lebih_awal(): void
    {
        $this->fakeLlm(['Pembuka?', 'Hmm, kurang meyakinkan. Kenapa?', "[EMOSI: marah]\nSaya rasa kita hentikan saja di sini. Anda tidak serius.\n[SELESAI]"]);
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'situasional']);
        $session = InterviewSession::firstOrFail();

        $this->answerJson($u, $session, 'ga tau')->assertOk()->assertJson(['done' => false]);
        $this->answerJson($u, $session, 'terserah deh')->assertOk()->assertJson(['done' => true, 'mood' => 'marah']);
        $this->assertSame('done', $session->fresh()->status);
    }

    public function test_hasil_penilaian_berupa_dropdown_dan_wajah_sesuai_gender(): void
    {
        $this->fakeLlm(['Pembuka?', 'Lanjut?', 'Lagi?', $this->report(60, 70)]);
        $u = User::factory()->create();
        $this->actingAs($u)->post('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'teknis']); // Pak Dimas, laki-laki
        $session = InterviewSession::firstOrFail();
        $this->answerJson($u, $session)->assertOk();
        $this->answerJson($u, $session, 'Jawaban kedua saya.')->assertOk();
        $this->actingAs($u)->post("/dashboard/wawancara/{$session->id}/akhiri");
        $this->actingAs($u)->postJson("/dashboard/wawancara/{$session->id}/nilai")->assertOk();

        $this->actingAs($u)->get("/dashboard/wawancara/{$session->id}")->assertOk()
            ->assertSee('<details class="pn-card iv-det"', false)->assertSee('Buka semua')->assertSee('data-gender="m"', false);

        $this->actingAs($u)->get('/dashboard/wawancara')->assertOk()->assertSee('data-gender="m"', false)->assertSee('data-gender="f"', false);
    }
}
