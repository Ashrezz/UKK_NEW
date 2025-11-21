<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Ruang;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,petugas')->only(['manage', 'approve', 'reject', 'verifikasiPembayaran']);
    }

    private $daysOfWeek = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    private $timeSlots = [
        '08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00',
        '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00'
    ];

    private function generateWeeklySchedule($ruang)
    {
        $schedule = [];
        foreach ($this->daysOfWeek as $day) {
            $schedule[$day] = [];
            foreach ($this->timeSlots as $timeSlot) {
                $schedule[$day][$timeSlot] = [
                    'available' => true,
                    'booking' => null
                ];
            }
        }

        // Get all bookings for this room for the current week
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $bookings = Peminjaman::where('ruang_id', $ruang->id)
            ->whereBetween('tanggal', [$startOfWeek, $endOfWeek])
            ->get();

        // Mark booked slots
        foreach ($bookings as $booking) {
            $day = date('l', strtotime($booking->tanggal));
            $dayIndo = $this->getDayInIndonesian($day);

            $timeSlot = $booking->jam_mulai . '-' . $booking->jam_selesai;
            if (isset($schedule[$dayIndo][$timeSlot])) {
                $schedule[$dayIndo][$timeSlot] = [
                    'available' => false,
                    'booking' => $booking
                ];
            }
        }

        return $schedule;
    }

    private function getDayInIndonesian($englishDay)
    {
        $days = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];
        return $days[$englishDay] ?? $englishDay;
    }
    public function index()
    {
        $peminjaman = Peminjaman::with('ruang', 'user')->latest()->get();

        // Calculate badge progress for current user
        $badgeProgress = null;
        if (auth()->check()) {
            $user = auth()->user();
            $stats = $user->peminjaman()
                ->where('status', 'disetujui')
                ->whereIn('status_pembayaran', ['terverifikasi', 'lunas'])
                ->select(DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(biaya),0) as total'))
                ->first();

            $currentCount = (int)($stats->count ?? 0);
            $currentTotal = (int)($stats->total ?? 0);
            $currentBadge = (int)($user->badge ?? 0);

            // Badge tiers: Badge 1=20/700k, Badge 2=50/1.2M, Badge 3=100/1.5M
            $badgeTiers = [
                1 => ['min_count' => 20, 'min_total' => 700000, 'name' => 'Badge 1'],
                2 => ['min_count' => 50, 'min_total' => 1200000, 'name' => 'Badge 2'],
                3 => ['min_count' => 100, 'min_total' => 1500000, 'name' => 'Badge 3'],
            ];

            $nextBadge = $currentBadge + 1;
            if ($nextBadge <= 3 && isset($badgeTiers[$nextBadge])) {
                $target = $badgeTiers[$nextBadge];
                $badgeProgress = [
                    'current_badge' => $currentBadge,
                    'next_badge' => $nextBadge,
                    'current_count' => $currentCount,
                    'target_count' => $target['min_count'],
                    'current_total' => $currentTotal,
                    'target_total' => $target['min_total'],
                    'count_percent' => min(100, round(($currentCount / $target['min_count']) * 100)),
                    'total_percent' => min(100, round(($currentTotal / $target['min_total']) * 100)),
                    'next_badge_name' => $target['name'],
                ];
            } elseif ($currentBadge >= 3) {
                // User already at max badge
                $badgeProgress = [
                    'current_badge' => $currentBadge,
                    'next_badge' => null,
                    'current_count' => $currentCount,
                    'current_total' => $currentTotal,
                    'is_max' => true,
                ];
            }
        }

        return view('home', compact('peminjaman', 'badgeProgress'));
    }

    public function create(Request $request)
    {
        // Cegah admin mengajukan peminjaman
        if (auth()->user()->role === 'admin') {
            return back()->with('error', 'Admin tidak dapat mengajukan peminjaman ruang!');
        }

        $ruangList = Ruang::all();
        $selectedRuang = null;
        $regularSchedule = [];
        $bookedTimeSlots = [];

        // Get all bookings for the current week
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $weeklyBookings = Peminjaman::with(['ruang', 'user'])
            ->whereBetween('tanggal', [$startOfWeek, $endOfWeek])
            ->whereIn('status', ['pending', 'disetujui'])
            ->get();

        // Organize bookings by day and time slot
        $today = now()->format('Y-m-d');
        foreach ($weeklyBookings as $booking) {
            // Skip bookings that are already approved and scheduled after today
            if ($booking->status === 'disetujui' && $booking->tanggal > $today) {
                continue;
            }

            $day = $this->getDayInIndonesian(date('l', strtotime($booking->tanggal)));
            $timeSlot = substr($booking->jam_mulai, 0, 5) . '-' . substr($booking->jam_selesai, 0, 5);

            if (!isset($regularSchedule[$day])) {
                $regularSchedule[$day] = [];
            }

            if (!isset($regularSchedule[$day][$timeSlot])) {
                $regularSchedule[$day][$timeSlot] = [];
            }

            $regularSchedule[$day][$timeSlot][] = [
                'tanggal' => $booking->tanggal,
                'ruang' => $booking->ruang->nama_ruang,
                'user' => $booking->user->name,
                'status' => $booking->status,
                'keperluan' => $booking->keperluan
            ];
        }

        if ($request->has('ruang_id')) {
            $selectedRuang = Ruang::find($request->ruang_id);
            if ($selectedRuang) {
                $weeklySchedule = $this->generateWeeklySchedule($selectedRuang);
            }
        }

        // Get bookings for specific date and room if selected
        if ($request->has('tanggal')) {
            $query = Peminjaman::with(['ruang', 'user'])
                ->where('tanggal', $request->tanggal)
                ->whereIn('status', ['pending', 'disetujui']);

            if ($request->has('ruang_id')) {
                $query->where('ruang_id', $request->ruang_id);
                $selectedRuang = Ruang::find($request->ruang_id);
            }

            $bookings = $query->get();

            foreach ($bookings as $booking) {
                $startTime = strtotime($booking->jam_mulai);
                $endTime = strtotime($booking->jam_selesai);

                while ($startTime < $endTime) {
                    $timeSlot = date('H:i', $startTime) . '-' . date('H:i', strtotime('+1 hour', $startTime));
                    $bookedTimeSlots[$timeSlot] = [
                        'ruang' => $booking->ruang->nama_ruang,
                        'status' => $booking->status,
                        'user' => $booking->user->name,
                        'keperluan' => $booking->keperluan
                    ];
                    $startTime = strtotime('+1 hour', $startTime);
                }
            }
        }

        // Transform regularSchedule into jadwalReguler format for the view
        $jadwalReguler = [];
        foreach ($regularSchedule as $day => $slots) {
            foreach ($slots as $timeSlot => $bookings) {
                foreach ($bookings as $booking) {
                    $jadwalReguler[] = [
                        'hari' => $day,
                        'tanggal' => $booking['tanggal'],
                        'jam' => $timeSlot,
                        'ruang' => $booking['ruang']
                    ];
                }
            }
        }

        return view('peminjaman.create', [
            'ruangs' => $ruangList,
            'selectedRuang' => $selectedRuang,
            'regularSchedule' => $regularSchedule,
            'jadwalReguler' => $jadwalReguler,
            'timeSlots' => $this->timeSlots,
            'daysOfWeek' => $this->daysOfWeek,
            'bookedTimeSlots' => $bookedTimeSlots
        ]);
    }

    public function store(Request $request)
    {
        // Cegah admin mengajukan peminjaman
        if (auth()->user()->role === 'admin') {
            return back()->with('error', 'Admin tidak dapat mengajukan peminjaman ruang!');
        }

        $request->validate([
            'ruang_id' => 'required',
            'tanggal' => 'required|date|after_or_equal:today',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'keperluan' => 'required',
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'bukti_pembayaran.required' => 'Bukti pembayaran harus diunggah berupa file gambar (jpg, png)',
            'tanggal.after_or_equal' => 'Tanggal peminjaman tidak boleh sebelum hari ini.'
        ]);

        // Additional validation: jam_selesai must be after jam_mulai
        if (strtotime($request->jam_selesai) <= strtotime($request->jam_mulai)) {
            return back()->withErrors(['jam_selesai' => 'Jam selesai harus lebih besar dari jam mulai.'])->withInput();
        }

        // Cek apakah ruang sudah dibooking pada waktu yang sama dan statusnya belum selesai
        $bentrok = Peminjaman::where('ruang_id', $request->ruang_id)
            ->where('tanggal', $request->tanggal)
            ->whereIn('status', ['pending', 'disetujui'])
            ->where(function($q) use ($request) {
                $q->where(function($q2) use ($request) {
                    $q2->where('jam_mulai', '<', $request->jam_selesai)
                        ->where('jam_selesai', '>', $request->jam_mulai);
                });
            })
            ->exists();
        if ($bentrok) {
            return back()->with('error', 'Ruang sudah dibooking pada waktu tersebut!');
        }

        // Calculate booking duration and cost
        $mulai = strtotime($request->jam_mulai);
        $selesai = strtotime($request->jam_selesai);
        $durasi = ceil(($selesai - $mulai) / 3600); // Duration in hours
        $biayaDasar = $durasi * 50000; // Rp. 50.000 per hour

        // Apply priority discount if any
        $diskonPersen = (int)(auth()->user()->prioritas_discount_percent ?? 0);
        $biayaAkhir = (int)round($biayaDasar * (100 - $diskonPersen) / 100);

        $peminjaman = Peminjaman::create([
            'user_id' => Auth::id(),
            'ruang_id' => $request->ruang_id,
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'keperluan' => $request->keperluan,
            'status' => 'pending',
            'biaya' => $biayaAkhir,
            'diskon_persen' => $diskonPersen,
            'status_pembayaran' => 'belum_bayar'
        ]);

        if ($request->hasFile('bukti_pembayaran')) {
            $file = $request->file('bukti_pembayaran');

            // ✅ BLOB PRIMARY: Save to BLOB immediately
            $fileContent = file_get_contents($file->getRealPath());
            $mimeType = $file->getMimeType();
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();

            // Also save to filesystem for backup
            $path = $file->store('bukti_pembayaran', 'public');

            $peminjaman->update([
                'bukti_pembayaran' => $path,
                // ✅ BLOB columns
                'bukti_pembayaran_blob' => $fileContent,
                'bukti_pembayaran_mime' => $mimeType,
                'bukti_pembayaran_name' => $fileName,
                'bukti_pembayaran_size' => $fileSize,
                // Status
                'status_pembayaran' => 'menunggu_verifikasi',
                'waktu_pembayaran' => now()
            ]);
        }
        
        // Create notification for admin/petugas
        Notification::create([
            'peminjaman_id' => $peminjaman->id,
            'type' => 'booking',
            'title' => 'Booking Baru dari ' . auth()->user()->name,
            'message' => 'Peminjaman ' . $peminjaman->ruang->nama_ruang . ' pada ' . date('d M Y', strtotime($peminjaman->tanggal)) . ' jam ' . $peminjaman->jam_mulai . '-' . $peminjaman->jam_selesai,
            'is_read' => false,
        ]);

        return redirect()->route('home')->with('success', 'Pengajuan peminjaman berhasil dibuat!');
    }

    public function jadwal()
    {
        // Use pagination to avoid loading too many records at once and hitting execution limits
        $jadwal = Peminjaman::with('ruang', 'user')
            ->orderBy('tanggal', 'desc')
            ->paginate(50);

        return view('peminjaman.jadwal', compact('jadwal'));
    }

    public function manage()
    {
        // Auto cleanup: Hapus booking yang sudah lewat tanggalnya
        $yesterday = now()->subDay()->format('Y-m-d');
        Peminjaman::where('tanggal', '<', $yesterday)
            ->whereIn('status', ['pending', 'disetujui'])
            ->delete();
        
        // Fetch regular (non-priority) bookings - users with prioritas_level = 0 AND badge = 0
        $peminjaman = Peminjaman::with('ruang', 'user')
            ->whereHas('user', function($q) {
                $q->where('prioritas_level', '=', 0)
                  ->where('badge', '=', 0);
            })
            ->latest()
            ->get();

        // Fetch priority bookings - users with prioritas_level > 0 OR badge > 0
        $prioritas = Peminjaman::with('ruang', 'user')
            ->whereHas('user', function($q) {
                $q->where(function($query) {
                    $query->where('prioritas_level', '>', 0)
                          ->orWhere('badge', '>', 0);
                });
            })
            ->latest()
            ->get();

        return view('peminjaman.manage', compact('peminjaman', 'prioritas'));
    }

    public function verifikasiPembayaran()
    {
        $peminjaman = Peminjaman::with(['ruang', 'user'])
            ->where('status_pembayaran', 'menunggu_verifikasi')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('peminjaman.verifikasi-pembayaran', compact('peminjaman'));
    }

    public function approve($id)
    {
        $pinjam = Peminjaman::findOrFail($id);
        // When approving, also mark the payment as verified
        $pinjam->update([
            'status' => 'disetujui',
            'status_pembayaran' => 'terverifikasi',
            'waktu_pembayaran' => $pinjam->waktu_pembayaran ?? now()
        ]);

        // Update user priority badge if thresholds are met
        try { $pinjam->user?->recalculatePrioritas(); } catch (\Throwable $e) { /* ignore */ }

        return back()->with('success', 'Peminjaman disetujui dan pembayaran ditandai terverifikasi');
    }

    public function reject(Request $request, $id)
    {
        $pinjam = Peminjaman::findOrFail($id);

        $request->validate([
            'alasan_penolakan' => 'nullable|string|max:2000'
        ]);

        $role = auth()->user()->role ?? null;
        $dibatalkanOleh = in_array($role, ['admin','petugas']) ? $role : 'user';

        $pinjam->update([
            'status' => 'ditolak',
            'status_pembayaran' => 'terverifikasi',
            'waktu_pembayaran' => $pinjam->waktu_pembayaran ?? now(),
            'alasan_penolakan' => $request->input('alasan_penolakan'),
            'dibatalkan_oleh' => $dibatalkanOleh,
        ]);

        return back()->with('success', 'Peminjaman ditolak. Keterangan telah disimpan.');
    }

    public function getJadwalByDate($date)
    {
        $peminjaman = Peminjaman::with(['ruang', 'user'])
            ->where('tanggal', $date)
            ->whereIn('status', ['pending', 'disetujui'])
            ->get();

        return response()->json($peminjaman);
    }

    public function detail($id)
    {
        $peminjaman = Peminjaman::with(['ruang', 'user'])
            ->findOrFail($id);
        return response()->json($peminjaman);
    }

    /**
     * Restore a soft-deleted peminjaman (admin/petugas)
     */
    public function restore($id)
    {
        $p = Peminjaman::withTrashed()->findOrFail($id);
        if ($p->trashed()) {
            $p->restore();
            return back()->with('success', 'Booking berhasil dikembalikan (restore).');
        }
        return back()->with('error', 'Booking tidak dalam status terhapus.');
    }

    /**
     * Permanently delete a peminjaman (force delete). Admin only.
     */
    public function forceDelete($id)
    {
        $p = Peminjaman::withTrashed()->findOrFail($id);
        try {
            // attempt to delete associated file if present
            if (!empty($p->bukti_pembayaran) && \Illuminate\Support\Facades\Storage::disk('public')->exists($p->bukti_pembayaran)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($p->bukti_pembayaran);
            }
        } catch (\Throwable $e) {
            // ignore file delete errors
        }
        $p->forceDelete();
        return back()->with('success', 'Booking dihapus permanen.');
    }

    // UPDATE: Show edit form
    public function edit($id)
    {
        $peminjaman = Peminjaman::with(['ruang', 'user'])->findOrFail($id);

        // Only allow editing if status is pending
        if ($peminjaman->status !== 'pending') {
            return back()->with('error', 'Hanya peminjaman dengan status pending yang dapat diedit!');
        }

        // Only allow user to edit their own booking, or admin/petugas can edit any
        if (auth()->id() !== $peminjaman->user_id && !in_array(auth()->user()->role, ['admin', 'petugas'])) {
            return back()->with('error', 'Anda tidak memiliki akses untuk mengedit peminjaman ini!');
        }

        $ruangs = Ruang::all();
        return view('peminjaman.edit', compact('peminjaman', 'ruangs'));
    }

    // UPDATE: Update peminjaman
    public function update(Request $request, $id)
    {
        $peminjaman = Peminjaman::findOrFail($id);

        // Only allow editing if status is pending
        if ($peminjaman->status !== 'pending') {
            return back()->with('error', 'Hanya peminjaman dengan status pending yang dapat diedit!');
        }

        // Only allow user to edit their own booking, or admin/petugas can edit any
        if (auth()->id() !== $peminjaman->user_id && !in_array(auth()->user()->role, ['admin', 'petugas'])) {
            return back()->with('error', 'Anda tidak memiliki akses untuk mengedit peminjaman ini!');
        }

        $request->validate([
            'ruang_id' => 'required|exists:ruang,id',
            'tanggal' => 'required|date|after_or_equal:today',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'keperluan' => 'required|string',
        ], [
            'tanggal.after_or_equal' => 'Tanggal peminjaman tidak boleh sebelum hari ini.'
        ]);

        // Validate jam_selesai must be after jam_mulai
        if (strtotime($request->jam_selesai) <= strtotime($request->jam_mulai)) {
            return back()->withErrors(['jam_selesai' => 'Jam selesai harus lebih besar dari jam mulai.'])->withInput();
        }

        // Check for conflicts (exclude current booking)
        $bentrok = Peminjaman::where('ruang_id', $request->ruang_id)
            ->where('tanggal', $request->tanggal)
            ->where('id', '!=', $id)
            ->whereIn('status', ['pending', 'disetujui'])
            ->where(function($q) use ($request) {
                $q->where(function($q2) use ($request) {
                    $q2->where('jam_mulai', '<', $request->jam_selesai)
                        ->where('jam_selesai', '>', $request->jam_mulai);
                });
            })
            ->exists();

        if ($bentrok) {
            return back()->with('error', 'Ruang sudah dibooking pada waktu tersebut!');
        }

        // Recalculate biaya and apply discount
        $mulai = strtotime($request->jam_mulai);
        $selesai = strtotime($request->jam_selesai);
        $durasi = ceil(($selesai - $mulai) / 3600);
        $biayaDasar = $durasi * 50000;
        // Use the booking owner's priority for discount
        $pemilik = $peminjaman->user()->first();
        $diskonPersen = (int)($pemilik?->prioritas_discount_percent ?? 0);
        $biayaAkhir = (int)round($biayaDasar * (100 - $diskonPersen) / 100);

        $peminjaman->update([
            'ruang_id' => $request->ruang_id,
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'keperluan' => $request->keperluan,
            'biaya' => $biayaAkhir,
            'diskon_persen' => $diskonPersen,
        ]);

        return redirect()->route('home')->with('success', 'Peminjaman berhasil diupdate!');
    }

    public function destroy($id)
    {
        $pinjam = Peminjaman::findOrFail($id);
        $pinjam->delete();
        return back()->with('success', 'Booking berhasil dihapus');
    }

    /**
     * Manual cleanup: delete peminjaman with tanggal before today. Accessible to admin/petugas.
     */
    public function cleanup()
    {
        $today = now()->format('Y-m-d');
        $old = Peminjaman::where('tanggal', '<', $today)->get();
        $count = 0;
        $ids = [];

        foreach ($old as $p) {
            try {
                $p->delete(); // soft-delete
                $ids[] = $p->id;
                $count++;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Log cleanup
        try {
            $logPath = storage_path('logs/peminjaman_cleanup.log');
            $message = '[' . now()->toDateTimeString() . '] Manual cleanup. Soft-deleted: ' . $count . '. IDs: ' . implode(',', $ids) . PHP_EOL;
            file_put_contents($logPath, $message, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ignore
        }

        return back()->with('success', "Cleanup selesai. Soft-deleted {$count} booking lama.");
    }

    /**
     * Generate monthly report of peminjaman per room and total revenue.
     * If the PDF package (barryvdh/laravel-dompdf) is installed, returns a PDF download.
     * Otherwise returns the HTML view so the user can confirm output or install the package.
     *
     * Optional query param: month=YYYY-MM (defaults to current month)
     */
    public function laporan(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        // parse start and end of month
        try {
            $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth()->format('Y-m-d');
            $end = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth()->format('Y-m-d');
        } catch (\Exception $e) {
            // fallback to current month if parse fails
            $start = now()->startOfMonth()->format('Y-m-d');
            $end = now()->endOfMonth()->format('Y-m-d');
            $month = now()->format('Y-m');
        }

        // Aggregate: count peminjaman per ruang and sum revenue only where payment is verified/lunas
        // Assumption: revenue counts peminjaman where status_pembayaran is 'terverifikasi' or 'lunas'
        $stats = DB::table('peminjaman')
            ->select('ruang_id', DB::raw('count(*) as total_peminjaman'), DB::raw("sum(case when status_pembayaran in ('terverifikasi','lunas') then COALESCE(biaya,0) else 0 end) as total_revenue"))
            ->whereBetween('tanggal', [$start, $end])
            ->groupBy('ruang_id')
            ->get();

        // Attach ruang models/names to stats
        $ruangIds = $stats->pluck('ruang_id')->all();
        $ruangs = \App\Models\Ruang::whereIn('id', $ruangIds)->get()->keyBy('id');

        $totalRevenue = 0;
        $totalBookings = 0;
        $rows = [];

        foreach ($stats as $s) {
            $ruang = $ruangs->get($s->ruang_id);
            $rows[] = [
                'ruang' => $ruang ? $ruang->nama_ruang : 'Ruang #' . $s->ruang_id,
                'kapasitas' => $ruang ? ($ruang->kapasitas ?? null) : null,
                'total_peminjaman' => (int) $s->total_peminjaman,
                'total_revenue' => (float) $s->total_revenue,
            ];
            $totalRevenue += (float) $s->total_revenue;
            $totalBookings += (int) $s->total_peminjaman;
        }

        $data = [
            'month' => $month,
            'start' => $start,
            'end' => $end,
            'rows' => $rows,
            'totalRevenue' => $totalRevenue,
            'totalBookings' => $totalBookings,
        ];

        // If request explicitly asks for a print view, return the clean print template (no menu)
        if ($request->query('format') === 'print') {
            return view('peminjaman.laporan_print', $data);
        }

        // If PDF generator (barryvdh/laravel-dompdf) is available, stream a PDF using the clean report view
        try {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('peminjaman.laporan_print', $data)->setPaper('a4', 'portrait');
                $filename = 'laporan-peminjaman-' . $month . '.pdf';
                return $pdf->download($filename);
            }
        } catch (\Throwable $e) {
            // If PDF generation fails, fall through to return HTML view with warning
            \Log::error('PDF generation failed: ' . $e->getMessage());
            $data['pdf_error'] = $e->getMessage();
            return view('peminjaman.laporan', $data);
        }

        // PDF lib not installed — return clean print view so user can print/download
        $data['pdf_missing'] = true;
        return view('peminjaman.laporan_print', $data);
    }
}
