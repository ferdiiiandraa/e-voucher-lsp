/* Halaman Kelola — CRUD voucher & pemakaian */
'use strict';

const S = {
    sel: Number(new URLSearchParams(location.search).get('id')) || null,
    q: '',
    f: 'all',
};

/* ---------- Daftar ---------- */
function renderList(vs) {
    const items = vs.map(v => ({ v, i: info(v) }));

    // Jumlah per filter
    const counts = { all: items.length, ok: 0, warn: 0, danger: 0, muted: 0 };
    items.forEach(x => counts[x.i.c]++);
    $$('#filters .chip').forEach(ch => {
        const label = ch.textContent.replace(/\s*\d+$/, '');
        ch.innerHTML = U.esc(label) + `<span class="count">${counts[ch.dataset.f]}</span>`;
        ch.classList.toggle('active', ch.dataset.f === S.f);
    });

    const q = S.q.toLowerCase();
    const shown = items
        .filter(x => S.f === 'all' || x.i.c === S.f)
        .filter(x => !q || x.v.klien.toLowerCase().includes(q) || (x.v.kode || '').toLowerCase().includes(q))
        .sort((a, b) => STATUS_ORDER[a.i.c] - STATUS_ORDER[b.i.c] || a.v.berakhir.localeCompare(b.v.berakhir));

    if (!items.length) {
        $('#vlist').innerHTML = '<div class="empty">Belum ada voucher.<br>Klik <b>Voucher baru</b> untuk mulai.</div>';
        return;
    }
    $('#vlist').innerHTML = shown.length ? shown.map(({ v, i }) => {
        const w = Math.min(100, i.p * 100), cls = i.c === 'ok' ? '' : i.c;
        return `<button type="button" class="vitem${v.id === S.sel ? ' active' : ''}" data-id="${v.id}">
            <div class="vitem-top"><span class="vitem-name">${U.esc(v.klien)}</span>${badge(i)}</div>
            <div class="vitem-sub">${U.esc(v.kode || 'Tanpa kode')} · s/d ${U.fmt(v.berakhir)}</div>
            <div class="usage-top"><span><b>${U.num(i.u)}</b> / ${U.num(v.kuota)}</span><span>sisa ${U.num(Math.max(i.s, 0))}</span></div>
            <div class="progress"><i class="${cls}" style="width:${w}%"></i></div>
        </button>`;
    }).join('') : '<div class="empty">Tidak ada voucher yang cocok.</div>';
}

/* ---------- Detail ---------- */
function renderDetail() {
    const v = S.sel ? App.find(S.sel) : null;
    $('#detailEmpty').hidden = !!v;
    $('#detailBody').hidden = !v;
    if (!v) return;

    const i = info(v);
    $('#dKlien').textContent = v.klien;
    $('#dBadge').className = 'badge b-' + i.c;
    $('#dBadge').textContent = i.t;
    $('#dMeta').textContent = [v.kode || 'Tanpa kode', U.fmt(v.mulai) + ' – ' + U.fmt(v.berakhir), v.telepon ? WA.tampil(v.telepon) : 'Tanpa no. WA'].join(' · ');

    const wa = $('#btnWa');
    if (v.telepon) {
        wa.href = WA.link(v.telepon, WA.pesanInfo(v));
        wa.classList.remove('is-disabled');
        wa.removeAttribute('aria-disabled');
        wa.title = 'Kirim info sisa voucher ke ' + WA.tampil(v.telepon);
    } else {
        wa.removeAttribute('href');
        wa.classList.add('is-disabled');
        wa.setAttribute('aria-disabled', 'true');
        wa.title = 'Belum ada nomor WhatsApp. Tambahkan lewat Edit.';
    }

    $('#dKuota').textContent = U.num(v.kuota);
    $('#dUsed').textContent = U.num(i.u);
    const sisa = $('#dSisa');
    sisa.textContent = i.s < 0 ? '−' + U.num(-i.s) : U.num(i.s);
    sisa.className = i.s < 0 ? 'danger' : i.p >= 0.8 ? 'warn' : '';
    const days = $('#dDays');
    days.textContent = i.d < 0 ? 'Berakhir' : i.d === 0 ? 'Hari ini' : i.d + ' hari';
    days.className = i.d < 0 ? '' : i.d <= 14 ? 'warn' : '';

    const bar = $('#dBar');
    bar.style.width = Math.min(100, i.p * 100) + '%';
    bar.className = i.c === 'ok' ? '' : i.c;

    const cat = $('#dCatatan');
    cat.hidden = !v.catatan;
    cat.textContent = v.catatan || '';

    $('#dLogCount').textContent = v.jumlah_catatan ? v.jumlah_catatan + ' catatan' : '';

    // Riwayat dimuat terpisah (tidak ikut sinkronisasi) agar ringan
    const cached = LOGS.get(v.id);
    if (cached) renderLogs(v, cached.logs);
    else $('#dLogs').innerHTML = '<tr><td colspan="9" class="empty">Memuat riwayat…</td></tr>';

    if (!cached || cached.rev !== App.rev) {
        const id = v.id;
        fetchLogs([id])
            .then(() => { if (S.sel === id && App.find(id)) renderLogs(App.find(id), LOGS.get(id).logs); })
            .catch(err => toast(err.message, 'error'));
    }
}

/* ---------- Riwayat (dimuat sesuai kebutuhan) ---------- */
const LOGS = new Map();     // voucher_id -> { rev, logs }
const PENDING = new Map();  // `${rev}:${ids}` -> Promise

/** Ambil riwayat voucher yang belum ada / sudah usang di cache. */
async function fetchLogs(ids) {
    const rev = App.rev;
    const need = ids.filter(id => LOGS.get(id)?.rev !== rev);
    for (let i = 0; i < need.length; i += 500) {
        const chunk = need.slice(i, i + 500);
        const key = rev + ':' + chunk.join(',');
        if (!PENDING.has(key)) {
            PENDING.set(key, App.request(APP.routes.riwayat + '?ids=' + chunk.join(','))
                .then(d => chunk.forEach(id => LOGS.set(id, { rev, logs: d.logs[id] || [] })))
                .finally(() => PENDING.delete(key)));
        }
        await PENDING.get(key);
    }
}

const TRASH = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/></svg>';

function renderLogs(v, logs) {
    // Hitung kumulatif urut tanggal, tampilkan terbaru di atas
    let run = 0;
    const rows = logs.map(l => ({ l, run: (run += l.jumlah) })).reverse();
    $('#dLogs').innerHTML = rows.length ? rows.map(({ l, run }, n) => `
        <tr class="${run > v.kuota ? 'over' : ''}">
            <td class="c muted">${n + 1}</td>
            <td class="nowrap">${U.fmt(l.tanggal)}</td>
            <td class="c">${l.jenis === 'koreksi' ? '<span class="badge b-info tag">Koreksi</span>' : '<span class="badge b-muted tag">Pemakaian</span>'}</td>
            <td class="c"><b>${l.jumlah < 0 ? '−' + U.num(-l.jumlah) : U.num(l.jumlah)}</b></td>
            <td class="c">${U.num(run)}</td>
            <td class="c">${U.num(v.kuota - run)}</td>
            <td class="log-ket">${U.esc(l.keterangan || '—')}</td>
            <td class="muted nowrap">${U.esc(l.oleh || '—')}</td>
            <td class="c log-act"><button type="button" class="btn btn-sm btn-icon btn-ghost-danger" data-del-log="${l.id}" title="Hapus catatan" aria-label="Hapus catatan">${TRASH}</button></td>
        </tr>`).join('') : '<tr><td colspan="9" class="empty">Belum ada pemakaian. Catat lewat form di atas.</td></tr>';
}

function render() { renderList(App.vouchers); renderDetail(); }

function select(id, scroll = true) {
    S.sel = id;
    const url = new URL(location.href);
    if (id) url.searchParams.set('id', id); else url.searchParams.delete('id');
    history.replaceState(null, '', url);
    render();
    if (id && scroll && window.matchMedia('(max-width: 1024px)').matches) {
        $('#detail').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/* ---------- Dialog voucher ---------- */
const dlgV = $('#dlgVoucher'), formV = $('#formVoucher');
let editingId = null;
let berakhirManual = false; // true bila user mengubah tanggal berakhir sendiri

/** Isi otomatis "berlaku sampai" dari mulai + bulan, kecuali sudah diubah manual. */
function autoBerakhir() {
    const m = formV.mulai.value, b = Number(formV.bulan.value);
    if (!berakhirManual && m && b > 0) formV.berakhir.value = U.addMonths(m, b);
}
function openVoucher(v = null) {
    editingId = v ? v.id : null;
    $('#vTitle').textContent = v ? 'Edit voucher' : 'Voucher baru';
    formV.klien.value = v ? v.klien : '';
    formV.kode.value = v ? (v.kode || '') : '';
    formV.telepon.value = v ? (v.telepon ? '0' + v.telepon.replace(/^62/, '') : '') : '';
    formV.kuota.value = v ? v.kuota : '';
    formV.mulai.value = v ? v.mulai : U.today();
    formV.bulan.value = v ? v.bulan : 3;
    formV.catatan.value = v ? (v.catatan || '') : '';
    berakhirManual = !!v && v.berakhir !== U.addMonths(v.mulai, v.bulan);
    if (v) formV.berakhir.value = v.berakhir;
    autoBerakhir();
    telpPreview();
    dlgV.showModal();
    formV.klien.focus();
}

formV.mulai.addEventListener('input', autoBerakhir);
formV.bulan.addEventListener('input', autoBerakhir);
formV.berakhir.addEventListener('input', () => { berakhirManual = true; });

/** Tampilkan nomor yang benar-benar akan dihubungi WhatsApp + tautan untuk mengecek. */
function telpPreview() {
    const el = $('#vTelpPrev'), raw = formV.telepon.value.trim();
    el.hidden = !raw;
    if (!raw) return;
    const n = WA.nomor(raw);
    el.innerHTML = `WhatsApp: <b>${U.esc(WA.tampil(raw))}</b> · <a href="https://wa.me/${n}" target="_blank" rel="noopener">cek nomor</a>`;
}
formV.telepon.addEventListener('input', telpPreview);
// Simpan saja — WhatsApp hanya dibuka saat tombol "WhatsApp" di detail voucher diklik
formV.addEventListener('submit', async e => {
    e.preventDefault();
    const body = formData(formV);
    await withBusy($('[type=submit]', formV), async () => {
        try {
            const d = editingId
                ? await App.mutate(APP.routes.vouchers + '/' + editingId, 'PUT', body)
                : await App.mutate(APP.routes.vouchers, 'POST', body);
            dlgV.close();
            toast(d.message);
            select(d.id);
        } catch (err) { toast(err.message, 'error'); }
    });
});

/* ---------- Update sisa (kuota / masa berlaku) ---------- */
const dlgS = $('#dlgSisa'), formS = $('#formSisa');
let sKat = 'kuota';

function sSetKat(k) {
    sKat = k;
    $$('#formSisa .seg').forEach(b => {
        const on = b.dataset.kat === k;
        b.classList.toggle('active', on);
        b.setAttribute('aria-selected', String(on));
    });
    $$('#formSisa .sisa-panel').forEach(p => { p.hidden = p.dataset.panel !== k; });
    $('#sKetWrap').hidden = k !== 'kuota';
    sPreview();
    setTimeout(() => (k === 'kuota' ? $('#s_sisa') : $('#s_berakhir')).focus(), 0);
}

function sPreview() {
    const v = App.find(S.sel);
    if (!v) return;
    const i = info(v);
    const btn = $('#sSubmit');

    if (sKat === 'kuota') {
        const raw = $('#s_sisa').value, el = $('#sPrevKuota');
        const baru = Number(raw), now = Math.max(i.s, 0);
        el.className = 'sisa-preview';
        if (raw === '') { el.textContent = `Masukkan sisa kuota sesuai kondisi sebenarnya (0 – ${U.num(v.kuota)}).`; btn.disabled = true; return; }
        if (baru < 0 || baru > v.kuota || !Number.isInteger(baru)) {
            el.textContent = `Sisa harus antara 0 dan ${U.num(v.kuota)}. Untuk menambah kuota, gunakan Edit.`;
            el.classList.add('is-error'); btn.disabled = true; return;
        }
        const diff = i.s - baru;
        btn.disabled = diff === 0;
        if (diff === 0) el.textContent = 'Sisa sama dengan saat ini, tidak ada perubahan.';
        else {
            el.innerHTML = diff > 0
                ? `Sisa <b>berkurang ${U.num(diff)}</b> asesi → menjadi <b>${U.num(baru)}</b>. Dicatat sebagai koreksi di riwayat.`
                : `Sisa <b>bertambah ${U.num(-diff)}</b> asesi → menjadi <b>${U.num(baru)}</b>. Dicatat sebagai koreksi di riwayat.`;
            if (baru > 0 && baru <= APP.notif.sisa) el.innerHTML += ` <span class="t-warn">Akan masuk notifikasi kuota.</span>`;
        }
        $('#sNowKuota').textContent = U.num(now) + ' asesi';
    } else {
        const val = $('#s_berakhir').value, el = $('#sPrevMasa');
        el.className = 'sisa-preview';
        if (!val) { el.textContent = 'Pilih tanggal berakhir yang baru.'; btn.disabled = true; return; }
        if (val < v.mulai) { el.textContent = 'Tanggal berakhir tidak boleh sebelum tanggal mulai (' + U.fmt(v.mulai) + ').'; el.classList.add('is-error'); btn.disabled = true; return; }
        btn.disabled = val === v.berakhir;
        const d = Math.round((U.pd(val) - U.pd(U.today())) / 864e5);
        el.innerHTML = val === v.berakhir
            ? 'Tanggal sama dengan saat ini, tidak ada perubahan.'
            : `Berlaku sampai <b>${U.fmt(val)}</b> · ${d < 0 ? '<span class="t-danger">' + daysText(d) + '</span>' : daysText(d)}.` +
              (d >= 0 && d <= APP.notif.hari ? ` <span class="t-warn">Akan masuk notifikasi masa berlaku.</span>` : '');
    }
}

$('#btnSisa').addEventListener('click', () => {
    const v = App.find(S.sel);
    if (!v) return;
    const i = info(v);
    $('#sKlien').textContent = v.klien + (v.kode ? ' · ' + v.kode : '');
    $('#sNowKuota').textContent = U.num(Math.max(i.s, 0)) + ' asesi';
    $('#sNowMasa').textContent = U.fmt(v.berakhir);
    $('#s_sisa').max = v.kuota;
    $('#s_sisa').value = '';
    $('#s_berakhir').value = v.berakhir;
    $('#s_berakhir').min = v.mulai;
    $('#s_ket').value = '';
    dlgS.showModal();
    sSetKat('kuota');
});

$$('#formSisa .seg').forEach(b => b.addEventListener('click', () => sSetKat(b.dataset.kat)));
$('#s_sisa').addEventListener('input', sPreview);
$('#s_berakhir').addEventListener('input', sPreview);

$('#sQuick').addEventListener('click', e => {
    const c = e.target.closest('.chip');
    const v = App.find(S.sel);
    if (!c || !v) return;
    if (c.hasAttribute('data-today')) $('#s_berakhir').value = U.today();
    else {
        const d = U.pd($('#s_berakhir').value || v.berakhir);
        d.setDate(d.getDate() + Number(c.dataset.add));
        $('#s_berakhir').value = U.fs(d);
    }
    sPreview();
});

formS.addEventListener('submit', async e => {
    e.preventDefault();
    const v = App.find(S.sel);
    if (!v) return;
    const body = sKat === 'kuota'
        ? { kategori: 'kuota', sisa: $('#s_sisa').value, keterangan: $('#s_ket').value.trim() }
        : { kategori: 'masa', berakhir: $('#s_berakhir').value };
    await withBusy($('#sSubmit'), async () => {
        try {
            const d = await App.mutate(APP.routes.vouchers + '/' + v.id + '/sisa', 'PATCH', body);
            dlgS.close();
            toast(d.message);
        } catch (err) { toast(err.message, 'error'); }
    });
});

// Susun ulang pesan WA tepat saat diklik, dari data terbaru voucher yang dibuka
// (sisa kuota & sisa hari selalu sesuai kondisi saat itu, bukan saat halaman dimuat).
$('#btnWa').addEventListener('click', e => {
    const v = App.find(S.sel);
    if (!v || !v.telepon) { e.preventDefault(); return; }
    e.currentTarget.href = WA.link(v.telepon, WA.pesanInfo(v));
});

/* ---------- Event ---------- */
$('#btnNew').addEventListener('click', () => openVoucher());
$('#btnEdit').addEventListener('click', () => { const v = App.find(S.sel); if (v) openVoucher(v); });

$('#btnDelete').addEventListener('click', async () => {
    const v = App.find(S.sel);
    if (!v) return;
    const ok = await confirmDialog({
        title: 'Hapus voucher?',
        message: `Voucher "${v.klien}" beserta ${v.jumlah_catatan} catatan pemakaiannya akan dihapus permanen.`,
        ok: 'Hapus voucher',
    });
    if (!ok) return;
    try {
        const d = await App.mutate(APP.routes.vouchers + '/' + v.id, 'DELETE');
        toast(d.message);
        select(null, false);
    } catch (err) { toast(err.message, 'error'); }
});

$('#vlist').addEventListener('click', e => {
    const it = e.target.closest('.vitem');
    if (it) select(Number(it.dataset.id));
});

$('#q').addEventListener('input', e => { S.q = e.target.value.trim(); renderList(App.vouchers); });

$('#filters').addEventListener('click', e => {
    const ch = e.target.closest('.chip');
    if (!ch) return;
    S.f = ch.dataset.f;
    renderList(App.vouchers);
});

const formUse = $('#formUse');
formUse.tanggal.value = U.today();

formUse.addEventListener('submit', async e => {
    e.preventDefault();
    const v = App.find(S.sel);
    if (!v) return;
    const body = { ...formData(formUse), voucher_id: v.id };
    const jumlah = Number(body.jumlah);
    const after = used(v) + jumlah;

    if (after > v.kuota) {
        const ok = await confirmDialog({
            title: 'Melebihi kuota',
            message: `Total pemakaian menjadi ${U.num(after)} dari kuota ${U.num(v.kuota)} (lebih ${U.num(after - v.kuota)}). Tetap catat?`,
            ok: 'Tetap catat',
        });
        if (!ok) return;
    }

    await withBusy($('[type=submit]', formUse), async () => {
        try {
            const d = await App.mutate(APP.routes.pemakaian, 'POST', body);
            formUse.jumlah.value = '';
            formUse.keterangan.value = '';
            formUse.jumlah.focus();
            toast(d.message);
        } catch (err) { toast(err.message, 'error'); }
    });
});

$('#dLogs').addEventListener('click', async e => {
    const btn = e.target.closest('[data-del-log]');
    if (!btn) return;
    const ok = await confirmDialog({ title: 'Hapus catatan?', message: 'Catatan pemakaian ini akan dihapus.', ok: 'Hapus' });
    if (!ok) return;
    try {
        const d = await App.mutate(APP.routes.pemakaian + '/' + btn.dataset.delLog, 'DELETE');
        toast(d.message);
    } catch (err) { toast(err.message, 'error'); }
});

/* ---------- Ekspor Excel ---------- */
const klienKey = s => s.trim().toLowerCase();

/** Daftar klien unik (nama dikelompokkan tanpa beda huruf besar/kecil). */
function klienList() {
    const map = new Map();
    App.vouchers.forEach(v => {
        const k = klienKey(v.klien);
        if (!map.has(k)) map.set(k, { key: k, nama: v.klien.trim(), vouchers: [] });
        map.get(k).vouchers.push(v);
    });
    return [...map.values()].sort((a, b) => a.nama.localeCompare(b.nama, 'id'));
}

function slug(s) {
    return s.toLowerCase().normalize('NFKD').replace(/[^\w\s-]/g, '').trim().replace(/[\s_]+/g, '-').slice(0, 50) || 'klien';
}
const countLogs = vs => vs.reduce((a, v) => a + (v.jumlah_catatan || 0), 0);

const X = {
    dlg: $('#dlgExport'),
    form: $('#formExport'),
    q: $('#x_q'),
    mode: 'all',          // 'all' | 'pick'
    picked: new Set(),    // kunci klien terpilih (mode pick)
    klien: [],
};

const BOX = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

/** Klien yang akan diekspor sesuai pilihan saat ini. */
function xTargets() {
    return X.mode === 'all' ? X.klien : X.klien.filter(k => X.picked.has(k.key));
}
function xVisible() {
    const q = X.q.value.trim().toLowerCase();
    return X.klien.filter(k => !q || k.nama.toLowerCase().includes(q) || k.vouchers.some(v => (v.kode || '').toLowerCase().includes(q)));
}

function xRenderList() {
    const shown = xVisible();
    $('#xList').innerHTML = shown.length ? shown.map(k => {
        const on = X.picked.has(k.key);
        const kode = k.vouchers.map(v => v.kode).filter(Boolean).join(', ');
        return `<button type="button" class="pick${on ? ' active' : ''}" data-key="${U.esc(k.key)}" role="option" aria-selected="${on}">
            <span class="pick-box">${BOX}</span>
            <span class="pick-body">
                <span class="pick-name">${U.esc(k.nama)}</span>
                <span class="pick-sub">${k.vouchers.length} voucher · ${countLogs(k.vouchers)} pemakaian${kode ? ' · ' + U.esc(kode) : ''}</span>
            </span>
        </button>`;
    }).join('') : '<div class="empty">Klien tidak ditemukan.</div>';

    const allShownPicked = shown.length && shown.every(k => X.picked.has(k.key));
    $('#xSelAll').textContent = X.q.value.trim() ? `Pilih hasil (${shown.length})` : 'Pilih semua';
    $('#xSelAll').disabled = !shown.length || allShownPicked;
    $('#xSelNone').disabled = !X.picked.size;
}

/** Perbarui semua bagian yang bergantung pada pilihan (tanpa merender ulang daftar). */
function xRefresh() {
    // Segmented control
    $$('#formExport .seg').forEach(b => {
        const on = b.dataset.mode === X.mode;
        b.classList.toggle('active', on);
        b.setAttribute('aria-checked', String(on));
    });
    $('#xPickWrap').hidden = X.mode !== 'pick';

    const vsAll = App.vouchers;
    $('#xAllInfo').hidden = X.mode !== 'all';
    $('#xAllInfo').innerHTML = `Mengekspor <b>${X.klien.length} klien</b> · ${vsAll.length} voucher · ${countLogs(vsAll)} pemakaian.`;

    $('#xSelCount').innerHTML = X.picked.size ? `<b>${X.picked.size}</b> dari ${X.klien.length} klien dipilih` : `Belum ada klien dipilih`;

    const t = xTargets();
    const vs = t.flatMap(k => k.vouchers);
    const multi = t.length > 1;
    const format = X.form.format.value;

    // Format hanya relevan bila lebih dari 1 klien
    $('#xFormatStep').classList.toggle('is-disabled', !multi);
    $$('#xFormatStep input').forEach(i => { i.disabled = !multi; });
    $('#xFormatHint').hidden = multi;

    const btn = $('#xSubmit');
    if (!t.length) {
        $('#xInfo').textContent = 'Pilih minimal 1 klien.';
        btn.textContent = 'Unduh';
        btn.disabled = true;
        return;
    }
    btn.disabled = false;
    $('#xInfo').innerHTML = `<b>${t.length} klien</b> · ${vs.length} voucher · ${countLogs(vs)} pemakaian`;
    btn.textContent = !multi ? 'Unduh Excel' : format === 'pisah' ? `Unduh ZIP (${t.length} file)` : 'Unduh 1 file Excel';
}

function xSetMode(mode) {
    X.mode = mode;
    xRefresh();
    if (mode === 'pick') { xRenderList(); setTimeout(() => X.q.focus(), 0); }
}

function xToggle(key) {
    X.picked.has(key) ? X.picked.delete(key) : X.picked.add(key);
    const b = $(`#xList .pick[data-key="${CSS.escape(key)}"]`);
    if (b) {
        const on = X.picked.has(key);
        b.classList.toggle('active', on);
        b.setAttribute('aria-selected', String(on));
    }
    const shown = xVisible();
    $('#xSelAll').disabled = !shown.length || shown.every(k => X.picked.has(k.key));
    $('#xSelNone').disabled = !X.picked.size;
    xRefresh();
}

$('#btnExport').addEventListener('click', () => {
    if (!App.vouchers.length) { toast('Belum ada data untuk diekspor.', 'error'); return; }
    X.klien = klienList();
    X.q.value = '';
    X.picked = new Set();
    X.form.format.value = 'gabung';

    // Bila sedang membuka voucher tertentu, langsung pilih kliennya
    const cur = App.find(S.sel);
    if (cur) { X.picked.add(klienKey(cur.klien)); X.mode = 'pick'; } else { X.mode = 'all'; }

    xRenderList();
    xRefresh();
    X.dlg.showModal();
    if (X.mode === 'pick') { X.q.focus(); $('#xList .pick.active')?.scrollIntoView({ block: 'nearest' }); }
});

$$('#formExport .seg').forEach(b => b.addEventListener('click', () => xSetMode(b.dataset.mode)));
$$('#xFormatStep input').forEach(i => i.addEventListener('change', xRefresh));
X.q.addEventListener('input', xRenderList);

// Enter di kolom cari: centang hasil pertama (bukan submit)
X.q.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const first = xVisible()[0];
    if (first && !X.picked.has(first.key)) xToggle(first.key);
});

$('#xList').addEventListener('click', e => {
    const b = e.target.closest('.pick');
    if (b) xToggle(b.dataset.key);
});

$('#xSelAll').addEventListener('click', () => {
    xVisible().forEach(k => X.picked.add(k.key));
    xRenderList(); xRefresh();
});
$('#xSelNone').addEventListener('click', () => {
    X.picked.clear();
    xRenderList(); xRefresh();
});

X.form.addEventListener('submit', async e => {
    e.preventDefault();
    const t = xTargets();
    if (!t.length) return;
    const btn = $('#xSubmit');
    const label = btn.textContent;
    btn.textContent = 'Menyiapkan…';
    await withBusy(btn, async () => {
        try {
            const ok = t.length > 1 && X.form.format.value === 'pisah'
                ? await exportZip(t)
                : await exportCombined(t, X.mode === 'all');
            if (ok) X.dlg.close();
        } catch (err) { toast(err.message, 'error'); }
    });
    btn.textContent = label;
});

// Tombol "Ekspor" di panel detail: langsung klien voucher yang sedang dibuka
$('#btnExportOne').addEventListener('click', async () => {
    const v = App.find(S.sel);
    if (!v) return;
    const key = klienKey(v.klien);
    const k = klienList().find(x => x.key === key);
    if (!k) return;
    await withBusy($('#btnExportOne'), () => exportCombined([k], false).catch(err => toast(err.message, 'error')));
});

/* --- Pembuatan file --- */
/** 'YYYY-MM-DD' -> Date (UTC) agar tanggal di Excel tidak bergeser karena zona waktu. */
function xDate(s) {
    const [y, m, d] = s.split('-').map(Number);
    return new Date(Date.UTC(y, m - 1, d));
}

function needLib(name) {
    if (window[name]) return true;
    toast('Library ekspor belum termuat. Cek koneksi internet lalu coba lagi.', 'error');
    return false;
}

const XL = {
    date: 'dd/mm/yyyy',
    int: '#,##0;[Red]-#,##0',
    border: { style: 'thin', color: { argb: 'FFD0D5DD' } },
    head: { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1F4E79' } },
    zebra: { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF5F8FC' } },
    status: { // warna teks status di sheet ringkasan
        ok: 'FF15803D', warn: 'FFB45309', danger: 'FFB91C1C', muted: 'FF6B7280',
    },
};

/**
 * Tambah sheet bergaya tabel: judul, sub-judul, header tebal berwarna,
 * border tipis, baris zebra, header dibekukan, dan filter otomatis.
 * cols: [{ header, key, width, fmt?, align? }]
 */
function xSheet(wb, name, title, cols, rows, decorate) {
    const ws = wb.addWorksheet(name, { views: [{ state: 'frozen', ySplit: 4 }] });
    const last = cols.length;

    ws.columns = cols.map(c => ({ key: c.key, width: c.width }));

    ws.mergeCells(1, 1, 1, last);
    ws.getCell(1, 1).value = title;
    ws.getCell(1, 1).font = { bold: true, size: 14, color: { argb: 'FF1F4E79' } };
    ws.mergeCells(2, 1, 2, last);
    ws.getCell(2, 1).value = `Diekspor ${U.fmt(U.today())} · ${rows.length} baris`;
    ws.getCell(2, 1).font = { italic: true, size: 10, color: { argb: 'FF6B7280' } };
    ws.getRow(1).height = 22;

    const head = ws.getRow(4);
    cols.forEach((c, k) => {
        const cell = head.getCell(k + 1);
        cell.value = c.header;
        cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = XL.head;
        cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
        cell.border = { top: XL.border, left: XL.border, bottom: XL.border, right: XL.border };
    });
    head.height = 24;

    rows.forEach((r, n) => {
        const row = ws.getRow(5 + n);
        cols.forEach((c, k) => {
            const cell = row.getCell(k + 1);
            cell.value = r[c.key] ?? '';
            if (c.fmt) cell.numFmt = c.fmt;
            cell.alignment = { vertical: 'top', horizontal: c.align || 'left', wrapText: !!c.wrap };
            cell.border = { top: XL.border, left: XL.border, bottom: XL.border, right: XL.border };
            if (n % 2 === 1) cell.fill = XL.zebra;
        });
        if (decorate) decorate(row, r);
    });

    if (rows.length) ws.autoFilter = { from: { row: 4, column: 1 }, to: { row: 4 + rows.length, column: last } };
    ws.pageSetup = { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0, printTitlesRow: '4:4' };
    return ws;
}

/** Buat workbook (ExcelJS) berisi sheet Ringkasan Voucher & Riwayat Pemakaian. */
function buildWorkbook(vs) {
    const ringkas = [], riwayat = [];

    vs.slice().sort((a, b) => a.klien.localeCompare(b.klien, 'id') || a.mulai.localeCompare(b.mulai)).forEach(v => {
        const i = info(v);
        let run = 0;
        ringkas.push({
            klien: v.klien, kode: v.kode || '', wa: v.telepon ? WA.tampil(v.telepon) : '',
            kuota: v.kuota, mulai: xDate(v.mulai), berakhir: xDate(v.berakhir),
            hari: i.d, terpakai: i.u, sisa: v.kuota - i.u, status: i.t, catatan: v.catatan || '', _c: i.c,
        });
        v.logs.forEach(l => {
            run += l.jumlah;
            riwayat.push({
                klien: v.klien, kode: v.kode || '', tanggal: xDate(l.tanggal),
                jenis: l.jenis === 'koreksi' ? 'Koreksi' : 'Pemakaian',
                jumlah: l.jumlah, kumulatif: run, sisa: v.kuota - run,
                ket: l.keterangan || '', oleh: l.oleh || '', _over: run > v.kuota,
            });
        });
    });

    const wb = new ExcelJS.Workbook();
    wb.creator = 'Monitor Voucher Asesi';
    wb.created = new Date();

    xSheet(wb, 'Ringkasan Voucher', 'Ringkasan Voucher Asesi', [
        { header: 'No.', key: 'no', width: 5, align: 'center' },
        { header: 'Klien', key: 'klien', width: 28 },
        { header: 'Kode Voucher', key: 'kode', width: 18 },
        { header: 'No. WhatsApp', key: 'wa', width: 18 },
        { header: 'Kuota', key: 'kuota', width: 9, fmt: XL.int, align: 'right' },
        { header: 'Mulai', key: 'mulai', width: 12, fmt: XL.date, align: 'center' },
        { header: 'Berlaku s/d', key: 'berakhir', width: 12, fmt: XL.date, align: 'center' },
        { header: 'Sisa Hari', key: 'hari', width: 10, fmt: XL.int, align: 'right' },
        { header: 'Terpakai', key: 'terpakai', width: 10, fmt: XL.int, align: 'right' },
        { header: 'Sisa', key: 'sisa', width: 9, fmt: XL.int, align: 'right' },
        { header: 'Status', key: 'status', width: 20 },
        { header: 'Catatan', key: 'catatan', width: 36, wrap: true },
    ], ringkas.map((r, n) => ({ ...r, no: n + 1 })), (row, r) => {
        row.getCell(11).font = { bold: true, color: { argb: XL.status[r._c] } };
    });

    xSheet(wb, 'Riwayat Pemakaian', 'Riwayat Pemakaian Voucher', [
        { header: 'No.', key: 'no', width: 5, align: 'center' },
        { header: 'Klien', key: 'klien', width: 28 },
        { header: 'Kode Voucher', key: 'kode', width: 18 },
        { header: 'Tanggal', key: 'tanggal', width: 12, fmt: XL.date, align: 'center' },
        { header: 'Jenis', key: 'jenis', width: 11, align: 'center' },
        { header: 'Jumlah Asesi', key: 'jumlah', width: 12, fmt: XL.int, align: 'right' },
        { header: 'Kumulatif', key: 'kumulatif', width: 11, fmt: XL.int, align: 'right' },
        { header: 'Sisa Voucher', key: 'sisa', width: 12, fmt: XL.int, align: 'right' },
        { header: 'Keterangan', key: 'ket', width: 40, wrap: true },
        { header: 'Dicatat oleh', key: 'oleh', width: 18 },
    ], riwayat.map((r, n) => ({ ...r, no: n + 1 })), (row, r) => {
        // Baris yang melewati kuota ditandai merah (sama seperti di aplikasi)
        if (r._over) row.eachCell(c => { c.font = { color: { argb: 'FFB91C1C' } }; });
    });

    return wb;
}

/** Workbook -> Blob .xlsx */
async function xlsxBlob(wb) {
    const buf = await wb.xlsx.writeBuffer();
    return new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
}

function download(blob, filename) {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
}

/** Lengkapi voucher dengan riwayatnya (diambil dari server bila belum ada di cache). */
async function withLogs(vs) {
    await fetchLogs(vs.map(v => v.id));
    return vs.map(v => ({ ...v, logs: LOGS.get(v.id)?.logs || [] }));
}

/** Semua klien terpilih dalam satu file. */
async function exportCombined(targets, isAll) {
    if (!needLib('ExcelJS')) return false;
    const vs = await withLogs(targets.flatMap(k => k.vouchers));
    const nama = targets.length === 1 ? slug(targets[0].nama) : isAll ? 'semua-klien' : targets.length + '-klien';
    download(await xlsxBlob(buildWorkbook(vs)), `e-voucher-${nama}-${U.today()}.xlsx`);
    toast(targets.length === 1 ? `Excel diunduh: ${targets[0].nama}` : `Excel diunduh: ${targets.length} klien dalam 1 file`);
    return true;
}

/** Satu file per klien, dibungkus dalam ZIP. */
async function exportZip(targets) {
    if (!needLib('ExcelJS') || !needLib('JSZip')) return false;
    const full = new Map((await withLogs(targets.flatMap(k => k.vouchers))).map(v => [v.id, v]));
    const zip = new JSZip(), names = new Set();
    for (const k of targets) {
        let name = slug(k.nama), n = 2;
        while (names.has(name)) name = slug(k.nama) + '-' + n++;
        names.add(name);
        const vs = k.vouchers.map(v => full.get(v.id));
        zip.file(`e-voucher-${name}.xlsx`, await buildWorkbook(vs).xlsx.writeBuffer());
    }
    download(await zip.generateAsync({ type: 'blob' }), `e-voucher-per-klien-${U.today()}.zip`);
    toast(`ZIP diunduh: ${targets.length} file Excel`);
    return true;
}

/* ---------- Mulai ---------- */
App.onData(render);
App.start();

// Dari tombol "Voucher baru" di Beranda: langsung buka dialog tambah
if (new URLSearchParams(location.search).has('baru')) {
    const url = new URL(location.href);
    url.searchParams.delete('baru');
    history.replaceState(null, '', url);
    openVoucher();
}
