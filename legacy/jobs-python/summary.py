"""Ringkasan hasil pencarian yang menyesuaikan diri dengan hasilnya (bot Telegram, web, notifikasi harian).

analyze()       -> data terstruktur (dipakai web & Telegram)
build_summary() -> teks ringkas untuk Telegram
"""
import math
import re
from collections import Counter

HOURS = {24: "24 jam terakhir", 168: "7 hari terakhir", 720: "30 hari terakhir"}
# istilah umum yang terlalu generik untuk disarankan sebagai "skill yang kurang"
GAP_IGNORE = {"english", "bahasa inggris", "public speaking", "negotiation", "research", "teaching"}
MAX_CHARS = 1800


def _norm(s):
    return re.sub(r"[^a-z0-9]", "", str(s).lower())


def _loc(s):
    s = (s or "").split(",")[0].strip()
    return s if s and s != "-" else ""


def _top(counter, n):
    return [[k, v] for k, v in counter.most_common(n)]


def _more(items, n):
    return f" +{len(items) - n} lagi" if len(items) > n else ""


def analyze(jobs, params=None, min_match=25):
    params = params or {}
    shown = [j for j in jobs if j.get("match", 0) >= min_match]
    mine = [str(s).strip() for s in params.get("skills") or [] if str(s).strip()]
    shown_name = {s.lower(): s for s in mine}

    # skill milik pengguna: yang benar-benar muncul di hasil (urut paling sering) vs belum muncul
    used_cnt = Counter(s.lower() for j in shown for s in j.get("matched", []))
    used = [[shown_name.get(k, k), v] for k, v in used_cnt.most_common()]
    unused = [s for s in mine if s.lower() not in used_cnt]

    # skill yang sering diminta lowongan relevan tapi belum ada di profil
    mine_n = {_norm(s) for s in mine}
    gap_cnt = Counter()
    for j in shown:
        for d in j.get("demanded", []):
            dn = _norm(d)
            if d.lower() in GAP_IGNORE or any(dn == m or (len(m) > 3 and (m in dn or dn in m)) for m in mine_n):
                continue
            gap_cnt[d] += 1
    need = max(2, math.ceil(0.2 * len(shown)))
    gap = [[k, v] for k, v in gap_cnt.most_common(5) if v >= need] if len(shown) >= 3 else []

    hi = sum(1 for j in jobs if j.get("match", 0) >= 70)
    mid = sum(1 for j in jobs if 40 <= j.get("match", 0) < 70)
    low = len(jobs) - hi - mid
    locs = Counter(_loc(j.get("location")) for j in shown)
    locs.pop("", None)
    for k in list(locs):                     # "Jakarta Selatan" digabung ke "Jakarta"
        base = next((b for b in locs if b != k and k.lower().startswith(b.lower() + " ")), None)
        if base:
            locs[base] += locs.pop(k)
    comps = Counter(j.get("company") for j in shown if j.get("company") not in (None, "", "-"))
    src = Counter(s for j in jobs for s in (j.get("sources") or [j.get("source", "-")]))

    out = {
        "kind": params.get("kind", "kerja"), "found": len(jobs), "shown": len(shown), "min_match": min_match,
        "tiers": {"hi": hi, "mid": mid, "low": low},
        "used": used, "unused": unused, "total_skills": len(mine), "gap": gap,
        "locations": _top(locs, 3), "companies": [c for c in _top(comps, 3) if c[1] > 1] if len(shown) >= 5 else [],
        "sources": _top(src, 6),
    }
    out["tips"] = _tips(out, params)
    return out


def _tips(a, params):
    tips = []
    hours = params.get("hours")
    locs = [l.lower() for l in params.get("locations") or []]
    narrow = bool(locs) and not any(l in ("indonesia", "remote") for l in locs)
    few = a["shown"] < 3
    if few:
        if hours == 24:
            tips.append("Perluas rentang waktu ke 7 atau 30 hari.")
        elif hours == 168:
            tips.append("Coba rentang 30 hari.")
        if narrow:
            tips.append("Tambahkan lokasi Indonesia atau Remote.")
        if a["total_skills"] < 3:
            tips.append("Tambahkan lebih banyak keahlian agar pencocokan lebih akurat.")
        if len(tips) < 2:
            tips.append("Coba judul posisi yang lebih umum, atau turunkan tingkat kecocokan.")
    if a["gap"]:
        names = " dan ".join(g[0] for g in a["gap"][:2])
        tips.append(f"Mempelajari {names} bisa membuka lebih banyak lowongan yang cocok.")
    elif a["shown"] >= 3 and a["tiers"]["hi"] == 0:
        tips.append("Belum ada yang sangat cocok. Tambahkan keahlian spesifik dari bidangmu.")
    if a["unused"] and a["used"] and len(a["unused"]) >= max(3, a["total_skills"] // 2):
        tips.append("Banyak keahlianmu belum muncul di hasil. Coba posisi lain yang lebih sesuai dengannya.")
    return tips[:3]


def _fit(lines, limit=MAX_CHARS):
    """Buang baris dari bawah (prioritas paling rendah) bila terlalu panjang."""
    while len("\n".join(lines)) > limit and len(lines) > 3:
        lines.pop()
    return lines


def build_summary(jobs, params=None, min_match=25):
    params = params or {}
    a = analyze(jobs, params, min_match)
    magang = a["kind"] == "magang"
    L = [f"📊 Ringkasan {'magang' if magang else 'lowongan'}", ""]

    # --- rekap pencarian (ringkas) ---
    pos = params.get("positions") or []
    head = ", ".join(pos[:3]) + (f" +{len(pos) - 3}" if len(pos) > 3 else "")
    bits = [x for x in (f"🔎 {head}" if head else "",
                        "📍 " + ", ".join((params.get("locations") or [])[:3]) if params.get("locations") else "",
                        "⏱ " + HOURS.get(params.get("hours"), f"{params['hours']} jam") if params.get("hours") else "") if x]
    if bits:
        L.append(" · ".join(bits))
        L.append("")

    # --- jumlah hasil (hanya tingkat yang ada) ---
    L.append(f"📌 {a['found']} ditemukan, {a['shown']} cocok (≥{min_match}%)")
    t = a["tiers"]
    tiers = [f"{n} {v}" for n, v in (("Sangat cocok", t["hi"]), ("Cukup cocok", t["mid"]), ("Kurang cocok", t["low"])) if v]
    if tiers:
        L.append("🎯 " + " · ".join(tiers))

    # --- keahlian: yang terpakai dulu, lalu yang belum muncul ---
    if a["used"]:
        top = a["used"][:6]
        L.append("🛠 Skill terpakai: " + ", ".join(f"{k} ({v})" for k, v in top) + _more(a["used"], 6))
        if a["unused"]:
            L.append("○ Belum muncul: " + ", ".join(a["unused"][:5]) + _more(a["unused"], 5))
    elif params.get("skills"):
        sk = params["skills"]
        L.append("🛠 Skill: " + ", ".join(sk[:8]) + _more(sk, 8))
    if a["gap"]:
        L.append("💡 Sering diminta, belum ada di profilmu: " + ", ".join(f"{k} ({v})" for k, v in a["gap"]))

    # --- peta lowongan ---
    if a["locations"]:
        L.append("📍 Terbanyak di: " + ", ".join(f"{k} ({v})" for k, v in a["locations"]))
    if a["companies"]:
        L.append("🏢 Paling banyak buka: " + ", ".join(f"{k} ({v})" for k, v in a["companies"]))
    if a["sources"]:
        L.append("🌐 Sumber: " + " · ".join(f"{k} ({v})" for k, v in a["sources"]))
    elif params.get("sources"):
        L.append("🌐 Sumber: " + ", ".join(params["sources"]))

    # --- saran yang menyesuaikan hasil ---
    if a["tips"]:
        L += ["", "👉 Saran"] + [f"• {x}" for x in a["tips"]]
    return "\n".join(_fit(L))