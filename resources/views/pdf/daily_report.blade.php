<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kasir Harian</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        h2 { font-size: 14px; margin-bottom: 10px; border-bottom: 1px solid #333; padding-bottom: 5px; margin-top: 20px;}
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #eee; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .summary-box { width: 100%; margin-bottom: 20px; margin-top: 20px; }
        .summary-item { display: inline-block; width: 30%; border: 1px solid #999; padding: 10px; margin-right: 10px; background: #fafafa; }
        .summary-label { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #555; }
        .summary-value { font-size: 16px; font-weight: bold; margin-top: 5px; }
        
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 20px;">
        <h1>LAPORAN HARIAN (CLOSING SHIFT) - RAWABI ZAMZAM</h1>
        <p>Tanggal: {{ $date->format('d F Y') }} | Dicetak Oleh: {{ auth()->user()->name }}</p>
    </div>

    <h2>I. POSISI KEUANGAN (SALDO SAAT INI)</h2>
    <div class="summary-box">
        <div class="summary-item">
            <div class="summary-label">Uang Masuk Hari Ini</div>
            <div class="summary-value">Rp {{ number_format($todayIncome, 0, ',', '.') }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Fisik Laci Kasir (Cash)</div>
            <div class="summary-value">Rp {{ number_format($laciBalance, 0, ',', '.') }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Sisa Petty Cash</div>
            <div class="summary-value">Rp {{ number_format($pettyBalance, 0, ',', '.') }}</div>
        </div>
    </div>

    <h2>II. POSISI SALDO BANK</h2>
    <table>
        <thead>
            <tr>
                <th>Nama Bank / Akun</th>
                <th class="text-right">Uang Masuk Hari Ini</th>
                <th class="text-right">Total Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bankWallets as $bank)
            <tr>
                <td>{{ $bank->name }}</td>
                <td class="text-right text-green-700">+ {{ number_format($bank->payments_sum_amount ?? 0, 0, ',', '.') }}</td>
                <td class="text-right font-bold">Rp {{ number_format($bank->balance, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2>III. RINCIAN PENGELUARAN (EXPENSES)</h2>
    <table>
        <thead>
            <tr>
                <th>Jam</th>
                <th>Sumber Dana</th>
                <th>Kategori</th>
                <th>Keperluan</th>
                <th>Oleh</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $exp)
            <tr>
                <td class="text-center">{{ $exp->created_at->format('H:i') }}</td>
                <td>{{ $exp->wallet->name ?? '-' }}</td>
                <td>{{ $exp->category->name ?? '-' }}</td>
                <td>{{ $exp->name }}</td>
                <td>{{ $exp->approver->name ?? '-' }}</td>
                <td class="text-right">{{ number_format($exp->amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Tidak ada pengeluaran hari ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL OPERASIONAL (PETTY CASH)</td>
                <td class="text-right">Rp {{ number_format($expenseOperasional, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL HPP/MODAL (KAS BESAR)</td>
                <td class="text-right">Rp {{ number_format($expenseHpp, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row" style="background-color: #ddd;">
                <td colspan="5" class="text-right">GRAND TOTAL KELUAR</td>
                <td class="text-right">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table style="margin-top: 40px; border: none;">
        <tr style="border: none;">
            <td style="border: none; text-align: center; width: 33%;">
                <p>Dibuat Oleh,</p>
                <br><br><br>
                <b>({{ auth()->user()->name }})</b><br>
                {{ auth()->user()->employee->position }}
            </td>
            <td style="border: none; width: 33%;"></td>
            <td style="border: none; text-align: center; width: 33%;">
                <p>Diperiksa Oleh,</p>
                <br><br><br>
                <b>(____________________)</b><br>
            </td>
        </tr>
    </table>
</body>
</html>