@extends('layout')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto">
        <div class="card p-6 mb-6">
            <h2 class="text-2xl font-semibold">Tambah Pengguna Baru</h2>
            <p class="muted mt-1">Buat akun baru untuk petugas atau pengunjung</p>
        </div>

        <div class="card overflow-hidden">
            <div class="p-4 sm:p-6 lg:p-8">
                <form action="{{ route('admin.tambah_user.store') }}" method="POST" class="space-y-4 sm:space-y-6">
                    @csrf

                    <!-- Name Field -->
                    <div>
                        <label for="name" class="block text-xs sm:text-sm font-medium text-black">
                            Nama Lengkap
                        </label>
                        <div class="mt-1">
                            <input type="text" name="name" id="name" required
                                class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm 
                                placeholder-black/50 focus:outline-none focus:ring-red-500 focus:border-red-500 
                                text-black transition-colors duration-200"
                                placeholder="Masukkan nama lengkap">
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs sm:text-sm font-medium text-black">
                            Alamat Email
                        </label>
                        <div class="mt-1">
                            <input type="email" name="email" id="email" required
                                class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm 
                                placeholder-black/50 focus:outline-none focus:ring-red-500 focus:border-red-500 
                                text-black transition-colors duration-200"
                                placeholder="nama@email.com">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-xs sm:text-sm font-medium text-black">
                            Password
                        </label>
                        <div class="mt-1">
                            <input type="password" name="password" id="password" required
                                class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm 
                                placeholder-black/50 focus:outline-none focus:ring-red-500 focus:border-red-500 
                                text-black transition-colors duration-200"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <!-- Role Selection -->
                    <div>
                        <label for="role" class="block text-xs sm:text-sm font-medium text-black">
                            Tipe Pengguna
                        </label>
                        <div class="mt-1">
                            <select name="role" id="role" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border border-gray-300
                                focus:outline-none focus:ring-red-500 focus:border-red-500 rounded-md
                                text-black transition-colors duration-200">
                                <option value="petugas">Petugas</option>
                                <option value="pengunjung">Pengunjung</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-3 sm:pt-4">
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 text-sm sm:text-base border border-transparent rounded-md shadow-sm font-medium 
                            text-white btn-danger
                            transform hover:scale-[1.02] transition-all duration-200">
                            Tambah Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
