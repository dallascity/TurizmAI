<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Otel Asistanı') — TurizmAI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-gradient: linear-gradient(135deg, #0ea5a4 0%, #2563eb 100%);
            --bs-body-font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            --bs-body-bg: #f6f8fb;
            --bs-primary: #0e8f9a;
            --bs-primary-rgb: 14, 143, 154;
            --bs-link-color: #0e8f9a;
            --bs-link-hover-color: #2563eb;
            --soft-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 8px 24px rgba(16, 24, 40, .06);
        }
        .btn-primary { --bs-btn-bg: #0e8f9a; --bs-btn-border-color: #0e8f9a; --bs-btn-hover-bg: #0b7781; --bs-btn-hover-border-color: #0b7781; --bs-btn-active-bg: #0b7781; }

        /* Yardımcı sınıflar */
        .bg-brand-gradient { background: var(--brand-gradient); }
        .text-gradient { background: var(--brand-gradient); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .card-soft { border: 0; border-radius: 1rem; box-shadow: var(--soft-shadow); }
        .avatar { width: 36px; height: 36px; flex-shrink: 0; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; }

        /* Navbar */
        .navbar-glass { background: rgba(255, 255, 255, .85); backdrop-filter: blur(10px); }
        .navbar-glass .nav-link { border-radius: 999px; padding: .4rem .75rem !important; color: #475467; font-weight: 500; }
        .navbar-glass .nav-link.active { background: rgba(14, 143, 154, .1); color: var(--bs-primary); }

        /* Sohbet baloncukları */
        .message { display: flex; gap: .6rem; align-items: flex-end; max-width: 88%; animation: fade-in .25s ease-out; }
        .message.from-user { align-self: flex-end; flex-direction: row-reverse; }
        .bubble { padding: .7rem 1rem; border-radius: 1.1rem; line-height: 1.55; overflow-wrap: anywhere; }
        .from-assistant .bubble { background: #fff; border: 1px solid #e9edf3; border-bottom-left-radius: .35rem; }
        .from-user .bubble { background: var(--brand-gradient); color: #fff; border-bottom-right-radius: .35rem; }
        .bubble p:last-child, .bubble ul:last-child, .bubble ol:last-child { margin-bottom: 0; }
        .bubble ul, .bubble ol { padding-left: 1.2rem; margin-bottom: .4rem; }
        .bubble time { display: block; font-size: .7rem; opacity: .6; margin-top: .25rem; }
        @media (max-width: 575.98px) { .message { max-width: 96%; } .message .avatar { display: none; } }

        .typing span { width: 7px; height: 7px; border-radius: 50%; background: #98a2b3; display: inline-block; animation: bounce 1.2s infinite; }
        .typing span:nth-child(2) { animation-delay: .15s; }
        .typing span:nth-child(3) { animation-delay: .3s; }
        @keyframes bounce { 0%, 60%, 100% { transform: translateY(0); opacity: .5; } 30% { transform: translateY(-5px); opacity: 1; } }
        @keyframes fade-in { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

        /* Mobil tarayıcılarda adres çubuğu yüksekliği hesaba katılsın */
        .vh-100 { height: 100dvh !important; }
    </style>
    @stack('styles')
</head>
<body class="@yield('body-class')">
    <nav class="navbar navbar-expand navbar-glass border-bottom sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/">
                <span class="avatar bg-brand-gradient text-white fs-6">🌴</span>
                <span class="d-none d-sm-inline">Turizm<span class="text-gradient">AI</span></span>
            </a>
            <div class="navbar-nav ms-auto gap-1">
                <a class="nav-link text-nowrap {{ request()->is('/') ? 'active' : '' }}" href="/"><i class="bi bi-chat-dots"></i> Asistan</a>
                <a class="nav-link text-nowrap {{ request()->is('reservations*') ? 'active' : '' }}" href="{{ route('reservations.index') }}"><i class="bi bi-journal-check"></i> Rezervasyonlar</a>
            </div>
        </div>
    </nav>

    @yield('content')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @stack('scripts')
</body>
</html>
