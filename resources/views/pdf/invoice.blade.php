<!DOCTYPE html>
<html>
<head>
    <title>Kuitansi Pembayaran</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #444; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #1a5f7a; }
        .company-info { font-size: 12px; color: #666; margin-top: 5px; }
        
        .title { text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 20px; text-decoration: underline; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        td { padding: 5px; vertical-align: top; }
        .label { width: 150px; font-weight: bold; }
        
        .amount-box { background: #f0f0f0; padding: 10px; border: 1px solid #ddd; font-weight: bold; font-size: 16px; }
        .terbilang { font-style: italic; font-size: 12px; margin-top: 5px; color: #555; }
        
        .footer { margin-top: 40px; width: 100%; }
        .signature { text-align: center; width: 40%; float: right; }
        .signature-line { border-top: 1px solid #333; margin-top: 60px; }
        
        .note { font-size: 10px; color: #888; margin-top: 50px; border-top: 1px dashed #ccc; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">RAWABI TRAVEL</div>
        <div class="company-info">
            Izin PPIU No. 123/2024 | Jl. Contoh No. 123, Jakarta Selatan<br>
            Telp: (021) 123-4567 | Email: info@rawabitravel.com
        </div>
    </div>

    <div class="title">Kuitansi Pembayaran</div>

    <table>
        <tr>
            <td class="label">No. Kuitansi</td>
            <td>: #INV-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td>
            <td class="label">Tanggal</td>
            <td>: {{ $payment->created_at->format('d F Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Sudah Terima Dari</td>
            <td colspan="3">: <strong>{{ $payment->booking->jamaah->name ?? 'Jamaah Umum' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Kode Booking</td>
            <td colspan="3">: {{ $payment->booking->booking_code }} (Paket: {{ $payment->booking->umrahPackage->name ?? '-' }})</td>
        </tr>
        <tr>
            <td class="label">Pembayaran</td>
            <td colspan="3">: {{ ucfirst($payment->type) }} - {{ $payment->method == 'cash' ? 'Tunai' : 'Transfer Bank' }}</td>
        </tr>
        <tr>
            <td class="label">Keterangan</td>
            <td colspan="3">: Pembayaran Umrah</td>
        </tr>
    </table>

    <div class="amount-box">
        Nominal: Rp {{ number_format($payment->amount, 0, ',', '.') }}
        <div class="terbilang">Terbilang: {{ $terbilang }}</div>
    </div>

    <table style="margin-top: 15px; width: 50%;">
        <tr>
            <td class="label">Sisa Tagihan</td>
            <td>: Rp {{ number_format($sisaTagihan < 0 ? 0 : $sisaTagihan, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer">
        <div class="signature">
            <p>Jakarta, {{ now()->format('d F Y') }}</p>
            <p>Penerima,</p>
            <div class="signature-line"></div>
            <p>{{ auth()->user()->name }}</p>
        </div>
    </div>

    <div class="note">
        Catatan: Pembayaran dianggap sah jika sudah divalidasi dan mendapatkan kuitansi ini.
        Simpan bukti ini sebagai syarat pelunasan/keberangkatan.
    </div>
</body>
</html>