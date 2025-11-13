<!-- Payment Section -->
<div class="mt-8 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Informasi Pembayaran</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4 bg-white dark:bg-gray-800">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Rincian Biaya</h4>

                <div class="grid grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Tarif per jam</p>
                        <p id="rateDisplay" class="font-medium mt-1">-</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Durasi</p>
                        <p id="durationDisplay" class="font-medium mt-1">-</p>
                    </div>

                    <div class="col-span-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Biaya</p>
                        <p id="totalDisplay" class="font-semibold text-lg text-blue-600 dark:text-blue-400 mt-1">-</p>
                    </div>
                </div>

                <!-- Hidden input untuk dikirim ke server -->
                <input type="hidden" name="biaya" value="0" />
            </div>            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Upload Bukti Pembayaran
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="bukti_pembayaran" class="relative cursor-pointer bg-white dark:bg-gray-700 rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none">
                                <span>Upload bukti pembayaran</span>
                                <input id="bukti_pembayaran" name="bukti_pembayaran" type="file" class="sr-only" accept="image/*">
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG, JPEG up to 2MB</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col items-center justify-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="mb-4 text-center">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Scan QRIS untuk Pembayaran</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Silakan scan kode QR berikut untuk melakukan pembayaran</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <img src="{{ asset('img/qris.png') }}" alt="QRIS Code" class="w-48 h-48 object-contain">
            </div>
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Setelah melakukan pembayaran, silakan upload bukti pembayaran</p>
            </div>
        </div>
    </div>
</div>

{{-- Remove duplicate/old scripts and replace with a single realtime script --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const RATE_PER_HOUR = 50000;
    const jamMulai = document.querySelector('input[name="jam_mulai"]');
    const jamSelesai = document.querySelector('input[name="jam_selesai"]');
    const rateEl = document.getElementById('rateDisplay');
    const durationEl = document.getElementById('durationDisplay');
    const totalEl = document.getElementById('totalDisplay');
    const biayaInput = document.querySelector('input[name="biaya"]');

    if (rateEl) rateEl.textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(RATE_PER_HOUR) + ' / jam';

    function formatIDR(v) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v);
    }

    function toMinutes(timeStr) {
        if (!timeStr) return null;
        const parts = timeStr.split(':');
        if (parts.length < 2) return null;
        const h = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);
        if (isNaN(h) || isNaN(m)) return null;
        return h * 60 + m;
    }

    function calculate(startMin, endMin) {
        // end must be after start (allow crossing midnight)
        if (startMin == null || endMin == null) return null;
        let end = endMin;
        if (end <= startMin) {
            // treat as next day
            end = end + 24 * 60;
        }
        let minutes = end - startMin;
        if (minutes <= 0) return 0;

        // subtract lunch break 12:00-13:00 if interval covers it
        const lunchStart = 12 * 60;
        const lunchEnd = 13 * 60;
        // check coverage on timeline possibly extended beyond midnight
        // check every hour in range for overlap with lunch window (single day)
        const realStart = startMin;
        const realEnd = end;
        // if original interval (in minutes timeline) intersects lunch window (12:00-13:00)
        // we only subtract 60 if overlap exists
        const overlapStart = Math.max(realStart, lunchStart);
        const overlapEnd = Math.min(realEnd, lunchEnd);
        if (overlapEnd > overlapStart) {
            minutes -= (overlapEnd - overlapStart);
        }

        return Math.max(0, minutes / 60); // duration in hours (decimal)
    }

    function update() {
        const startMin = toMinutes(jamMulai?.value);
        const endMin = toMinutes(jamSelesai?.value);

        if (startMin == null || endMin == null) {
            if (durationEl) durationEl.textContent = '-';
            if (totalEl) totalEl.textContent = formatIDR(0);
            if (biayaInput) biayaInput.value = 0;
            return;
        }

        // basic operational-hour validation (08:00 - 17:00)
        const startHour = parseInt(jamMulai.value.split(':')[0], 10);
        const endHour = parseInt(jamSelesai.value.split(':')[0], 10);
        if (isNaN(startHour) || isNaN(endHour)) {
            if (durationEl) durationEl.textContent = '-';
            if (totalEl) totalEl.textContent = formatIDR(0);
            if (biayaInput) biayaInput.value = 0;
            return;
        }

        // compute duration hours (decimal)
        const hours = calculate(startMin, endMin);

        if (hours === 0) {
            durationEl.textContent = 'Waktu selesai harus setelah mulai';
            totalEl.textContent = formatIDR(0);
            if (biayaInput) biayaInput.value = 0;
            return;
        }

        // validate operating hours and lunch (no alerts to keep realtime UX)
        if (startHour < 8 || startHour >= 17 || endHour < 8 || endHour > 17) {
            durationEl.textContent = 'Jam operasional: 08:00 - 17:00';
            totalEl.textContent = formatIDR(0);
            if (biayaInput) biayaInput.value = 0;
            return;
        }
        if ((startHour === 12) || (endHour === 12)) {
            durationEl.textContent = 'Tidak dapat meminjam pada jam istirahat (12:00-13:00)';
            totalEl.textContent = formatIDR(0);
            if (biayaInput) biayaInput.value = 0;
            return;
        }

        // limit max 8 hours
        if (hours > 8) {
            durationEl.textContent = 'Maksimal peminjaman 8 jam';
            totalEl.textContent = formatIDR(0);
            if (biayaInput) biayaInput.value = 0;
            return;
        }

        // show duration with up to 2 decimals
        const hoursDisplay = Math.round(hours * 100) / 100;
        const total = Math.round(hours * RATE_PER_HOUR);

        durationEl.textContent = `${hoursDisplay} jam`;
        totalEl.textContent = formatIDR(total);
        if (biayaInput) biayaInput.value = total;
    }

    ['input','change'].forEach(evt => {
        if (jamMulai) jamMulai.addEventListener(evt, update);
        if (jamSelesai) jamSelesai.addEventListener(evt, update);
    });

    // init
    update();
});
</script>
@endpush