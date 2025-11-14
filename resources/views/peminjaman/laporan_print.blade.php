<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Laporan Peminjaman - {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</title>
  <style>
    /* Minimal print styles */
    body { font-family: Arial, Helvetica, sans-serif; color: #000; margin: 24px; }
    h1 { font-size: 20px; margin-bottom: 0; }
    .meta { margin-bottom: 12px; color: #333; }
    .summary { margin: 12px 0; font-weight: 600; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { padding: 8px 6px; border: 1px solid #ddd; text-align: left; font-size: 12px; }
    th { background: #f5f5f5; font-weight: 700; }
    tfoot td { font-weight: 700; }
    .right { text-align: right; }
    @media print {
      body { margin: 12mm; }
    }
  </style>
</head>
<body>
  <h1>Laporan Peminjaman - {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h1>
  <div class="meta">Periode: {{ $start }} s/d {{ $end }}</div>

  <table>
    <thead>
      <tr>
        <th style="width:4%">#</th>
        <th style="width:44%">Ruang</th>
        <th style="width:18%">Kapasitas</th>
        <th style="width:14%">Jumlah Peminjaman</th>
        <th style="width:20%">Pendapatan (Rp)</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $i => $r)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $r['ruang'] }}</td>
        <td>{{ isset($r['kapasitas']) ? $r['kapasitas'] . ' Orang' : '-' }}</td>
        <td>{{ $r['total_peminjaman'] }}</td>
        <td class="right">{{ number_format($r['total_revenue'], 0, ',', '.') }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="5">Tidak ada data untuk periode ini.</td>
      </tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3">Total</td>
        <td>{{ $totalBookings }}</td>
        <td class="right">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
      </tr>
    </tfoot>
  </table>

  <div style="margin-top:18px; font-size:12px; color:#666">Dicetak: {{ now()->toDateTimeString() }}</div>
</body>
</html>
