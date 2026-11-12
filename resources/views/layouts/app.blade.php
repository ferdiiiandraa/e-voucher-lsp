<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
@php
    $me = auth()->user();
    $avatar = $me->avatarHtml();
    $mi = fn ($p) => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$p.'</svg>';
    $menuIcons = [
        'user' => $mi('<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'),
        'lock' => $mi('<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'),
    ];
    $logoutIcon ='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>';
    $icons = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'ticket' => '<path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v2M14 11v2M14 16v2"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    ];
    $menu = [
        ['route' => 'beranda', 'label' => 'Beranda', 'short' => 'Beranda', 'icon' => 'home'],
        ['route' => 'kelola', 'label' => 'Kelola Voucher', 'short' => 'Kelola', 'icon' => 'ticket'],
    ];
    if ($me->is_admin) {
        $menu[] = ['route' => 'pengguna', 'label' => 'Pengguna', 'short' => 'Pengguna', 'icon' => 'users'];
    }
    $bell = '<button type="button" class="notif-btn" data-menu-btn aria-haspopup="true" aria-expanded="false" aria-label="Notifikasi" title="Notifikasi">'
        .'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>'
        .'<span class="notif-count" hidden>0</span></button>';
    $svg = fn ($name, $size = 20) => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$icons[$name].'</svg>';
@endphp
<body>

{{-- Sidebar (desktop) --}}
<aside class="sidebar" aria-label="Navigasi utama">
    <a href="{{ route('beranda') }}" class="side-brand">
        <span class="brand-mark">E</span>
        <span class="side-brand-text">
            <strong>E-Voucher</strong>
            <small>Monitor voucher asesi</small>
        </span>
    </a>

    <nav class="side-nav">
        <span class="side-label">Menu</span>
        @foreach($menu as $m)
            @php $on = request()->routeIs($m['route']); @endphp
            <a href="{{ route($m['route']) }}" class="side-link {{ $on ? 'active' : '' }}" @if($on) aria-current="page" @endif>
                {!! $svg($m['icon']) !!}
                <span>{{ $m['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="side-foot">
        <span class="live" title="Status sinkronisasi">
            <span class="live-dot"></span><span class="live-text">Menghubungkan…</span>
        </span>
    </div>
</aside>

{{-- Header ringkas (tablet & HP) --}}
<header class="mobile-bar">
    <a href="{{ route('beranda') }}" class="side-brand">
        <span class="brand-mark">E</span>
        <strong>E-Voucher</strong>
    </a>
    <div class="mobile-bar-right">
        <span class="live" title="Status sinkronisasi"><span class="live-dot"></span><span class="live-text">Menghubungkan…</span></span>
        <div class="menu">
            {!! $bell !!}
            <div class="menu-pop notif-pop" hidden></div>
        </div>
        <div class="menu">
            <button type="button" class="avatar" data-menu-btn aria-haspopup="true" aria-expanded="false" aria-label="Menu akun">{!! $avatar !!}</button>
            <div class="menu-pop" hidden>
                <div class="menu-head">
                    <strong>{{ $me->name }}</strong>
                    <span>{{ '@'.$me->username }}{{ $me->is_admin ? ' · Admin' : '' }}</span>
                </div>
                <button type="button" data-open="dlgProfile">Profil saya</button>
                <button type="button" data-open="dlgPassword">Ganti password</button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="danger-text">Keluar</button>
                </form>
            </div>
        </div>
    </div>
</header>

<main class="main">
    {{-- Bar atas (desktop): lonceng notifikasi di kanan atas --}}
    <div class="topbar">
        <div class="menu">
            {!! $bell !!}
            <div class="menu-pop notif-pop" hidden></div>
        </div>
        <span class="topbar-sep" aria-hidden="true"></span>
        <div class="menu">
            <button type="button" class="topbar-user" data-menu-btn aria-haspopup="true" aria-expanded="false" title="Menu akun">
                <span class="avatar">{!! $avatar !!}</span>
                <span class="topbar-user-text">
                    <strong>{{ $me->name }}</strong>
                    <small>{{ $me->is_admin ? 'Admin' : 'Staf' }}</small>
                </span>
                <svg class="topbar-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="menu-pop account-pop" hidden>
                <div class="menu-head">
                    <strong>{{ $me->name }}</strong>
                    <span>{{ '@'.$me->username }}{{ $me->is_admin ? ' · Admin' : '' }}</span>
                </div>
                <button type="button" data-open="dlgProfile">{!! $menuIcons['user'] !!} Profil saya</button>
                <button type="button" data-open="dlgPassword">{!! $menuIcons['lock'] !!} Ganti password</button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="danger-text">{!! $logoutIcon !!} Keluar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="container">
        @yield('content')
    </div>
</main>

{{-- Tab bar bawah (tablet & HP) --}}
<nav class="tabbar" aria-label="Navigasi utama">
    @foreach($menu as $m)
        @php $on = request()->routeIs($m['route']); @endphp
        <a href="{{ route($m['route']) }}" class="tab {{ $on ? 'active' : '' }}" @if($on) aria-current="page" @endif>
            {!! $svg($m['icon'], 22) !!}
            <span>{{ $m['short'] }}</span>
        </a>
    @endforeach
</nav>

{{-- Dialog ganti password --}}
<dialog class="dialog" id="dlgPassword">
    <form class="dialog-body" id="formPassword">
        <div class="dialog-head">
            <h3>Ganti password</h3>
            <button type="button" class="icon-btn" data-close aria-label="Tutup">&times;</button>
        </div>
        <div class="field">
            <label for="pw_old">Password lama</label>
            <input type="password" id="pw_old" name="password_lama" autocomplete="current-password" required>
        </div>
        <div class="field">
            <label for="pw_new">Password baru <span class="hint">min. 8 karakter</span></label>
            <input type="password" id="pw_new" name="password" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="field">
            <label for="pw_new2">Ulangi password baru</label>
            <input type="password" id="pw_new2" name="password_confirmation" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="dialog-foot">
            <button type="button" class="btn" data-close>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</dialog>

{{-- Dialog profil (nama & foto) --}}
<dialog class="dialog dialog-sm" id="dlgProfile">
    <form class="dialog-body" id="formProfile" enctype="multipart/form-data">
        <div class="dialog-head">
            <h3>Profil saya</h3>
            <button type="button" class="icon-btn" data-close aria-label="Tutup">&times;</button>
        </div>
        <div class="profile-photo">
            <span class="avatar avatar-xl" id="pfPreview" data-initial="{{ $me->initial() }}">{!! $avatar !!}</span>
            <div class="profile-photo-actions">
                <label class="btn btn-sm">
                    <input type="file" name="avatar" id="pfFile" accept="image/jpeg,image/png,image/webp" hidden>
                    Pilih foto
                </label>
                <button type="button" class="btn btn-sm btn-ghost-danger" id="pfRemove" @if(! $me->avatar) hidden @endif>Hapus foto</button>
                <small class="muted">JPG, PNG, atau WEBP · maks. 2 MB</small>
            </div>
        </div>
        <input type="hidden" name="hapus_foto" id="pfHapus" value="0">
        <div class="field">
            <label for="pf_name">Nama</label>
            <input id="pf_name" name="name" value="{{ $me->name }}" maxlength="100" required>
        </div>
        <div class="field">
            <label>Username</label>
            <input value="{{ '@'.$me->username }}" disabled>
        </div>
        <div class="dialog-foot">
            <button type="button" class="btn" data-close>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</dialog>

{{-- Dialog konfirmasi umum --}}
<dialog class="dialog dialog-sm" id="dlgConfirm">
    <div class="dialog-body">
        <h3 id="confirmTitle">Yakin?</h3>
        <p id="confirmMsg" class="muted"></p>
        <div class="dialog-foot">
            <button type="button" class="btn" id="confirmNo">Batal</button>
            <button type="button" class="btn btn-danger" id="confirmYes">Ya, lanjutkan</button>
        </div>
    </div>
</dialog>

<div class="toasts" id="toasts" aria-live="polite"></div>

<script>
    window.APP = {
        routes: {
            state: @json(route('data.state')),
            riwayat: @json(route('data.riwayat')),
            vouchers: @json(url('data/vouchers')),
            pemakaian: @json(url('data/pemakaian')),
            password: @json(route('data.akun.password')),
            profil: @json(route('data.akun.profil')),
            kelola: @json(route('kelola')),
            login: @json(route('login')),
        },
        user: @json(auth()->user()->only('id', 'name', 'is_admin')),
        notif: { sisa: {{ (int) config('voucher.notif_sisa') }}, hari: {{ (int) config('voucher.notif_hari') }} },
        waTtd: @json(config('voucher.wa_ttd')),
    };
</script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
