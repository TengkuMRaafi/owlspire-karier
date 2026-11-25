import os
import re
import requests
from urllib.parse import quote, quote_plus
from datetime import datetime, timezone
from bs4 import BeautifulSoup

UA = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) JobFinder/1.0"}

# id, nama tampil, catatan, env var API key (None = tanpa key)
CATALOG = [
    {"id": "linkedin", "label": "LinkedIn", "env": None, "default": True,
     "site": "https://www.linkedin.com/jobs",
     "note": "Lowongan umum, termasuk lokal Indonesia"},
    {"id": "jooble", "label": "Jooble", "env": "JOOBLE_API_KEY", "default": True,
     "site": "https://id.jooble.org",
     "note": "Agregator 60+ negara, termasuk Indonesia"},
    {"id": "jsearch", "label": "JSearch", "env": "JSEARCH_API_KEY", "default": False,
     "site": "https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch",
     "note": "Google for Jobs: gabungan banyak situs karier"},
    {"id": "remoteok", "label": "RemoteOK", "env": None, "default": False,
     "site": "https://remoteok.com",
     "note": "Remote global, dominan tech"},
    {"id": "jobicy", "label": "Jobicy", "env": None, "default": False,
     "site": "https://jobicy.com",
     "note": "Remote global, beragam bidang"},
    {"id": "arbeitnow", "label": "Arbeitnow", "env": None, "default": False,
     "site": "https://www.arbeitnow.com",
     "note": "Eropa + remote"},
]
LABELS = {c["id"]: c["label"] for c in CATALOG}


def catalog():
    return [{**c, "available": c["env"] is None or bool(os.environ.get(c["env"]))}
            for c in CATALOG]


def _clean(v):
    if isinstance(v, (list, tuple)):
        v = ", ".join(str(x) for x in v)
    return BeautifulSoup(str(v or ""), "html.parser").get_text(" ", strip=True)


def _job(source, id, title, company, location, url, posted="", logo="",
         job_type="", seniority="", industry="", description=""):
    return {
        "source": source, "id": f"{source}-{id}",
        "title": _clean(title) or "-", "company": _clean(company) or "-",
        "location": _clean(location) or "-", "url": url or "",
        "posted": posted or "", "logo": logo or "",
        "job_type": _clean(job_type), "seniority": _clean(seniority),
        "industry": _clean(industry), "function": "", "applicants": "",
        "description": _clean(description),
    }


def _get(url, **kw):
    r = requests.get(url, headers=UA, timeout=25, **kw)
    r.raise_for_status()
    return r.json()


def _each(queries, fn):
    """Jalankan beberapa query; satu query gagal tidak menggugurkan yang lain."""
    out, err = [], None
    for q in queries:
        try:
            out += fn(q)
        except Exception as e:
            err = e
    if err and not out:
        raise err
    return out


def remoteok(positions, skills, locations, hours, kind="kerja"):
    out = []
    for j in _get("https://remoteok.com/api"):
        if not isinstance(j, dict) or not j.get("position"):
            continue  # elemen pertama = pemberitahuan legal
        out.append(_job("RemoteOK", j.get("id"), j["position"], j.get("company"),
                        j.get("location") or "Remote", j.get("url"),
                        posted=(j.get("date") or "")[:10],
                        logo=j.get("logo") or j.get("company_logo"),
                        job_type="Remote", description=j.get("description")))
    return out


def arbeitnow(positions, skills, locations, hours, kind="kerja"):
    locs = [l.lower() for l in locations]
    out = []
    for j in _get("https://www.arbeitnow.com/api/job-board-api").get("data", []):
        loc = j.get("location") or ""
        if not (j.get("remote") or any(l in loc.lower() for l in locs)):
            continue
        ts = j.get("created_at")
        posted = (datetime.fromtimestamp(ts, timezone.utc).strftime("%Y-%m-%d")
                  if isinstance(ts, (int, float)) else "")
        out.append(_job("Arbeitnow", j.get("slug"), j.get("title"), j.get("company_name"),
                        loc or "Remote", j.get("url"), posted=posted,
                        job_type=j.get("job_types") or ("Remote" if j.get("remote") else ""),
                        description=j.get("description")))
    return out


def jobicy(positions, skills, locations, hours, kind="kerja"):
    def one(p):
        data = _get("https://jobicy.com/api/v2/remote-jobs", params={"count": 50, "tag": p})
        return [_job("Jobicy", j.get("id"), j.get("jobTitle"), j.get("companyName"),
                     j.get("jobGeo") or "Remote", j.get("url"),
                     posted=(j.get("pubDate") or "")[:10], logo=j.get("companyLogo"),
                     job_type=j.get("jobType"), seniority=j.get("jobLevel"),
                     industry=j.get("jobIndustry"),
                     description=j.get("jobDescription") or j.get("jobExcerpt"))
                for j in data.get("jobs", [])]
    return _each(positions[:3], one)


def jooble(positions, skills, locations, hours, kind="kerja"):
    key = os.environ["JOOBLE_API_KEY"]

    def one(q):
        r = requests.post(f"https://jooble.org/api/{key}", headers=UA, timeout=25,
                          json={"keywords": q[0] + (" magang" if kind == "magang" else ""),
                                "location": q[1]})
        r.raise_for_status()
        return [_job("Jooble", j.get("id"), j.get("title"), j.get("company"),
                     j.get("location"), j.get("link"),
                     posted=(j.get("updated") or "")[:10], job_type=j.get("type"),
                     description=j.get("snippet"))
                for j in r.json().get("jobs", [])]
    return _each([(p, l) for p in positions[:3] for l in locations[:2]], one)


def jsearch(positions, skills, locations, hours, kind="kerja"):
    key = os.environ["JSEARCH_API_KEY"]
    period = {24: "today", 168: "week", 720: "month"}.get(hours, "week")

    def one(q):
        r = requests.get("https://jsearch.p.rapidapi.com/search", timeout=30,
                         headers={"X-RapidAPI-Key": key, "X-RapidAPI-Host": "jsearch.p.rapidapi.com"},
                         params={"query": f"{q[0]} {'internship' if kind == 'magang' else ''} in {q[1]}".replace("  ", " "),
                                 "page": "1", "num_pages": "1", "date_posted": period,
                                 **({"employment_types": "INTERN"} if kind == "magang" else {})})
        r.raise_for_status()
        out = []
        for j in r.json().get("data", []):
            loc = ", ".join(x for x in (j.get("job_city"), j.get("job_state"),
                                        j.get("job_country")) if x)
            out.append(_job("JSearch", j.get("job_id"), j.get("job_title"),
                            j.get("employer_name"), loc, j.get("job_apply_link"),
                            posted=(j.get("job_posted_at_datetime_utc") or "")[:10],
                            logo=j.get("employer_logo"),
                            job_type=j.get("job_employment_type"),
                            description=j.get("job_description")))
        return out
    # hemat kuota gratis: maksimal 3 query
    return _each([(p, l) for p in positions[:2] for l in locations[:2]][:3], one)


FETCHERS = {"remoteok": remoteok, "arbeitnow": arbeitnow, "jobicy": jobicy,
            "jooble": jooble, "jsearch": jsearch}

# ---------- khusus magang ----------
INTERN_RE = re.compile(r"(?<![a-z])(intern|interns|internship|magang|praktik kerja|trainee)(?![a-z])", re.I)


def is_intern(job):
    """Untuk sumber feed global yang tidak punya filter magang."""
    text = f"{job.get('title', '')} {job.get('job_type', '')} {job.get('description', '')}"
    return bool(INTERN_RE.search(text))


def _slug(text):
    return re.sub(r"[^a-z0-9]+", "-", text.lower()).strip("-")


def search_links(positions, locations, kind="kerja"):
    """Tautan pencarian langsung ke situs lain (tanpa scraping): pengguna yang membukanya."""
    pos = (positions or [""])[0]
    loc = (locations or ["Indonesia"])[0]
    anywhere = loc.lower() in ("indonesia", "remote", "")
    magang = kind == "magang"
    q = f"{pos} magang".strip() if magang else pos
    links = [
        ("LinkedIn", "https://www.linkedin.com/jobs/search/?keywords=" + quote_plus(pos)
         + "&location=" + quote_plus(loc) + ("&f_JT=I" if magang else "")),
        ("Indeed", "https://id.indeed.com/jobs?q=" + quote_plus(q) + "&l=" + quote_plus("" if anywhere else loc)),
    ]
    if magang:
        city = "" if anywhere else "-" + _slug(loc)
        links.insert(1, ("Glints", f"https://glints.com/id/find-jobs/loker-{_slug(pos) or 'magang'}-internship{city}"))
    return links