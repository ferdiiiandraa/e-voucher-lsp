@extends('layouts.app')

@section('title', 'Pengguna')

@php
    $me = auth()->user();
    $total = $users->count();
    $aktif = $users->where('is_active', true)->count();
    $nonaktif = $total - $aktif;
    $admin = $users->where('is_admin', true)->count();
    $i = fn ($p) => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$p.'</svg>';
    $ico = [
        'key' => $i('<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4M12 15v2"/>'),
        'off' => $i('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 8 5 5M22 8l-5 5"/>'),
        'on' => $i('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/>'),
        'trash' => $i('<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>'),
    ];
@endphp

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <h1>Pengguna</h1>
            <p class="muted">Atur siapa saja yang bisa masuk dan mengelola voucher.</p>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" data-open="dlgAddUser">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Tambah pengguna
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any() && ! old('_dialog'))
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <section class="stats stats-sm">
        <div class="stat"><span class="stat-label">Total pengguna</span><strong class="stat-value">{{ $total }}</strong></div>
        <div class="stat"><span class="stat-label">Aktif</span><strong class="stat-value t-ok">{{ $aktif }}</strong></div>
        <div class="stat"><span class="stat-label">Nonaktif</span><strong class="stat-value t-muted">{{ $nonaktif }}</strong></div>
        <div class="stat"><span class="stat-label">Admin</span><strong class="stat-value t-info">{{ $admin }}</strong></div>
    </section>

    <section class="card">
        <div class="toolbar">
            <div class="search toolbar-search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" id="uq" placeholder="Cari nama atau username…" autocomplete="off">
            </div>
            <div class="chips" id="ufilter">
                <button type="button" class="chip active" data-f="all">Semua<span class="count">{{ $total }}</span></button>
                <button type="button" class="chip" data-f="1">Aktif<span class="count">{{ $aktif }}</span></button>
                <button type="button" class="chip" data-f="0">Nonaktif<span class="count">{{ $nonaktif }}</span></button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table users-table">
                <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Peran</th>
                    <th>Status</th>
                    <th class="center">Catatan</th>
                    <th class="center">Aksi</th>
                </tr>
                </thead>
                <tbody id="ulist">
                @foreach($users as $u)
                    @php $self = $u->is($me); $n = $catatan[$u->id] ?? 0; @endphp
                    <tr class="{{ $u->is_active ? '' : 'is-inactive' }}"
                        data-active="{{ $u->is_active ? 1 : 0 }}"
                        data-search="{{ strtolower($u->name.' '.$u->username) }}">
                        <td>
                            <div class="user-cell">
                                <span class="user-avatar">{!! $u->avatarHtml() !!}</span>
                                <span>
                                    <span class="cell-title">{{ $u->name }}</span>
                                    @if($self)<span class="you">Anda</span>@endif
                                    <span class="cell-sub">{{ '@'.$u->username }}</span>
                                </span>
                            </div>
                        </td>
                        <td><span class="badge {{ $u->is_admin ? 'b-info' : 'b-muted' }}">{{ $u->is_admin ? 'Admin' : 'Staf' }}</span></td>
                        <td><span class="badge {{ $u->is_active ? 'b-ok' : 'b-muted' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="center muted">{{ $n }}</td>
                        <td class="center">
                            <div class="row-actions user-actions">
                                @if($self)
                                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-open="dlgPassword"
                                            title="Ganti password saya" aria-label="Ganti password saya">{!! $ico['key'] !!}</button>
                                @else
                                    <button type="button" class="btn btn-sm btn-icon btn-ghost"
                                            data-reset="{{ route('pengguna.password', $u) }}" data-name="{{ $u->name }}"
                                            title="Reset password" aria-label="Reset password {{ $u->name }}">{!! $ico['key'] !!}</button>

                                    <form method="POST" action="{{ route('pengguna.status', $u) }}"
                                          data-confirm="{{ $u->is_active ? 'deactivate' : 'activate' }}" data-name="{{ $u->name }}">
                                        @csrf @method('PATCH')
                                        @if($u->is_active)
                                            <button class="btn btn-sm btn-icon btn-ghost-warn" title="Nonaktifkan" aria-label="Nonaktifkan {{ $u->name }}">{!! $ico['off'] !!}</button>
                                        @else
                                            <button class="btn btn-sm btn-icon btn-ghost-ok" title="Aktifkan" aria-label="Aktifkan {{ $u->name }}">{!! $ico['on'] !!}</button>
                                        @endif
                                    </form>

                                    <form method="POST" action="{{ route('pengguna.destroy', $u) }}"
                                          data-confirm="delete" data-name="{{ $u->name }}" data-count="{{ $n }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-icon btn-ghost-danger" title="Hapus pengguna" aria-label="Hapus {{ $u->name }}">{!! $ico['trash'] !!}</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                <tr id="uempty" hidden><td colspan="5" class="empty">Tidak ada pengguna yang cocok.</td></tr>
                </tbody>
            </table>
        </div>

    </section>

    {{-- Dialog tambah pengguna --}}
    <dialog class="dialog" id="dlgAddUser">
        <form method="POST" action="{{ route('pengguna.store') }}" class="dialog-body">
            @csrf
            <input type="hidden" name="_dialog" value="add">
            <div class="dialog-head">
                <h3>Tambah pengguna</h3>
                <button type="button" class="icon-btn" data-close aria-label="Tutup">&times;</button>
            </div>
            @if($errors->any() && old('_dialog') === 'add')
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <div class="form-grid cols-2">
                <div class="field span-2">
                    <label for="n_name">Nama lengkap</label>
                    <input id="n_name" name="name" value="{{ old('name') }}" maxlength="100" required>
                </div>
                <div class="field">
                    <label for="n_user">Username</label>
                    <input id="n_user" name="username" value="{{ old('username') }}" maxlength="50" pattern="[A-Za-z0-9_\-]+" title="Huruf, angka, - atau _" autocomplete="off" required>
                </div>
                <div class="field">
                    <label for="n_pass">Password <span class="hint">min. 8</span></label>
                    <input id="n_pass" type="password" name="password" minlength="8" autocomplete="new-password" required>
                </div>
            </div>
            <label class="role-toggle">
                <input type="checkbox" name="is_admin" value="1" @checked(old('is_admin'))>
                <span>
                    <strong>Jadikan admin</strong>
                    <small>Admin bisa menambah, menonaktifkan, dan menghapus pengguna.</small>
                </span>
            </label>
            <div class="dialog-foot">
                <button type="button" class="btn" data-close>Batal</button>
                <button type="submit" class="btn btn-primary">Tambah pengguna</button>
            </div>
        </form>
    </dialog>

    {{-- Dialog reset password --}}
    <dialog class="dialog dialog-sm" id="dlgReset">
        <form method="POST" class="dialog-body" id="formReset">
            @csrf @method('PUT')
            <div class="dialog-head">
                <h3>Reset password</h3>
                <button type="button" class="icon-btn" data-close aria-label="Tutup">&times;</button>
            </div>
            <p class="muted">Atur password baru untuk <b id="resetName"></b>. Beri tahu password ini kepada yang bersangkutan.</p>
            <div class="field">
                <label for="r_pass">Password baru <span class="hint">min. 8 karakter</span></label>
                <input id="r_pass" type="password" name="password" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="dialog-foot">
                <button type="button" class="btn" data-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan password</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
(() => {
    // Pencarian & filter
    let f = 'all';
    const rows = $$('#ulist tr[data-search]');
    function apply() {
        const q = $('#uq').value.trim().toLowerCase();
        let shown = 0;
        rows.forEach(r => {
            const ok = (f === 'all' || r.dataset.active === f) && (!q || r.dataset.search.includes(q));
            r.hidden = !ok;
            if (ok) shown++;
        });
        $('#uempty').hidden = shown > 0;
    }
    $('#uq').addEventListener('input', apply);
    $('#ufilter').addEventListener('click', e => {
        const c = e.target.closest('.chip');
        if (!c) return;
        f = c.dataset.f;
        $$('#ufilter .chip').forEach(x => x.classList.toggle('active', x === c));
        apply();
    });

    // Reset password
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-reset]');
        if (!b) return;
        const form = $('#formReset');
        form.action = b.dataset.reset;
        form.reset();
        $('#resetName').textContent = b.dataset.name;
        $('#dlgReset').showModal();
        $('#r_pass').focus();
    });

    // Konfirmasi aksi
    const TEXT = {
        deactivate: n => ({
            title: 'Nonaktifkan pengguna?',
            message: `${n} tidak akan bisa login dan langsung dikeluarkan dari sesi yang sedang berjalan. Data dan riwayatnya tetap tersimpan, dan akun bisa diaktifkan lagi kapan saja.`,
            ok: 'Nonaktifkan', danger: true,
        }),
        activate: n => ({
            title: 'Aktifkan kembali?',
            message: `${n} akan bisa login lagi dengan password yang sama.`,
            ok: 'Aktifkan', danger: false,
        }),
        delete: (n, c) => ({
            title: 'Hapus pengguna permanen?',
            message: `Akun ${n} akan dihapus dan tidak bisa dikembalikan.` +
                (Number(c) ? ` ${c} catatan pemakaian yang ia buat tetap tersimpan, tapi nama pencatatnya akan hilang.` : '') +
                ' Jika hanya ingin memblokir akses, pilih Nonaktifkan.',
            ok: 'Hapus permanen', danger: true,
        }),
    };
    $$('form[data-confirm]').forEach(form => form.addEventListener('submit', async e => {
        if (form.dataset.ok) return;
        e.preventDefault();
        const t = TEXT[form.dataset.confirm](form.dataset.name, form.dataset.count);
        if (await confirmDialog(t)) { form.dataset.ok = '1'; form.requestSubmit(); }
    }));

    // Buka kembali dialog tambah bila validasi gagal
    @if($errors->any() && old('_dialog') === 'add')
        $('#dlgAddUser').showModal();
    @endif

    App.start();
})();
</script>
@endpush
