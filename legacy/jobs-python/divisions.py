"""Bidang kerja (divisi) umum, supaya pencocokan lowongan relevan untuk semua bidang, bukan hanya IT.

  context(positions, skills)      -> deteksi bidang + konsep posisi, dihitung sekali per pencarian
  title_points(title, ctx)        -> 0..35, seberapa dekat judul lowongan dengan posisi yang dicari
  related(text, ctx, exclude)     -> istilah khas bidang itu yang muncul di lowongan
  classify(title, text, fallback) -> label bidang sebuah lowongan (kolom "Bidang" di Google Sheets)
  demanded(text)                  -> istilah keahlian dari semua bidang yang muncul di lowongan

Tidak ada daftar skill pengguna yang di-hardcode: bidang dideteksi dari posisi + keahlian yang
diketik / dibaca dari CV, lalu dipakai untuk menilai judul dan isi lowongan.
"""
import re

# titles = kata di judul posisi / lowongan yang menandai bidang (Indonesia + Inggris)
# terms  = istilah keahlian / tools khas bidang yang biasa tertulis di deskripsi lowongan
DIVISIONS = {
    "IT & Software": {
        "titles": ["software", "developer", "programmer", "web", "backend", "back end", "frontend", "front end",
                   "fullstack", "full stack", "devops", "mobile", "android", "ios", "data scientist",
                   "data analyst", "data engineer", "machine learning", "qa tester", "cyber", "system analyst",
                   "it support", "network engineer", "database", "ui/ux", "ux", "ai"],
        "terms": ["python", "java", "javascript", "typescript", "php", "laravel", "react", "vue", "node.js",
                  "sql", "mysql", "postgresql", "mongodb", "rest api", "git", "docker", "kubernetes", "linux",
                  "aws", "cloud", "machine learning", "deep learning", "nlp", "tensorflow", "pytorch", "flutter",
                  "figma", "agile", "scrum", "ci/cd", "flask", "django", "golang", "c++", "c#"],
    },
    "Teknik Mesin & Listrik": {
        "titles": ["mechanical", "mekanik", "mesin", "electrical", "elektrikal", "listrik", "elektro",
                   "maintenance", "perawatan", "instrument", "instrumentasi", "utility", "mekatronika",
                   "otomasi", "automation", "hvac", "boiler", "process engineer", "design engineer"],
        "terms": ["autocad", "solidworks", "catia", "ansys", "matlab", "plc", "scada", "hmi", "cnc", "hvac",
                  "welding", "preventive maintenance", "commissioning", "p&id", "troubleshooting", "pneumatic",
                  "hydraulic", "inventor", "wiring", "k3"],
    },
    "Sipil, Konstruksi & Arsitektur": {
        "titles": ["civil", "sipil", "construction", "konstruksi", "site engineer", "site manager",
                   "quantity surveyor", "estimator", "arsitek", "architect", "arsitektur", "drafter",
                   "surveyor", "struktur", "structural", "interior"],
        "terms": ["autocad", "revit", "sketchup", "rab", "boq", "shop drawing", "surveying", "mep", "primavera",
                  "ms project", "staad", "etabs", "lumion", "site supervisor", "k3"],
    },
    "Produksi, Manufaktur & Kualitas": {
        "titles": ["production", "produksi", "manufacturing", "manufaktur", "quality", "qc", "qa", "operator",
                   "ppic", "safety", "hse", "k3", "pabrik", "factory"],
        "terms": ["quality control", "quality assurance", "lean", "5s", "kaizen", "iso 9001", "six sigma", "ppic",
                  "oee", "hse", "k3", "iso 14001", "haccp", "gmp", "sop", "fmea"],
    },
    "Keuangan, Akuntansi & Pajak": {
        "titles": ["accounting", "accountant", "akuntansi", "akuntan", "finance", "keuangan", "tax", "pajak",
                   "perpajakan", "audit", "auditor", "treasury", "accounts payable", "accounts receivable",
                   "billing", "cashier", "kasir", "credit analyst", "banking", "teller", "bank"],
        "terms": ["accurate", "zahir", "sap", "excel", "pajak", "laporan keuangan", "financial report", "jurnal",
                  "general ledger", "rekonsiliasi", "reconciliation", "accounts payable", "accounts receivable",
                  "psak", "ifrs", "budgeting", "cash flow", "e-faktur", "spt", "audit", "bookkeeping", "pembukuan",
                  "neraca", "ppn", "pph"],
    },
    "SDM / HR": {
        "titles": ["hr", "hrd", "hrbp", "human resource", "human resources", "sdm", "recruiter", "recruitment",
                   "rekrutmen", "talent acquisition", "personalia", "payroll", "learning development",
                   "hr generalist", "people culture"],
        "terms": ["recruitment", "rekrutmen", "onboarding", "payroll", "bpjs", "employee relations", "talent",
                  "performance appraisal", "hris", "interview", "ketenagakerjaan", "kompensasi", "benefit",
                  "employer branding", "training", "talent acquisition", "uu ketenagakerjaan"],
    },
    "Marketing, Konten & Komunikasi": {
        "titles": ["marketing", "digital marketing", "content", "konten", "copywriter", "social media", "seo",
                   "brand", "community", "public relations", "humas", "communications", "komunikasi", "media",
                   "campaign", "influencer", "event", "pr"],
        "terms": ["seo", "sem", "google ads", "meta ads", "facebook ads", "instagram", "tiktok", "canva",
                  "copywriting", "content plan", "content calendar", "engagement", "analytics", "brand awareness",
                  "campaign", "email marketing", "press release", "storytelling", "kol", "google analytics"],
    },
    "Sales & Business Development": {
        "titles": ["sales", "penjualan", "business development", "account executive", "account manager",
                   "key account", "telesales", "sales executive", "retail", "store", "toko", "area manager",
                   "territory", "salesman", "relationship manager", "sales representative"],
        "terms": ["negotiation", "negosiasi", "closing", "prospecting", "customer acquisition", "crm", "pipeline",
                  "b2b", "b2c", "canvassing", "penawaran", "quotation", "after sales", "lead generation",
                  "sales target", "target penjualan"],
    },
    "Admin & Operasional": {
        "titles": ["admin", "administrasi", "administration", "administrative", "secretary", "sekretaris",
                   "receptionist", "resepsionis", "data entry", "back office", "general affair", "operations",
                   "operasional", "executive assistant", "personal assistant", "document control", "clerk",
                   "front office"],
        "terms": ["ms office", "excel", "microsoft word", "korespondensi", "arsip", "data entry", "filing",
                  "invoice", "purchase order", "surat menyurat", "notulen", "google workspace", "administrasi",
                  "microsoft office", "document control"],
    },
    "Logistik & Supply Chain": {
        "titles": ["logistic", "logistics", "logistik", "supply chain", "warehouse", "gudang", "procurement",
                   "purchasing", "pengadaan", "inventory", "distribution", "distribusi", "expedition", "ekspedisi",
                   "forwarding", "import export", "shipping", "courier", "kurir", "driver", "supir", "fleet",
                   "buyer"],
        "terms": ["inventory", "procurement", "wms", "forklift", "customs", "bea cukai", "freight", "forwarding",
                  "tender", "vendor", "stock opname", "purchase order", "sourcing", "last mile", "fleet",
                  "incoterms", "3pl", "supply chain", "import", "export"],
    },
    "Customer Service": {
        "titles": ["customer service", "cs", "customer support", "customer care", "call center", "contact center",
                   "customer success", "client service", "frontliner", "layanan pelanggan"],
        "terms": ["komplain", "complaint", "live chat", "zendesk", "crm", "sla", "ticketing",
                  "customer satisfaction", "inbound", "outbound", "escalation", "service excellence", "freshdesk"],
    },
    "Desain & Kreatif": {
        "titles": ["designer", "desainer", "graphic", "grafis", "ui designer", "video editor", "videographer",
                   "videografer", "motion graphic", "illustrator", "photographer", "fotografer", "creative",
                   "art director", "animator", "3d artist"],
        "terms": ["photoshop", "illustrator", "figma", "canva", "premiere pro", "after effects", "coreldraw",
                  "blender", "adobe", "lightroom", "capcut", "indesign", "branding", "storyboard", "typography",
                  "video editing", "motion graphic"],
    },
    "Kesehatan & Farmasi": {
        "titles": ["nurse", "perawat", "bidan", "midwife", "dokter", "doctor", "physician", "apoteker",
                   "pharmacist", "farmasi", "pharmacy", "medical", "analis kesehatan", "laboratorium",
                   "radiographer", "fisioterapi", "ahli gizi", "nutritionist", "dentist", "dokter gigi",
                   "clinical", "rekam medis"],
        "terms": ["rekam medis", "bls", "klinik", "rumah sakit", "hospital", "farmasi klinis", "cpob",
                  "medical device", "triage", "emr", "pelayanan kesehatan", "patient care", "str", "sip",
                  "pasien", "patient"],
    },
    "Pendidikan & Pelatihan": {
        "titles": ["teacher", "guru", "dosen", "lecturer", "tutor", "instructor", "pengajar", "trainer",
                   "kurikulum", "curriculum", "education", "pendidikan", "academic", "kepala sekolah",
                   "instruktur"],
        "terms": ["kurikulum", "curriculum", "lesson plan", "rpp", "mengajar", "teaching", "classroom",
                  "e-learning", "lms", "silabus", "pembelajaran", "bimbingan belajar", "kelas"],
    },
    "Perhotelan, F&B & Pariwisata": {
        "titles": ["hotel", "front desk", "housekeeping", "chef", "cook", "commis", "barista", "waiter",
                   "waitress", "food beverage", "f&b", "restaurant", "restoran", "banquet", "tourism",
                   "pariwisata", "travel", "tour guide", "guest relation", "reservation", "kitchen",
                   "bartender", "steward"],
        "terms": ["f&b", "food safety", "haccp", "reservasi", "pms", "opera pms", "housekeeping", "banquet",
                  "pastry", "check in", "guest service", "mice", "ticketing"],
    },
    "Hukum & Kepatuhan": {
        "titles": ["legal", "hukum", "lawyer", "advokat", "paralegal", "compliance", "kepatuhan", "legal officer",
                   "corporate secretary", "notaris", "litigation"],
        "terms": ["kontrak", "contract drafting", "legal drafting", "perizinan", "licensing", "litigasi",
                  "litigation", "due diligence", "regulasi", "corporate law", "hukum perdata", "legal review",
                  "aml", "kyc"],
    },
    "Energi, Migas & Pertambangan": {
        "titles": ["oil gas", "migas", "mining", "tambang", "pertambangan", "geologist", "geologi", "drilling",
                   "mine planning", "petroleum", "reservoir", "wellsite", "geophysics", "geophysicist"],
        "terms": ["drilling", "oil and gas", "migas", "wellsite", "reservoir", "mine planning", "surpac",
                  "minescape", "pipeline", "upstream", "downstream", "refinery", "coal", "batubara", "nickel",
                  "geotechnical", "petrel"],
    },
    "Agribisnis & Perkebunan": {
        "titles": ["agronomist", "agronomi", "agribisnis", "agribusiness", "perkebunan", "plantation", "sawit",
                   "kelapa sawit", "pertanian", "agriculture", "kehutanan", "forestry", "estate", "asisten kebun",
                   "perikanan", "peternakan", "agro"],
        "terms": ["sawit", "kelapa sawit", "plantation", "perkebunan", "agronomi", "estate", "panen",
                  "pemupukan", "rspo", "ispo", "budidaya", "irigasi", "traceability", "peternakan"],
    },
}

# kelompok sinonim (Indonesia <-> Inggris) untuk mencocokkan posisi yang dicari dengan judul lowongan
SYN = [
    ["akuntansi", "akuntan", "accounting", "accountant", "pembukuan", "bookkeeping", "bookkeeper"],
    ["keuangan", "finance", "financial"],
    ["pajak", "perpajakan", "tax", "taxation"],
    ["audit", "auditor", "internal audit"],
    ["hr", "hrd", "hrbp", "sdm", "human resource", "human resources", "personalia", "people operations"],
    ["rekrutmen", "recruitment", "recruiter", "talent acquisition"],
    ["marketing", "pemasaran"],
    ["sales", "penjualan", "salesman", "sales executive"],
    ["admin", "administrasi", "administration", "administrative", "back office", "clerk"],
    ["sekretaris", "secretary"],
    ["resepsionis", "receptionist", "front office", "front desk"],
    ["gudang", "warehouse", "inventory"],
    ["pengadaan", "procurement", "purchasing", "buyer"],
    ["logistik", "logistic", "logistics", "supply chain", "distribusi", "distribution"],
    ["customer service", "cs", "customer support", "customer care", "call center", "contact center",
     "layanan pelanggan"],
    ["guru", "teacher", "pengajar", "tutor", "instruktur", "instructor"],
    ["dosen", "lecturer"],
    ["perawat", "nurse", "nursing"],
    ["apoteker", "pharmacist", "farmasi", "pharmacy"],
    ["dokter", "doctor", "physician"],
    ["desainer", "designer", "desain", "design"],
    ["grafis", "graphic"],
    ["video editor", "videografer", "videographer"],
    ["mesin", "mechanical", "mekanik"],
    ["listrik", "electrical", "elektrikal", "elektro", "electric"],
    ["sipil", "civil"],
    ["konstruksi", "construction"],
    ["arsitek", "architect", "arsitektur", "architecture"],
    ["produksi", "production", "manufacturing", "manufaktur"],
    ["kualitas", "quality", "qc", "qa"],
    ["konten", "content", "copywriter"],
    ["developer", "programmer", "pengembang", "software engineer", "software developer", "software", "backend",
     "frontend", "fullstack", "full stack", "web developer"],
    ["data scientist", "machine learning", "ml", "ai", "artificial intelligence"],
    ["it support", "helpdesk", "technical support"],
    ["teknisi", "technician"],
    ["hukum", "legal", "lawyer", "advokat"],
    ["perkebunan", "plantation", "sawit", "estate"],
    ["pertambangan", "tambang", "mining"],
    ["perhotelan", "hotel", "hospitality"],
    ["koki", "chef", "cook"],
    ["kasir", "cashier"],
    ["supir", "driver", "pengemudi"],
    ["kurir", "courier"],
]

# kata jabatan yang tidak membedakan bidang -> tidak dihitung saat membandingkan judul
GENERIC = {"engineer", "insinyur", "staff", "staf", "officer", "manager", "manajer", "senior", "junior", "sr", "jr",
           "supervisor", "assistant", "asisten", "executive", "specialist", "spesialis", "coordinator",
           "koordinator", "associate", "intern", "internship", "magang", "trainee", "lead", "head", "kepala",
           "profesional", "professional", "fresh", "graduate"}
STOP = {"and", "dan", "di", "of", "for", "the", "at", "in", "to", "atau", "or", "dengan", "with", "untuk", "&",
        "-", "a", "an"}

_B, _E = r"(?<![a-z0-9])", r"(?![a-z0-9])"


def _alt(words, flex):
    """Satu regex untuk banyak kata. flex=True: boleh berakhiran 1-3 huruf (design -> designer)."""
    ws = sorted({w.lower() for w in words if w}, key=len, reverse=True)
    parts = [re.escape(w) + (r"[a-z]{0,3}" if flex and len(w) > 3 else "") for w in ws]
    return re.compile(_B + "(?:" + "|".join(parts) + ")" + _E)


_DIV = {n: {"title": _alt(d["titles"], True), "term": _alt(d["terms"], False)} for n, d in DIVISIONS.items()}
_ALL_TERMS = _alt([t for d in DIVISIONS.values() for t in d["terms"]], False)
_GROUP_RE = [_alt(g, True) for g in SYN]
_SYN_ENTRIES = sorted(((v, gi) for gi, g in enumerate(SYN) for v in g), key=lambda x: -len(x[0]))


def _clean(text):
    return " ".join(str(text or "").lower().split())


def _flat(items):
    return _clean(" ".join(str(x) for x in (items or [])))


def _count(rx, text):
    return len({m.group(0) for m in rx.finditer(text)})


def detect(positions, skills=(), limit=2):
    """Bidang yang paling mungkin dari posisi + keahlian pencarian (maksimal `limit`, bisa kosong)."""
    pos, sk = _flat(positions), _flat(skills)
    scores = {}
    for name, rx in _DIV.items():
        s = (3 * _count(rx["title"], pos)           # kata jabatan di posisi = bukti terkuat
             + _count(rx["term"], pos + " " + sk)    # istilah khas bidang di posisi / keahlian
             + _count(rx["title"], sk))
        if s >= 3:
            scores[name] = s
    if not scores:
        return []
    top = max(scores.values())
    ranked = sorted((n for n in scores if scores[n] >= 0.5 * top), key=lambda n: -scores[n])
    return ranked[:limit]


def _concepts(position):
    """Pecah satu posisi jadi konsep: kelompok sinonim (regex) + kata biasa yang bukan kata jabatan umum."""
    p = " " + " ".join(t.strip("./&,") for t in re.findall(r"[a-z0-9+#&./]+", str(position).lower())) + " "
    out = []
    for v, gi in _SYN_ENTRIES:                      # frasa terpanjang dulu: "customer service" sebelum "customer"
        pat = " " + v + " "
        if pat in p:
            if _GROUP_RE[gi] not in out:
                out.append(_GROUP_RE[gi])
            while pat in p:
                p = p.replace(pat, " ")
    words = [t for t in p.split() if t not in STOP]
    plain = [t for t in words if t not in GENERIC]
    for t in (plain or ([] if out else words)):    # posisi yang hanya "Engineer"/"Manager" tetap dicocokkan apa adanya
        out.append(_alt([t], True))
    return out


def context(positions, skills=()):
    """Dihitung sekali per pencarian, lalu dipakai untuk semua lowongan."""
    divs = detect(positions, skills)
    concepts = [c for c in (_concepts(p) for p in positions or [] if str(p).strip()) if c]
    return {"divs": divs, "label": divs[0] if divs else "", "concepts": concepts,
            "title_re": [_DIV[d]["title"] for d in divs], "term_re": [_DIV[d]["term"] for d in divs]}


def title_points(title, ctx):
    """0..35. Judul sama persis dengan posisi yang dicari = 35; hanya sebidang = 12; tidak terkait = 0."""
    t = _clean(title)
    best = 0.0
    for concepts in ctx["concepts"]:
        best = max(best, sum(1 for rx in concepts if rx.search(t)) / len(concepts))
    pts = round(35 * best) if best >= 0.33 else 0
    if pts < 12 and any(rx.search(t) for rx in ctx["title_re"]):
        pts = 12
    return pts


def related(text, ctx, exclude=()):
    """Istilah khas bidang pengguna yang muncul di lowongan, di luar skill yang sudah dihitung."""
    t, ex = _clean(text), {str(e).lower() for e in exclude}
    out = []
    for rx in ctx["term_re"]:
        for m in rx.finditer(t):
            w = m.group(0)
            if w not in out and w not in ex:
                out.append(w)
    return out


def classify(title, text, fallback=""):
    """Label bidang sebuah lowongan; kalau tidak jelas pakai bidang pencarian, atau 'Umum'."""
    t, x = _clean(title), _clean(text)
    best, bs = "", 0
    for name, rx in _DIV.items():
        s = 3 * _count(rx["title"], t) + min(4, _count(rx["term"], x))
        if s > bs:
            best, bs = name, s
    return best if bs >= 3 else (fallback or "Umum")


def demanded(text):
    """Istilah keahlian (semua bidang) yang diminta sebuah lowongan."""
    out = []
    for m in _ALL_TERMS.finditer(_clean(text)):
        if m.group(0) not in out:
            out.append(m.group(0))
    return out