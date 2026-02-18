<!DOCTYPE html>
<html>
<head>
    <title>Manifest Keberangkatan - {{ $package->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        h1 { font-size: 16px; margin-bottom: 5px; text-transform: uppercase; }
        p { margin: 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #333; padding: 4px; text-align: left; }
        th { background-color: #eee; text-align: center; }
        .text-center { text-align: center; }
        .success { color: green; font-weight: bold; }
        .danger { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 20px;">
        <h1>MANIFEST KEBERANGKATAN</h1>
        <p>Paket: <strong>{{ $package->name }}</strong> | Tanggal: {{ \Carbon\Carbon::parse($package->departure_date)->format('d F Y') }}</p>
        <p>Total Jamaah: {{ count($manifest) }} Pax</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%">No</th>
                <th>Nama Jamaah</th>
                <th>NIK / Gender</th>
                <th>No Paspor</th>
                <th>Expired</th>
                <th>No Visa</th>
                <th width="5%">KTP</th>
                <th width="5%">KK</th>
                <th width="5%">Akta Lahir</th>
                <th width="5%">Buku Nikah</th>
                <th width="15%">Vaksin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($manifest as $row)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>
                    <b>{{ $row->jamaah->name }}</b><br>
                    <span style="color:#777">{{ $row->jamaah->phone }}</span>
                </td>
                <td>
                    {{ $row->jamaah->nik }}<br>
                    {{ ucfirst($row->jamaah->gender) }}
                </td>
                <td>{{ $row->jamaah->passport_number ?? '-' }}</td>
                <td>{{ $row->jamaah->passport_expiry ?? '-' }}</td>
                <td>{{ $row->documentCheck->visa_number ?? '-' }}</td>
                
                <td class="text-center">{!! $row->documentCheck?->ktp ? 'Ada' : '-' !!}</td>
                <td class="text-center">{!! $row->documentCheck?->kk ? 'Ada' : '-' !!}</td>
                <td class="text-center">{!! $row->documentCheck?->akta ? 'Ada' : '-' !!}</td>
                <td class="text-center">{!! $row->documentCheck?->buku_nikah ? 'Ada' : '-' !!}</td>
                
                <td>
                    Vaksin: {{ $row->jamaah->vaccine_status ?? 'Belum' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right;">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>