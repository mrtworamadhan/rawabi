<!DOCTYPE html>
<html>
<head>
    <title>Flight Manifest Recap - {{ $package->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        .page-break { page-break-after: always; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; font-size: 9pt; }
        th { background-color: #f0f0f0; text-align: center; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; text-align: center; }
        .flight-info { background: #eee; padding: 10px; margin-bottom: 10px; font-weight: bold; font-size: 11pt; border: 1px solid #ccc; }
    </style>
</head>
<body>

    @foreach($flights as $flight)
    
    <div class="{{ !$loop->last ? 'page-break' : '' }}">
        <div class="header">
            <h2 style="margin:0;">FLIGHT MANIFEST</h2>
            <p style="margin:5px 0;">Group: {{ $package->name }}</p>
        </div>

        <div class="flight-info">
            {{ $flight->airline }} ({{ $flight->flight_number }}) <br>
            {{ $flight->depart_airport }} ->{{ $flight->arrival_airport }} <br>
            {{ \Carbon\Carbon::parse($flight->depart_at)->format('d M Y, H:i') }}
        </div>

        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="35%">Nama Jamaah</th>
                    <th width="10%">Gender</th>
                    <th width="15%">No. Paspor</th>
                    <th width="15%">No.Tiket</th>
                    <th width="20%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $index => $booking)
                    @php
                        $flightDetail = $booking->bookingFlights
                            ->where('package_flight_id', $flight->id) 
                            ->first();
                    @endphp
                <tr>
                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                    <td>{{ strtoupper($booking->jamaah->name) }}</td>
                    <td style="text-align: center;">{{ $booking->jamaah->gender == 'male' ? 'L' : 'P' }}</td>
                    <td>{{ $booking->jamaah->passport_number ?? '-' }}</td>
                    <td>{{ $flightDetail->ticket_number ?? '-' }}</td>
                    <td>{{ $flightDetail->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div style="margin-top: 20px; font-size: 9pt; color: #555; text-align: right;">
            Total Penumpang: {{ $bookings->count() }} Pax
        </div>
    </div>

    @endforeach

</body>
</html>