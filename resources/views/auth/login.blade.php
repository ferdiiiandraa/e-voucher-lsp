<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="auth-body">
<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand-mark lg">E</span>
            <h1>E-Voucher</h1>
            <p class="muted">Masuk untuk memantau dan mengelola voucher.</p>
        </div>

        <form method="POST" action="{{ url('/login') }}" class="stack">
            @csrf
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" autofocus required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
            </div>
            <label class="check">
                <input type="checkbox" name="remember" value="1"> Ingat saya
            </label>
            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
        </form>
    </div>
    <p class="auth-foot muted">&copy; {{ date('Y') }} E-Voucher · Monitor Voucher Asesi LSP</p>
</main>
</body>
</html>
