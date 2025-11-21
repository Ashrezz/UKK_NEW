@extends('layout')

@section('content')
<div class="py-8">
    <!-- Hero -->
    <div class="card p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Selamat Datang di Sistem Peminjaman Ruangan</h1>
                <p class="muted mt-1">Lihat status peminjaman ruangan Anda dan kelola peminjaman dengan mudah</p>
            </div>
            <div class="flex items-center gap-3">
                @if(auth()->user()->role !== 'admin'    )
                    <a href="/peminjaman/create" class="btn-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span>Ajukan Peminjaman</span>
                    </a>
                @endif
                <a href="/peminjaman/jadwal" class="btn-ghost">Lihat Jadwal</a>
            </div>
        </div>
    </div>
    
    <!-- Notifications for Admin/Petugas (Right Side) -->
    @if(auth()->user()->role == 'admin' || auth()->user()->role == 'petugas')
    @php
        $unreadNotifications = \App\Models\Notification::where('is_read', false)->orderBy('created_at', 'desc')->take(5)->get();
        $unreadCount = \App\Models\Notification::where('is_read', false)->count();
        $unreadMessages = \App\Models\Message::where('is_read', false)->orderBy('created_at', 'desc')->take(5)->get();
        $unreadMessageCount = \App\Models\Message::where('is_read', false)->count();
    @endphp
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <!-- Stats will go here -->
        </div>
        <div class="space-y-4">
            <!-- Notifications Card -->
            @if($unreadCount > 0)
            <div class="card p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-sm">Notifikasi Booking</h3>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                        {{ $unreadCount }}
                    </span>
                </div>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @foreach($unreadNotifications as $notif)
                    <a href="{{ route('notifications.read', $notif->id) }}" class="block p-3 rounded-lg bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-colors">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-blue-900 truncate">{{ $notif->title }}</p>
                                <p class="text-xs text-blue-700 mt-0.5 line-clamp-2">{{ $notif->message }}</p>
                                <p class="text-xs text-blue-600 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Messages Card -->
            @if($unreadMessageCount > 0)
            <div class="card p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-sm">Pesan dari User</h3>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                        {{ $unreadMessageCount }}
                    </span>
                </div>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @foreach($unreadMessages as $msg)
                    <a href="{{ route('messages.index') }}" class="block p-3 rounded-lg bg-green-50 hover:bg-green-100 border border-green-200 transition-colors">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-green-900 truncate">{{ $msg->sender->name }}</p>
                                <p class="text-xs text-green-700 mt-0.5 font-medium truncate">{{ $msg->subject }}</p>
                                <p class="text-xs text-green-600 mt-1">{{ $msg->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Badge Progress Card (for regular users) -->
    @if(auth()->user()->role !== 'admin' && auth()->user()->role !== 'petugas' && $badgeProgress)
    <div class="card p-6 mb-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                    @if($badgeProgress['current_badge'] > 0)
                        ⭐{{ $badgeProgress['current_badge'] }}
                    @else
                        🎯
                    @endif
                </div>
            </div>
            <div class="flex-1">
                @if(isset($badgeProgress['is_max']) && $badgeProgress['is_max'])
                    <h3 class="text-lg font-semibold mb-2">🏆 Badge Maksimal Tercapai!</h3>
                    <p class="text-sm text-gray-600 mb-3">Selamat! Anda telah mencapai Badge 3 (level tertinggi)</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-gray-600">Total Peminjaman</p>
                            <p class="text-xl font-bold text-blue-600">{{ $badgeProgress['current_count'] }}</p>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <p class="text-xs text-gray-600">Total Transaksi</p>
                            <p class="text-xl font-bold text-green-600">Rp {{ number_format($badgeProgress['current_total'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                @else
                    <h3 class="text-lg font-semibold mb-1">Progress Menuju {{ $badgeProgress['next_badge_name'] }}</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        @if($badgeProgress['current_badge'] == 0)
                            Selesaikan {{ $badgeProgress['target_count'] }} peminjaman dengan total Rp {{ number_format($badgeProgress['target_total'], 0, ',', '.') }} untuk mendapatkan badge pertama!
                        @else
                            Tingkatkan badge Anda ke level berikutnya!
                        @endif
                    </p>

                    <!-- Progress: Jumlah Peminjaman -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Jumlah Peminjaman</span>
                            <span class="text-sm font-semibold text-blue-600">{{ $badgeProgress['current_count'] }} / {{ $badgeProgress['target_count'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-500" style="width: {{ $badgeProgress['count_percent'] }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $badgeProgress['count_percent'] }}% tercapai</p>
                    </div>

                    <!-- Progress: Total Transaksi -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Total Transaksi</span>
                            <span class="text-sm font-semibold text-green-600">Rp {{ number_format($badgeProgress['current_total'], 0, ',', '.') }} / Rp {{ number_format($badgeProgress['target_total'], 0, ',', '.') }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-500" style="width: {{ $badgeProgress['total_percent'] }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $badgeProgress['total_percent'] }}% tercapai</p>
                    </div>

                    @php
                        $remainingCount = max(0, $badgeProgress['target_count'] - $badgeProgress['current_count']);
                        $remainingTotal = max(0, $badgeProgress['target_total'] - $badgeProgress['current_total']);
                    @endphp
                    @if($remainingCount > 0 || $remainingTotal > 0)
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-xs text-yellow-800">
                            <strong>Sisa yang dibutuhkan:</strong>
                            @if($remainingCount > 0)
                                {{ $remainingCount }} peminjaman lagi
                            @endif
                            @if($remainingCount > 0 && $remainingTotal > 0)
                                dan
                            @endif
                            @if($remainingTotal > 0)
                                Rp {{ number_format($remainingTotal, 0, ',', '.') }} lagi
                            @endif
                        </p>
                    </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stats-card card">
            <div class="muted text-xs">Total Peminjaman</div>
            <div class="text-2xl font-semibold">{{ $peminjaman->count() }}</div>
        </div>
        <div class="stats-card card">
            <div class="muted text-xs">Peminjaman Aktif</div>
            <div class="text-2xl font-semibold text-green-600">{{ $peminjaman->where('status', 'disetujui')->count() }}</div>
        </div>
        <div class="stats-card card">
            <div class="muted text-xs">Menunggu Persetujuan</div>
            <div class="text-2xl font-semibold text-yellow-600">{{ $peminjaman->where('status', 'pending')->count() }}</div>
        </div>
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
        <div class="px-6 py-4 border-b">
            <h3 class="font-medium">Daftar Peminjaman Terkini</h3>
            <div class="muted text-sm">Semua peminjaman ruangan yang telah diajukan</div>
        </div>

        <div class="p-4 overflow-x-auto">
            <table class="w-full table-auto border-collapse text-sm">
                <thead>
                    <tr class="text-left text-xs text-muted uppercase tracking-wide">
                        <th class="px-4 py-3">Ruang</th>
                        <th class="hidden sm:table-cell px-4 py-3">Tanggal</th>
                        <th class="hidden md:table-cell px-4 py-3">Jam</th>
                        <th class="hidden lg:table-cell px-4 py-3">Peminjam</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($peminjaman as $p)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ $p->ruang->nama_ruang }}</td>
                        <td class="hidden sm:table-cell px-4 py-3 muted">{{ $p->tanggal }}</td>
                        <td class="hidden md:table-cell px-4 py-3 muted">{{ $p->jam_mulai }} - {{ $p->jam_selesai }}</td>
                        <td class="hidden lg:table-cell px-4 py-3 muted">{{ $p->user->name }}</td>
                        <td class="px-4 py-3">
                            @if($p->status == 'pending')
                                <span class="badge" style="background:#fff7ed;color:#92400e;border:1px solid rgba(148,64,14,0.06)">{{ ucfirst($p->status) }}</span>
                            @elseif($p->status == 'disetujui')
                                <span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid rgba(6,95,70,0.06)">{{ ucfirst($p->status) }}</span>
                            @else
                                <span class="badge" style="background:#fff1f2;color:#981b1b;border:1px solid rgba(152,27,27,0.06)">{{ ucfirst($p->status) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($p->status == 'pending' && (auth()->id() == $p->user_id || in_array(auth()->user()->role, ['admin', 'petugas'])))
                                <a href="{{ route('peminjaman.edit', $p->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                    Edit
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
