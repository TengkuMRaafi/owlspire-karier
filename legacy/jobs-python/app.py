import hmac
import os
import secrets
import threading
import time
from flask import Flask, render_template, request, jsonify, abort

from main import run_search
from summary import analyze
from cv_parser import extract_text, extract_profile
from sources import catalog, search_links
import telegram_bot
import sheets

app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = 5 * 1024 * 1024  # maks 5 MB
JOBS = {}
JOB_TTL = 6 * 3600   # hasil disimpan 6 jam (cukup untuk dibuka lewat Telegram)
MAX_RUNNING = 3      # batasi pencarian bersamaan karena situs ini publik


def prune_jobs():
    now = time.time()
    for k in [k for k, v in JOBS.items() if now - v.get("ts", now) > JOB_TTL]:
        JOBS.pop(k, None)


def clean(items, limit):
    out = []
    for x in items or []:
        x = str(x).strip()[:60]
        if x and x.lower() not in [o.lower() for o in out]:
            out.append(x)
    return out[:limit]


@app.get("/")
def index():
    return render_template("index.html")


@app.get("/api/sources")
def api_sources():
    return jsonify(catalog())


@app.get("/api/config")
def api_config():
    return jsonify(telegram=telegram_bot.public_config(), sheets=sheets.enabled())


@app.get("/api/sheet-test")
def api_sheet_test():
    """Diagnosa koneksi ke Google Sheets. Buka: /api/sheet-test?key=<TELEGRAM_WEBHOOK_SECRET>"""
    secret = os.environ.get("TELEGRAM_WEBHOOK_SECRET", "")
    if not secret or not hmac.compare_digest(request.args.get("key", ""), secret):
        abort(403)
    ok, msg = sheets.ping()
    return jsonify(
        koneksi_ok=ok, pesan=msg,
        env_terisi={k: bool(os.environ.get(k)) for k in
                    ("GSHEET_WEBHOOK_URL", "GSHEET_SECRET", "TELEGRAM_BOT_TOKEN", "WEB_URL")},
        percobaan_terakhir=sheets.STATUS)


@app.post("/api/cv")
def api_cv():
    f = request.files.get("cv")
    if not f:
        return jsonify(error="File CV belum dipilih"), 400
    text = extract_text(f.filename, f.read())
    if len(text.strip()) < 30:
        return jsonify(error="Teks CV tidak terbaca (mungkin PDF hasil scan)"), 400
    return jsonify(extract_profile(text))


def worker(job_id, params):
    st = JOBS[job_id]

    def progress(msg, pct):
        st.update(msg=msg, pct=pct)

    try:
        st["results"] = run_search(**params, progress=progress, warnings=st["warnings"])
        st["insights"] = analyze(st["results"], st.get("params"))
        st.update(status="done", pct=100, msg="Selesai")
        sheets.save_search_async(st["results"], st.get("params"), telegram_bot.MIN_MATCH, "web")
    except Exception as e:
        st.update(status="error", msg=f"Gagal: {e}")


@app.post("/api/search")
def api_search():
    d = request.get_json(force=True)
    skills = clean(d.get("skills"), 25)
    positions = clean(d.get("positions"), 5) or skills[:3]
    if not positions:
        return jsonify(error="Isi minimal satu skill atau posisi"), 400
    skills = skills or positions
    locations = clean(d.get("locations"), 3) or ["Indonesia"]
    hours = int(d.get("hours", 24))
    hours = hours if hours in (24, 168, 720) else 24
    kind = "magang" if d.get("kind") == "magang" else "kerja"
    avail = {c["id"] for c in catalog() if c["available"]}
    sources = [s for s in (d.get("sources") or ["linkedin"]) if s in avail] or ["linkedin"]

    prune_jobs()
    if sum(1 for v in JOBS.values() if v["status"] == "running") >= MAX_RUNNING:
        return jsonify(error="Server sedang sibuk, coba lagi sebentar lagi."), 429

    job_id = secrets.token_urlsafe(9)  # 12 karakter; aman dipakai sebagai parameter t.me/?start=
    params = dict(positions=positions, skills=skills, locations=locations,
                  hours=hours, pages=1, max_detail=40, sources=sources, kind=kind)
    JOBS[job_id] = {"status": "running", "pct": 0, "msg": "Memulai...", "results": [],
                    "warnings": [], "ts": time.time(),
                    "links": search_links(positions, locations, kind),
                    "params": dict(positions=positions, skills=skills, locations=locations,
                                   hours=hours, sources=sources, kind=kind)}
    threading.Thread(target=worker, args=(job_id, params), daemon=True).start()
    return jsonify(id=job_id)


@app.post("/api/export/<job_id>")
def api_export(job_id):
    job = JOBS.get(job_id)
    if not job or job["status"] != "done":
        return jsonify(error="Hasil pencarian belum siap atau sudah kedaluwarsa"), 400
    if not sheets.enabled():
        return jsonify(error="Fitur Google Sheets belum diaktifkan"), 400
    if not job.get("sheet"):
        job["sheet"] = sheets.export_to_sheet(job["results"], job.get("params"))
    if not job["sheet"]:
        return jsonify(error="Gagal menyimpan ke Google Sheets, coba lagi nanti"), 502
    return jsonify(url=job["sheet"])


@app.get("/api/status/<job_id>")
def api_status(job_id):
    return jsonify(JOBS.get(job_id, {"status": "error", "msg": "Job tidak ditemukan"}))


@app.post("/telegram/webhook")
def telegram_webhook():
    secret = os.environ.get("TELEGRAM_WEBHOOK_SECRET", "")
    got = request.headers.get("X-Telegram-Bot-Api-Secret-Token", "")
    if not secret or not hmac.compare_digest(got, secret):
        abort(403)
    update = request.get_json(silent=True) or {}
    # balas Telegram cepat; pengiriman pesan jalan di thread terpisah
    threading.Thread(target=telegram_bot.handle_update, args=(update, JOBS), daemon=True).start()
    return "ok"


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=False)