"""Bot Telegram publik.

Perintah:
  /cari   (alias /input)  pencarian dipandu, atau satu baris: /cari posisi | skill | lokasi
  /magang sama seperti /cari, tetapi khusus magang / internship
  /skill  cari cepat dari keahlian:  /skill python, flask, docker | Jakarta
  /cv     kirim file CV (PDF/DOCX) -> keahlian dibaca otomatis -> cari lowongan
  /langsung  ulangi pencarian terakhir dengan mengumpulkan data langsung dari web
  /web    buka website Job Finder (bisa dijalankan di HP maupun laptop)
  /sheet  bantuan: cara membuat & membuka Google Sheets hasil pencarian
  /batal  /help

Env yang dipakai:
  TELEGRAM_BOT_TOKEN       token dari @BotFather
  TELEGRAM_BOT_USERNAME    username bot tanpa @ (untuk membuat link t.me)
  TELEGRAM_CHANNEL_URL     (opsional) link channel publik
  TELEGRAM_WEBHOOK_SECRET  string acak; dikirim Telegram di header agar webhook tidak bisa dipalsukan
  WEB_URL                  alamat website Job Finder, mis. https://domainmu.com
"""
import os
import re
import threading
import time

import requests

from main import build_messages, send_telegram, run_search, score_job
from sources import catalog, search_links
from summary import build_summary
from sheets import export_to_sheet, save_search_async, fetch_archive
from cv_parser import extract_text, extract_profile
import divisions

TOKEN_RE = re.compile(r"^[A-Za-z0-9_-]{6,64}$")
MIN_MATCH = 25
COOLDOWN = 120        # detik jeda antar pencarian langsung per orang
ARCHIVE_COOLDOWN = 10 # detik jeda antar pencarian arsip per orang
LAST_TTL = 1800       # /langsung boleh dipakai sampai 30 menit setelah pencarian arsip
MAX_CONCURRENT = 2    # pencarian bersamaan dari Telegram
STATE_TTL = 600       # percakapan dipandu kedaluwarsa setelah 10 menit diam
HOURS = {"1": 24, "2": 168, "3": 720}
MAX_CV_BYTES = 5 * 1024 * 1024
CV_EXT = (".pdf", ".docx", ".txt")

# Semua state memakai kunci "chat_id:user_id", jadi di grup tiap orang punya percakapan sendiri.
_state = {}           # kunci -> {"step", "data", "ts"}
_last = {}            # kunci -> waktu pencarian langsung terakhir
_last_arc = {}        # kunci -> waktu pencarian arsip terakhir
_params = {}          # kunci -> (params, waktu) pencarian terakhir, untuk /langsung
_slots = threading.BoundedSemaphore(MAX_CONCURRENT)

WELCOME = (
    "👋 Halo! Aku bot Job Finder.\n\n"
    "Cara mencari lowongan:\n"
    "• /cari  — aku tanya posisi, keahlian, dan lokasi satu per satu\n"
    "• /magang — cari magang / internship (format sama seperti /cari)\n"
    "• /skill python, flask, docker | Jakarta — cari cepat dari keahlian\n"
    "• /cv — kirim CV (PDF/DOCX), keahlianmu kubaca otomatis\n"
    "• /langsung — ulangi pencarian terakhir dengan ambil data langsung dari web\n"
    "• /cari python developer | python, flask | Jakarta, Remote\n"
    "   (format: posisi | keahlian | lokasi, dipisah tanda |)\n"
    "• /web — buka website Job Finder di HP atau laptop\n"
    "• /sheet — cara membuat & membuka Google Sheets hasil pencarian\n"
    "• /batal — membatalkan pencarian yang sedang diisi\n\n"
    "Di akhir pertanyaan kamu bisa pilih: cek ARSIP Google Sheets (instan, dikumpulkan otomatis jam 12 siang "
    "& 12 malam WIB) atau suruh bot kumpulkan data LANGSUNG dari web (2–3 menit, paling baru).\n\n"
    "Lowongan diambil dari situs sumber resmi; tiap hasil menyertakan nama sumber dan link aslinya."
)

SHEET_HELP = (
    "📊 Cara membuat Google Sheets hasil pencarian\n\n"
    "Sheet dibuat OTOMATIS, kamu tidak perlu membuat apa pun:\n"
    "1. Ketik /cari, /skill, atau /cv lalu tunggu hasilnya keluar.\n"
    "2. Di bawah pesan hasil ada tombol \"📊 Buka hasil di Google Sheets\". Tap tombol itu.\n"
    "3. Sheet terbuka di Google Sheets / browser. Isinya: judul (bisa diklik), perusahaan, "
    "lokasi, persen kecocokan, skill yang cocok, sumber, dan link lowongan.\n\n"
    "Tips:\n"
    "• Tiap pencarian membuat satu sheet baru, jadi simpan linknya kalau mau dipakai lagi.\n"
    "• Sheet bersifat view-only; untuk mengedit atau menambah catatan, pilih File > Buat salinan.\n"
    "• Kalau tombol tidak muncul, berarti hasil kosong atau fitur Sheets belum diaktifkan "
    "oleh pemilik bot (env GSHEET_WEBHOOK_URL & GSHEET_SECRET), atau batas pembuatan sheet per jam "
    "sedang penuh. Coba lagi beberapa saat.\n"
    "• Kalau Google minta login, pakai akun Google apa saja.\n\n"
    "Catatan: lowongan hasil pencarianmu ikut dicatat di arsip lowongan milik pemilik bot, "
    "beserta posisi yang kamu cari. Isi CV dan identitasmu tidak pernah disimpan."
)

PROMPTS = {
    "positions": "1/5 Posisi apa yang dicari? Pisahkan dengan koma.\nContoh: Mechanical Engineer, Maintenance Engineer",
    "skills": "2/5 Keahlian kamu? Pisahkan dengan koma.\nContoh: autocad, solidworks, plc\n(ketik - kalau mau dilewati)",
    "locations": "3/5 Lokasi? Maksimal 3, pisahkan dengan koma.\nContoh: Pekanbaru, Jakarta, Remote\n(ketik - untuk seluruh Indonesia)",
    "hours": "4/5 Lowongan dari berapa lama terakhir?\n1 = 24 jam\n2 = 7 hari\n3 = 30 hari",
    "mode": "5/5 Mau cari lewat mana?\n"
            "1 = 📚 Cek arsip Google Sheets (instan; isinya hasil pengumpulan otomatis jam 12 siang & 12 malam WIB "
            "dan pencarian orang lain)\n"
            "2 = 🌐 Bot kumpulkan data langsung dari web (2–3 menit, paling baru)",
}
STEPS = ["positions", "skills", "locations", "hours", "mode"]


def web_url():
    return os.environ.get("WEB_URL", "").strip().rstrip("/.")


def bot_username():
    """Terima 'jobfinder_id_bot', '@jobfinder_id_bot', atau 'https://t.me/jobfinder_id_bot'."""
    v = os.environ.get("TELEGRAM_BOT_USERNAME", "").strip()
    v = re.sub(r"^(https?://)?(t\.me/)?@?", "", v, flags=re.I)
    return v.strip().strip("/.")


def web_buttons():
    u = web_url()
    return [("🌐 Buka website Job Finder", u)] if u.startswith("http") else None


def _sheet_buttons(url):
    return [("📊 Buka hasil di Google Sheets", url)] if url else None


def result_buttons(params):
    """Tautan pencarian langsung ke situs lain + website kita."""
    links = [(f"🔗 Cari juga di {n}", u) for n, u in
             search_links(params.get("positions"), params.get("locations"), params.get("kind", "kerja"))]
    return links + (web_buttons() or [])


def public_config():
    bot = bot_username()
    return {
        "enabled": bool(bot and os.environ.get("TELEGRAM_BOT_TOKEN")),
        "bot": bot,
        "channel": os.environ.get("TELEGRAM_CHANNEL_URL", "").strip(),
    }


def clean_list(text, limit):
    out = []
    for x in re.split(r"[,;\n]", text or ""):
        x = x.strip()[:60]
        if x and x != "-" and x.lower() not in [o.lower() for o in out]:
            out.append(x)
    return out[:limit]


# ---------- kirim pesan (c = konteks pesan masuk: chat, kunci per-orang, grup/pribadi) ----------
def _say(c, text, buttons=None):
    send_telegram(text, c["chat"], buttons=buttons)


def _ask(c, text):
    """Kirim pertanyaan. Di grup dikirim sebagai balasan + force_reply (selective): jawaban orang itu
    pasti sampai ke bot walau privacy mode BotFather masih aktif, dan hanya dia yang diminta menjawab."""
    if not c["group"] or not c.get("mid"):
        return _say(c, text)
    try:
        r = requests.post(
            f"https://api.telegram.org/bot{os.environ['TELEGRAM_BOT_TOKEN']}/sendMessage", timeout=20,
            json={"chat_id": c["chat"], "text": text, "disable_web_page_preview": True,
                  "reply_to_message_id": c["mid"], "allow_sending_without_reply": True,
                  "reply_markup": {"force_reply": True, "selective": True}})
        r.raise_for_status()
    except Exception as e:
        print("[warn] kirim pertanyaan gagal:", e)
        _say(c, text)


# ---------- hasil pencarian dari website (/start <id>) ----------
def _send_search(c, token, jobs):
    job = jobs.get(token)
    if not job:
        return _say(c, "Hasil pencarian tidak ditemukan atau sudah kedaluwarsa. "
                       "Silakan cari ulang di website lalu klik tombol Telegram lagi.")
    if job["status"] == "running":
        return _say(c, "Pencarian masih berjalan. Tunggu selesai di website, lalu klik tombol lagi.")
    if job["status"] != "done":
        return _say(c, "Pencarian ini gagal, silakan ulangi di website.")

    results = job["results"]
    good = [j for j in results if j["match"] >= MIN_MATCH][:10]
    if "sheet" not in job:
        job["sheet"] = export_to_sheet(results, job.get("params"))
    _say(c, build_summary(results, job.get("params"), MIN_MATCH), buttons=_sheet_buttons(job.get("sheet")))
    for msg in build_messages(good):
        _say(c, msg)


# ---------- menampilkan hasil ----------
def _deliver(c, results, params, note=""):
    """Ringkasan + sheet khusus pencarian ini + 10 lowongan teratas."""
    good = [j for j in results if j["match"] >= MIN_MATCH][:10]
    sheet = export_to_sheet(results, params)   # sheet khusus pencarian ini, untuk pengguna
    who = f"👤 {c['name']}\n" if c["group"] and c.get("name") else ""
    _say(c, who + note + build_summary(results, params, MIN_MATCH), buttons=_sheet_buttons(sheet))
    for msg in build_messages(good):
        _say(c, msg)
    return good


# ---------- pencarian LANGSUNG ke web ----------
def _default_sources():
    return [x["id"] for x in catalog() if x["available"] and x["default"]] or ["linkedin"]


def _run_live(c, params):
    k, now = c["key"], time.time()
    if now - _last.get(k, 0) < COOLDOWN:
        wait = int(COOLDOWN - (now - _last[k]))
        return _say(c, f"Tunggu sekitar {wait} detik lagi sebelum pencarian langsung berikutnya ya.")
    if not _slots.acquire(blocking=False):
        return _say(c, "Bot sedang sibuk melayani pencarian lain. Coba lagi 1–2 menit lagi.")
    _last[k] = now
    try:
        _say(c, "🔎 Bot mengumpulkan lowongan langsung dari web, sekitar 2–3 menit. Mohon ditunggu...")
        results = run_search(positions=params["positions"], skills=params["skills"],
                             locations=params["locations"], hours=params["hours"],
                             pages=1, max_detail=30, sources=params["sources"],
                             kind=params.get("kind", "kerja"))
        save_search_async(results, params, MIN_MATCH, "telegram")   # semua lowongan relevan masuk arsip
        _deliver(c, results, params)
        _say(c, "Mau lihat lebih banyak? Buka pencarian langsung di situs lain atau website kami. "
                "Pencarian baru: /cari, /magang, /skill, /cv.", buttons=result_buttons(params))
    except Exception as e:
        print("[warn] pencarian telegram gagal:", e)
        _say(c, "Maaf, pencarian gagal. Coba lagi beberapa menit lagi.")
    finally:
        _slots.release()


# ---------- pencarian di ARSIP Google Sheets ----------
def _archive_jobs(params):
    """Ambil lowongan dari arsip lalu hitung kecocokannya dengan skill & posisi pengguna ini.
    Arsip hanya menyimpan judul, lokasi, dan skill yang cocok (bukan deskripsi penuh), jadi
    pencocokannya memakai itu. None bila arsip tidak bisa dibaca."""
    items = fetch_archive(params)
    if items is None:
        return None
    ctx = divisions.context(params["positions"], params["skills"])
    out = []
    for it in items:
        srcs = [x.strip() for x in it.get("sources", "").split(",") if x.strip()] or ["Arsip"]
        job = {"id": "", "title": it["title"], "company": it.get("company") or "-",
               "location": it.get("location") or "-", "posted": it.get("posted", ""), "posted_date": "",
               "url": it["url"], "source": srcs[0], "sources": srcs,
               "job_type": it.get("job_type", ""), "seniority": it.get("seniority", ""),
               "description": it.get("skills", "").replace("≈", " ")}
        out.append(score_job(job, params["skills"], params["positions"], ctx))
    out = [j for j in out if j["match"] > 0]
    out.sort(key=lambda j: j["match"], reverse=True)
    return out


def _run_archive(c, params):
    k, now = c["key"], time.time()
    if now - _last_arc.get(k, 0) < ARCHIVE_COOLDOWN:
        return _say(c, "Sebentar ya, tunggu beberapa detik sebelum cek arsip lagi.")
    _last_arc[k] = now
    _params[k] = (params, now)          # supaya /langsung bisa mengulang dengan data yang sama
    _say(c, "📚 Mengecek arsip Google Sheets...")
    try:
        results = _archive_jobs(params)
    except Exception as e:
        print("[warn] cek arsip gagal:", e)
        results = None
    if results is None:
        _say(c, "Arsip belum bisa dibaca (fitur Google Sheets belum aktif atau sedang bermasalah). "
                "Aku cari langsung ke web saja ya.")
        return _run_live(c, params)
    good = [j for j in results if j["match"] >= MIN_MATCH]
    if not good:
        return _say(c, "📚 Arsip belum punya lowongan yang cocok dengan posisi, keahlian, dan lokasi itu "
                       "dalam rentang waktu yang dipilih.\n\nKetik /langsung supaya bot mengumpulkan data "
                       "langsung dari web (2–3 menit), atau coba buka situs lain di bawah.",
                    buttons=result_buttons(params))
    note = f"📚 Hasil dari ARSIP Google Sheets ({len(good)} cocok dari {len(results)} lowongan terarsip)\n\n"
    _deliver(c, results, params, note)
    _say(c, "Ini dari arsip, jadi bisa saja belum termasuk lowongan paling baru. "
            "Mau data langsung dari web? Ketik /langsung.", buttons=result_buttons(params))


def _langsung(c):
    p = _params.get(c["key"])
    if not p or time.time() - p[1] > LAST_TTL:
        return _say(c, "Belum ada pencarian sebelumnya. Mulai dengan /cari, /skill, atau /cv dulu ya.")
    _run_live(c, p[0])


# ---------- input pengguna ----------
def _build_params(positions, skills, locations, hours, kind="kerja"):
    return dict(positions=positions, skills=skills or positions,
                locations=locations or ["Indonesia"], hours=hours,
                sources=_default_sources(), kind=kind)


def _begin_mode(c, data):
    """Semua data sudah lengkap: tanya mau cek arsip atau cari langsung."""
    _state[c["key"]] = {"step": "mode", "data": data, "ts": time.time()}
    _ask(c, PROMPTS["mode"])


def _oneliner(c, arg, kind="kerja"):
    parts = [p.strip() for p in arg.split("|")]
    positions = clean_list(parts[0], 5)
    if not positions:
        cmd = "magang" if kind == "magang" else "cari"
        return _say(c, f"Posisinya belum terbaca. Contoh:\n/{cmd} python developer | python, flask | Jakarta")
    skills = clean_list(parts[1], 25) if len(parts) > 1 else []
    locations = clean_list(parts[2], 3) if len(parts) > 2 else []
    _begin_mode(c, dict(positions=positions, skills=skills, locations=locations, hours=168, kind=kind))


def _skill_search(c, arg):
    """/skill python, flask, docker | Jakarta  -> posisi diambil dari 3 skill pertama."""
    parts = [p.strip() for p in arg.split("|")]
    skills = clean_list(parts[0], 25)
    if not skills:
        return _say(c, "Tulis keahlianmu, dipisah koma. Contoh:\n/skill python, flask, docker | Jakarta")
    locations = clean_list(parts[1], 3) if len(parts) > 1 else []
    _begin_mode(c, dict(positions=skills[:3], skills=skills, locations=locations, hours=168, kind="kerja"))


def _begin_guided(c, kind="kerja"):
    _state[c["key"]] = {"step": "positions", "data": {"kind": kind}, "ts": time.time()}
    if kind == "magang":
        _say(c, "🎓 Mode magang / internship.")
    _ask(c, PROMPTS["positions"])


MODE_ALIAS = {"arsip": "1", "sheet": "1", "sheets": "1", "langsung": "2", "web": "2"}


def _continue_guided(c, text):
    st = _state[c["key"]]
    step, d = st["step"], st["data"]
    if step == "positions":
        d["positions"] = clean_list(text, 5)
        if not d["positions"]:
            return _ask(c, "Posisi wajib diisi. " + PROMPTS["positions"])
    elif step == "skills":
        d["skills"] = clean_list(text, 25)
    elif step == "locations":
        d["locations"] = clean_list(text, 3)
    elif step == "hours":
        key = text.strip()
        if key not in HOURS:
            return _ask(c, "Balas dengan angka 1, 2, atau 3.\n" + PROMPTS["hours"])
        d["hours"] = HOURS[key]
    elif step == "mode":
        key = MODE_ALIAS.get(text.strip().lower(), text.strip())
        if key not in ("1", "2"):
            return _ask(c, "Balas dengan angka 1 atau 2.\n" + PROMPTS["mode"])
        _state.pop(c["key"], None)
        params = _build_params(d["positions"], d.get("skills", []), d.get("locations", []),
                               d.get("hours", 168), d.get("kind", "kerja"))
        return _run_archive(c, params) if key == "1" else _run_live(c, params)
    st["step"] = STEPS[STEPS.index(step) + 1]
    st["ts"] = time.time()
    _ask(c, PROMPTS[st["step"]])


# ---------- CV lewat Telegram ----------
def _begin_cv(c, kind="kerja"):
    _state[c["key"]] = {"step": "cv", "data": {"kind": kind}, "ts": time.time()}
    _ask(c, "📄 Kirim file CV kamu di sini (PDF atau DOCX, maks 5 MB).\n"
            "Isi CV hanya dibaca untuk mengambil posisi & keahlian, tidak disimpan.\n"
            "Ketik /batal untuk membatalkan.")


def _download(file_id):
    token = os.environ["TELEGRAM_BOT_TOKEN"]
    r = requests.get(f"https://api.telegram.org/bot{token}/getFile",
                     params={"file_id": file_id}, timeout=20)
    r.raise_for_status()
    path = r.json()["result"]["file_path"]
    f = requests.get(f"https://api.telegram.org/file/bot{token}/{path}", timeout=60)
    f.raise_for_status()
    return f.content


def _handle_cv(c, doc):
    name = (doc.get("file_name") or "cv").lower()
    if not name.endswith(CV_EXT):
        return _say(c, "Format belum didukung. Kirim CV dalam bentuk PDF atau DOCX.")
    if (doc.get("file_size") or 0) > MAX_CV_BYTES:
        return _say(c, "File terlalu besar (maks 5 MB).")
    _say(c, "📥 CV diterima, sedang dibaca...")
    try:
        text = extract_text(name, _download(doc["file_id"]))
    except Exception as e:
        print("[warn] baca CV gagal:", e)
        return _say(c, "CV tidak bisa dibaca. Coba file lain, atau pakai /skill untuk input manual.")
    if len(text.strip()) < 30:
        return _say(c, "Teks CV tidak terbaca (mungkin PDF hasil scan). "
                       "Coba /skill python, flask | Jakarta untuk input manual.")
    prof = extract_profile(text)
    skills = clean_list(",".join(prof.get("skills", [])), 25)
    positions = clean_list(",".join(prof.get("positions", [])), 5) or skills[:3]
    if not positions:
        return _say(c, "Belum ada keahlian yang terdeteksi dari CV. "
                       "Pakai /cari atau /skill untuk input manual.")
    kind = (_state.get(c["key"]) or {}).get("data", {}).get("kind", "kerja")
    _state[c["key"]] = {"step": "locations", "ts": time.time(),
                        "data": {"positions": positions, "skills": skills, "kind": kind}}
    note = ("" if prof.get("source") == "claude" else
            "ℹ️ CV dibaca dengan pencocokan kata kunci. Kalau ada yang kurang, tambahkan lewat /skill.\n")
    _ask(c, "✅ Hasil baca CV\n"
            f"🔎 Posisi: {', '.join(positions)}\n"
            f"🛠 Skill: {', '.join(skills[:15]) or '-'}\n"
            + note + "\n" + PROMPTS["locations"].replace("3/5 ", ""))


def handle_update(update, jobs):
    msg = update.get("message") or {}
    chat = msg.get("chat") or {}
    sender = msg.get("from") or {}
    chat_id = chat.get("id")
    text = (msg.get("text") or "").strip()
    doc = msg.get("document")
    if not chat_id or sender.get("is_bot") or not (text or doc):
        return
    private = chat.get("type") == "private"
    c = {"chat": chat_id, "key": f"{chat_id}:{sender.get('id') or chat_id}", "group": not private,
         "mid": msg.get("message_id"), "name": sender.get("first_name") or sender.get("username") or ""}
    key = c["key"]
    if _state.get(key) and time.time() - _state[key]["ts"] > STATE_TTL:
        _state.pop(key, None)
    try:
        if doc:
            # file dikirim: baca sebagai CV bila sedang /cv, atau di chat pribadi
            if (_state.get(key) or {}).get("step") == "cv" or private:
                _handle_cv(c, doc)
        elif text.startswith("/"):
            cmd, _, arg = text.partition(" ")
            cmd, arg = cmd.split("@")[0].lower(), arg.strip()
            if cmd == "/start" and TOKEN_RE.match(arg):
                _state.pop(key, None)
                _send_search(c, arg, jobs)
            elif cmd in ("/start", "/help"):
                send_telegram(WELCOME, chat_id, buttons=web_buttons())
            elif cmd in ("/cari", "/input"):
                _state.pop(key, None)
                _oneliner(c, arg) if arg else _begin_guided(c)
            elif cmd == "/magang":
                _state.pop(key, None)
                _oneliner(c, arg, "magang") if arg else _begin_guided(c, "magang")
            elif cmd in ("/skill", "/keahlian"):
                _state.pop(key, None)
                _skill_search(c, arg)
            elif cmd == "/cv":
                _begin_cv(c, "magang" if arg.lower() == "magang" else "kerja")
            elif cmd == "/langsung":
                _state.pop(key, None)
                _langsung(c)
            elif cmd == "/web":
                if web_buttons():
                    send_telegram("Buka website Job Finder di HP atau laptop — bisa upload CV, "
                                  "pilih sumber lowongan, dan lihat hasil lengkap:", chat_id,
                                  buttons=web_buttons())
                else:
                    send_telegram("Alamat website belum diatur (env WEB_URL).", chat_id)
            elif cmd in ("/sheet", "/sheets"):
                send_telegram(SHEET_HELP, chat_id)
            elif cmd == "/batal":
                _state.pop(key, None)
                send_telegram("Dibatalkan. Ketik /cari kapan pun untuk mulai lagi.", chat_id)
        elif key in _state:
            if _state[key]["step"] == "cv":
                _say(c, "Aku menunggu file CV (PDF/DOCX). Kirim sebagai dokumen, atau /batal.")
            else:
                _continue_guided(c, text)
        elif private:
            send_telegram("Ketik /cari, /skill, atau /cv untuk mulai, atau /help untuk bantuan.",
                          chat_id, buttons=web_buttons())
    except Exception as e:  # jangan sampai webhook error karena satu chat bermasalah
        print("[warn] telegram:", e)