"""Cek apakah sebuah lowongan masih dibuka.

check_job(url) -> (state, alasan)
  state: "open"    lowongan masih ada
         "closed"  lowongan sudah ditutup / dihapus
         "unknown" tidak bisa dipastikan (situs memblokir, halaman kosong, dll.)
         "blocked" LinkedIn membatasi permintaan -> hentikan cek LinkedIn untuk hari ini
"""
import re
import random
import time

import requests

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
                  "(KHTML, like Gecko) Chrome/124.0 Safari/537.36",
    "Accept-Language": "id-ID,id;q=0.9,en;q=0.8",
}
LI_DETAIL = "https://www.linkedin.com/jobs-guest/jobs/api/jobPosting/{id}"

# kalimat yang muncul di halaman lowongan yang sudah ditutup (huruf kecil)
CLOSED_MARKERS = (
    "no longer accepting applications", "tidak lagi menerima lamaran", "tidak lagi menerima",
    "this job is no longer available", "job is no longer available", "no longer available",
    "position has been filled", "this job has expired", "job has expired", "this position is closed",
    "lowongan ini sudah ditutup", "lowongan telah ditutup", "lowongan sudah ditutup",
    "lowongan tidak tersedia", "lowongan ini tidak tersedia",
)


def _linkedin_id(url):
    ids = re.findall(r"\d{7,}", url or "")
    return ids[-1] if ids else ""


def _check_linkedin(url):
    jid = _linkedin_id(url)
    if not jid:
        return "unknown", "id LinkedIn tidak ditemukan"
    r = requests.get(LI_DETAIL.format(id=jid), headers=HEADERS, timeout=20)
    if r.status_code in (429, 999, 403):
        return "blocked", f"HTTP {r.status_code}"
    if r.status_code in (404, 410):
        return "closed", f"HTTP {r.status_code}"
    if r.status_code != 200:
        return "unknown", f"HTTP {r.status_code}"
    html = r.text.lower()
    if "closed-job" in html or any(m in html for m in CLOSED_MARKERS):
        return "closed", "tidak lagi menerima lamaran"
    if "top-card-layout__title" in html or "show-more-less-html" in html or "topcard" in html:
        return "open", "halaman lowongan masih ada"
    return "unknown", "halaman kosong"


def _check_generic(url):
    r = requests.get(url, headers=HEADERS, timeout=20, allow_redirects=True)
    if r.status_code in (404, 410):
        return "closed", f"HTTP {r.status_code}"
    if r.status_code != 200:
        return "unknown", f"HTTP {r.status_code}"
    html = r.text.lower()
    if any(m in html for m in CLOSED_MARKERS):
        return "closed", "halaman menyatakan lowongan ditutup"
    return "open", "halaman masih bisa dibuka"


def check_job(url, source=""):
    try:
        if "linkedin.com" in (url or ""):
            return _check_linkedin(url)
        return _check_generic(url)
    except requests.RequestException as e:
        return "unknown", f"gagal: {str(e)[:60]}"


def recheck(items, progress=print):
    """items: [{'url':..., 'source':...}] -> daftar hasil untuk dikirim ke arsip."""
    out, li_blocked = [], 0
    for n, it in enumerate(items, 1):
        url = it.get("url", "")
        is_li = "linkedin.com" in url
        if is_li and li_blocked >= 2:           # LinkedIn sedang membatasi: lewati sisanya
            continue
        state, why = check_job(url, it.get("source", ""))
        if state == "blocked":
            li_blocked += 1
            continue
        li_blocked = 0 if is_li else li_blocked
        out.append({"url": url, "state": state, "reason": why})
        progress(f"[cek {n}/{len(items)}] {state}: {url[:70]}")
        time.sleep(random.uniform(1.5, 3) if is_li else random.uniform(0.5, 1.2))
    return out