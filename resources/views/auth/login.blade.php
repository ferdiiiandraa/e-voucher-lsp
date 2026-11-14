<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: 1 }}">
</head>
<body class="auth-body">
@php
    $ic = fn ($p) => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$p.'</svg>';

    // Logo NAS: kotak biru membulat dengan centang putih
    $nas = fn ($size = 44, $id = 'n') => '<svg class="nas-logo" width="'.$size.'" height="'.$size.'" viewBox="0 0 64 64" aria-hidden="true">'
        .'<defs><linearGradient id="'.$id.'g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#5aa0ff"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>'
        .'<rect x="2" y="2" width="60" height="60" rx="16" fill="url(#'.$id.'g)"/>'
        .'<path d="M17 33l10 10 21-24" fill="none" stroke="#0b2a7a" stroke-opacity=".25" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" transform="translate(1.5 2)"/>'
        .'<path class="nas-check" d="M17 33l10 10 21-24" fill="none" stroke="#fff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>'
        .'</svg>';

    // Ikon centang yang mengambang di latar: [posisi kiri %, ukuran px, durasi s, jeda s]
    $floaters = [[6, 34, 18, 0], [18, 22, 14, 4], [30, 46, 22, 9], [44, 26, 16, 2], [58, 38, 20, 12], [70, 20, 13, 6], [82, 42, 24, 1], [92, 28, 17, 8]];
    $checkTile = '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="2" y="2" width="60" height="60" rx="16" fill="currentColor"/><path d="M17 33l10 10 21-24" fill="none" stroke="var(--tick, #fff)" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $floatHtml = collect($floaters)->map(fn ($f) => sprintf(
        '<span class="floater" style="left:%d%%;width:%dpx;height:%dpx;animation-duration:%ds;animation-delay:-%ds">%s</span>',
        $f[0], $f[1], $f[1], $f[2], $f[3], $checkTile
    ))->implode('');
@endphp

{{-- Latar animasi (sisi form & layar HP): aurora + centang NAS mengambang --}}
<div class="auth-bg" aria-hidden="true">
    <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
    {!! $floatHtml !!}
</div>

<div class="auth-split">

    {{-- Panel brand --}}
    <aside class="auth-hero" aria-hidden="true">
        <div class="hero-anim">
            <span class="blob b1"></span><span class="blob b2"></span>
            {!! $floatHtml !!}
        </div>
        <div class="auth-watermark">{!! $nas(420, 'w') !!}</div>

        <div class="auth-hero-top">
            <span class="auth-hero-mark">{!! $nas(46, 'h') !!}</span>
            <span class="auth-hero-name">NAS <small>E-Voucher</small></span>
        </div>

        <div class="auth-hero-body">
            <h2>Every voucher,<br><span class="grad-text">under control.</span></h2>
            <p>Quota, validity, and usage. One dashboard.</p>

            {{-- Ilustrasi tiket kupon bergaya garis (SVG, tanpa gambar): 2 lapis + parallax --}}
            <div class="coupon-stage" id="couponStage">
                <div class="c-layer c-back"><div class="c-float">
                    <svg class="coupon coupon-ghost" viewBox="0 0 440 180" aria-hidden="true">
                        <path d="M16 2H308A12 12 0 0 0 332 2H424A14 14 0 0 1 438 16V164A14 14 0 0 1 424 178H332A12 12 0 0 0 308 178H16A14 14 0 0 1 2 164V16A14 14 0 0 1 16 2Z"/>
                        <line x1="320" y1="22" x2="320" y2="158"/>
                    </svg>
                </div></div>
                <div class="c-layer c-front"><div class="c-float">
            <svg class="coupon" viewBox="0 0 440 180" role="img" aria-label="Voucher">
                <defs>
                    <clipPath id="stubClip"><rect x="336" y="30" width="80" height="120"/></clipPath>
                    <linearGradient id="scanG" x1="0" x2="1">
                        <stop offset="0" stop-color="#fff" stop-opacity="0"/>
                        <stop offset=".5" stop-color="#fff" stop-opacity=".55"/>
                        <stop offset="1" stop-color="#fff" stop-opacity="0"/>
                    </linearGradient>
                </defs>

                {{-- Badan tiket dengan potongan setengah lingkaran di garis sobekan --}}
                <path class="coupon-body" pathLength="1"
                      d="M16 2H308A12 12 0 0 0 332 2H424A14 14 0 0 1 438 16V164A14 14 0 0 1 424 178H332A12 12 0 0 0 308 178H16A14 14 0 0 1 2 164V16A14 14 0 0 1 16 2Z"/>
                <line class="coupon-perf" x1="320" y1="22" x2="320" y2="158"/>

                {{-- Isi bagian utama --}}
                <g class="coupon-text">
                    <text x="30" y="44" class="c-label">E-VOUCHER</text>
                    <text x="30" y="80" class="c-title">Competency</text>
                    <text x="30" y="106" class="c-title">Assessment</text>
                    <line x1="30" y1="128" x2="290" y2="128" class="c-rule"/>
                    <text x="30" y="152" class="c-meta">VALID 3 MONTHS</text>
                    <text x="290" y="152" class="c-meta" text-anchor="end">NO. 0142</text>
                </g>

                {{-- Barcode di potongan tiket + garis pemindai --}}
                <g class="coupon-barcode">
                    @php $bx = 341; @endphp
                    @foreach([3,1,2,1,3,2,1,1,3,1,2,3,1,2,1,3,1,2] as $w)
                        <rect x="{{ $bx }}" y="36" width="{{ $w * 1.5 }}" height="96" rx=".5"/>
                        @php $bx += $w * 1.5 + 1.4; @endphp
                    @endforeach
                    <text x="378" y="150" class="c-meta" text-anchor="middle">2026</text>
                </g>
                <g clip-path="url(#stubClip)"><rect class="coupon-scan" x="336" y="30" width="80" height="10" fill="url(#scanG)"/></g>

                {{-- Cap stempel centang NAS --}}
                <g class="coupon-stamp">
                    <circle cx="250" cy="66" r="34" class="s-ring-outer"/>
                    <circle cx="250" cy="66" r="26" class="s-ring"/>
                    <path d="M238 66l8 8 16-17" class="s-check"/>
                </g>
            </svg>
                </div></div>
            </div>
        </div>

        <p class="auth-hero-foot">&copy; {{ date('Y') }} NAS · E-Voucher</p>
    </aside>

    {{-- Form --}}
    <main class="auth-main">
        <div class="auth-panel">
            <div class="auth-head">
                <span class="auth-mobile-mark">{!! $nas(40, 'm') !!}<span class="nas-word">NAS</span></span>
                <h1>Sign in</h1>
                <p class="muted">Welcome back. Enter your account details.</p>
            </div>

            <form method="POST" action="{{ url('/login') }}" class="stack" id="loginForm">
                @csrf
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <div class="field">
                    <label for="username">Username</label>
                    <div class="input-icon">
                        {!! $ic('<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>') !!}
                        <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" placeholder="Enter your username" autofocus required>
                    </div>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-icon">
                        {!! $ic('<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>') !!}
                        <input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password" aria-pressed="false">
                            <svg class="eye-on" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.9 17.9A10.4 10.4 0 0 1 12 19c-6.5 0-10-7-10-7a18 18 0 0 1 4.1-5M9.9 5.2A9.6 9.6 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-2.2 3.2M14.1 14.1a3 3 0 1 1-4.2-4.2M3 3l18 18"/></svg>
                        </button>
                    </div>
                </div>

                <label class="check">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))> Remember me
                </label>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginBtn">
                    <span class="btn-label">Sign in</span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
            </form>

            <p class="auth-help muted">Forgot your password? Contact your administrator.</p>
        </div>
        <p class="auth-foot muted">&copy; {{ date('Y') }} NAS · E-Voucher</p>
    </main>
</div>

<script>
(() => {
    const pw = document.getElementById('password');
    const t = document.getElementById('pwToggle');
    t.addEventListener('click', () => {
        const show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        t.setAttribute('aria-pressed', show);
        t.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
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

    // Parallax halus: tiket mengikuti arah mouse (hanya perangkat dengan mouse)
    const stage = document.getElementById('couponStage');
    const hero = document.querySelector('.auth-hero');
    if (stage && hero && matchMedia('(pointer: fine)').matches && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
        let tx = 0, ty = 0, x = 0, y = 0, raf = 0;
        const tick = () => {
            x += (tx - x) * 0.08; y += (ty - y) * 0.08;
            stage.style.setProperty('--px', x.toFixed(3));
            stage.style.setProperty('--py', y.toFixed(3));
            raf = (Math.abs(tx - x) > 0.001 || Math.abs(ty - y) > 0.001) ? requestAnimationFrame(tick) : 0;
        };
        const go = () => { if (!raf) raf = requestAnimationFrame(tick); };
        hero.addEventListener('pointermove', e => {
            const r = hero.getBoundingClientRect();
            tx = (e.clientX - r.left) / r.width * 2 - 1;
            ty = (e.clientY - r.top) / r.height * 2 - 1;
            go();
        });
        hero.addEventListener('pointerleave', () => { tx = 0; ty = 0; go(); });
    }
})();
</script>
</body>
</html>
