{{-- Yeni sohbet başlangıcı: karşılama mesajı ve öneri kartları --}}
@include('partials.bubble', ['role' => 'assistant', 'content' => "Merhaba! Ben **TurizmAI** 🌴\n\nSize en uygun oteli bulmak için buradayım. **Nerede ve ne zaman tatil yapmak istersiniz?** Aklınızda bir yer yoksa nasıl bir tatil istediğinizi anlatın, ben önereyim."])

<div class="row g-2 mt-auto flex-nowrap flex-md-wrap overflow-x-auto pb-1" id="suggestions">
    @foreach ($suggestions as [$emoji, $title, $message])
        <div class="col-8 col-sm-5 col-md-3">
            <button class="suggestion w-100 h-100 text-start p-3 d-flex flex-column justify-content-start" data-message="{{ $message }}">
                <div class="emoji">{{ $emoji }}</div>
                <div class="fw-semibold">{{ $title }}</div>
                <div class="small text-secondary">{{ $message }}</div>
            </button>
        </div>
    @endforeach
</div>
