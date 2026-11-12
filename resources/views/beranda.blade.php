@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <p class="eyebrow" id="todayLabel"></p>
            <h1>Halo, {{ auth()->user()->name }}</h1>
            <p class="muted">Ringkasan pemakaian voucher asesi, diperbarui otomatis.</p>
        </div>
        <div class="actions">
            <a href="{{ route('kelola', ['baru' => 1]) }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Voucher baru
            </a>
        </div>
    </div>

    <section class="stats" id="stats">
        @foreach(['Voucher aktif', 'Total kuota', 'Terpakai', 'Sisa kuota'] as $label)
            <div class="stat skeleton"><span class="stat-label">{{ $label }}</span><strong class="stat-value">&nbsp;</strong><span class="stat-sub">&nbsp;</span></div>
        @endforeach
    </section>

    <div class="grid-2">
        <section class="card">
            <div class="card-head">
                <h2>Perlu perhatian</h2>
                <div class="tabs-sm" role="tablist" id="attTabs">
                    <button type="button" class="tab-sm active" data-t="kuota" role="tab" aria-selected="true">Kuota <span class="pill" id="cntKuota">0</span></button>
                    <button type="button" class="tab-sm" data-t="masa" role="tab" aria-selected="false">Masa berlaku <span class="pill" id="cntMasa">0</span></button>
                </div>
            </div>
            <p class="muted small att-rule" id="attRule"></p>
            <div id="attention" class="list"><div class="empty">Memuat…</div></div>
        </section>

        <section class="card">
            <div class="card-head">
                <h2>Aktivitas terbaru</h2>
            </div>
            <div id="activity" class="list"><div class="empty">Memuat…</div></div>
        </section>
    </div>

    <section class="card">
        <div class="card-head">
            <h2>Semua voucher</h2>
            <a href="{{ route('kelola') }}" class="link">Buka halaman kelola →</a>
        </div>
        <div class="table-wrap">
            <table class="table table-overview">
                <thead>
                <tr>
                    <th>Klien</th>
                    <th>Pemakaian</th>
                    <th>Sisa</th>
                    <th>Berlaku s/d</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody id="overview">
                <tr><td colspan="5" class="empty">Memuat…</td></tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/beranda.js') }}?v={{ filemtime(public_path('js/beranda.js')) }}"></script>
@endpush
