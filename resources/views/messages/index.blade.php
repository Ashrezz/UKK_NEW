@extends('layout')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="card p-6 mb-6">
            <h2 class="text-2xl font-semibold">Pesan dari User</h2>
            <p class="muted mt-1">Daftar pesan yang dikirim oleh pengguna sistem</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Messages List -->
        <div class="space-y-4">
            @forelse($messages as $message)
            <div class="card {{ $message->is_read ? 'bg-white' : 'bg-blue-50 border-blue-200' }}">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                @if(!$message->is_read)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    Baru
                                </span>
                                @endif
                                <h3 class="text-lg font-semibold text-gray-900">{{ $message->subject }}</h3>
                            </div>
                            
                            <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Dari: <strong>{{ $message->sender->name }}</strong></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>{{ $message->created_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                            
                            <div class="prose max-w-none">
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $message->message }}</p>
                            </div>
                            
                            @if($message->is_read)
                            <div class="mt-4 text-xs text-gray-500">
                                Dibaca oleh {{ $message->reader->name }} pada {{ $message->read_at->format('d M Y, H:i') }}
                            </div>
                            @endif
                        </div>
                        
                        @if(!$message->is_read)
                        <form action="{{ route('messages.read', $message->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-primary text-sm">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Tandai Dibaca
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="card p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <p class="text-gray-500 text-lg">Belum ada pesan dari user</p>
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
