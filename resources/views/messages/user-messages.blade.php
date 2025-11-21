@extends('layout')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="card p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold">Pesan Saya</h2>
                    <p class="muted mt-1">Daftar pesan yang Anda kirim dan balasan dari admin</p>
                </div>
                <a href="{{ route('messages.create') }}" class="btn-primary">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Pesan Baru
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Messages List -->
        <div class="space-y-4">
            @forelse($messages as $message)
            <div class="card">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $message->subject }}</h3>
                                @if($message->reply)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        Sudah Dibalas
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                        Menunggu Balasan
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ $message->created_at->format('d M Y, H:i') }}</span>
                            </div>

                            <div class="prose max-w-none">
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $message->message }}</p>
                            </div>

                            <!-- Admin Reply -->
                            @if($message->reply)
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <div class="bg-green-50 rounded-lg p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <h4 class="font-semibold text-green-800">Balasan dari {{ $message->replier ? $message->replier->name : 'Admin' }}</h4>
                                    </div>
                                    <p class="text-gray-700 whitespace-pre-wrap ml-7">{{ $message->reply }}</p>
                                    <p class="text-xs text-green-600 mt-2 ml-7">{{ $message->replied_at ? $message->replied_at->format('d M Y, H:i') : '' }}</p>
                                    
                                    <!-- Mark as Read Button -->
                                    @if(!$message->is_read)
                                    <form action="{{ route('messages.user.read', $message->id) }}" method="POST" class="mt-3 ml-7">
                                        @csrf
                                        <button type="submit" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            ✓ Tandai Sudah Dibaca
                                        </button>
                                    </form>
                                    @else
                                    <p class="text-xs text-gray-500 mt-2 ml-7">
                                        ✓ Dibaca pada {{ $message->read_at->format('d M Y, H:i') }}
                                    </p>
                                    @endif

                                    <!-- Confirmation Options (if message contains "Konfirmasi Peminjaman") -->
                                    @if(str_contains($message->subject, 'Konfirmasi Peminjaman') && !str_contains($message->message, 'Konfirmasi: Ya') && !str_contains($message->message, 'Konfirmasi: Tidak'))
                                    <div class="mt-4 ml-7 flex gap-2">
                                        <form action="{{ route('messages.confirm', $message->id) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="confirmation" value="Ya">
                                            <button type="submit" class="btn-primary text-sm">
                                                ✓ Ya, Saya Akan Hadir
                                            </button>
                                        </form>
                                        <form action="{{ route('messages.confirm', $message->id) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="confirmation" value="Tidak">
                                            <button type="submit" class="btn-danger text-sm">
                                                ✗ Tidak, Saya Batalkan
                                            </button>
                                        </form>
                                    </div>
                                    @endif
                                </div>
                                
                                <!-- User Reply to Admin's Reply -->
                                @if(!str_contains($message->subject, 'Re: Re:'))
                                <div class="mt-4">
                                    <button onclick="toggleReplyForm({{ $message->id }})" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                        💬 Balas Pesan Ini
                                    </button>
                                    <form id="replyForm{{ $message->id }}" action="{{ route('messages.user.reply', $message->id) }}" method="POST" class="mt-3 hidden">
                                        @csrf
                                        <label class="block">
                                            <span class="text-sm font-medium text-gray-700">Balasan Anda</span>
                                            <textarea name="user_reply" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" placeholder="Tulis balasan Anda..." required></textarea>
                                        </label>
                                        <div class="flex gap-2 mt-2">
                                            <button type="submit" class="btn-primary text-sm">Kirim Balasan</button>
                                            <button type="button" onclick="toggleReplyForm({{ $message->id }})" class="btn-secondary text-sm">Batal</button>
                                        </div>
                                    </form>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="card p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <p class="text-gray-500 text-lg">Anda belum mengirim pesan</p>
                <a href="{{ route('messages.create') }}" class="btn-primary mt-4 inline-block">Kirim Pesan Pertama</a>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($messages->hasPages())
        <div class="mt-6">
            {{ $messages->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function toggleReplyForm(messageId) {
    const form = document.getElementById('replyForm' + messageId);
    if (form) {
        form.classList.toggle('hidden');
    }
}
</script>
@endsection
