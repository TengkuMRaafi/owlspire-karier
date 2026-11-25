/**
 * Job Finder -> Google Sheets
 *
 * 1. Buka https://script.google.com  >  Proyek baru  >  hapus isi bawaan  >  tempel seluruh kode ini.
 * 2. Project Settings (ikon roda gigi)  >  Script properties  >  Add script property:
 *      Property: SECRET     Value: tulisan acak buatanmu (huruf & angka)
 * 3. Deploy  >  New deployment  >  jenis "Web app":
 *      Execute as: Me      Who has access: Anyone
 *    Klik Deploy, izinkan akses (jika muncul "belum diverifikasi": Advanced > Go to ... > Allow).
 * 4. Salin "Web app URL" (berakhiran /exec) ke env GSHEET_WEBHOOK_URL, dan SECRET tadi ke GSHEET_SECRET.
 */
function doPost(e) {
  try {
    var data = JSON.parse(e.postData.contents);
    var secret = PropertiesService.getScriptProperties().getProperty("SECRET");
    if (!secret || data.secret !== secret) return out_({ ok: false, error: "unauthorized" });
    if (data.mode) return out_(routeMaster_(data));   // arsip tetap (notifikasi harian + cek ulang)

    var headers = data.headers || [];
    var rows = (data.rows || []).slice(0, 500);
    var ss = SpreadsheetApp.create(String(data.title || "Job Finder").slice(0, 100));

    var sh = ss.getSheets()[0];
    sh.setName("Hasil");
    var all = [headers].concat(rows);
    var range = sh.getRange(1, 1, all.length, headers.length);
    range.setNumberFormat("@");            // teks biasa: isi lowongan tidak pernah dijalankan sebagai rumus
    range.setValues(all);
    sh.getRange(1, 1, 1, headers.length).setFontWeight("bold").setBackground("#e8f0fe");
    sh.setFrozenRows(1);
    var widths = [40, 300, 200, 160, 90, 220, 110, 110, 110, 110, 320];
    for (var i = 0; i < headers.length; i++) sh.setColumnWidth(i + 1, widths[i] || 120);

    var info = data.info || [];
    if (info.length) {
      var s2 = ss.insertSheet("Info pencarian");
      var r2 = s2.getRange(1, 1, info.length, 2);
      r2.setNumberFormat("@");
      r2.setValues(info);
      s2.getRange(1, 1, info.length, 1).setFontWeight("bold");
      s2.setColumnWidth(1, 150);
      s2.setColumnWidth(2, 420);
    }

    DriveApp.getFileById(ss.getId()).setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
    return out_({ ok: true, url: ss.getUrl() });
  } catch (err) {
    return out_({ ok: false, error: String(err) });
  }
}

function out_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}

/* ===================== ARSIP TETAP (notifikasi harian) =====================
 * Satu spreadsheet "Job Finder - Arsip Lowongan" dibuat otomatis pada run pertama
 * (ID-nya disimpan di Script Property MASTER_ID). Isinya 3 tab:
 *   - Hari Ini        : top lowongan run terakhir (ditimpa tiap run)
 *   - Semua Lowongan  : arsip kumulatif SEMUA lowongan relevan, terbaru di atas, duplikat dilewati.
 *                       Kolom M-Q melacak apakah lowongan masih dibuka / sudah ditutup dan lama bukanya.
 *   - Log             : riwayat tiap run
 * Spreadsheet bersifat PRIBADI (hanya akun pemilik script); buka lewat link di Telegram.
 *
 * Yang masuk ke arsip: hasil notifikasi harian DAN hasil pencarian pengguna (bot Telegram / website),
 * semua bidang kerja. Kolom R "Bidang" memudahkan menyaring per bidang. Pencarian pengguna hanya
 * menambah baris arsip + satu baris Log; tab "Hari Ini" tetap milik notifikasi harian.
 */
var ARCH = "Semua Lowongan", TODAY = "Hari Ini", LOG = "Log";
var ARCH_HEADERS = ["Tanggal ambil", "Judul", "Perusahaan", "Lokasi", "Kecocokan", "Skill cocok",
                    "Jenis", "Level", "Diposting", "Sumber", "Status", "Link",
                    "Status lowongan", "Tgl posting", "Terakhir dicek", "Ditutup pada", "Lama dibuka (hari)", "Bidang"];
var NCOL = ARCH_HEADERS.length;          // 18
var APPLY_STATUS = ["Belum dilamar", "Sudah dilamar", "Wawancara", "Ditolak", "Diterima", "Lewati"];
var DAY = 86400000;

function routeMaster_(data) {
  var lock = LockService.getScriptLock();
  lock.waitLock(60000);                    // beberapa pencarian bersamaan antre, tidak saling menimpa
  try {
    if (data.mode === "master") return saveMaster_(data);
    if (data.mode === "to_check") return listToCheck_(data);
    if (data.mode === "update_checks") return updateChecks_(data);
    if (data.mode === "query") return queryArchive_(data);
    if (data.mode === "ping") return pingArchive_();
    return { ok: false, error: "mode tidak dikenal" };
  } finally {
    lock.releaseLock();
  }
}

function saveMaster_(data) {
  var ss = getMaster_();
  var r = writeArchive_(ss, data);
  refreshDurations_(ss.getSheetByName(ARCH), data.today, data.stale_days);
  if (!data.skip_today) writeToday_(ss, data);       // pencarian pengguna tidak menimpa "Hari Ini"
  ss.getSheetByName(LOG).appendRow([data.log[0], data.log[1], data.log[2], r.added, data.log[3], data.log[4]]);
  return { ok: true, url: ss.getUrl(), added: r.added, skipped: r.touched };
}

/* ---------- tanggal ---------- */
function parseISO_(s) {            // "2026-10-07" -> ms UTC, atau null
  if (s instanceof Date) s = Utilities.formatDate(s, "Asia/Jakarta", "yyyy-MM-dd");
  var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(s || ""));
  return m ? Date.UTC(+m[1], +m[2] - 1, +m[3]) : null;
}
function parseWib_(s) {            // "07-10-2026 18:00" -> ms UTC (tanggalnya saja), atau null
  if (s instanceof Date) s = Utilities.formatDate(s, "Asia/Jakarta", "dd-MM-yyyy HH:mm");
  var m = /^(\d{2})-(\d{2})-(\d{4})/.exec(String(s || ""));
  return m ? Date.UTC(+m[3], +m[2] - 1, +m[1]) : null;
}

/* ---------- membuat / membuka spreadsheet ---------- */
function getMaster_() {
  var props = PropertiesService.getScriptProperties();
  var id = props.getProperty("MASTER_ID"), ss = null;
  if (id) { try { ss = SpreadsheetApp.openById(id); } catch (err) { ss = null; } }
  if (ss) { ensureSchema_(ss); return ss; }

  ss = SpreadsheetApp.create("Job Finder - Arsip Lowongan");
  props.setProperty("MASTER_ID", ss.getId());
  ss.getSheets()[0].setName(TODAY);

  var a = ss.insertSheet(ARCH);
  a.getRange(1, 1, 1, NCOL).setValues([ARCH_HEADERS]);
  styleArchive_(a);

  var l = ss.insertSheet(LOG);
  l.getRange(1, 1, 1, 6).setValues([["Waktu (WIB)", "Terkumpul", "Lolos min. kecocokan", "Baru masuk arsip", "Ditampilkan", "Posisi / catatan"]])
    .setFontWeight("bold").setBackground("#e8f0fe");
  l.setFrozenRows(1);
  for (var k = 1; k <= 6; k++) l.setColumnWidth(k, k === 6 ? 380 : 140);
  return ss;
}

// sheet lama otomatis ditambah kolom pelacakan M-Q dan kolom Bidang (R)
function ensureSchema_(ss) {
  var a = ss.getSheetByName(ARCH);
  if (!a) return;
  var have = a.getRange(1, 13, 1, NCOL - 12).getValues()[0], ok = true;
  for (var i = 0; i < have.length; i++) if (have[i] !== ARCH_HEADERS[12 + i]) ok = false;
  if (ok) return;
  a.getRange(1, 13, 1, NCOL - 12).setValues([ARCH_HEADERS.slice(12)]);
  styleArchive_(a);
}

function styleArchive_(a) {
  var rows = Math.max(a.getMaxRows(), 1000);
  if (a.getMaxRows() < rows) a.insertRowsAfter(a.getMaxRows(), rows - a.getMaxRows());
  a.getRange(1, 1, 1, NCOL).setFontWeight("bold").setBackground("#e8f0fe").setVerticalAlignment("middle");
  a.setRowHeight(1, 28);
  a.setFrozenRows(1);
  var f = a.getFilter(); if (f) f.remove();
  a.getRange(1, 1, a.getMaxRows(), NCOL).createFilter();
  var w = [110, 300, 200, 150, 90, 220, 100, 110, 110, 110, 120, 60, 120, 100, 110, 110, 120, 190];
  for (var i = 0; i < NCOL; i++) a.setColumnWidth(i + 1, w[i]);
  a.getRange(2, 11, a.getMaxRows() - 1, 1).setDataValidation(
    SpreadsheetApp.newDataValidation().requireValueInList(APPLY_STATUS, true).build());
  a.setConditionalFormatRules([
    // lowongan yang sudah ditutup: baris diabu-abukan
    SpreadsheetApp.newConditionalFormatRule().whenFormulaSatisfied('=$M2="Ditutup"')
      .setFontColor("#9e9e9e").setBackground("#f1f1f1").setRanges([a.getRange("A2:R")]).build(),
    SpreadsheetApp.newConditionalFormatRule().whenNumberGreaterThanOrEqualTo(70)
      .setBackground("#c8e6c9").setRanges([a.getRange("E2:E")]).build(),
    SpreadsheetApp.newConditionalFormatRule().whenNumberBetween(40, 69)
      .setBackground("#fff3c4").setRanges([a.getRange("E2:E")]).build()
  ]);
}

/* ---------- arsip: tambah lowongan baru, tandai yang lama masih aktif ---------- */
function writeArchive_(ss, data) {
  var a = ss.getSheetByName(ARCH), today = data.today, last = a.getLastRow();
  var idx = {};                                                 // link -> nomor baris
  if (last > 1) a.getRange(2, 12, last - 1, 1).getValues().forEach(function (r, i) { if (r[0]) idx[r[0]] = i + 2; });

  var fresh = [], touch = [], seenNow = {};
  (data.archive_rows || []).forEach(function (r) {
    while (r.length < NCOL) r.push("");                         // kiriman lama (17 kolom) tetap diterima
    var u = r[11];
    if (!u || seenNow[u]) return;
    seenNow[u] = true;
    if (idx[u]) touch.push(idx[u]); else fresh.push(r);
  });

  // lowongan lama yang muncul lagi di pencarian hari ini = pasti masih dibuka
  if (touch.length) {
    var blk = a.getRange(2, 13, last - 1, 4), v = blk.getValues();    // kolom M..P
    touch.forEach(function (row) { var x = v[row - 2]; x[0] = "Aktif"; x[2] = today; x[3] = ""; });
    blk.setNumberFormat("@");
    blk.setValues(v);
  }

  if (fresh.length) {
    a.insertRowsBefore(2, fresh.length);                        // terbaru di atas
    var rng = a.getRange(2, 1, fresh.length, NCOL);
    rng.setNumberFormat("@");                                   // teks biasa, tidak pernah jadi rumus
    a.getRange(2, 5, fresh.length, 1).setNumberFormat("0\"%\"");
    a.getRange(2, 17, fresh.length, 1).setNumberFormat("0");
    rng.setValues(fresh);
    rng.setVerticalAlignment("middle").setWrap(false);
    a.getRange(2, 2, fresh.length, 1).setRichTextValues(fresh.map(function (r) {   // judul = link
      return [SpreadsheetApp.newRichTextValue().setText(r[1]).setLinkUrl(r[11]).build()];
    }));
    a.getRange(2, 11, fresh.length, 1).setDataValidation(
      SpreadsheetApp.newDataValidation().requireValueInList(APPLY_STATUS, true).build());
  }
  return { added: fresh.length, touched: touch.length };
}

/* ---------- hitung lama dibuka & tandai yang sudah terlalu lama tidak dicek ---------- */
function refreshDurations_(a, today, staleDays) {
  var last = a.getLastRow();
  if (last < 2) return;
  var t = parseISO_(today), stale = staleDays || 45;
  var v = a.getRange(2, 1, last - 1, NCOL).getValues(), out = [];
  v.forEach(function (r) {
    var st = r[12], first = parseWib_(r[0]);
    var start = parseISO_(r[13]) || first;
    var closed = parseISO_(r[15]);
    if (st !== "Ditutup" && st !== "Tidak dicek lagi" && first !== null && (t - first) / DAY > stale && r[14] !== today) st = "Tidak dicek lagi";
    var end = closed || (st === "Tidak dicek lagi" ? (parseISO_(r[14]) || t) : t);
    var days = (start !== null && end !== null) ? Math.max(0, Math.round((end - start) / DAY)) : "";
    out.push([st, r[13], r[14], r[15], days]);
  });
  var o = a.getRange(2, 13, out.length, 5);
  o.setNumberFormat("@");
  a.getRange(2, 17, out.length, 1).setNumberFormat("0");
  o.setValues(out);
}

/* ---------- cek ulang: daftar yang perlu dicek, lalu terima hasilnya ---------- */
function listToCheck_(data) {
  var a = getMaster_().getSheetByName(ARCH), last = a.getLastRow();
  if (last < 2) return { ok: true, items: [] };
  var v = a.getRange(2, 1, last - 1, NCOL).getValues();
  var t = parseISO_(data.today), stale = data.stale_days || 45, c = [];
  v.forEach(function (r) {
    var st = r[12], url = r[11];
    if (!url || st === "Ditutup" || st === "Tidak dicek lagi" || r[10] === "Lewati") return;
    if (r[14] === data.today) return;                              // sudah dicek / terlihat hari ini
    var first = parseWib_(r[0]);
    if (first !== null && t !== null && (t - first) / DAY > stale) return;
    c.push({ url: url, source: r[9], last: r[14] || "" });
  });
  c.sort(function (x, y) { return x.last < y.last ? -1 : (x.last > y.last ? 1 : 0); });   // yang paling lama / belum pernah dicek dulu
  return { ok: true, items: c.slice(0, data.limit || 60) };
}

function updateChecks_(data) {
  var ss = getMaster_(), a = ss.getSheetByName(ARCH), last = a.getLastRow();
  if (last < 2) return { ok: true, updated: 0, closed: 0 };
  var blk = a.getRange(2, 12, last - 1, 5), v = blk.getValues();   // L..P: link, status lowongan, tgl posting, dicek, ditutup
  var idx = {};
  v.forEach(function (r, i) { if (r[0]) idx[r[0]] = i; });
  var upd = 0, closed = 0;
  (data.checks || []).forEach(function (c) {
    var i = idx[c.url];
    if (i === undefined) return;
    var r = v[i];
    if (c.state === "open") { r[1] = "Aktif"; r[4] = ""; }
    else if (c.state === "closed") { if (r[1] !== "Ditutup") { r[4] = data.today; closed++; } r[1] = "Ditutup"; }
    else if (!r[1]) { r[1] = "Tidak bisa dicek"; }
    r[3] = data.today;
    upd++;
  });
  blk.setNumberFormat("@");
  blk.setValues(v);
  refreshDurations_(a, data.today, data.stale_days);
  ss.getSheetByName(LOG).appendRow([data.wib, "-", "-", "-", "-", "Cek ulang: " + upd + " dicek, " + closed + " baru ditutup"]);
  return { ok: true, updated: upd, closed: closed };
}

/* ---------- cari di arsip (dipakai bot Telegram: opsi "cek Google Sheet") ---------- */
function queryArchive_(data) {
  var ss = getMaster_(), a = ss.getSheetByName(ARCH), last = a.getLastRow();
  if (last < 2) return { ok: true, items: [], total: 0 };
  var n = Math.min(last - 1, data.max_scan || 2500);          // baris terbaru ada di atas
  var v = a.getRange(2, 1, n, NCOL).getValues();
  var t = parseISO_(data.today), since = t - (data.days || 7) * DAY, lim = data.limit || 600, items = [];
  for (var i = 0; i < v.length && items.length < lim; i++) {
    var r = v[i];
    if (!r[11] || r[12] === "Ditutup") continue;               // tanpa link / sudah ditutup
    var d = parseISO_(r[13]) || parseWib_(r[0]);               // tgl posting, kalau kosong tgl diambil
    if (d === null || d < since) continue;
    items.push({ title: String(r[1]), company: String(r[2]), location: String(r[3]), skills: String(r[5]),
                 job_type: String(r[6]), seniority: String(r[7]), posted: String(r[8]), sources: String(r[9]),
                 url: String(r[11]), division: String(r[17] || "") });
  }
  return { ok: true, items: items, total: last - 1 };
}

function pingArchive_() {
  var ss = getMaster_();
  return { ok: true, url: ss.getUrl(), rows: Math.max(0, ss.getSheetByName(ARCH).getLastRow() - 1), version: "arsip-v2" };
}

function writeToday_(ss, data) {
  var t = ss.getSheetByName(TODAY);
  t.clear();
  t.clearConditionalFormatRules();
  var hdr = data.today_headers, rows = data.today_rows, n = hdr.length;
  t.getRange(1, 1).setValue("Top " + rows.length + " lowongan paling cocok - " + data.wib + " WIB")
    .setFontWeight("bold").setFontSize(13);
  t.getRange(3, 1, 1, n).setValues([hdr]).setFontWeight("bold").setBackground("#e8f0fe");
  if (rows.length) {
    var body = rows.map(function (r) { return r.slice(0, n); });
    var rng = t.getRange(4, 1, rows.length, n);
    rng.setNumberFormat("@");
    t.getRange(4, 5, rows.length, 1).setNumberFormat("0\"%\"");
    rng.setValues(body);
    t.getRange(4, 2, rows.length, 1).setRichTextValues(rows.map(function (r) {
      return [SpreadsheetApp.newRichTextValue().setText(r[1]).setLinkUrl(r[n]).build()];
    }));
    t.setConditionalFormatRules([
      SpreadsheetApp.newConditionalFormatRule().whenNumberGreaterThanOrEqualTo(70)
        .setBackground("#c8e6c9").setRanges([t.getRange(4, 5, rows.length, 1)]).build(),
      SpreadsheetApp.newConditionalFormatRule().whenNumberBetween(40, 69)
        .setBackground("#fff3c4").setRanges([t.getRange(4, 5, rows.length, 1)]).build()
    ]);
  }
  t.setFrozenRows(3);
  var w = [40, 320, 200, 150, 90, 240, 110, 110];
  for (var i = 0; i < n; i++) t.setColumnWidth(i + 1, w[i]);
}

/**
 * TOMBOL TES: pilih fungsi "buatSheetSekarang" di editor Apps Script lalu klik Run (Jalankan).
 * Sheet arsip langsung dibuat / diperbarui (kolom pelacakan ditambahkan); linknya muncul di Log eksekusi.
 */
function buatSheetSekarang() {
  var ss = getMaster_();
  Logger.log("Sheet siap: " + ss.getUrl());
}