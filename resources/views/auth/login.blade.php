<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Masuk · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) ?: 1 }}">
</head>
<body class="lg-body">
@php
    // Logo NAS: kotak biru membulat dengan centang putih
    $nas = fn ($size, $id) => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 64 64" aria-hidden="true">'
        .'<defs><linearGradient id="'.$id.'" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#5aa0ff"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>'
        .'<rect x="2" y="2" width="60" height="60" rx="16" fill="url(#'.$id.')"/>'
        .'<path class="nas-check" d="M17 33l10 10 21-24" fill="none" stroke="#fff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>'
        .'</svg>';
@endphp

<div class="lg-bg" aria-hidden="true"></div>

<div class="lg-page">
    <header class="lg-top">
        <span class="lg-brand">
            <span class="lg-brand-mark">{!! $nas(26, 'nasA') !!}</span>
            NAS <small>E-Voucher</small>
        </span>    </header>

    <main class="lg-center">
        <div class="lg-card-wrap"><div class="lg-card" id="lgCard">
            <span class="lg-shine" aria-hidden="true"></span>
            <span class="lg-logo">{!! $nas(48, 'nasB') !!}</span>
            <h1>Masuk</h1>
            <p class="lg-sub">Silakan masuk ke akun Anda.</p>

            <form method="POST" action="{{ url('/login') }}" id="loginForm">
                @csrf
                @if($errors->any())
                    <div class="lg-alert" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="lg-field">
                    <label for="username">Username</label>
                    <div class="lg-input">
                        <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" placeholder="Masukkan username" autofocus required>
                    </div>
                </div>

                <div class="lg-field">
                    <label for="password">Password</label>
                    <div class="lg-input">
                        <input id="password" type="password" name="password" autocomplete="current-password" placeholder="Masukkan password" required>
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Tampilkan password" aria-pressed="false">
                            <svg class="eye-on" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.9 17.9A10.4 10.4 0 0 1 12 19c-6.5 0-10-7-10-7a18 18 0 0 1 4.1-5M9.9 5.2A9.6 9.6 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-2.2 3.2M14.1 14.1a3 3 0 1 1-4.2-4.2M3 3l18 18"/></svg>
                        </button>
                    </div>
                </div>

                <div class="lg-row">
                    <label class="lg-check">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya
                    </label>
                    <span class="lg-hint">Lupa password? Hubungi admin</span>
                </div>

                <button type="submit" class="lg-btn" id="loginBtn">
                    <span class="btn-label">Masuk</span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
            </form>
        </div></div>
    </main>

    <p class="lg-foot">&copy; {{ date('Y') }} NAS · E-Voucher</p>
</div>

<script>
(() => {
    const pw = document.getElementById('password');
    const t = document.getElementById('pwToggle');
    t.addEventListener('click', () => {
        const show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        t.setAttribute('aria-pressed', show);
        t.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        pw.focus();
    });

    const form = document.getElementById('loginForm');
    const btn = document.getElementById('loginBtn');
    form.addEventListener('submit', () => {
        btn.classList.add('is-loading');
        btn.disabled = true;
    });
    // Saat kembali lewat tombol Back browser, tombol jangan tetap "loading"
    window.addEventListener('pageshow', () => { btn.classList.remove('is-loading'); btn.disabled = false; });

    // Kartu sedikit miring mengikuti mouse + pantulan cahaya (hanya perangkat dengan mouse)
    const card = document.getElementById('lgCard');
    if (matchMedia('(pointer: fine)').matches && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
        let raf = 0, tx = 0, ty = 0, x = 0, y = 0;
        const tick = () => {
            x += (tx - x) * 0.1; y += (ty - y) * 0.1;
            card.style.setProperty('--ry', (x * 4).toFixed(2) + 'deg');
            card.style.setProperty('--rx', (-y * 4).toFixed(2) + 'deg');
            card.style.setProperty('--mx', (50 + x * 50).toFixed(1) + '%');
            card.style.setProperty('--my', (50 + y * 50).toFixed(1) + '%');
            raf = (Math.abs(tx - x) > 0.001 || Math.abs(ty - y) > 0.001) ? requestAnimationFrame(tick) : 0;
        };
        const go = () => { if (!raf) raf = requestAnimationFrame(tick); };
        window.addEventListener('pointermove', e => {
            const r = card.getBoundingClientRect();
            tx = Math.max(-1, Math.min(1, (e.clientX - (r.left + r.width / 2)) / (r.width / 2) / 1.6));
            ty = Math.max(-1, Math.min(1, (e.clientY - (r.top + r.height / 2)) / (r.height / 2) / 1.6));
            card.classList.add('is-lit');
            go();
        });
        document.addEventListener('pointerleave', () => { tx = 0; ty = 0; card.classList.remove('is-lit'); go(); });
    }
})();
</script>
</body>
</html>
