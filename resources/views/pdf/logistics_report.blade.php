<!DOCTYPE html>
<html>
<head>
    <title>Laporan Serah Terima Logistik</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h1 { font-size: 16px; margin-bottom: 5px; text-transform: uppercase; text-align: center; }
        h2 { font-size: 14px; margin-top: 30px; margin-bottom: 10px; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 5px; }
        p { margin: 0; color: #555; text-align: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; vertical-align: middle; }
        th { background-color: #eee; text-align: center; font-weight: bold; }
        
        .items-list { margin: 0; padding-left: 15px; }
        
        /* REVISI UKURAN TTD */
        .signature-img { height: 60px; width: auto; display: block; margin: 0 auto; }
        
        .status-pending { color: red; font-style: italic; text-align: center; }
    </style>
</head>
<body>
    <div>
        <h1>BERITA ACARA SERAH TERIMA LOGISTIK</h1>
        <p>Paket: <strong>{{ $package->name }}</strong> | Tanggal Cetak: {{ now()->format('d F Y') }}</p>
    </div>

    @php
        // PISAHKAN DATA
        $sudahAmbil = $logistics->filter(fn($row) => $row->inventoryMovements->isNotEmpty());
        $belumAmbil = $logistics->filter(fn($row) => $row->inventoryMovements->isEmpty());
    @endphp

    <h2>I. DAFTAR JAMAAH SUDAH MENERIMA BARANG ({{ $sudahAmbil->count() }} Orang)</h2>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="25%">Nama Jamaah</th>
                <th>Barang Yang Diterima</th>
                <th width="15%">Penerima / Tgl</th>
                <th width="10%">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sudahAmbil as $row)
                @php
                    $firstMove = $row->inventoryMovements->first();
                @endphp
            <tr>
                <td style="text-align: center;">{{ $loop->iteration }}</td>
                <td>
                    <b>{{ $row->jamaah->name }}</b><br>
                    <span style="color:#777">{{ ucfirst($row->jamaah->gender) }}</span>
                </td>
                <td>
                    <ul class="items-list">
                        @foreach($row->inventoryMovements as $move)
                            <li>{{ $move->inventoryItem->name }}</li>
                        @endforeach
                    </ul>
                </td>
                <td>
                    {{ $firstMove->receiver_name ?? '-' }}<br>
                    <span style="font-size: 9px; color: #666;">
                        {{ $firstMove->taken_date ? $firstMove->taken_date->format('d/m/Y') : '-' }}
                    </span>
                </td>
                <td style="text-align: center; padding: 2px;">
                    @if($firstMove && $firstMove->signature_file)
                        <img src="{{ public_path('storage/' . $firstMove->signature_file) }}" class="signature-img">
                    @else
                        <span style="color:#ccc; font-style: italic; font-size: 10px;">(Manual Signature)</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 20px;">Belum ada data pengambilan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($belumAmbil->isNotEmpty())
        <div style="page-break-inside: avoid;">
            <h2>II. DAFTAR JAMAAH BELUM MENGAMBIL BARANG ({{ $belumAmbil->count() }} Orang)</h2>
            <table>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="40%">Nama Jamaah</th>
                        <th width="20%">Gender</th>
                        <th width="35%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($belumAmbil as $row)
                    <tr>
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>
                            <b>{{ $row->jamaah->name }}</b>
                        </td>
                        <td style="text-align: center;">{{ ucfirst($row->jamaah->gender) }}</td>
                        <td class="status-pending">Belum Ada Pengambilan</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div style="margin-top: 30px; width: 100%;">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 70%;"></td>
                <td style="border: none; text-align: center;">
                    <p>Mengetahui,</p>
                    <br><br><br>
                    <b>( Tim Logistik )</b>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>