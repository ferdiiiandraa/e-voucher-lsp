/* Halaman Beranda — ringkasan */
'use strict';

const ICON = {
    alert: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>',
    clock: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    user: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>',
};

const link = id => APP.routes.kelola + '?id=' + id;

function renderStats(vs) {
    const items = vs.map(v => ({ v, i: info(v) }));
    const active = items.filter(x => x.i.c !== 'muted');
    const kuota = active.reduce((a, x) => a + x.v.kuota, 0);
    const terpakai = active.reduce((a, x) => a + x.i.u, 0);
    const sisa = active.reduce((a, x) => a + Math.max(x.i.s, 0), 0);
    const pct = kuota ? Math.round(terpakai / kuota * 100) : 0;
    const barCls = pct > 100 ? 'danger' : pct >= 80 ? 'warn' : '';

    $('#stats').innerHTML = `
        <div class="stat">
            <span class="stat-label">Voucher aktif</span>
            <strong class="stat-value">${U.num(active.length)}</strong>
            <span class="stat-sub">dari ${U.num(vs.length)} voucher tercatat</span>
        </div>
        <div class="stat">
            <span class="stat-label">Total kuota</span>
            <strong class="stat-value">${U.num(kuota)}</strong>
            <span class="stat-sub">asesi dari voucher aktif</span>
        </div>
        <div class="stat">
            <span class="stat-label">Terpakai</span>
            <strong class="stat-value">${U.num(terpakai)}</strong>
            <span class="stat-sub">${pct}% dari kuota</span>
            <div class="progress"><i class="${barCls}" style="width:${Math.min(pct, 100)}%"></i></div>
        </div>
        <div class="stat">
            <span class="stat-label">Sisa kuota</span>
            <strong class="stat-value">${U.num(sisa)}</strong>
            <span class="stat-sub">asesi masih tersedia</span>
        </div>`;
}

let attTab = 'kuota';

function renderAttention(vs) {
    const a = alerts(vs);
    [['#cntKuota', a.kuota], ['#cntMasa', a.masa]].forEach(([sel, arr]) => {
        $(sel).textContent = arr.length;
        $(sel).classList.toggle('warn', arr.length > 0);
    });
    $$('#attTabs .tab-sm').forEach(b => {
        const on = b.dataset.t === attTab;
        b.classList.toggle('active', on);
        b.setAttribute('aria-selected', String(on));
    });

    const isKuota = attTab === 'kuota';
    $('#attRule').textContent = isKuota
        ? `Voucher dengan sisa ${APP.notif.sisa} asesi atau kurang.`
        : `Voucher yang berakhir dalam ${APP.notif.hari} hari atau kurang.`;

    const items = isKuota ? a.kuota : a.masa;
    $('#attention').innerHTML = items.length ? items.map(({ v, i }) => {
        const danger = isKuota ? i.u > v.kuota || i.s <= Math.ceil(APP.notif.sisa / 5) : i.d <= 7;
        const sub = isKuota ? `${kuotaText(v, i)} · ${daysText(i.d)}` : `Berakhir ${U.fmt(v.berakhir)} · sisa ${U.num(i.s)} asesi`;
        const end = isKuota
            ? `<strong>${i.u > v.kuota ? '+' + U.num(i.u - v.kuota) : U.num(i.s)}</strong>${i.u > v.kuota ? 'lebih' : 'sisa'}`
            : `<strong>${i.d}</strong>hari lagi`;
        return `<a class="list-item" href="${link(v.id)}">
            <span class="li-icon ${danger ? 'danger' : 'warn'}">${isKuota ? ICON.alert : ICON.clock}</span>
            <span class="li-body"><span class="li-title">${U.esc(v.klien)}</span><span class="li-sub">${sub}</span></span>
            <span class="li-end">${end}</span>
        </a>`;
    }).join('') : `<div class="empty">${isKuota ? 'Tidak ada voucher dengan kuota menipis 👍' : 'Tidak ada voucher yang segera berakhir 👍'}</div>`;
}

$('#attTabs').addEventListener('click', e => {
    const b = e.target.closest('.tab-sm');
    if (!b) return;
    attTab = b.dataset.t;
    renderAttention(App.vouchers);
});

function renderActivity() {
    const logs = App.terbaru
        .map(l => ({ l, v: App.find(l.voucher_id) }))
        .filter(x => x.v);

    $('#activity').innerHTML = logs.length ? logs.slice(0, 7).map(({ v, l }) => `
        <a class="list-item" href="${link(v.id)}">
            <span class="li-icon">${ICON.user}</span>
            <span class="li-body">
                <span class="li-title">${U.esc(v.klien)}</span>
                <span class="li-sub">${U.fmt(l.tanggal)}${l.keterangan ? ' · ' + U.esc(l.keterangan) : ''}${l.oleh ? ' · oleh ' + U.esc(l.oleh) : ''}</span>
            </span>
            <span class="li-end"><strong>${l.jenis === 'koreksi' ? 'Koreksi ' : ''}${l.jumlah < 0 ? '−' + U.num(-l.jumlah) : (l.jenis === 'koreksi' ? '' : '+') + U.num(l.jumlah)}</strong>${U.ago(l.dibuat)}</span>
        </a>`).join('') : '<div class="empty">Belum ada pemakaian tercatat.</div>';
}

function renderOverview(vs) {
    const items = vs.map(v => ({ v, i: info(v) }))
        .sort((a, b) => STATUS_ORDER[a.i.c] - STATUS_ORDER[b.i.c] || a.v.berakhir.localeCompare(b.v.berakhir));

    $('#overview').innerHTML = items.length ? items.map(({ v, i }) => {
        const w = Math.min(100, i.p * 100);
        const cls = i.c === 'ok' ? '' : i.c;
        return `<tr class="clickable" onclick="location.href='${link(v.id)}'">
            <td><div class="cell-title">${U.esc(v.klien)}</div><div class="cell-sub">${U.esc(v.kode || '—')}</div></td>
            <td><div class="usage">
                <div class="usage-top"><span><b>${U.num(i.u)}</b> / ${U.num(v.kuota)}</span><span>${Math.round(i.p * 100)}%</span></div>
                <div class="progress"><i class="${cls}" style="width:${w}%"></i></div>
            </div></td>
            <td><b>${U.num(Math.max(i.s, 0))}</b></td>
            <td><div>${U.fmt(v.berakhir)}</div><div class="cell-sub">${daysText(i.d)}</div></td>
            <td>${badge(i)}</td>
        </tr>`;
    }).join('') : `<tr><td colspan="5" class="empty">Belum ada voucher. <a href="${APP.routes.kelola}">Tambah voucher pertama →</a></td></tr>`;
}

$('#todayLabel').textContent = U.fmtLong(new Date());

App.onData(vs => {
    renderStats(vs);
    renderAttention(vs);
    renderActivity();
    renderOverview(vs);
});
App.start();
