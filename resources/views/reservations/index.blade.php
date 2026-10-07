@extends('layouts.app')

@section('title', 'Rezervasyonlar')

@php
    $statusBadge = ['confirmed' => ['Onaylandı', 'success'], 'pending' => ['Beklemede', 'warning'], 'cancelled' => ['İptal', 'secondary']];
    $money = fn ($amount) => number_format($amount, 0, ',', '.').' TL';
@endphp

@section('content')
<main class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Rezervasyonlar</h1>
        <p class="text-secondary mb-0">Yapay zekâ asistanının sohbet üzerinden aldığı rezervasyonlar. Detay ve sohbetin tamamı için bir kayda tıklayın.</p>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['bi-journal-check', 'Toplam rezervasyon', $stats->count],
            ['bi-cash-stack', 'Toplam tutar', $money($stats->revenue)],
            ['bi-moon-stars', 'Toplam gece', $stats->nights],
        ] as [$icon, $label, $value])
            <div class="col-12 col-sm-4">
                <div class="card card-soft h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar bg-brand-gradient text-white fs-5" style="width: 48px; height: 48px;"><i class="bi {{ $icon }}"></i></span>
                        <div>
                            <div class="small text-secondary">{{ $label }}</div>
                            <div class="fs-4 fw-bold">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card card-soft overflow-hidden">
        @if ($reservations->isEmpty())
            <div class="card-body text-center py-5">
                <span class="avatar bg-brand-gradient text-white fs-3 mb-3" style="width: 64px; height: 64px;"><i class="bi bi-inbox"></i></span>
                <h2 class="h5">Henüz rezervasyon yok</h2>
                <p class="text-secondary">Asistanla konuşarak ilk rezervasyonu oluşturabilirsiniz.</p>
                <a href="/" class="btn btn-primary rounded-pill px-4"><i class="bi bi-chat-dots"></i> Asistanla rezervasyon yap</a>
            </div>
        @else
            {{-- Mobil: kart listesi --}}
            <div class="list-group list-group-flush d-md-none">
                @foreach ($reservations as $r)
                    @php([$label, $color] = $statusBadge[$r->status] ?? [$r->status, 'secondary'])
                    <a href="{{ route('reservations.show', $r) }}" class="list-group-item list-group-item-action p-3">
                        <div class="d-flex gap-3">
                            <span class="avatar bg-primary-subtle text-primary">{{ $r->initials() }}</span>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong class="text-truncate">{{ $r->unit->hotel->name }}</strong>
                                    <span class="badge rounded-pill bg-{{ $color }}-subtle text-{{ $color }}-emphasis">{{ $label }}</span>
                                </div>
                                <div class="small text-secondary">{{ $r->unit->hotel->city->name }} · {{ $r->unit->name }}</div>
                                <div class="small mt-1"><i class="bi bi-calendar3"></i> {{ $r->check_in->locale('tr')->translatedFormat('j M') }} – {{ $r->check_out->locale('tr')->translatedFormat('j M Y') }} · {{ $r->nights() }} gece</div>
                                <div class="d-flex justify-content-between align-items-end mt-1">
                                    <span class="small text-secondary">{{ $r->guest_name }} · {{ $r->guest_phone }}</span>
                                    <span class="fw-bold text-gradient">{{ $money($r->total_price) }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Tablet / masaüstü: tablo --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead>
                        <tr class="small text-secondary text-uppercase">
                            <th class="ps-4 fw-semibold">Misafir</th>
                            <th class="fw-semibold">Otel</th>
                            <th class="fw-semibold">Tarih</th>
                            <th class="fw-semibold text-center">Gece / Kişi</th>
                            <th class="fw-semibold text-end">Tutar</th>
                            <th class="fw-semibold pe-4">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reservations as $r)
                            @php([$label, $color] = $statusBadge[$r->status] ?? [$r->status, 'secondary'])
                            <tr role="button" onclick="location.href='{{ route('reservations.show', $r) }}'">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar bg-primary-subtle text-primary">{{ $r->initials() }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $r->guest_name }}</div>
                                            <div class="small text-secondary">{{ $r->guest_phone }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $r->unit->hotel->name }}</div>
                                    <div class="small text-secondary"><i class="bi bi-geo-alt"></i> {{ $r->unit->hotel->city->name }} · {{ $r->unit->name }}{{ $r->quantity > 1 ? ' × '.$r->quantity : '' }}</div>
                                </td>
                                <td>
                                    <div>{{ $r->check_in->locale('tr')->translatedFormat('j M') }} – {{ $r->check_out->locale('tr')->translatedFormat('j M Y') }}</div>
                                    <div class="small text-secondary">#{{ $r->id }} · {{ $r->created_at->locale('tr')->translatedFormat('j M H:i') }}</div>
                                </td>
                                <td class="text-center">{{ $r->nights() }} / {{ $r->guests }}</td>
                                <td class="text-end fw-bold">{{ $money($r->total_price) }}</td>
                                <td class="pe-4"><span class="badge rounded-pill bg-{{ $color }}-subtle text-{{ $color }}-emphasis">{{ $label }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($reservations->hasPages())
        <div class="mt-3">{{ $reservations->links('pagination::bootstrap-5') }}</div>
    @endif
</main>
@endsection
