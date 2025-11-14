@extends('layout')

@section('content')
<div class="py-8">
    <!-- Header Card -->
    <div class="card p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Ajukan Peminjaman Ruang</h1>
                <p class="muted mt-1">Isi formulir di bawah untuk mengajukan peminjaman ruang dengan detail lengkap</p>
            </div>
            <a href="{{ route('peminjaman.jadwal') }}" class="btn-ghost">Lihat Jadwal Lengkap</a>
        </div>
    </div>

    <!-- Form Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form Card -->
        <div class="lg:col-span-2">
            <form action="{{ route('peminjaman.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @if($errors->any())
                    <div class="card p-4 bg-red-50 border border-red-100">
                        <ul class="text-sm text-red-700 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <!-- Pilih Ruang Card -->
                <div class="card">
                    <div class="px-6 py-4 border-b">
                        <h3 class="font-medium">Pilih Ruang</h3>
                        <p class="muted text-sm">Tentukan ruang mana yang akan Anda pinjam</p>
                    </div>
                    <div class="p-6">
                        <select id="ruang_id" name="ruang_id" class="appearance-none w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                            <option value="">-- Pilih Ruang --</option>
                            @foreach($ruangs as $ruang)
                                <option value="{{ $ruang->id }}" data-rate="50000">{{ $ruang->nama_ruang }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Tanggal & Jam Card -->
                <div class="card">
                    <div class="px-6 py-4 border-b">
                        <h3 class="font-medium">Jadwal Peminjaman</h3>
                        <p class="muted text-sm">Tentukan tanggal dan jam peminjaman</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="tanggal" class="block text-sm font-medium mb-2">Tanggal Pinjam</label>
                                <input type="date" id="tanggal" name="tanggal" min="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Durasi Peminjaman</label>
                                <div class="text-2xl font-semibold text-blue-600" id="durationDisplay">0 jam</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="jam_mulai" class="block text-sm font-medium mb-2">Jam Mulai</label>
                                <select id="jam_mulai" name="jam_mulai" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                                    <option value="">Pilih Jam Mulai</option>
                                    @for ($h = 8; $h <= 18; $h++)
                                        @php $time = str_pad($h,2,'0',STR_PAD_LEFT) . ':00'; @endphp
                                        <option value="{{ $time }}">{{ $time }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label for="jam_selesai" class="block text-sm font-medium mb-2">Jam Selesai</label>
                                <select id="jam_selesai" name="jam_selesai" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                                    <option value="">Pilih Jam Selesai</option>
                                    @for ($h = 8; $h <= 18; $h++)
                                        @php $time = str_pad($h,2,'0',STR_PAD_LEFT) . ':00'; @endphp
                                        <option value="{{ $time }}">{{ $time }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tujuan Peminjaman Card -->
                <div class="card">
                    <div class="px-6 py-4 border-b">
                        <h3 class="font-medium">Tujuan Peminjaman</h3>
                        <p class="muted text-sm">Jelaskan keperluanmu menggunakan ruang ini</p>
                    </div>
                    <div class="p-6">
                        <textarea id="keperluan" name="keperluan" rows="4" placeholder="Jelaskan tujuan peminjaman ruang..." class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required></textarea>
                    </div>
                </div>

                <!-- Upload Bukti Pembayaran Card -->
                <div class="card">
                    <div class="px-6 py-4 border-b">
                        <h3 class="font-medium">Bukti Pembayaran</h3>
                        <p class="muted text-sm">Upload screenshot atau bukti pembayaran Anda</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="bukti_pembayaran" class="block text-sm font-medium mb-2">Pilih File</label>
                            <input type="file" id="bukti_pembayaran" name="bukti_pembayaran" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                            <p class="text-xs muted mt-1">Format: JPG, PNG | Max: 2MB</p>
                        </div>
                        <img id="buktiPreview" src="" alt="Preview Bukti" class="rounded-lg shadow-md hidden w-full max-w-xs"/>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" class="w-full btn-primary font-medium py-2 px-4 rounded-lg transition">
                        <svg class="h-5 w-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Ajukan Peminjaman
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
            <!-- Biaya Card -->
            <div class="card">
                <div class="px-6 py-4 border-b header-accent">
                    <h3 class="font-medium text-white">Ringkasan Biaya</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div id="payment-details" class="hidden space-y-4">
                        <div>
                            <p class="muted text-sm">Harga per Jam</p>
                            <p id="rateDisplay" class="text-lg font-semibold">Rp 50.000</p>
                        </div>
                        <div>
                            <p class="muted text-sm">Durasi</p>
                            <p id="durationDisplay2" class="text-lg font-semibold">0 jam</p>
                        </div>
                        <hr>
                        <div>
                            <p class="muted text-sm">Total Biaya</p>
                            <p id="totalDisplay" class="text-2xl font-bold" style="color: var(--accent);">Rp 0</p>
                        </div>
                        <input type="hidden" name="biaya" id="biaya">
                    </div>
                    <div v-if="!hasSchedule" class="p-3 bg-blue-50 rounded-lg border border-blue-200 text-sm text-blue-700">
                        <p>Isi formulir di samping untuk melihat ringkasan biaya</p>
                    </div>
                </div>
            </div>

            <!-- QRIS Payment Card -->
            <div class="card">
                <div class="px-6 py-4 border-b header-accent">
                    <h3 class="font-medium text-white">Pembayaran (QRIS)</h3>
                </div>
                <div class="p-6 text-center">
                    <p class="muted text-sm mb-4">Scan QRIS berikut untuk melakukan pembayaran</p>
                    <img src="{{ asset('img/qris.png') }}" alt="QRIS" class="mx-auto w-40 h-40 rounded-lg shadow-md">
                    <p class="text-xs muted mt-3">Setelah membayar, unggah bukti pembayaran pada formulir.</p>
                </div>
            </div>

            <!-- Jadwal Reguler Card -->
            <div class="card">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-medium">Jadwal Ruang Terpakai</h3>
                    <p class="muted text-sm">Jadwal peminjaman yang sudah terdaftar</p>
                </div>
                <div class="p-6">
                    @if(count($jadwalReguler) > 0)
                        <div class="space-y-2 text-sm max-h-64 overflow-y-auto">
                            @foreach($jadwalReguler as $jadwal)
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="font-medium">{{ $jadwal['hari'] }}, {{ \Carbon\Carbon::parse($jadwal['tanggal'])->format('d/m/Y') }}</div>
                                    <div class="muted text-xs">{{ $jadwal['jam'] }} • {{ $jadwal['ruang'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="muted text-sm text-center py-4">Belum ada jadwal ruang terpakai</p>
                    @endif
                </div>
            </div>

            <!-- Informasi Penting & Kebijakan Pembayaran -->
            <div class="card">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-medium">Informasi Penting</h3>
                </div>
                <div class="p-6">
                    <ul class="space-y-2 text-sm muted">
                        <li class="flex gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span>Pembayaran harus lunas sebelum disetujui</span>
                        </li>
                        <li class="flex gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span>Datang 15 menit sebelum waktu pinjam</span>
                        </li>
                        <li class="flex gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span>Jaga kebersihan dan ketertiban ruang</span>
                        </li>
                        <li class="flex gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span>Tanggung jawab atas kerusakan ruang</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Kebijakan Pembayaran Card -->
            <div class="card">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-medium">Kebijakan Pembayaran & Refund</h3>
                </div>
                <div class="p-6 space-y-4 text-sm muted">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Jika Pengajuan Disetujui</h4>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>Pengajuan yang disetujui otomatis menandakan pembayaran terverifikasi</li>
                            <li>Anda dapat mengakses detail booking via halaman peminjaman</li>
                        </ul>
                    </div>
                    <hr class="border-gray-200">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Jika Pengajuan Ditolak</h4>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li><strong>Down Payment (DP):</strong> DP tidak dapat dikembalikan jika pembatalan oleh Anda sendiri</li>
                            <li><strong>Pembatalan Admin:</strong> Admin akan memproses pengembalian dana</li>
                            <li><strong>Penolakan Internal:</strong> Admin akan memberikan keterangan dan dapat mengembalikan pembayaran</li>
                        </ul>
                    </div>
                    <hr class="border-gray-200">
                    <p class="text-xs italic">Pertanyaan tentang refund? Hubungi admin atau buka tiket pembatalan di akun Anda</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ruangSelect = document.getElementById('ruang_id');
    const jamMulaiInput = document.getElementById('jam_mulai');
    const jamSelesaiInput = document.getElementById('jam_selesai');
    const tanggalInput = document.getElementById('tanggal');
    const rateDisplay = document.getElementById('rateDisplay');
    const durationDisplay = document.getElementById('durationDisplay');
    const durationDisplay2 = document.getElementById('durationDisplay2');
    const totalDisplay = document.getElementById('totalDisplay');
    const biayaInput = document.getElementById('biaya');
    const buktiInput = document.getElementById('bukti_pembayaran');
    const buktiPreview = document.getElementById('buktiPreview');
    const paymentDetails = document.getElementById('payment-details');

    // Function to calculate total cost
    function calculateTotal() {
        const selectedOption = ruangSelect.options[ruangSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            paymentDetails.classList.add('hidden');
            return;
        }

        const rate = parseFloat(selectedOption.dataset.rate) || 50000;
        const startTime = jamMulaiInput.value;
        const endTime = jamSelesaiInput.value;

        if (startTime && endTime) {
            const start = new Date(`1970-01-01T${startTime}:00`);
            const end = new Date(`1970-01-01T${endTime}:00`);
            
            let duration = (end - start) / (1000 * 60 * 60);
            if (duration < 0) duration = 0;

            const total = duration * rate;

            paymentDetails.classList.remove('hidden');
            rateDisplay.textContent = `Rp ${rate.toLocaleString('id-ID')}`;
            durationDisplay2.textContent = `${duration} jam`;
            durationDisplay.textContent = `${duration} jam`;
            totalDisplay.textContent = `Rp ${total.toLocaleString('id-ID')}`;
            biayaInput.value = total;
        } else {
            paymentDetails.classList.add('hidden');
        }
    }

    // Preview for payment proof
    buktiInput.addEventListener('change', function(event) {
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                buktiPreview.src = e.target.result;
                buktiPreview.classList.remove('hidden');
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    });

    ruangSelect.addEventListener('change', calculateTotal);
    jamMulaiInput.addEventListener('change', calculateTotal);
    jamSelesaiInput.addEventListener('change', calculateTotal);
    tanggalInput.addEventListener('change', calculateTotal);

    // Initial calculation
    calculateTotal();
});
</script>
@endsection
