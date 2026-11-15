@extends('layouts.app')

@section('title', 'Kelola Voucher')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <h1>Kelola voucher</h1>
            <p class="muted">Tambah voucher, catat pemakaian asesi, dan pantau sisa kuota.</p>
        </div>
        <div class="actions">
            <button type="button" class="btn" id="btnExport">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                Ekspor Excel
            </button>
            <button type="button" class="btn btn-primary" id="btnNew">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Voucher baru
            </button>
        </div>
    </div>

    <div class="manage">
        {{-- Daftar voucher --}}
        <aside class="card manage-list">
            <div class="list-tools">
                <div class="search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" id="q" placeholder="Cari…" title="Cari berdasarkan nama klien atau kode" autocomplete="off">
                </div>
                <select id="filters" class="filter-select" aria-label="Filter status">
                    <option value="all" data-label="Semua">Semua</option>
                    <option value="ok" data-label="Aman">Aman</option>
                    <option value="warn" data-label="Perhatian">Perhatian</option>
                    <option value="danger" data-label="Over">Over</option>
                    <option value="muted" data-label="Selesai">Selesai</option>
                </select>
            </div>
            <div id="vlist" class="vlist"><div class="empty">Memuat…</div></div>
        </aside>

        {{-- Detail voucher --}}
        <section class="manage-detail" id="detail">
            <div class="card empty-state" id="detailEmpty">
                <div class="empty-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v12" stroke-dasharray="2 2"/></svg>
                </div>
                <h3>Pilih voucher</h3>
                <p class="muted">Pilih voucher di daftar untuk melihat detail dan mencatat pemakaian, atau buat voucher baru.</p>
            </div>

            <div id="detailBody" hidden>
                <div class="card">
                    <div class="detail-head">
                        <div>
                            <div class="title-row">
                                <h2 id="dKlien"></h2>
                                <span class="badge" id="dBadge"></span>
                            </div>
                            <p class="muted" id="dMeta"></p>
                        </div>
                    </div>

                    <div class="detail-toolbar">
                        <div class="tool-group">
                            <button type="button" class="btn btn-sm btn-soft" id="btnSisa" title="Sesuaikan sisa kuota atau masa berlaku dengan data sebenarnya">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                                Update sisa
                            </button>
                            <a class="btn btn-sm btn-wa" id="btnWa" target="_blank" rel="noopener" title="Kirim info sisa voucher ke klien via WhatsApp">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.4.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2l-.4-.3z"/></svg>
                                Kirim info WA
                            </a>
                        </div>
                        <div class="tool-group">
                            <button type="button" class="btn btn-sm btn-ghost" id="btnEdit" title="Ubah data voucher">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-ghost" id="btnExportOne" title="Unduh Excel untuk klien ini">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                                Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-ghost-danger" id="btnDelete" title="Hapus voucher">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                Hapus
                            </button>
                        </div>
                    </div>

                    <div class="mini-stats">
                        <div><span>Kuota</span><strong id="dKuota"></strong></div>
                        <div><span>Terpakai</span><strong id="dUsed"></strong></div>
                        <div><span>Sisa</span><strong id="dSisa"></strong></div>
                        <div><span>Sisa waktu</span><strong id="dDays"></strong></div>
                    </div>
                    <div class="progress lg"><i id="dBar"></i></div>
                    <p class="note" id="dCatatan" hidden></p>
                </div>

                <div class="card">
                    <div class="card-head"><h2>Catat pemakaian</h2></div>
                    <form id="formUse" class="form-grid">
                        <div class="field">
                            <label for="u_tgl">Tanggal pakai</label>
                            <input type="date" id="u_tgl" name="tanggal" required>
                        </div>
                        <div class="field">
                            <label for="u_jml">Jumlah asesi</label>
                            <input type="number" id="u_jml" name="jumlah" min="1" inputmode="numeric" placeholder="0" required>
                        </div>
                        <div class="field span-2">
                            <label for="u_ket">Keterangan <span class="hint">opsional · mis. skema, lokasi TUK</span></label>
                            <input id="u_ket" name="keterangan" maxlength="255">
                        </div>
                        <div class="field field-btn">
                            <button type="submit" class="btn btn-primary">Tambah</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-head">
                        <h2>Riwayat pemakaian</h2>
                        <span class="muted small" id="dLogCount"></span>
                    </div>
                    <div class="table-wrap logs-wrap">
                        <table class="table table-logs">
                            <thead>
                            <tr>
                                <th class="c">No</th>
                                <th>Tanggal</th>
                                <th class="c">Asesi</th>
                                <th class="c">Sisa</th>
                                <th>Keterangan</th>
                                <th>Dicatat oleh</th>
                                <th class="c">Aksi</th>
                            </tr>
                            </thead>
                            <tbody id="dLogs"></tbody>
                        </table>
                    </div>
                    <p class="muted small legend"><span class="legend-swatch"></span> Baris merah = melewati kuota.</p>
                </div>
            </div>
        </section>
    </div>

    {{-- Dialog update sisa (2 kategori: kuota & masa berlaku) --}}
    <dialog class="dialog" id="dlgSisa">
        <form class="dialog-body" id="formSisa">
            <div class="dialog-head">
                <div>
                    <h3>Update sisa voucher</h3>
                    <p class="muted small" id="sKlien"></p>
                </div>
                <button type="button" class="icon-btn" data-close aria-label="Tutup">&times;</button>
            </div>

            <div class="segmented" role="tablist" aria-label="Kategori">
                <button type="button" class="seg" data-kat="kuota" role="tab">Sisa kuota</button>
                <button type="button" class="seg" data-kat="masa" role="tab">Masa berlaku</button>
            </div>

            {{-- Kuota --}}
            <div class="sisa-panel" data-panel="kuota">
                <div class="sisa-now">
                    <div><span>Sisa saat ini</span><strong id="sNowKuota"></strong></div>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    <div class="field">
                        <label for="s_sisa">Sisa yang benar</label>
                        <input type="number" id="s_sisa" min="0" inputmode="numeric">
                    </div>
                </div>
                <p class="sisa-preview" id="sPrevKuota"></p>
            </div>

            {{-- Masa berlaku --}}
            <div class="sisa-panel" data-panel="masa" hidden>
                <div class="sisa-now">
                    <div><span>Berlaku s/d saat ini</span><strong id="sNowMasa"></strong></div>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    <div class="field">
                        <label for="s_berakhir">Tanggal berakhir baru</label>
                        <input type="date" id="s_berakhir">
                    </div>
                </div>
                <div class="quick" id="sQuick">
                    <span class="muted small">Cepat:</span>
                    <button type="button" class="chip" data-add="-30">−30 hari</button>
                    <button type="button" class="chip" data-add="30">+30 hari</button>
                    <button type="button" class="chip" data-add="90">+3 bulan</button>
                    <button type="button" class="chip" data-today>Berakhir hari ini</button>
                </div>
                <p class="sisa-preview" id="sPrevMasa"></p>
            </div>

            <div class="field" id="sKetWrap">
                <label for="s_ket">Alasan / keterangan <span class="hint">opsional, tercatat di riwayat</span></label>
                <input id="s_ket" maxlength="200" placeholder="mis. cocokkan dengan data LSP">
            </div>

            <div class="dialog-foot">
                <button type="button" class="btn" data-close>Batal</button>
                <button type="submit" class="btn btn-primary" id="sSubmit">Simpan</button>
            </div>
        </form>
    </dialog>

    {{-- Dialog ekspor Excel --}}
    <dialog class="dialog" id="dlgExport">
        <form class="dialog-body" id="formExport">
            <div class="dialog-head">
                <div>
                    <h3>Ekspor Excel</h3>
                    <p class="muted small">Pilih klien yang ingin diekspor, lalu tentukan format filenya.</p>
                </div>
                <button type="button" class="icon-btn" data-close aria-label="Tutup">&times;</button>
            </div>

            {{-- Langkah 1: klien --}}
            <section class="x-step">
                <div class="x-step-head"><span class="x-num">1</span><span class="x-title">Klien</span></div>
                <div class="segmented" role="radiogroup" aria-label="Cakupan klien">
                    <button type="button" class="seg" data-mode="all" role="radio">Semua klien</button>
                    <button type="button" class="seg" data-mode="pick" role="radio">Pilih klien</button>
                </div>

                <div id="xPickWrap" class="x-pick">
                    <div class="search">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input type="search" id="x_q" placeholder="Cari nama klien atau kode voucher…" autocomplete="off">
                    </div>
                    <div class="picker-bar">
                        <span id="xSelCount" class="muted small"></span>
                        <span class="picker-bar-actions">
                            <button type="button" class="link-btn" id="xSelAll">Pilih semua</button>
                            <button type="button" class="link-btn" id="xSelNone">Kosongkan</button>
                        </span>
                    </div>
                    <div class="picker" id="xList" role="listbox" aria-multiselectable="true" aria-label="Daftar klien"></div>
                </div>

                <div id="xAllInfo" class="x-allinfo"></div>
            </section>

            {{-- Langkah 2: format --}}
            <section class="x-step" id="xFormatStep">
                <div class="x-step-head">
                    <span class="x-num">2</span><span class="x-title">Format file</span>
                    <span class="x-hint" id="xFormatHint">Hanya untuk lebih dari 1 klien</span>
                </div>
                <div class="opt-cards">
                    <label class="opt">
                        <input type="radio" name="format" value="gabung" checked>
                        <span class="opt-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>
                        </span>
                        <span class="opt-text">
                            <strong>Satu file gabungan</strong>
                            <small>Semua klien terpilih dalam 1 file Excel</small>
                        </span>
                    </label>
                    <label class="opt">
                        <input type="radio" name="format" value="pisah">
                        <span class="opt-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V5a2 2 0 0 1 2-2h6l4 4v10a2 2 0 0 1-2 2h-2"/><path d="M4 9a2 2 0 0 1 2-2h5l3 3v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/></svg>
                        </span>
                        <span class="opt-text">
                            <strong>File terpisah per klien</strong>
                            <small>1 file per klien, diunduh dalam 1 ZIP</small>
                        </span>
                    </label>
                </div>
            </section>

            <div class="dialog-foot x-foot">
                <span class="x-summary" id="xInfo"></span>
                <span class="x-foot-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary" id="xSubmit">Unduh</button>
                </span>
            </div>
        </form>
    </dialog>

    {{-- Dialog tambah / edit voucher --}}
    <dialog class="dialog" id="dlgVoucher">
        <form class="dialog-body" id="formVoucher">
            <div class="dialog-head">
                <h3 id="vTitle">Voucher baru</h3>
                <button type="button" class="icon-btn" data-close aria-label="Tutup">&times;</button>
            </div>
            <div class="form-grid cols-2">
                <div class="field span-2">
                    <label for="v_klien">Nama klien</label>
                    <input id="v_klien" name="klien" maxlength="150" required>
                </div>
                <div class="field">
                    <label for="v_kode">Kode voucher <span class="hint">opsional</span></label>
                    <input id="v_kode" name="kode" maxlength="80">
                </div>
                <div class="field">
                    <label for="v_telp">No. WhatsApp <span class="hint">opsional</span></label>
                    <input type="tel" id="v_telp" name="telepon" maxlength="20" inputmode="tel" placeholder="0812-3456-7890" autocomplete="off">
                    <small class="muted small" id="vTelpPrev" hidden></small>
                </div>
                <div class="field">
                    <label for="v_kuota">Kuota asesi</label>
                    <input type="number" id="v_kuota" name="kuota" min="1" inputmode="numeric" placeholder="mis. 50" required>
                </div>
                <div class="field">
                    <label for="v_mulai">Tanggal mulai / beli</label>
                    <input type="date" id="v_mulai" name="mulai" required>
                </div>
                <div class="field">
                    <label for="v_bulan">Masa berlaku (bulan)</label>
                    <input type="number" id="v_bulan" name="bulan" min="1" max="120" inputmode="numeric" required>
                </div>
                <div class="field">
                    <label for="v_berakhir">Berlaku sampai <span class="hint">otomatis, bisa diubah</span></label>
                    <input type="date" id="v_berakhir" name="berakhir" required>
                </div>
                <div class="field span-2">
                    <label for="v_catatan">Catatan <span class="hint">opsional</span></label>
                    <textarea id="v_catatan" name="catatan" rows="2" maxlength="1000"></textarea>
                </div>
            </div>
            <div class="dialog-foot">
                <button type="button" class="btn" data-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" defer></script>
    <script src="{{ asset('js/kelola.js') }}?v={{ filemtime(public_path('js/kelola.js')) }}"></script>
@endpush
