{{-- Sohbet mesajı. $role: user|assistant, $content: markdown, $time: isteğe bağlı saat --}}
<div class="message {{ $role === 'user' ? 'from-user' : 'from-assistant' }}">
    @if ($role === 'user')
        <span class="avatar bg-white border text-secondary"><i class="bi bi-person"></i></span>
    @else
        <span class="avatar bg-brand-gradient text-white"><i class="bi bi-stars"></i></span>
    @endif
    <div class="bubble">
        {!! Str::markdown($content, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
        @isset($time)
            <time>{{ $time }}</time>
        @endisset
    </div>
</div>
