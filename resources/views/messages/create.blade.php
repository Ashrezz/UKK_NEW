@extends('layout')

@section('content')
<div class="py-8">
    <div class="max-w-2xl mx-auto">
        <div class="card p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-black">Kirim Pesan ke Admin</h1>
                <p class="text-sm text-gray-600 mt-1">Sampaikan pertanyaan, keluhan, atau saran Anda kepada admin</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('messages.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="subject" class="block text-sm font-medium text-black mb-2">
                        Subjek <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="subject" id="subject" required
                        value="{{ old('subject') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
                        placeholder="Tulis subjek pesan Anda">
                    @error('subject')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="message" class="block text-sm font-medium text-black mb-2">
                        Pesan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="message" id="message" rows="8" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
                        placeholder="Tulis pesan Anda di sini...">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Maksimal 5000 karakter</p>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Kirim Pesan
                    </button>
                    <a href="{{ route('home') }}" class="btn-secondary flex-1 text-center">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
