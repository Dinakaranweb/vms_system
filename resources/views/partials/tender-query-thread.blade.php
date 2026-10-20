{{-- Tender Query / Discussion Thread --}}
{{-- Required variable: $tender (with queries.sender eager loaded) --}}
@php
    $user     = auth()->user();
    $prefix   = $user->hasRole('super-admin') ? 'super-admin'
              : ($user->hasRole('admin') ? 'admin'
              : ($user->hasRole('tender-owner') ? 'tender-owner'
              : ($user->hasRole('management') ? 'management' : 'portal')));
    $queryRoute = route($prefix . '.tenders.queries.store', $tender->id);
    $markReadRoute = route($prefix . '.tenders.queries.mark-read', $tender->id);
    $queries = $tender->relationLoaded('queries') ? $tender->queries : $tender->queries()->with('sender')->orderBy('created_at')->get();
    $isManagement = $user->can('raise tender queries');
@endphp

<div class="mt-8 pt-6 border-t border-gray-200">
    <div class="flex items-center justify-between mb-4">
        <h4 class="font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-comments text-blue-600"></i>
            Management Query / Discussion Thread
            @if($queries->count() > 0)
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">{{ $queries->count() }}</span>
            @endif
        </h4>
    </div>

    {{-- Thread Messages --}}
    @if($queries->count() > 0)
    <div class="space-y-4 mb-6" id="tenderQueryThreadMessages">
        @foreach($queries as $q)
        @php
            $isMe = auth()->id() == $q->sender_id;
            $roleLabel = match($q->sender_role) {
                'super-admin'  => 'Super Admin',
                'admin'        => 'Admin',
                'tender-owner' => 'Tender Owner',
                'management'   => 'Management',
                default        => ucfirst($q->sender_role),
            };
            $roleColor = match($q->sender_role) {
                'super-admin'  => 'bg-slate-200 text-slate-800',
                'admin'        => 'bg-red-100 text-red-700',
                'tender-owner' => 'bg-purple-100 text-purple-700',
                'management'   => 'bg-indigo-100 text-indigo-700',
                default        => 'bg-gray-100 text-gray-700',
            };
        @endphp
        <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[85%] rounded-xl p-4 border {{ $isMe ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200' }}">
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span class="font-semibold text-sm text-gray-800">{{ $q->sender->name ?? 'Unknown' }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $roleColor }}">{{ $roleLabel }}</span>
                    <span class="text-xs text-gray-400 ml-auto whitespace-nowrap">{{ $q->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="text-sm text-gray-700 prose prose-sm max-w-none" style="white-space: pre-wrap;">{{ $q->message }}</div>
                @if($q->attachment_path)
                <div class="mt-3 pt-2 border-t border-gray-200">
                    <a href="{{ Storage::url($q->attachment_path) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:underline">
                        <i class="fas fa-paperclip"></i>
                        {{ $q->attachment_name ?? 'Attachment' }}
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-10 text-gray-400 mb-6 bg-gray-50 rounded-xl">
        <i class="fas fa-comments text-4xl mb-3 opacity-40"></i>
        <p class="text-sm font-medium">No messages yet.</p>
        <p class="text-xs mt-1">{{ $isManagement ? 'Raise a query about this tender below — Super Admin and the Tender Owner will see it.' : 'Replies here are visible to Management (VC / Pro-VC / Registrar / Finance Officer).' }}</p>
    </div>
    @endif

    {{-- Compose Form --}}
    <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
        <h5 class="font-medium text-gray-700 mb-3 text-sm flex items-center gap-2">
            @if($isManagement)
                <i class="fas fa-question-circle text-orange-500"></i> Raise a Query
            @else
                <i class="fas fa-reply text-green-600"></i> Reply
            @endif
        </h5>
        @if(session('success'))
            <div class="mb-3 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
        @endif
        @if($errors->has('message'))
            <div class="mb-3 px-4 py-2 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                {{ $errors->first('message') }}
            </div>
        @endif
        <form action="{{ $queryRoute }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <textarea name="message" rows="3" placeholder="{{ $isManagement ? 'Write your query here...' : 'Write your reply here...' }}"
                          class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2">{{ old('message') }}</textarea>
            </div>
            <div class="flex items-end gap-4 flex-wrap">
                <div class="flex-1 min-w-48">
                    <label class="block text-xs text-gray-500 mb-1">
                        <i class="fas fa-paperclip mr-1"></i> Attachment (PDF, DOC, JPG, PNG — max 10 MB)
                    </label>
                    <input type="file" name="attachment" class="block w-full text-xs text-gray-600
                           file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0
                           file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700
                           hover:file:bg-blue-100 cursor-pointer"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Mark as read on page load --}}
<script>
(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    fetch('{{ $markReadRoute }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
    }).catch(() => {});
})();
</script>
