<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Ruang;
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
        return view('home', compact('peminjaman'));
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
        $biaya = $durasi * 50000; // Rp. 50.000 per hour

        $peminjaman = Peminjaman::create([
            'user_id' => Auth::id(),
            'ruang_id' => $request->ruang_id,
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'keperluan' => $request->keperluan,
            'status' => 'pending',
            'biaya' => $biaya,
            'status_pembayaran' => 'belum_bayar'
        ]);

        if ($request->hasFile('bukti_pembayaran')) {
            $file = $request->file('bukti_pembayaran');

            // Store the uploaded file in the public disk under bukti_pembayaran
            // This keeps files in storage/app/public/bukti_pembayaran and allows serving via /storage
            $path = $file->store('bukti_pembayaran', 'public');

            $peminjaman->update([
                'bukti_pembayaran' => $path, // stores like 'bukti_pembayaran/filename.jpg'
                'status_pembayaran' => 'menunggu_verifikasi',
                'waktu_pembayaran' => now()
            ]);
        }

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
        $peminjaman = Peminjaman::with('ruang', 'user')
            ->where('status', '!=', 'disetujui')
            ->latest()
            ->get();
        return view('peminjaman.manage', compact('peminjaman'));
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

        // If PDF generator (barryvdh/laravel-dompdf) is available, stream a PDF.
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) || class_exists(\Barryvdh\DomPDF\PDF::class) || class_exists('\PDF')) {
            try {
                // Prefer the facade alias PDF if available
                if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('peminjaman.laporan', $data)->setPaper('a4', 'portrait');
                } else if (class_exists('\PDF')) {
                    $pdf = \PDF::loadView('peminjaman.laporan', $data)->setPaper('a4', 'portrait');
                } else {
                    // fallback to the other class name
                    $pdf = \Barryvdh\DomPDF\PDF::loadView('peminjaman.laporan', $data)->setPaper('a4', 'portrait');
                }
                $filename = 'laporan-peminjaman-' . $month . '.pdf';
                return $pdf->download($filename);
            } catch (\Throwable $e) {
                // If PDF generation fails, fall through to return HTML view with warning
                \Log::error('PDF generation failed: ' . $e->getMessage());
                $data['pdf_error'] = $e->getMessage();
                return view('peminjaman.laporan', $data);
            }
        }

        // PDF lib not installed — return HTML so user can preview the report and install the package.
        $data['pdf_missing'] = true;
        return view('peminjaman.laporan', $data);
    }
}
