@extends('layouts.app')

@section('title', 'Otel Asistanı')
@section('body-class', 'd-flex flex-column vh-100')

@php
    $suggestions = [
        ['🏖️', 'Deniz', 'Deniz kenarında sakin bir otel öner'],
        ['🏛️', 'Kültür', 'Kültür turu için hangi şehirleri önerirsin?'],
        ['⛰️', 'Doğa', "Kasımda 2 kişi Kapadokya'ya gitmek istiyoruz"],
        ['💍', 'Organizasyon', "İstanbul'da 150 kişilik düğün salonu arıyorum"],
    ];
@endphp

@push('styles')
<style>
    .hero h1 { font-weight: 800; letter-spacing: -.02em; }
    .suggestion { border: 1px solid #e9edf3; border-radius: 1rem; background: #fff; transition: all .15s; }
    .suggestion:hover { border-color: #0e8f9a; transform: translateY(-2px); box-shadow: var(--soft-shadow); }
    .suggestion .emoji { font-size: 1.5rem; }
    .composer { border-radius: 999px; }
    .composer input:focus { box-shadow: none; }
    .composer .btn-send { width: 44px; height: 44px; border-radius: 50%; border: 0; }
</style>
@endpush

@section('content')
<main class="container flex-grow-1 d-flex flex-column py-3" style="max-width: 900px; min-height: 0;">
    <div class="hero bg-brand-gradient text-white rounded-4 p-3 p-md-4 mb-3 d-flex justify-content-between align-items-center gap-3">
        <div>
            <h1 class="h4 mb-1">Hayalindeki tatili konuşarak bul</h1>
            <p class="mb-0 small opacity-75 d-none d-sm-block">Nereye, ne zaman, kaç kişi… Gerisini yapay zekâ asistanımız halleder.</p>
        </div>
        <div class="d-flex flex-column align-items-end gap-2 flex-shrink-0">
            <span class="badge rounded-pill bg-white bg-opacity-25 fw-normal"><i class="bi bi-stars"></i> {{ $model }}</span>
            <button class="btn btn-light btn-sm rounded-pill" id="reset"><i class="bi bi-arrow-clockwise"></i> Yeni sohbet</button>
        </div>
    </div>

    @unless ($configured)
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle"></i> <strong>Gemini API anahtarı tanımlı değil.</strong>
            <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener" class="alert-link">aistudio.google.com/apikey</a> adresinden ücretsiz anahtar alıp
            proje klasöründeki <code>.env</code> dosyasına <code>GEMINI_API_KEY=...</code> olarak ekleyin ve <code>docker compose up -d</code> ile yeniden başlatın.
        </div>
    @endunless

    <div class="card card-soft flex-grow-1 mb-2" style="min-height: 0;">
        <div class="card-body d-flex flex-column gap-3 overflow-auto p-3" id="chat">
            @forelse ($messages as $msg)
                @include('partials.bubble', ['role' => $msg->role, 'content' => $msg->content])
            @empty
                @include('partials.welcome')
            @endforelse
        </div>
    </div>

    <div id="quick" class="d-flex gap-2 overflow-x-auto pb-2 {{ $messages->isEmpty() ? 'd-none' : '' }}">
        @foreach ($suggestions as [$emoji, $title, $message])
            <button class="btn btn-sm btn-light border rounded-pill flex-shrink-0 text-nowrap" data-message="{{ $message }}" title="{{ $message }}">{{ $emoji }} {{ $title }}</button>
        @endforeach
    </div>

    <form id="form" class="composer card-soft bg-white d-flex align-items-center p-1 ps-3">
        <input id="input" class="form-control border-0 bg-transparent" autocomplete="off" maxlength="1000" autofocus
               placeholder="Mesajınızı yazın…">
        <button id="send" class="btn-send bg-brand-gradient text-white" type="submit" aria-label="Gönder">
            <i class="bi bi-send-fill"></i>
        </button>
    </form>

    {{-- "Yeni sohbet" denince bu içerik tekrar gösterilir --}}
    <template id="welcome">@include('partials.welcome')</template>
</main>
@endsection

@push('scripts')
<script>
    $(function () {
        const $chat = $('#chat'), $input = $('#input'), $send = $('#send');

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        function add(html) {
            const $el = $(html).appendTo($chat);
            $chat.scrollTop($chat[0].scrollHeight);
            return $el;
        }

        $chat.scrollTop($chat[0].scrollHeight);

        function userMessage(text) {
            return $('<div class="message from-user">').append(
                '<span class="avatar bg-white border text-secondary"><i class="bi bi-person"></i></span>',
                $('<div class="bubble">').text(text)
            );
        }

        function send(message) {
            $('#suggestions').remove();
            $('#quick').removeClass('d-none');
            add(userMessage(message));
            const $typing = add('<div class="message from-assistant"><span class="avatar bg-brand-gradient text-white"><i class="bi bi-stars"></i></span>'
                + '<div class="bubble typing"><span></span> <span></span> <span></span></div></div>');
            $send.prop('disabled', true);

            $.post('/chat', { message })
                .done(data => add(data.html))
                .fail(xhr => {
                    if (xhr.status === 419) { // Oturum süresi doldu (CSRF)
                        add('<div class="alert alert-info py-2 mb-0">Oturumunuz yenileniyor, lütfen mesajınızı tekrar gönderin…</div>');
                        return setTimeout(() => location.reload(), 1500);
                    }
                    add('<div class="alert alert-danger py-2 mb-0">⚠️ Bir hata oluştu, lütfen tekrar deneyin. (HTTP ' + xhr.status + ')</div>');
                })
                .always(() => { $typing.remove(); $send.prop('disabled', false); $input.trigger('focus'); });
        }

        $('#form').on('submit', function (e) {
            e.preventDefault();
            const message = $input.val().trim();
            if (message) { $input.val(''); send(message); }
        });

        $(document).on('click', '[data-message]', function () { send($(this).data('message')); });

        $('#reset').on('click', () => {
            $.post('/chat/reset').done(() => {
                $chat.html($('#welcome').html());
                $('#quick').addClass('d-none');
            });
        });
    });
</script>
@endpush
