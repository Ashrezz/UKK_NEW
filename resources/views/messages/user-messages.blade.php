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

                                    <!-- Confirmation Options (if message contains "Konfirmasi Peminjaman") -->
                                    @if(str_contains($message->subject, 'Konfirmasi Peminjaman') && !$message->is_read)
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
                                    @elseif(str_contains($message->subject, 'Konfirmasi Peminjaman') && $message->is_read)
                                    <div class="mt-4 ml-7">
                                        <p class="text-sm text-green-700 font-medium">
                                            ✓ Anda sudah merespons pesan konfirmasi ini
                                        </p>
                                        @if($message->reader)
                                        <p class="text-xs text-gray-600 mt-1">
                                            Dibaca pada {{ $message->read_at->format('d M Y, H:i') }}
                                        </p>
                                        @endif
                                    </div>
                                    @endif
                                </div>
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
@endsection
