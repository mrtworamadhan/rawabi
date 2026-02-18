<!DOCTYPE html>
<html>
<head>
    <title>Rooming List</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h1 { font-size: 16px; margin-bottom: 5px; text-transform: uppercase; }
        .hotel-section { margin-bottom: 20px; page-break-inside: avoid; }
        .hotel-title { background: #eee; padding: 5px; font-weight: bold; border: 1px solid #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { border: 1px solid #333; padding: 4px; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 20px;">
        <h1>ROOMING LIST KEBERANGKATAN</h1>
        <p>Paket: <strong>{{ $package->name }}</strong></p>
    </div>

    @foreach($groupedAssignments as $hotelName => $rooms)
        <div class="hotel-section">
            <div class="hotel-title">HOTEL: {{ $hotelName }}</div>
            
            @php 
                // Group by Room Number inside Hotel
                $groupedRooms = $rooms->groupBy('room_number')->sortKeys(); 
            @endphp

            <table>
                <thead>
                    <tr>
                        <th width="10%">No Kamar</th>
                        <th width="10%">Tipe</th>
                        <th>Nama Jamaah</th>
                        <th width="15%">Gender</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedRooms as $roomNum => $occupants)
                        @foreach($occupants as $index => $row)
                        <tr>
                            @if($index === 0)
                                <td rowspan="{{ count($occupants) }}" style="text-align: center; font-weight: bold;">{{ $roomNum }}</td>
                                <td rowspan="{{ count($occupants) }}" style="text-align: center;">{{ ucfirst($row->room_type) }}</td>
                            @endif
                            <td>{{ $row->booking->jamaah->name }}</td>
                            <td>{{ ucfirst($row->booking->jamaah->gender) }}</td>
                        </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>