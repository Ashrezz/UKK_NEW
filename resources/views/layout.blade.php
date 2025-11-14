<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peminjaman Ruang</title>
    {{-- Built Tailwind/CSS via Vite --}}
    @vite([
    'resources/css/app.css',
    'resources/css/layout-redesign.css',
    'resources/js/app.js'
])

    <style>
        /* Hide the floating mobile toggle when sidebar is open to avoid overlapping menu text */
        @media (max-width: 767px) {
            body.sidebar-open #sidebarToggle {
                display: none !important;
            }
            /* Ensure close button remains visible */
            body.sidebar-open #sidebarClose {
                display: inline-flex;
            }
            /* Slightly shift sidebar content to avoid being under system UI */
            #sidebar { z-index: 60; }
        }
    </style>

</head>
<body class="min-h-screen">
    @if(!request()->routeIs('login') && !request()->is('register'))
    <!-- Mobile sidebar toggle (fixed) -->
    <button id="sidebarToggle" class="md:hidden fixed top-4 left-4 z-50 p-2 rounded-lg bg-white shadow" aria-expanded="false">
        <svg class="h-6 w-6 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 transform -translate-x-full md:translate-x-0 z-40 p-4">
        <div class="h-16 flex items-center justify-between px-2">
            <div class="text-lg font-semibold">Menu</div>
            <button id="sidebarClose" class="md:hidden p-1 rounded-md">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="mt-4 space-y-2">
            @auth
                <a href="{{ route('home') }}" class="sidebar-link {{ request()->routeIs('home') ? 'active' : ''}}">Home</a>
                <a href="{{ route('peminjaman.jadwal') }}" class="sidebar-link {{ request()->routeIs('peminjaman.jadwal') ? 'active' : ''}}">Jadwal</a>
                <a href="{{ route('info-pengajuan') }}" class="sidebar-link {{ request()->routeIs('info-pengajuan') ? 'active' : ''}}">Info Pengajuan</a>

                @if(auth()->user()->role !== 'admin')
                    <a href="{{ route('peminjaman.create') }}" class="sidebar-link {{ request()->routeIs('peminjaman.create') ? 'active' : ''}}">Ajukan Pinjam</a>
                @endif

                @if(auth()->user()->role == 'admin' || auth()->user()->role == 'petugas')
                    <a href="{{ url('/ruang') }}" class="sidebar-link {{ request()->is('ruang*') ? 'active' : ''}}">Kelola Ruang</a>
                    <a href="{{ route('peminjaman.manage') }}" class="sidebar-link {{ request()->routeIs('peminjaman.manage') ? 'active' : ''}}">Kelola Peminjaman</a>
                @endif

                @if(auth()->user()->role == 'admin')
                    <a href="{{ route('admin.tambah_user.create') }}" class="sidebar-link {{ request()->routeIs('admin.tambah_user.create') ? 'active' : ''}}">Tambah User</a>
                @endif

                <a href="{{ route('logout') }}" class="sidebar-link text-red-600">Logout</a>
            @else
                <a href="{{ route('login') }}" class="sidebar-link {{ request()->routeIs('login') ? 'active' : ''}}">Login</a>
                <a href="{{ route('info-pengajuan') }}" class="sidebar-link {{ request()->routeIs('info-pengajuan') ? 'active' : ''}}">Info Pengajuan</a>
                <a href="/register" class="sidebar-link {{ request()->is('register') ? 'active' : ''}}">Register</a>
            @endauth
        </nav>

        @auth
            <div class="absolute bottom-4 left-4 right-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-700 font-semibold">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                    <div>
                        <div class="font-medium">{{ auth()->user()->name }}</div>
                        <div class="text-xs muted">{{ auth()->user()->role }}</div>
                    </div>
                </div>
            </div>
        @endauth
    </aside>
    @endif

    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-40 md:hidden"></div>

    <!-- Main -->
    <main class="@if(request()->routeIs('login') || request()->is('register')) w-full @else pt-20 md:pl-64 @endif">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4">
                    <div class="card p-4 bg-green-50">
                        <div class="flex gap-3 items-center">
                            <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <div class="text-sm">{{ session('success') }}</div>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4">
                    <div class="card p-4 bg-red-50">
                        <div class="flex gap-3 items-center">
                            <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            <div class="text-sm">{{ session('error') }}</div>
                        </div>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggle = document.getElementById('sidebarToggle');
            const closeBtn = document.getElementById('sidebarClose');
            const body = document.body;

            function openSidebar(){ sidebar.classList.remove('-translate-x-full'); overlay.classList.add('active'); body.classList.add('sidebar-open'); }
            function closeSidebar(){ sidebar.classList.add('-translate-x-full'); overlay.classList.remove('active'); body.classList.remove('sidebar-open'); }
            function toggleSidebar(){ sidebar.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar(); }

            if(toggle) toggle.addEventListener('click', function(e){ e.preventDefault(); toggleSidebar(); });
            if(closeBtn) closeBtn.addEventListener('click', function(e){ e.preventDefault(); closeSidebar(); });
            if(overlay) overlay.addEventListener('click', function(e){ e.preventDefault(); closeSidebar(); });

            // close when clicking sidebar links (mobile)
            // Also write a small cookie 'sidebar_nav' so middleware that requires sidebar navigation
            // can detect a legitimate sidebar click and allow the navigation.
            sidebar.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
                try { document.cookie = 'sidebar_nav=1; path=/'; } catch(e) {}
                setTimeout(closeSidebar, 80);
            }));
            window.addEventListener('resize', () => { if(window.innerWidth >= 768) closeSidebar(); });
        });
    </script>
</body>

</html>
