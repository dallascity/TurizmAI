@extends('layouts.app')

@section('title', 'Rezervasyon #'.$reservation->id)

@php
    $hotel = $reservation->unit->hotel;
    [$statusLabel, $statusColor] = ['confirmed' => ['Onaylandı', 'success'], 'pending' => ['Beklemede', 'warning'], 'cancelled' => ['İptal', 'secondary']][$reservation->status] ?? [$reservation->status, 'secondary'];
    $details = [
        ['bi-door-open', 'Oda / Salon', $reservation->unit->name.($reservation->quantity > 1 ? ' × '.$reservation->quantity : '')],
        ['bi-box-arrow-in-right', 'Giriş', $reservation->check_in->locale('tr')->translatedFormat('j F Y, l')],
        ['bi-box-arrow-right', 'Çıkış', $reservation->check_out->locale('tr')->translatedFormat('j F Y, l')],
        ['bi-moon-stars', 'Süre', $reservation->nights().' gece'],
        ['bi-people', 'Kişi', $reservation->guests.' kişi'],
        ['bi-clock-history', 'Oluşturulma', $reservation->created_at->locale('tr')->translatedFormat('j F Y H:i')],
    ];
@endphp

@section('content')
<main class="container py-4">
    <a href="{{ route('reservations.index') }}" class="btn btn-link px-0 mb-3 text-decoration-none"><i class="bi bi-arrow-left"></i> Tüm rezervasyonlar</a>

    <div class="bg-brand-gradient text-white rounded-4 p-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="small opacity-75 mb-1">Rezervasyon #{{ $reservation->id }} · <span class="badge rounded-pill bg-white text-{{ $statusColor }}">{{ $statusLabel }}</span></div>
            <h1 class="h3 fw-bold mb-1">{{ $hotel->name }} <span class="text-warning fs-6">{{ str_repeat('★', $hotel->stars) }}</span></h1>
            <div class="opacity-75"><i class="bi bi-geo-alt"></i> {{ $hotel->address }}, {{ $hotel->city->country }}</div>
        </div>
        <div class="text-md-end">
            <div class="small opacity-75">Toplam tutar</div>
            <div class="display-6 fw-bold">{{ number_format($reservation->total_price, 0, ',', '.') }} TL</div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Konaklama</h2>
                    @foreach ($details as [$icon, $label, $value])
                        <div class="d-flex align-items-center gap-3 {{ $loop->last ? '' : 'mb-3' }}">
                            <span class="avatar bg-primary-subtle text-primary"><i class="bi {{ $icon }}"></i></span>
                            <div>
                                <div class="small text-secondary">{{ $label }}</div>
                                <div class="fw-semibold">{{ $value }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Misafir</h2>
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar bg-brand-gradient text-white" style="width: 48px; height: 48px;">{{ $reservation->initials() }}</span>
                        <div>
                            <div class="fw-semibold">{{ $reservation->guest_name }}</div>
                            @if ($reservation->guest_phone)
                                <a href="tel:{{ $reservation->guest_phone }}" class="small text-decoration-none"><i class="bi bi-telephone"></i> {{ $reservation->guest_phone }}</a>
                            @endif
                            @if ($reservation->guest_email)
                                <div><a href="mailto:{{ $reservation->guest_email }}" class="small text-decoration-none"><i class="bi bi-envelope"></i> {{ $reservation->guest_email }}</a></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-1"><i class="bi bi-chat-dots"></i> Asistanla yapılan sohbet</h2>
                    <p class="small text-secondary mb-4">Bu rezervasyonu oluşturan konuşmanın tamamı</p>
                    <div class="d-flex flex-column gap-3 p-3 rounded-4" style="background: var(--bs-body-bg);">
                        @forelse ($reservation->chatSession->messages as $message)
                            @include('partials.bubble', ['role' => $message->role, 'content' => $message->content, 'time' => $message->created_at->format('H:i')])
                        @empty
                            <p class="text-secondary mb-0">Sohbet kaydı bulunamadı.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
