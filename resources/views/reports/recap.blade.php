<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Saldo {{ $kelas->nama_kelas }}</title>
    <style>
        body{font:13px Arial,sans-serif;color:#1e293b;max-width:1000px;margin:32px auto;padding:0 20px}
        h1{font-size:22px;margin-bottom:4px}p{line-height:1.6}.muted{color:#64748b}
        table{width:100%;border-collapse:collapse;margin-top:16px}td,th{padding:8px;border:1px solid #cbd5e1;text-align:left}
        th{background:#f1f5f9;font-size:11px;text-transform:uppercase}.right{text-align:right;white-space:nowrap}
        tfoot td{font-weight:bold;background:#f8fafc}tr{break-inside:avoid}thead{display:table-header-group}
        .signature{margin:36px 0 0 auto;width:240px;text-align:center;break-inside:avoid}
        button{padding:12px 20px;border:0;border-radius:8px;background:#4f46e5;color:white;cursor:pointer}
        @media print{body{margin:0;padding:0}.no-print{display:none}@page{size:A4;margin:15mm}}
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Cetak / Simpan PDF</button>
    <p class="muted">{{ $school }}</p>
    <h1>Rekap Saldo Tabungan per Kelas</h1>
    <p><strong>{{ $kelas->label() }}</strong> · {{ $rows->count() }} siswa</p>
    <table>
        <thead><tr><th>No</th><th>NIS</th><th>Nama siswa</th><th>Status</th><th class="right">Setoran</th><th class="right">Penarikan</th><th class="right">Saldo</th></tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row['nis'] }}</td>
                    <td>{{ $row['nama'] }}</td>
                    <td>{{ ucfirst($row['status']) }}</td>
                    <td class="right">{{ number_format($row['masuk'], 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($row['keluar'], 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada siswa di kelas ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="4">Total</td><td class="right">{{ number_format($rows->sum('masuk'), 0, ',', '.') }}</td><td class="right">{{ number_format($rows->sum('keluar'), 0, ',', '.') }}</td><td class="right">{{ number_format($rows->sum('saldo'), 0, ',', '.') }}</td></tr>
        </tfoot>
    </table>
    <p class="muted">Nominal dalam rupiah. Pengajuan penarikan yang belum disetujui tidak dihitung.</p>
    <div class="signature"><p>Dicetak {{ now()->format('d/m/Y H:i') }}<br>Pengelola tabungan</p><p style="margin-top:65px">{{ $teacher }}</p></div>
</body>
</html>
