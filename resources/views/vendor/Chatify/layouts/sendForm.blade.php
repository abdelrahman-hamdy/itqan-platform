<div class="messenger-sendCard">
    <form id="message-form" method="POST" action="{{ route('send.message', ['subdomain' => request()->route('subdomain') ?? (auth()->user()->academy->subdomain ?? 'itqan-academy')]) }}" enctype="multipart/form-data">
        @csrf
        <label><span class="fas fa-plus-circle"></span><input disabled='disabled' type="file" class="upload-attachment" name="file" accept=".{{implode(', .',config('chatify.attachments.allowed_images'))}}, .{{implode(', .',config('chatify.attachments.allowed_files'))}}" /></label>
        <button class="emoji-button"></span><span class="fas fa-smile"></button>
        <textarea readonly='readonly' name="message" class="m-send app-scroll" placeholder="{{ __('اكتب رسالة...') }}"></textarea>
        <button disabled='disabled' class="send-button"><span class="fas fa-paper-plane"></span><span class="send-label">{{ __('إرسال') }}</span></button>
    </form>
</div>
