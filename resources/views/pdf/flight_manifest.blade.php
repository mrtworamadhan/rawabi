<!DOCTYPE html>
<html>
<head>
    <title>Flight Manifest - {{ $flight->airline }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        
        /* Header Style */
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        h1 { font-size: 18px; margin: 0; text-transform: uppercase; }
        .sub-header { margin-top: 5px; font-size: 12px; color: #555; }
        
        /* Flight Info Box */
        .flight-info { width: 100%; margin-bottom: 15px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; }
        .flight-info td { border: none; padding: 2px 5px; }

        /* Table Style */
        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th, table.data-table td { border: 1px solid #333; padding: 6px; text-align: left; }
        table.data-table th { background-color: #eee; text-align: center; font-weight: bold; }
        
        .pnr-code { font-family: monospace; font-weight: bold; font-size: 12px; }
        .notes { font-style: italic; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    
    <div class="header">
        <h1>FLIGHT MANIFEST</h1>
        <div class="sub-header">
            Group: <strong>{{ $package->name }}</strong>
        </div>
    </div>

    <table class="flight-info">
        <tr>
            <td width="15%"><strong>Maskapai</strong></td>
            <td width="35%">: {{ $flight->airline }} ({{ $flight->flight_number }})</td>
            <td width="15%"><strong>Keberangkatan</strong></td>
            <td>: {{ \Carbon\Carbon::parse($flight->depart_at)->format('d M Y, H:i') }} ({{ $flight->depart_airport }})</td>
        </tr>
        <tr>
            <td><strong>Rute</strong></td>
            <td>: {{ $flight->depart_airport }} ➡ {{ $flight->arrival_airport }}</td>
            <td><strong>Kedatangan</strong></td>
            <td>: {{ \Carbon\Carbon::parse($flight->arrive_at)->format('d M Y, H:i') }} ({{ $flight->arrival_airport }})</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="30%">Nama Jamaah</th>
                <th width="10%">Gender</th>
                <th width="15%">Kode PNR</th>
                <th width="20%">No. Tiket (E-Ticket)</th>
                <th width="20%">Catatan / Seat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
                @php
                    // LOGIC KUNCI: Ambil data flight spesifik dari relasi
                    // $selectedFlightId dikirim dari controller
                    $flightData = $booking->bookingFlights->where('package_flight_id', $selectedFlightId)->first();
                @endphp
            <tr>
                <td style="text-align: center">{{ $loop->iteration }}</td>
                <td>
                    <b>{{ $booking->jamaah->name }}</b><br>
                    <span style="font-size: 9px; color: #555;">Pass: {{ $booking->jamaah->passport_number ?? '-' }}</span>
                </td>
                <td style="text-align: center">{{ ucfirst($booking->jamaah->gender) }}</td>
                
                <td style="text-align: center">
                    @if($flightData && $flightData->pnr_code)
                        <span class="pnr-code">{{ strtoupper($flightData->pnr_code) }}</span>
                    @else
                        -
                    @endif
                </td>
                
                <td>
                    {{ $flightData->ticket_number ?? '-' }}
                </td>
                
                <td>
                    <span class="notes">{{ $flightData->notes ?? '' }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 9px; color: #777; text-align: right;">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>