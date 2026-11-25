"""Simpan hasil pencarian ke Google Sheets lewat Google Apps Script (tanpa API key Google Cloud).

Tiap pencarian dibuat jadi satu spreadsheet baru (view-only untuk siapa pun yang punya link),
lalu link-nya dikirim ke pengguna. Isi CV TIDAK pernah dikirim ke sini, hanya data lowongan.

Env:
  GSHEET_WEBHOOK_URL  alamat Web App Apps Script (berakhiran /exec)
  GSHEET_SECRET       kata sandi yang sama dengan Script Property SECRET di Apps Script
"""
import os
import re
import threading
import time
from collections import deque
from datetime import datetime, timezone, timedelta

import requests

MAX_ROWS = 300
MAX_PER_HOUR = 30            # batasi karena bot & web bersifat publik
MAX_SAVES_PER_HOUR = 60      # batas simpan hasil pencarian pengguna ke arsip
_recent = deque()
_saves = deque()
STATUS = {"export": "belum pernah", "arsip": "belum pernah"}   # hasil percobaan terakhir, dilihat lewat /api/sheet-test

HEADERS = ["No", "Judul", "Perusahaan", "Lokasi", "Kecocokan", "Skill cocok",
           "Jenis", "Level", "Diposting", "Sumber", "Link"]
KIND = {"kerja": "Lowongan kerja", "magang": "Magang / internship"}
HOURS = {24: "24 jam terakhir", 168: "7 hari terakhir", 720: "30 hari terakhir"}


def enabled():
    return bool(os.environ.get("GSHEET_WEBHOOK_URL") and os.environ.get("GSHEET_SECRET"))


def _allowed():
    now = time.time()
    while _recent and now - _recent[0] > 3600:
        _recent.popleft()
    if len(_recent) >= MAX_PER_HOUR:
        return False
    _recent.append(now)
    return True


def _skills_cell(j):
    """Skill yang cocok; kalau tidak ada, istilah terkait bidangnya (diberi tanda ≈)."""
    if j.get("matched"):
        return ", ".join(j["matched"])
    return ("≈ " + ", ".join(j["related"])) if j.get("related") else ""


def _rows(results):
    out = []
    for i, j in enumerate(sorted(results, key=lambda x: x.get("match", 0), reverse=True)[:MAX_ROWS], 1):
        out.append([
            str(i), j.get("title", ""), j.get("company", ""), j.get("location", ""),
            f"{j.get('match', 0)}%", _skills_cell(j),
            j.get("job_type", ""), j.get("seniority", ""), j.get("posted", ""),
            ", ".join(j.get("sources") or [j.get("source", "")]), j.get("url", ""),
        ])
    return out


def export_to_sheet(results, params=None):
    """Kembalikan URL spreadsheet, atau None bila dimatikan / gagal."""
    if not enabled() or not results or not _allowed():
        return None
    params = params or {}
    wib = datetime.now(timezone(timedelta(hours=7))).strftime("%d-%m-%Y %H:%M")
    pos = ", ".join(params.get("positions") or [])[:50]
    info = [["Dibuat (WIB)", wib],
            ["Jenis", KIND.get(params.get("kind", "kerja"), "Lowongan kerja")],
            ["Posisi", ", ".join(params.get("positions") or [])],
            ["Skill", ", ".join(params.get("skills") or [])],
            ["Lokasi", ", ".join(params.get("locations") or [])],
            ["Rentang", HOURS.get(params.get("hours"), "")],
            ["Jumlah lowongan", str(len(results))]]
    try:
        r = requests.post(os.environ["GSHEET_WEBHOOK_URL"], timeout=90, json={
            "secret": os.environ["GSHEET_SECRET"],
            "title": f"Job Finder - {pos or 'hasil'} - {wib}",
            "headers": HEADERS, "rows": _rows(results), "info": info})
        r.raise_for_status()
        d = r.json()
        if d.get("ok") and d.get("url"):
            STATUS["export"] = "OK " + wib
            return d["url"]
        STATUS["export"] = f"DITOLAK {wib}: {d.get('error')}"
        print("[warn] gsheet ditolak:", d.get("error"))
    except Exception as e:
        STATUS["export"] = f"GAGAL {wib}: {str(e)[:150]}"
        print("[warn] gsheet gagal:", e)
    return None


# ---------------------------------------------------------------------------
# Arsip permanen untuk notifikasi harian (main.py / GitHub Actions)
# Satu spreadsheet tetap: semua lowongan relevan ditambahkan tiap hari, duplikat dilewati,
# lalu lowongan lama dicek ulang apakah masih dibuka.
# ---------------------------------------------------------------------------
_DATE_RE = re.compile(r"^\d{4}-\d{2}-\d{2}")


def wib_now():
    return datetime.now(timezone(timedelta(hours=7)))


def _posted_date(j):
    d = j.get("posted_date") or ""
    if not d and _DATE_RE.match(j.get("posted") or ""):
        d = j["posted"]
    return d[:10] if _DATE_RE.match(d) else ""


def _archive_row(j, wib, today):
    """18 kolom: 12 kolom utama + Status lowongan, Tgl posting, Terakhir dicek, Ditutup pada, Lama dibuka, Bidang."""
    return [wib, j.get("title", ""), j.get("company", ""), j.get("location", ""),
            int(j.get("match", 0)), _skills_cell(j),
            j.get("job_type", ""), j.get("seniority", ""), j.get("posted", ""),
            ", ".join(j.get("sources") or [j.get("source", "")]), "Belum dilamar", j.get("url", ""),
            "Aktif", _posted_date(j), today, "", "", j.get("division", "")]


def _post(payload, timeout=120):
    r = requests.post(os.environ["GSHEET_WEBHOOK_URL"], json=payload, timeout=timeout)
    r.raise_for_status()
    return r.json()


def save_to_master(results, top, params=None, min_match=25, stale_days=45, origin="harian", update_today=True):
    """Simpan SEMUA hasil relevan ke arsip tetap. results = semua hasil, top = yang ditampilkan hari ini.
    origin = asal pencarian (dicatat di tab Log); update_today=False agar tab "Hari Ini" tidak ditimpa.
    Kembalikan URL spreadsheet, atau None bila dimatikan / gagal."""
    if not enabled():
        STATUS["arsip"] = "DIMATIKAN: GSHEET_WEBHOOK_URL / GSHEET_SECRET belum diisi di server ini"
        return None
    if not results:
        STATUS["arsip"] = "dilewati: pencarian terakhir tidak menghasilkan lowongan sama sekali"
        return None
    params = params or {}
    now = wib_now()
    wib, today = now.strftime("%d-%m-%Y %H:%M"), now.strftime("%Y-%m-%d")
    keep = [j for j in sorted(results, key=lambda x: x.get("match", 0), reverse=True)
            if j.get("match", 0) >= min_match][:MAX_ROWS]
    today_headers = ["No", "Judul", "Perusahaan", "Lokasi", "Kecocokan", "Skill cocok", "Diposting", "Sumber"]
    payload = {
        "secret": os.environ["GSHEET_SECRET"], "mode": "master",
        "wib": wib, "today": today, "stale_days": stale_days, "skip_today": not update_today,
        "archive_rows": [_archive_row(j, wib, today) for j in keep],
        "today_headers": today_headers,
        "today_rows": [[str(i), j.get("title", ""), j.get("company", ""), j.get("location", ""),
                        int(j.get("match", 0)), _skills_cell(j), j.get("posted", ""),
                        ", ".join(j.get("sources") or [j.get("source", "")]), j.get("url", "")]
                       for i, j in enumerate(top, 1)],
        "log": [wib, len(results), len(keep), len(top),
                f"[{origin}] " + ", ".join(params.get("positions") or [])[:80]],
    }
    try:
        d = _post(payload)
        if d.get("ok") and d.get("url"):
            STATUS["arsip"] = f"OK {wib} [{origin}] baru={d.get('added')} sudah_ada={d.get('skipped')}"
            print(f"[sheet] baru: {d.get('added')}, sudah ada (ditandai masih aktif): {d.get('skipped')}")
            return d["url"]
        STATUS["arsip"] = f"DITOLAK {wib} [{origin}]: {d.get('error')}"
        print("[warn] gsheet ditolak:", d.get("error"))
    except Exception as e:
        STATUS["arsip"] = f"GAGAL {wib} [{origin}]: {str(e)[:150]}"
        print("[warn] gsheet gagal:", e)
    return None


def _allowed_save():
    now = time.time()
    while _saves and now - _saves[0] > 3600:
        _saves.popleft()
    if len(_saves) >= MAX_SAVES_PER_HOUR:
        return False
    _saves.append(now)
    return True


def save_search(results, params=None, min_match=25, origin="bot"):
    """Simpan hasil pencarian pengguna (bot Telegram / website) ke arsip tetap.
    Hanya data lowongan yang masuk; isi CV dan identitas pengguna tidak pernah dikirim."""
    if not enabled() or not results or not _allowed_save():
        return None
    return save_to_master(results, [], params, min_match, origin=origin, update_today=False)


def save_search_async(results, params=None, min_match=25, origin="bot"):
    """Sama seperti save_search tetapi di thread terpisah, supaya pengguna tidak menunggu."""
    def run():
        try:
            save_search(results, params, min_match, origin)
        except Exception as e:
            print("[warn] simpan pencarian ke arsip gagal:", e)
    threading.Thread(target=run, daemon=True).start()


def fetch_pending(limit=60, stale_days=45):
    """Minta daftar lowongan di arsip yang perlu dicek ulang. [] bila gagal."""
    if not enabled():
        return []
    try:
        d = _post({"secret": os.environ["GSHEET_SECRET"], "mode": "to_check", "limit": limit,
                   "stale_days": stale_days, "today": wib_now().strftime("%Y-%m-%d")}, timeout=90)
        if d.get("ok"):
            return d.get("items") or []
        print("[warn] daftar cek ulang ditolak:", d.get("error"))
    except Exception as e:
        print("[warn] daftar cek ulang gagal:", e)
    return []


def push_checks(checks, stale_days=45):
    """Kirim hasil cek ulang ke arsip. True bila berhasil."""
    if not enabled() or not checks:
        return False
    now = wib_now()
    try:
        d = _post({"secret": os.environ["GSHEET_SECRET"], "mode": "update_checks", "checks": checks,
                   "today": now.strftime("%Y-%m-%d"), "wib": now.strftime("%d-%m-%Y %H:%M"),
                   "stale_days": stale_days}, timeout=120)
        if d.get("ok"):
            return True
        print("[warn] update cek ulang ditolak:", d.get("error"))
    except Exception as e:
        print("[warn] update cek ulang gagal:", e)
    return False


# ---------------------------------------------------------------------------
# Cari di arsip (opsi "cek Google Sheet" di bot) + uji koneksi
# ---------------------------------------------------------------------------
def fetch_archive(params, limit=600):
    """Ambil lowongan di arsip yang masih dibuka dan masih dalam rentang waktu pencarian.
    Kembalikan list dict (judul, perusahaan, lokasi, skills, ...), atau None bila gagal / dimatikan.
    Filter lokasi & jenis (kerja/magang) dikerjakan di sini; pencocokan skor di telegram_bot."""
    if not enabled():
        return None
    days = max(1, round((params.get("hours") or 168) / 24))
    try:
        d = _post({"secret": os.environ["GSHEET_SECRET"], "mode": "query", "limit": limit,
                   "days": days, "today": wib_now().strftime("%Y-%m-%d")}, timeout=90)
    except Exception as e:
        print("[warn] baca arsip gagal:", e)
        return None
    if not d.get("ok"):
        print("[warn] baca arsip ditolak:", d.get("error"))
        return None
    items = d.get("items") or []

    locs = [l.lower() for l in (params.get("locations") or []) if l.lower() not in ("indonesia", "-")]
    if locs:
        items = [i for i in items if any(l in i.get("location", "").lower() for l in locs)]
    if params.get("kind") == "magang":
        rx = re.compile(r"(?<![a-z])(intern|internship|magang|praktik kerja|trainee)(?![a-z])", re.I)
        items = [i for i in items if rx.search(f"{i.get('title', '')} {i.get('job_type', '')}")]
    return items


def ping():
    """Uji koneksi ke Apps Script. Kembalikan (ok, pesan)."""
    if not enabled():
        return False, "GSHEET_WEBHOOK_URL dan/atau GSHEET_SECRET belum diisi di environment server ini"
    try:
        r = requests.post(os.environ["GSHEET_WEBHOOK_URL"], timeout=60,
                          json={"secret": os.environ["GSHEET_SECRET"], "mode": "ping"})
    except Exception as e:
        return False, f"tidak bisa menghubungi URL Apps Script: {e}"
    try:
        d = r.json()
    except ValueError:
        return False, (f"Apps Script tidak membalas JSON (HTTP {r.status_code}). Biasanya akses Web App bukan "
                       f"'Anyone' atau URL salah. Awal balasan: {r.text[:120]!r}")
    if d.get("ok"):
        return True, f"nyambung. versi script={d.get('version')}, baris arsip={d.get('rows')}, sheet={d.get('url')}"
    if "mode tidak dikenal" in str(d.get("error")):
        return False, "Apps Script masih versi LAMA. Deploy ulang: Terapkan > Kelola deployment > Edit > Versi baru."
    return False, f"Apps Script menjawab: {d.get('error')}"