import os
import re
import time
import random
import requests
import yaml

from sources import FETCHERS, LABELS, is_intern
from summary import build_summary
from sheets import export_to_sheet, save_to_master, fetch_pending, push_checks
from checker import recheck
from cv_parser import _bank_skills
import divisions
from bs4 import BeautifulSoup

SEARCH_URL = "https://www.linkedin.com/jobs-guest/jobs/api/seeMoreJobPostings/search"
DETAIL_URL = "https://www.linkedin.com/jobs-guest/jobs/api/jobPosting/{id}"
HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
                  "(KHTML, like Gecko) Chrome/124.0 Safari/537.36",
    "Accept-Language": "id-ID,id;q=0.9,en;q=0.8",
}


def load_config(path="config.yaml"):
    with open(path, encoding="utf-8") as f:
        return yaml.safe_load(f)


def _txt(el):
    return el.get_text(" ", strip=True) if el else ""


def search_jobs(keyword, location, hours, pages=1, kind="kerja"):
    """Ambil daftar lowongan dari endpoint publik LinkedIn (tanpa login)."""
    jobs = []
    for page in range(pages):
        params = {
            "keywords": keyword,
            "location": location,
            "f_TPR": f"r{hours * 3600}",
            "start": page * 25,
        }
        if kind == "magang":
            params["f_JT"] = "I"  # filter jenis pekerjaan: Internship
        r = requests.get(SEARCH_URL, params=params, headers=HEADERS, timeout=20)
        if r.status_code != 200:
            print(f"[warn] '{keyword}' page {page}: HTTP {r.status_code}")
            break
        soup = BeautifulSoup(r.text, "html.parser")
        cards = soup.select("li")
        if not cards:
            break
        for c in cards:
            link = c.select_one("a.base-card__full-link")
            title = c.select_one(".base-search-card__title")
            urn = c.select_one("[data-entity-urn]")
            if not (link and title and urn):
                continue
            tm = c.select_one("time")
            img = c.select_one("img")
            logo = (img.get("data-delayed-url") or img.get("src") or "") if img else ""
            if logo.startswith("data:"):
                logo = ""
            jobs.append({
                "id": urn["data-entity-urn"].split(":")[-1],
                "title": _txt(title),
                "company": _txt(c.select_one(".base-search-card__subtitle")) or "-",
                "location": _txt(c.select_one(".job-search-card__location")) or "-",
                "posted": _txt(tm),
                "posted_date": (tm.get("datetime") or "")[:10] if tm else "",
                "logo": logo,
                "url": link["href"].split("?")[0],
                "source": "LinkedIn",
            })
        time.sleep(random.uniform(2, 4))
    return jobs


CRITERIA = {
    "seniority": ("seniority", "senioritas"),
    "job_type": ("employment", "jenis pekerjaan", "jenis pekerja"),
    "function": ("job function", "fungsi"),
    "industry": ("industr",),
}


def fetch_detail(job_id):
    out = {"description": "", "seniority": "", "job_type": "",
           "function": "", "industry": "", "applicants": ""}
    try:
        r = requests.get(DETAIL_URL.format(id=job_id), headers=HEADERS, timeout=20)
        if r.status_code != 200:
            return out
    except requests.RequestException:
        return out
    soup = BeautifulSoup(r.text, "html.parser")
    out["description"] = _txt(soup.select_one(".show-more-less-html__markup"))
    for li in soup.select("li.description__job-criteria-item"):
        k = _txt(li.select_one("h3")).lower()
        v = _txt(li.select_one("span"))
        for field, keys in CRITERIA.items():
            if any(x in k for x in keys):
                out[field] = v
    out["applicants"] = _txt(soup.select_one(".num-applicants__caption"))
    return out


def score_job(job, skills, positions, ctx=None):
    """Persentase kecocokan, berlaku untuk bidang apa pun (bukan hanya IT):
      - 60%  keahlian pengguna yang muncul di judul/deskripsi (+ setengah poin untuk istilah khas bidangnya)
      - 35%  kedekatan judul lowongan dengan posisi yang dicari (sinonim Indonesia/Inggris dihitung)
    Judul yang sama sekali tidak terkait bidang pengguna dikurangi, supaya skill umum seperti
    'excel' tidak meloloskan lowongan bidang lain."""
    ctx = ctx or divisions.context(positions, skills)
    title = job["title"].lower()
    desc = job.get("description", "")
    text = f"{title} {desc.lower()}"
    matched = []
    for s in skills:
        pattern = r"(?<![a-z0-9])" + re.escape(s.lower()) + r"(?![a-z0-9])"
        if re.search(pattern, text):
            matched.append(s)
    extra = divisions.related(text, ctx, exclude=[s.lower() for s in skills])
    base = max(3, min(len(skills), 8))
    skill_pts = min(100, 100 * (len(matched) + 0.5 * min(len(extra), 4)) / base)
    title_pts = divisions.title_points(title, ctx)
    pct = 0.6 * skill_pts + title_pts
    if title_pts == 0 and ctx["divs"]:
        pct *= 0.6
    job["match"] = min(100, round(pct))
    job["matched"] = matched
    job["related"] = extra[:6]
    job["division"] = divisions.classify(title, text, ctx["label"])
    demanded = _bank_skills(text)   # skill yang diminta lowongan (bahan analisis ringkasan)
    job["demanded"] = demanded + [d for d in divisions.demanded(text) if d not in demanded]
    job["snippet"] = (desc[:320] + "...") if len(desc) > 320 else desc
    job.pop("description", None)
    return job


def run_search(positions, skills, locations, hours=24, pages=1, max_detail=40,
               progress=None, sources=None, warnings=None, kind="kerja"):
    log = progress or (lambda msg, pct: None)
    warn = warnings if warnings is not None else []
    sources = sources or ["linkedin"]
    ctx = divisions.context(positions, skills)   # bidang dideteksi dari posisi + keahlian pencarian
    seen = {}

    def add(j):
        if not j.get("url"):
            return
        k = re.sub(r"\W+", "", (j["title"] + j["company"]).lower())
        if k in seen:  # lowongan yang sama dari sumber lain digabung
            if j["source"] not in seen[k]["sources"]:
                seen[k]["sources"].append(j["source"])
        else:
            j["sources"] = [j["source"]]
            seen[k] = j

    for n, name in enumerate(sources):
        label = LABELS.get(name, name)
        log(f"Mengambil dari {label}...", int(5 + 35 * n / len(sources)))
        try:
            if name == "linkedin":
                combos = [(p, l) for p in positions for l in locations][:8]
                batches = []
                for i, (p, l) in enumerate(combos):
                    log(f"LinkedIn: '{p}' di {l} ({i + 1}/{len(combos)})",
                        int(5 + 35 * (n + i / len(combos)) / len(sources)))
                    batches.append(search_jobs(p, l, hours, pages, kind))
                # selang-seling antar posisi, supaya jatah baca deskripsi tidak habis di posisi pertama saja
                for k in range(max((len(b) for b in batches), default=0)):
                    for b in batches:
                        if k < len(b):
                            add(b[k])
            elif name in FETCHERS:
                got = FETCHERS[name](positions=positions, skills=skills,
                                     locations=locations, hours=hours, kind=kind)
                if kind == "magang" and name in ("remoteok", "arbeitnow", "jobicy"):
                    got = [j for j in got if is_intern(j)]  # feed global tanpa filter magang
                for j in got:
                    add(j)
        except Exception as e:
            warn.append(f"{label}: {str(e)[:80]}")
            continue
        if not any(label in j["sources"] for j in seen.values()):
            warn.append(f"{label}: tidak ada hasil")

    li = [j for j in seen.values() if j["source"] == "LinkedIn"]
    li.sort(key=lambda j: -divisions.title_points(j["title"], ctx))   # judul paling relevan dibaca dulu
    li = li[:max_detail]
    for i, job in enumerate(li):
        log(f"Membaca deskripsi LinkedIn {i + 1}/{len(li)}", int(40 + 55 * i / max(1, len(li))))
        job.update(fetch_detail(job["id"]))
        time.sleep(random.uniform(1.5, 3))

    pool = [j for j in seen.values() if "description" in j]
    results = [score_job(j, skills, positions, ctx) for j in pool]
    # sumber feed umum (remote) tidak mem-filter kata kunci, jadi buang yang 0%
    results = [j for j in results if j["match"] > 0 or j["source"] == "LinkedIn"]
    results.sort(key=lambda j: j["match"], reverse=True)
    return results


def format_job(i, j):
    meta = " · ".join(x for x in (j.get("posted"), j.get("job_type"), j.get("seniority")) if x)
    lines = [
        f"{i}. {j['title']}  —  🎯 {j['match']}%",
        f"   🏢 {j['company']}  📍 {j['location']}",
    ]
    if meta:
        lines.append(f"   🕒 {meta}")
    if j.get("matched"):
        lines.append(f"   ✅ {', '.join(j['matched'][:8])}")
    elif j.get("related"):
        lines.append(f"   🧩 Terkait bidangmu: {', '.join(j['related'][:5])}")
    lines.append(f"   🌐 via {', '.join(j.get('sources', []))}")
    lines.append(f"   🔗 {j['url']}")
    return "\n".join(lines) + "\n\n"


def build_messages(jobs, max_len=3800):
    if not jobs:
        return ["Hari ini belum ada lowongan baru yang cocok."]
    cur = f"🔔 {len(jobs)} lowongan paling cocok untuk kamu:\n\n"
    chunks = []
    for i, j in enumerate(jobs, 1):
        block = format_job(i, j)
        if len(cur) + len(block) > max_len:
            chunks.append(cur)
            cur = ""
        cur += block
    chunks.append(cur)
    return chunks


def send_telegram(text, chat_id=None, buttons=None):
    """chat_id kosong = kirim ke TELEGRAM_CHAT_ID (channel/grup harian).
    buttons: daftar (label, url) untuk tombol di bawah pesan."""
    token = os.environ["TELEGRAM_BOT_TOKEN"]
    chat_id = chat_id or os.environ["TELEGRAM_CHAT_ID"]
    payload = {"chat_id": chat_id, "text": text, "disable_web_page_preview": True}
    if buttons:
        payload["reply_markup"] = {"inline_keyboard": [[{"text": t, "url": u}] for t, u in buttons]}
    r = requests.post(
        f"https://api.telegram.org/bot{token}/sendMessage",
        json=payload,
        timeout=20,
    )
    r.raise_for_status()


def recheck_archive(cfg):
    """Cek ulang lowongan di arsip: masih dibuka atau sudah ditutup. Kembalikan (dicek, ditutup)."""
    limit = cfg.get("recheck_limit", 60)
    stale = cfg.get("recheck_stop_days", 45)
    if limit <= 0:
        return None
    items = fetch_pending(limit, stale)
    if not items:
        return None
    checks = recheck(items, progress=print)
    if not checks or not push_checks(checks, stale):
        return None
    return len(checks), sum(1 for c in checks if c["state"] == "closed")


def main():
    cfg = load_config()
    warns = []
    results = run_search(
        cfg["keywords"], cfg["skills"], cfg["locations"],
        cfg.get("hours_back", 24), cfg.get("pages", 1), cfg.get("max_detail_fetch", 60),
        progress=lambda m, p: print(f"[{p}%] {m}"),
        sources=cfg.get("sources", ["linkedin"]), warnings=warns, kind=cfg.get("kind", "kerja"),
    )
    for w in warns:
        print("[warn]", w)
    min_match = cfg.get("min_match", 25)
    good = [j for j in results if j["match"] >= min_match][: cfg.get("top_n", 15)]
    params = dict(positions=cfg["keywords"], skills=cfg["skills"], locations=cfg["locations"],
                  hours=cfg.get("hours_back", 24), sources=cfg.get("sources", ["linkedin"]),
                  kind=cfg.get("kind", "kerja"))
    summary = build_summary(results, params, min_match)
    sheet = save_to_master(results, good, params, min_match, cfg.get("recheck_stop_days", 45)) or export_to_sheet(results, params)
    if sheet:
        summary += f"\n\n📊 Hasil lengkap tersimpan di Google Sheets:\n{sheet}"
        rc = recheck_archive(cfg)
        if rc:
            summary += f"\n🔎 Cek ulang arsip: {rc[0]} lowongan dicek, {rc[1]} sudah ditutup."
    for msg in [summary] + build_messages(good):
        if os.environ.get("TELEGRAM_BOT_TOKEN"):
            send_telegram(msg)
        else:
            print(msg)

if __name__ == "__main__":
    main()