/* E-Voucher — fungsi bersama (sinkronisasi realtime, helper, dialog, toast) */
'use strict';

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

/* ---------- Format & tanggal ---------- */
const U = {
    esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    },
    num(n) { return Number(n || 0).toLocaleString('id-ID'); },
    pd(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); },
    fs(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); },
    today() { return U.fs(new Date()); },
    fmt(s) { return U.pd(s).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); },
    fmtLong(d) { return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }); },
    addMonths(s, n) {
        // Sama dengan server: mulai + n bulan (tanpa overflow) - 1 hari
        const d = U.pd(s), day = d.getDate();
        d.setDate(1); d.setMonth(d.getMonth() + n);
        const last = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
        d.setDate(Math.min(day, last)); d.setDate(d.getDate() - 1);
        return U.fs(d);
    },
    ago(iso) {
        if (!iso) return '';
        const s = Math.round((Date.now() - new Date(iso).getTime()) / 1000);
        if (s < 60) return 'baru saja';
        if (s < 3600) return Math.floor(s / 60) + ' menit lalu';
        if (s < 86400) return Math.floor(s / 3600) + ' jam lalu';
        if (s < 86400 * 7) return Math.floor(s / 86400) + ' hari lalu';
        return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    },
};

/* ---------- Status voucher ---------- */
function used(v) { return v.terpakai || 0; }
function daysLeft(v) { return Math.round((U.pd(v.berakhir) - U.pd(U.today())) / 864e5); }

/**
 * c: ok | warn | danger | muted   (kelas warna)
 * t: label status
 * kuotaAlert / masaAlert: dua kategori notifikasi
 *   - kuota  : sisa asesi <= APP.notif.sisa (atau sudah over kuota)
 *   - masa   : sisa hari  <= APP.notif.hari
 */
function info(v) {
    const N = APP.notif;
    const u = used(v), s = v.kuota - u, p = v.kuota ? u / v.kuota : 0, d = daysLeft(v);
    const kuotaMenipis = s > 0 && s <= N.sisa;
    const masaMenipis = d >= 0 && d <= N.hari;
    let c, t;
    if (u > v.kuota) { c = 'danger'; t = 'Over kuota +' + U.num(u - v.kuota); }
    else if (s <= 0) { c = 'muted'; t = 'Habis'; }
    else if (d < 0) { c = 'muted'; t = 'Kedaluwarsa'; }
    else if (kuotaMenipis) { c = 'warn'; t = 'Kuota menipis'; }
    else if (masaMenipis) { c = 'warn'; t = 'Segera berakhir'; }
    else { c = 'ok'; t = 'Aman'; }
    return {
        u, s, p, d, c, t,
        kuotaAlert: d >= 0 && (u > v.kuota || kuotaMenipis),
        masaAlert: s > 0 && masaMenipis,
    };
}

/** Kelompokkan voucher yang butuh perhatian ke dua kategori notifikasi. */
function alerts(vs) {
    const kuota = [], masa = [];
    vs.forEach(v => {
        const i = info(v);
        if (i.kuotaAlert) kuota.push({ v, i });
        if (i.masaAlert) masa.push({ v, i });
    });
    kuota.sort((a, b) => a.i.s - b.i.s);
    masa.sort((a, b) => a.i.d - b.i.d);
    return { kuota, masa };
}
function kuotaText(v, i) {
    return i.u > v.kuota ? `Melebihi kuota ${U.num(i.u - v.kuota)} asesi` : `Sisa ${U.num(i.s)} dari ${U.num(v.kuota)} asesi`;
}

/* ---------- WhatsApp ---------- */
const WA = {
    nomor(t) {
        let d = String(t || '').replace(/\D+/g, '');
        if (d.startsWith('0')) d = '62' + d.slice(1);
        else if (d.startsWith('8')) d = '62' + d;
        return d;
    },
    tampil(t) {
        const d = WA.nomor(t);
        return d ? '+' + d.replace(/^(\d{2})(\d{3})(\d{4})(\d+)$/, '$1 $2-$3-$4') : '';
    },
    link(t, pesan) { return `https://wa.me/${WA.nomor(t)}?text=${encodeURIComponent(pesan)}`; },

    /** Pesan saat voucher baru dibuat. */
    pesanBaru(v) {
        return [
            `Halo *${v.klien}*,`,
            '',
            'Voucher asesi Anda telah aktif dengan rincian berikut:',
            '',
            `🎫 Kode voucher : *${v.kode || '-'}*`,
            `👥 Kuota        : *${U.num(v.kuota)} asesi*`,
            `📅 Masa berlaku : ${U.fmt(v.mulai)} s/d *${U.fmt(v.berakhir)}*`,
            '',
            'Silakan hubungi kami bila ada pertanyaan. Terima kasih 🙏',
            '',
            `Salam,\n${APP.waTtd}`,
        ].join('\n');
    },

    /** Pesan informasi sisa voucher (bisa dikirim kapan saja). */
    pesanInfo(v) {
        const i = info(v);
        const tgl = U.pd(v.berakhir).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        const sisaHari = i.d < 0 ? 'sudah berakhir' : i.d === 0 ? 'berakhir hari ini' : `${i.d} hari lagi`;

        let ket;
        if (i.u > v.kuota) ket = `Pemakaian voucher Anda telah melebihi kuota sebanyak ${U.num(i.u - v.kuota)} asesi.`;
        else if (i.s <= 0) ket = 'Kuota voucher Anda saat ini telah habis.';
        else if (i.d < 0) ket = `Masa berlaku voucher Anda telah berakhir. Sisa kuota sebanyak ${U.num(i.s)} asesi tidak dapat digunakan.`;
        else {
            ket = `Kuota voucher Anda saat ini masih tersedia sebanyak ${U.num(i.s)} asesi.`;
            if (i.masaAlert) ket += i.d === 0 ? ' Masa berlaku voucher berakhir hari ini.' : ` Masa berlaku voucher akan berakhir dalam ${i.d} hari.`;
        }

        return [
            'Yth. Bapak/Ibu,',
            '',
            'Kami informasikan bahwa terdapat pembaruan informasi voucher asesi Anda dengan rincian sebagai berikut:',
            '',
            '*Informasi Voucher*',
            `- Kode Voucher: ${v.kode || '-'}`,
            `- Sisa Kuota: ${U.num(Math.max(i.s, 0))} dari ${U.num(v.kuota)} asesi`,
            `- Masa Berlaku: ${tgl} (${sisaHari})`,
            '',
            '*Informasi:*',
            ket,
            '',
            'Apabila Bapak/Ibu ingin melakukan penambahan kuota atau perpanjangan masa berlaku voucher, silakan membalas pesan ini melalui kontak WhatsApp ini.',
            '',
            'Terima kasih atas perhatian dan kerja samanya.',
        ].join('\n');
    },
};

/* ---------- Lonceng notifikasi (global) ---------- */
const NOTIF_ICON = {
    kuota: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
    masa: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
};
const BASE_TITLE = document.title;

/*
 * Status "sudah dibaca" disimpan per pengguna di browser. Kunci notifikasi ikut
 * memuat kondisinya (sisa kuota / tanggal berakhir), jadi bila kondisi berubah
 * notifikasi muncul lagi sebagai belum dibaca.
 */
const NOTIF = {
    storeKey: 'notifSeen:' + (window.APP?.user?.id ?? ''),
    keys: [],        // kunci semua notifikasi yang sedang tampil
    seen() {
        try { return new Set(JSON.parse(localStorage.getItem(this.storeKey) || '[]')); } catch { return new Set(); }
    },
    markAllRead() {
        try { localStorage.setItem(this.storeKey, JSON.stringify(this.keys)); } catch { /* abaikan */ }
        updateNotifBadge();
    },
};
const notifKey = (type, v, i) => type === 'kuota' ? `k${v.id}:${i.s}` : `m${v.id}:${v.berakhir}`;

function updateNotifBadge() {
    const seen = NOTIF.seen();
    const unread = NOTIF.keys.filter(k => !seen.has(k)).length;
    $$('.notif-count').forEach(c => { c.textContent = unread > 99 ? '99+' : unread; c.hidden = !unread; });
    $$('.notif-btn').forEach(b => b.setAttribute('aria-label', unread ? `Notifikasi (${unread} belum dibaca)` : 'Notifikasi'));
    document.title = (unread ? `(${unread}) ` : '') + BASE_TITLE;
}

function renderNotif(vs) {
    const a = alerts(vs);
    const link = id => APP.routes.kelola + '?id=' + id;
    const seen = NOTIF.seen();
    NOTIF.keys = [
        ...a.kuota.map(({ v, i }) => notifKey('kuota', v, i)),
        ...a.masa.map(({ v, i }) => notifKey('masa', v, i)),
    ];

    const section = (key, title, items, sub) => `
        <div class="notif-sec">
            <div class="notif-sec-head"><span class="notif-dot ${key}"></span>${title}<span class="pill">${items.length}</span></div>
            ${items.length ? items.map(({ v, i }) => `
                <a class="notif-item${seen.has(notifKey(key, v, i)) ? '' : ' unread'}" href="${link(v.id)}">
                    <span class="notif-ico ${key} ${i.c}">${NOTIF_ICON[key]}</span>
                    <span class="notif-body"><span class="notif-title">${U.esc(v.klien)}</span><span class="notif-sub">${sub(v, i)}</span></span>
                </a>`).join('') : '<div class="notif-empty">Tidak ada</div>'}
        </div>`;

    const html = `
        <div class="notif-head">
            <strong>Notifikasi</strong>
            <span class="muted small">Kuota ≤ ${APP.notif.sisa} · Masa ≤ ${APP.notif.hari} hari</span>
        </div>
        <div class="notif-list">
            ${section('kuota', 'Kuota', a.kuota, kuotaText)}
            ${section('masa', 'Masa berlaku', a.masa, (v, i) => `${daysText(i.d)} · ${U.fmt(v.berakhir)}`)}
        </div>`;

    $$('.notif-pop').forEach(p => { p.innerHTML = html; });
    updateNotifBadge();
    // Bila popup sedang terbuka saat data baru masuk, langsung anggap terbaca
    if ($$('.notif-pop').some(p => !p.hidden)) NOTIF.markAllRead();
}

// Membuka lonceng = semua notifikasi yang tampil dianggap sudah dibaca → badge turun
// (dipasang langsung di tombol, karena handler menu memanggil stopPropagation)
$$('.notif-btn').forEach(b => b.addEventListener('click', () => NOTIF.markAllRead()));
const STATUS_ORDER = { danger: 0, warn: 1, ok: 2, muted: 3 };

function badge(i) { return `<span class="badge b-${i.c}">${U.esc(i.t)}</span>`; }
function daysText(d) {
    if (d < 0) return 'berakhir ' + Math.abs(d) + ' hari lalu';
    if (d === 0) return 'berakhir hari ini';
    return d + ' hari lagi';
}

/* ---------- Aplikasi ---------- */
const App = {
    csrf: $('meta[name="csrf-token"]').content,
    rev: null,
    vouchers: [],   // ringkasan voucher (tanpa riwayat)
    terbaru: [],    // beberapa catatan pemakaian terbaru
    subs: [],
    timer: null,
    interval: 3000,

    async request(url, { method = 'GET', body } = {}) {
        const opt = {
            method,
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        };
        if (body !== undefined) {
            opt.headers['Content-Type'] = 'application/json';
            opt.body = JSON.stringify(body);
        }
        if (method !== 'GET') opt.headers['X-CSRF-TOKEN'] = this.csrf;

        const res = await fetch(url, opt);
        if (res.status === 401) { location.href = APP.routes.login; throw new Error('Sesi berakhir.'); }
        if (res.status === 419) { throw new Error('Sesi kedaluwarsa. Muat ulang halaman.'); }

        let data = {};
        try { data = await res.json(); } catch (e) { /* respons bukan JSON */ }
        if (!res.ok) {
            const first = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(first || data.message || 'Terjadi kesalahan (' + res.status + ').');
        }
        return data;
    },

    /** Ambil data terbaru; hanya memicu render bila revisi berubah. */
    async sync() {
        const url = APP.routes.state + (this.rev !== null ? '?rev=' + this.rev : '');
        try {
            const d = await this.request(url);
            setLive(true);
            if (d.changed !== false) {
                this.rev = d.rev;
                this.vouchers = d.vouchers;
                this.terbaru = d.terbaru || [];
                renderNotif(this.vouchers);
                this.subs.forEach(fn => fn(this.vouchers));
            }
        } catch (e) {
            setLive(false);
            throw e;
        }
    },

    onData(fn) { this.subs.push(fn); },

    start() {
        const loop = async () => {
            clearTimeout(this.timer);
            let delay = this.interval;
            if (!document.hidden) {
                try { await this.sync(); } catch (e) { delay = this.interval * 3; }
            }
            this.timer = setTimeout(loop, delay);
        };
        document.addEventListener('visibilitychange', () => { if (!document.hidden) loop(); });
        window.addEventListener('online', loop);
        loop();
    },

    /** Kirim perubahan lalu segera sinkronkan. */
    async mutate(url, method, body) {
        const d = await this.request(url, { method, body });
        await this.sync().catch(() => {});
        return d;
    },

    find(id) { return this.vouchers.find(v => v.id === id); },
};

function setLive(ok) {
    $$('.live').forEach(el => {
        el.classList.toggle('on', ok);
        el.classList.toggle('off', !ok);
        $('.live-text', el).textContent = ok ? 'Tersinkron' : 'Terputus';
        el.title = ok ? 'Data diperbarui otomatis' : 'Gagal terhubung ke server, mencoba lagi…';
    });
}

/* ---------- Toast ---------- */
function toast(msg, type = 'ok') {
    const el = document.createElement('div');
    el.className = 'toast' + (type === 'error' ? ' error' : '');
    el.textContent = msg;
    $('#toasts').appendChild(el);
    setTimeout(() => { el.classList.add('hide'); setTimeout(() => el.remove(), 250); }, type === 'error' ? 5000 : 2800);
}

/* ---------- Dialog konfirmasi ---------- */
function confirmDialog({ title = 'Yakin?', message = '', ok = 'Ya, lanjutkan', danger = true } = {}) {
    return new Promise(resolve => {
        const dlg = $('#dlgConfirm'), yes = $('#confirmYes'), no = $('#confirmNo');
        $('#confirmTitle').textContent = title;
        $('#confirmMsg').textContent = message;
        yes.textContent = ok;
        yes.className = 'btn ' + (danger ? 'btn-danger' : 'btn-primary');
        const done = val => { dlg.close(); yes.onclick = no.onclick = dlg.onclose = null; resolve(val); };
        yes.onclick = () => done(true);
        no.onclick = () => done(false);
        dlg.onclose = () => resolve(false);
        dlg.showModal();
        yes.focus();
    });
}

/* ---------- Helper form ---------- */
function formData(form) {
    const o = {};
    new FormData(form).forEach((v, k) => { o[k] = typeof v === 'string' ? v.trim() : v; });
    return o;
}
async function withBusy(btn, fn) {
    if (btn) btn.disabled = true;
    try { return await fn(); } finally { if (btn) btn.disabled = false; }
}

/* ---------- Interaksi umum ---------- */
document.addEventListener('click', e => {
    const open = e.target.closest('[data-open]');
    if (open) { closeMenus(); $('#' + open.dataset.open).showModal(); }
    const close = e.target.closest('[data-close]');
    if (close) close.closest('dialog').close();
});

// Klik backdrop menutup dialog
$$('dialog').forEach(d => d.addEventListener('click', e => { if (e.target === d) d.close(); }));

// Menu akun (ada di sidebar & header HP)
function closeMenus(except) {
    $$('[data-menu-btn]').forEach(b => {
        const pop = b.nextElementSibling;
        if (pop === except) return;
        pop.hidden = true;
        b.setAttribute('aria-expanded', 'false');
    });
}
$$('[data-menu-btn]').forEach(btn => {
    const pop = btn.nextElementSibling;
    btn.addEventListener('click', e => {
        e.stopPropagation();
        closeMenus(pop);
        pop.hidden = !pop.hidden;
        btn.setAttribute('aria-expanded', String(!pop.hidden));
    });
});
document.addEventListener('click', e => { if (!e.target.closest('.menu-pop')) closeMenus(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenus(); });

// Ganti password
/* ---------- Profil: nama & foto ---------- */
(() => {
    const form = $('#formProfile');
    if (!form) return;
    const prev = $('#pfPreview'), file = $('#pfFile'), del = $('#pfRemove'), hapus = $('#pfHapus');
    const original = prev.innerHTML;

    const reset = () => { form.reset(); prev.innerHTML = original; hapus.value = '0'; del.hidden = !prev.querySelector('img'); };
    $('#dlgProfile').addEventListener('close', reset);
    // Juga reset tepat sebelum dialog dibuka (fase capture), agar selalu mulai dari data tersimpan
    document.addEventListener('click', e => { if (e.target.closest('[data-open="dlgProfile"]')) reset(); }, true);

    file.addEventListener('change', () => {
        const f = file.files[0];
        if (!f) return;
        if (f.size > 2 * 1024 * 1024) { toast('Ukuran foto maksimal 2 MB.', 'error'); file.value = ''; return; }
        const img = document.createElement('img');
        img.alt = '';
        img.src = URL.createObjectURL(f);
        prev.replaceChildren(img);
        hapus.value = '0';
        del.hidden = false;
    });

    del.addEventListener('click', () => {
        file.value = '';
        hapus.value = '1';
        prev.textContent = prev.dataset.initial;
        del.hidden = true;
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        await withBusy($('[type=submit]', form), async () => {
            try {
                const res = await fetch(APP.routes.profil, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': App.csrf },
                    body: new FormData(form),
                });
                const d = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(d.errors ? Object.values(d.errors)[0][0] : (d.message || 'Gagal menyimpan profil.'));
                toast(d.message);
                setTimeout(() => location.reload(), 500); // perbarui nama & foto di seluruh halaman
            } catch (err) { toast(err.message, 'error'); }
        });
    });
})();

$('#formPassword')?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target, btn = $('[type=submit]', form);
    await withBusy(btn, async () => {
        try {
            const d = await App.request(APP.routes.password, { method: 'PUT', body: formData(form) });
            form.reset();
            form.closest('dialog').close();
            toast(d.message);
        } catch (err) { toast(err.message, 'error'); }
    });
});
