<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;
use App\Models\UmrahPackage;
use App\Models\Booking;
use App\Models\Jamaah;
use App\Models\Rundown;
use App\Models\RoomAssignment;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PackageHotel;
use App\Models\BookingFlight;
use App\Models\PackageFlight;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\MediaAsset;
use App\Models\ContentRequest;

new #[Layout('layouts::app')] class extends Component
{
    use WithFileUploads;

    // --- STATE ---
    public $activeTab = 'manifest';
    public $selectedPackageId = null;

    public $selectedBookingIds = []; 

    public $selectedHotelName = null;
    public $newRoomNumber;
    public $newRoomType = 'quad';
    public $newRoomHotel = ''; 

    public $bulkPnr = '';
    public $showPnrModal = false;

    public $activeLogisticsJamaahId = null;
    public $logisticsItems = [];
    public $signature = null;
    public $receiverName = '';

    public $selectedFlightId = null;
    
    // Upload
    public $uploads = []; 

    public $showMediaModal = false;
    public $mediaTab = 'upload';
    
    // State Upload
    public $mediaPhotos = [];
    public $mediaTags;
    
    // State Request
    public $reqTitle, $reqDesc, $reqDeadline, $reqPriority = 'medium';

    // RUNDOWN STATE
    public $rundownPhase = 'during';
    public $rdDay, $rdTime, $rdActivity, $rdLoc, $rdDesc, $rdId;
    public $showRundownModal = false;

    public $rdDate;

    public $showMassRundownModal = false;
    public $massRows = [];

    // --- INIT ---
    public function mount()
    {
        $latestPackage = UmrahPackage::latest('departure_date')->first();
        if ($latestPackage) {
            $this->selectedPackageId = $latestPackage->id;
        }
    }

    // --- COMPUTED MANIFEST ---
    public function getPackagesProperty()
    {
        return UmrahPackage::orderBy('departure_date', 'desc')->get();
    }

    public function getSelectedPackageProperty()
    {
        return UmrahPackage::find($this->selectedPackageId);
    }

    public function getManifestDataProperty()
    {
        if (!$this->selectedPackageId) return [];

        return Booking::with(['jamaah', 'documentCheck', 'bookingFlights'])
            ->where('umrah_package_id', $this->selectedPackageId)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sortBy(fn($b) => $b->jamaah->name); 
    }

    // --- COMPUTED ROOMING ---

    public function getHotelListProperty()
    {
        if (!$this->selectedPackageId) return [];
        return PackageHotel::where('umrah_package_id', $this->selectedPackageId)->get();
    }
    
    public function getUnassignedJamaahProperty()
    {
        if (!$this->selectedPackageId) return [];
                
        $assignedQuery = RoomAssignment::where('umrah_package_id', $this->selectedPackageId);
        
        if ($this->selectedHotelName) {
            $assignedQuery->where('hotel_name', $this->selectedHotelName);
        }
        
        $assignedBookingIds = $assignedQuery->pluck('booking_id')->toArray();

        return Booking::with(['jamaah'])
            ->where('umrah_package_id', $this->selectedPackageId)
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('id', $assignedBookingIds) 
            ->get()
            ->sortBy(fn($b) => $b->jamaah->name);
    }

    public function getRoomsProperty()
    {
        if (!$this->selectedPackageId || !$this->selectedHotelName) return [];

        return RoomAssignment::with(['booking.jamaah'])
            ->where('umrah_package_id', $this->selectedPackageId)
            ->where('hotel_name', $this->selectedHotelName) 
            ->get()
            ->groupBy('room_number')
            ->sortKeys();
    }

    public function getFlightListProperty()
    {
        if (!$this->selectedPackageId) return [];
        return PackageFlight::where('umrah_package_id', $this->selectedPackageId)
            ->orderBy('depart_at', 'asc')
            ->get();
    }

    // --- COMPUTED: RUNDOWN ---
    public function getRundownsProperty()
    {
        if(!$this->selectedPackageId) return collect();
        
        $query = Rundown::where('umrah_package_id', $this->selectedPackageId)
            ->where('phase', $this->rundownPhase);

        if ($this->rundownPhase === 'during') {
            $query->orderBy('day_number')->orderBy('time_start');
            return $query->get()->groupBy('day_number');
        } else {
            $query->orderBy('date')->orderBy('time_start');
            return $query->get()->groupBy(function($item) {
                return $item->date ? \Carbon\Carbon::parse($item->date)->format('d M Y') : 'Hari ke-'.$item->day_number;
            });
        }
    }

    // --- COMPUTED: LOGISTICS ---

    public function getInventoryItemsProperty()
    {
        if ($this->activeLogisticsJamaahId) {
            $booking = Booking::with('jamaah')->find($this->activeLogisticsJamaahId);
            
            if ($booking && $booking->jamaah) {
                $gender = strtolower($booking->jamaah->gender); 
                
                return InventoryItem::whereIn('type', ['umum', $gender])
                    ->orderBy('type', 'asc') 
                    ->get();
            }
        }

        return InventoryItem::orderBy('type', 'asc')->get();
    }

    public function getLogisticsDataProperty()
    {
        if (!$this->selectedPackageId) return [];

        return Booking::with(['jamaah', 'inventoryMovements.inventoryItem'])
            ->where('umrah_package_id', $this->selectedPackageId)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sortBy(fn($b) => $b->jamaah->name);
    }

    public function getActiveJamaahLogisticsProperty()
    {
        if (!$this->activeLogisticsJamaahId) return null;
        return Booking::with(['jamaah', 'inventoryMovements'])->find($this->activeLogisticsJamaahId);
    }

    // --- ACTIONS: ROOMING MANAGEMENT ---

    public function updatedSelectedPackageId()
    {
        $this->selectedHotelName = null;
        $this->selectedFlightId = null;
        $this->selectedBookingIds = [];
    }

    public function createRoom()
    {
        $this->validate([
            'newRoomNumber' => 'required|string|max:10',
            'selectedHotelName' => 'required',
        ]);

        if (empty($this->selectedBookingIds)) {
            Notification::make()->title('Pilih jamaah dulu!')->warning()->send();
            return;
        }

        foreach ($this->selectedBookingIds as $bookingId) {
            RoomAssignment::create([
                'umrah_package_id' => $this->selectedPackageId,
                'booking_id'       => $bookingId,
                'room_number'      => $this->newRoomNumber,
                'room_type'        => $this->newRoomType,
                'hotel_name'       => $this->selectedHotelName,
            ]);
        }

        $this->reset(['selectedBookingIds', 'newRoomNumber']);
        Notification::make()->title('Jamaah masuk kamar ' . $this->selectedHotelName)->success()->send();
    }

    public function assignToExistingRoom($roomNumber, $roomType)
    {
        if (empty($this->selectedBookingIds)) {
            Notification::make()->title('Pilih jamaah dulu!')->warning()->send();
            return;
        }

        foreach ($this->selectedBookingIds as $bookingId) {
            RoomAssignment::create([
                'umrah_package_id' => $this->selectedPackageId,
                'booking_id'       => $bookingId,
                'room_number'      => $roomNumber,
                'room_type'        => $roomType,
            ]);
        }

        $this->reset(['selectedBookingIds']);
        Notification::make()->title('Jamaah ditambahkan')->success()->send();
    }

    public function removeFromRoom($assignmentId)
    {
        RoomAssignment::find($assignmentId)?->delete();
        Notification::make()->title('Jamaah dikeluarkan dari kamar')->success()->send();
    }

    public function deleteRoom($roomNumber)
    {
        RoomAssignment::where('umrah_package_id', $this->selectedPackageId)
            ->where('hotel_name', $this->selectedHotelName)
            ->where('room_number', $roomNumber)
            ->delete();

        Notification::make()->title('Kamar dihapus')->success()->send();
    }

    // --- ACTIONS: BULK PNR (MODAL) ---

    public function askToApplyBulkPnr()
    {
        if (empty($this->bulkPnr)) {
            Notification::make()->title('Isi Kode PNR dulu!')->warning()->send();
            return;
        }
        
        $this->showPnrModal = true;
    }

    public function processBulkPnr()
    {
        if (empty($this->bulkPnr) || !$this->selectedFlightId) return;

        $bookings = Booking::where('umrah_package_id', $this->selectedPackageId)
            ->where('status', '!=', 'cancelled')
            ->pluck('id');

        foreach ($bookings as $bookingId) {
            $flightData = BookingFlight::firstOrCreate([
                'booking_id' => $bookingId,
                'package_flight_id' => $this->selectedFlightId
            ]);

            if (empty($flightData->pnr_code)) {
                $flightData->update(['pnr_code' => $this->bulkPnr]);
            }
        }
            
        Notification::make()->title('PNR Massal Applied ke Penerbangan Terpilih')->success()->send();
        
        $this->bulkPnr = '';
        $this->showPnrModal = false;
    }

    // --- ACTIONS: AIRLINES / FLIGHTS---

    public function updateFlightField($bookingId, $field, $value)
    {
        if (!$this->selectedFlightId) {
            Notification::make()->title('Pilih Penerbangan Dulu!')->warning()->send();
            return;
        }

        BookingFlight::updateOrCreate(
            [
                'booking_id' => $bookingId,
                'package_flight_id' => $this->selectedFlightId
            ],
            [
                $field => $value
            ]
        );

        Notification::make()->title('Data Tersimpan')->success()->duration(500)->send();
    }

    // Bulk Update PNR (Fitur Keren)
    
    public function applyBulkPnr()
    {
        if(!$this->bulkPnr) return;

        Booking::where('umrah_package_id', $this->selectedPackageId)
            ->whereNull('airline_pnr')
            ->update(['airline_pnr' => $this->bulkPnr]);
            
        Notification::make()->title('PNR Massal Berhasil Diaplay')->success()->send();
        $this->bulkPnr = '';
    }

    // --- ACTIONS: INLINE EDITING ---
    
    public function updateJamaahField($jamaahId, $field, $value)
    {
        $jamaah = Jamaah::find($jamaahId);
        
        if ($jamaah) {
            if ($field === 'passport_expiry' && $value) {
                // Contoh: Cek expiry date warning kalau < 6 bulan (bisa ditambah nanti)
            }

            $jamaah->update([$field => $value]);

            Notification::make()
                ->title('Data Tersimpan')
                ->success()
                ->duration(1000)
                ->send();
        }
    }

    // --- ACTIONS: DOCUMENT CHECKLIST ---
    
    public function toggleDocument($bookingId, $field)
    {
        $allowedFields = ['ktp', 'kk', 'akta', 'buku_nikah', 'passport']; 
                
        $booking = Booking::find($bookingId);
        
        if ($booking) {
            $doc = $booking->documentCheck ?? $booking->documentCheck()->create([]);

            if ($field === 'passport') {
                $newStatus = ($doc->passport_status === 'received') ? 'missing' : 'received';
                $doc->update(['passport_status' => $newStatus]);
            } elseif (in_array($field, $allowedFields)) {
                $doc->update([
                    $field => ! $doc->$field
                ]);
            }
        }
    }

    // --- ACTIONS: FILE UPLOAD ---
    
    // Upload Paspor
    public function updatedUploads($value, $key)
    {

        $parts = explode('.', $key);
        
        if (count($parts) < 2) return;

        $type = $parts[0]; 
        $jamaahId = $parts[1];

        $jamaah = Jamaah::find($jamaahId);
        
        if ($jamaah && $value) {
            
            $path = $value->store('jamaah-documents/passport', 'public');
            
            $jamaah->update([
                $type => $path
            ]);

            Notification::make()
                ->title('Passport Berhasil Diupload')
                ->success()
                ->duration(2000)
                ->send();
        }
    }

    public function updateDocumentField($bookingId, $field, $value)
    {
        $booking = Booking::find($bookingId);
        
        if ($booking) {
            $doc = $booking->documentCheck ?? $booking->documentCheck()->create([]);

            $doc->update([$field => $value]);

            Notification::make()->title('Data Visa Tersimpan')->success()->duration(1000)->send();
        }
    }

    public function setTab($tab) 
    { 
        $this->activeTab = $tab; 
    }

    // ACTION: SIMPAN KEGIATAN
    public function saveRundown()
    {
        $rules = [
            'rdActivity' => 'required',
        ];

        if ($this->rundownPhase === 'during') {
            $rules['rdDay'] = 'required|numeric';
        } else {
            $rules['rdDate'] = 'required|date'; 
        }

        $this->validate($rules);

        $data = [
            'umrah_package_id' => $this->selectedPackageId,
            'phase' => $this->rundownPhase,
            
            'day_number' => ($this->rundownPhase === 'during') ? $this->rdDay : 0,
            
            'date' => ($this->rundownPhase !== 'during') ? $this->rdDate : null,
            
            'time_start' => $this->rdTime,
            'activity' => $this->rdActivity,
            'location' => $this->rdLoc,
            'description' => $this->rdDesc,
        ];

        if($this->rdId) {
            Rundown::find($this->rdId)->update($data);
        } else {
            Rundown::create($data);
        }

        $this->reset(['rdId', 'rdActivity', 'rdTime', 'rdLoc', 'rdDesc', 'rdDate', 'rdDay']);
        $this->showRundownModal = false;
        Notification::make()->title('Rundown Disimpan')->success()->send();
    }

    public function editRundown($id)
    {
        $r = Rundown::find($id);
        $this->rdId = $r->id;
        $this->rdDay = $r->day_number;
        $this->rdDate = $r->date;
        $this->rdTime = $r->time_start; 
        $this->rdActivity = $r->activity;
        $this->rdLoc = $r->location;
        $this->rdDesc = $r->description;
        $this->showRundownModal = true;
    }

    public function deleteRundown($id)
    {
        Rundown::find($id)->delete();
        Notification::make()->title('Dihapus')->success()->send();
    }

    public function openMassRundown()
    {
        $this->massRows = [];
        for ($i = 0; $i < 3; $i++) {
            $this->addMassRow();
        }
        $this->showMassRundownModal = true;
    }

    // 2. TAMBAH BARIS BARU
    public function addMassRow()
    {
        $this->massRows[] = [
            'day_or_date' => $this->rundownPhase === 'during' ? 1 : date('Y-m-d'), 
            'time_start' => '08:00',
            'activity' => '',
            'location' => '',
            'description' => ''
        ];
    }

    // 3. HAPUS BARIS
    public function removeMassRow($index)
    {
        unset($this->massRows[$index]);
        $this->massRows = array_values($this->massRows); 
    }

    // 4. SIMPAN SEMUA SEKALIGUS
    public function saveMassRundown()
    {
        $this->validate([
            'massRows.*.activity' => 'required',
            'massRows.*.time_start' => 'required',
        ]);

        foreach ($this->massRows as $row) {
            if (empty($row['activity'])) continue;

            Rundown::create([
                'umrah_package_id' => $this->selectedPackageId,
                'phase' => $this->rundownPhase,
                'day_number' => ($this->rundownPhase === 'during') ? $row['day_or_date'] : 0,
                'date' => ($this->rundownPhase !== 'during') ? $row['day_or_date'] : null,
                'time_start' => $row['time_start'],
                'activity' => $row['activity'],
                'location' => $row['location'],
                'description' => $row['description'],
            ]);
        }

        $this->showMassRundownModal = false;
        $this->reset('massRows');
        Notification::make()->title('Data Masal Berhasil Disimpan 🚀')->success()->send();
    }

    // --- ACTIONS: LOGISTICS ---

    public function selectJamaahForLogistics($bookingId)
    {
        $this->activeLogisticsJamaahId = $bookingId;
        $this->signature = null;
        $this->logisticsItems = [];
        
        $booking = Booking::find($bookingId);
        $this->receiverName = $booking->jamaah->name ?? '';
    }

    public function saveHandover()
    {
        $this->validate([
            'activeLogisticsJamaahId' => 'required',
            'logisticsItems' => 'required|array|min:1',
            'receiverName' => 'required',
            // 'signature' => 'required', 
        ]);

        $booking = Booking::find($this->activeLogisticsJamaahId);

        $signaturePath = null;
        if ($this->signature) {
            $image = $this->signature;  
            $image = str_replace('data:image/png;base64,', '', $image);
            $image = str_replace(' ', '+', $image);
            $imageName = 'signatures/handover-' . $booking->id . '-' . time() . '.png';
            
            \Illuminate\Support\Facades\Storage::disk('public')->put($imageName, base64_decode($image));
            $signaturePath = $imageName;
        }

        foreach ($this->logisticsItems as $itemId) {
            
            $exists = InventoryMovement::where('booking_id', $booking->id)
                ->where('inventory_item_id', $itemId)
                ->exists();

            if (!$exists) {
                InventoryMovement::create([
                    'booking_id' => $booking->id,
                    'inventory_item_id' => $itemId,
                    'quantity' => 1,
                    'taken_date' => now(),
                    'receiver_name' => $this->receiverName,
                    'signature_file' => $signaturePath,
                ]);

                InventoryItem::find($itemId)->decrement('stock_quantity', 1);
            }
        }

        Notification::make()->title('Serah Terima Berhasil')->success()->send();
        
        $this->activeLogisticsJamaahId = null;
        $this->signature = null;
        $this->logisticsItems = [];
    }

    public $bulkLogisticsItem = null;
    
    public function saveBulkHandover()
    {
        if (empty($this->selectedBookingIds) || !$this->bulkLogisticsItem) {
            Notification::make()->title('Pilih Jamaah & Barang dulu!')->warning()->send();
            return;
        }

        $item = InventoryItem::find($this->bulkLogisticsItem);
        $count = 0;

        foreach ($this->selectedBookingIds as $bookingId) {
            $exists = InventoryMovement::where('booking_id', $bookingId)
                ->where('inventory_item_id', $item->id)
                ->exists();

            if (!$exists) {
                InventoryMovement::create([
                    'booking_id' => $bookingId,
                    'inventory_item_id' => $item->id,
                    'quantity' => 1,
                    'taken_date' => now(),
                    'receiver_name' => 'Mass Distribution',
                ]);
                $item->decrement('stock_quantity', 1);
                $count++;
            }
        }

        Notification::make()->title("$count Jamaah menerima {$item->name}")->success()->send();
        $this->selectedBookingIds = []; 
    }

    // --- ACTIONS: EXPORT EXCEL (CSV) ---

    public function exportExcel()
    {
        if (!$this->selectedPackage) return;

        $fileName = 'Manifest-' . Str::slug($this->selectedPackage->name) . '.csv';        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['No', 'Nama Jamaah', 'NIK', 'Gender', 'No Paspor', 'Paspor Expired', 'Vaksin', 'Status Visa', 'No Visa', 'KTP', 'KK', 'Buku Nikah'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($this->manifestData as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row->jamaah->name,
                    $row->jamaah->nik ?? '-',
                    $row->jamaah->gender,
                    $row->jamaah->passport_number ?? '-',
                    $row->jamaah->passport_expiry ?? '-',
                    $row->jamaah->vaccine_status ?? '-',
                    $row->documentCheck->visa_status ?? 'pending',
                    $row->documentCheck->visa_number ?? '-',
                    $row->documentCheck?->ktp ? 'Ada' : 'Belum',
                    $row->documentCheck?->kk ? 'Ada' : 'Belum',
                    $row->documentCheck?->buku_nikah ? 'Ada' : 'Belum',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- ACTIONS: EXPORT PDF ---
    public function exportPdf()
    {
        if (!$this->selectedPackage) return;

        $data = [
            'package' => $this->selectedPackage,
            'manifest' => $this->manifestData
        ];

        $pdf = Pdf::loadView('pdf.manifest_print', $data);
        $pdf->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Manifest-' . Str::slug($this->selectedPackage->name) . '.pdf');
    }

    // --- ACTIONS: EXPORT ROOMING & FLIGHT ---

    public function exportRoomingPdf()
    {
        if (!$this->selectedPackage) return;

        $assignments = RoomAssignment::with(['booking.jamaah'])
            ->where('umrah_package_id', $this->selectedPackageId)
            ->get()
            ->groupBy('hotel_name');

        $pdf = Pdf::loadView('pdf.rooming_list', [
            'package' => $this->selectedPackage,
            'groupedAssignments' => $assignments
        ]);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Rooming-List-' . Str::slug($this->selectedPackage->name) . '.pdf');
    }

    public function exportFlightPdf()
    {
        if (!$this->selectedPackage) return;
        
        $allFlights = PackageFlight::where('umrah_package_id', $this->selectedPackageId)
            ->orderBy('depart_at', 'asc')
            ->get();

        if ($allFlights->isEmpty()) {
            Notification::make()->title('Belum ada jadwal penerbangan di paket ini!')->warning()->send();
            return;
        }

        $data = [
            'package'  => $this->selectedPackage,
            'flights'  => $allFlights,
            'bookings' => $this->manifestData,
        ];

        $pdf = Pdf::loadView('pdf.flight_manifest', $data);
        $pdf->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Flight-Manifest-All-' . Str::slug ($this->selectedPackage->name) . '.pdf');
    }
    
    // Export Excel (CSV Sederhana)
    public function exportFlightExcel()
    {
         if (!$this->selectedFlightId) {
             Notification::make()->title('Pilih Penerbangan Dulu!')->warning()->send();
             return;
         }
         
         $flightInfo = PackageFlight::find($this->selectedFlightId);
         $fileName = 'Manifest-' . $flightInfo->airline . '-' . $flightInfo->flight_number . '.csv';

         $headers = [ "Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$fileName", "Pragma" => "no-cache", "Expires" => "0" ];
         $columns = ['No', 'Nama Jamaah', 'PNR', 'No Tiket', 'Notes'];

         $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            foreach ($this->manifestData as $index => $row) {
                $flightData = $row->bookingFlights->where('package_flight_id', $this->selectedFlightId)->first();
                
                fputcsv($file, [
                    $index + 1, 
                    $row->jamaah->name, 
                    $flightData->pnr_code ?? '', 
                    $flightData->ticket_number ?? '', 
                    $flightData->notes ?? ''
                ]);
            }
            fclose($file);
         };
         return response()->stream($callback, 200, $headers);
    }

    // Export Logistic
    public function exportLogisticsPdf()
    {
        if (!$this->selectedPackageId) return;

        $data = Booking::with(['jamaah', 'inventoryMovements.inventoryItem'])
            ->where('umrah_package_id', $this->selectedPackageId)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sortBy(fn($b) => $b->jamaah->name);

        $pdf = Pdf::loadView('pdf.logistics_report', [
            'package' => $this->selectedPackage,
            'logistics' => $data
        ]);
        
        $pdf->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Logistik-' . Str::slug($this->selectedPackage->name) . '.pdf');
    }

    public function saveMediaAssets()
    {
        $this->validate([
            'mediaPhotos.*' => 'image|max:10240',
            'selectedPackageId' => 'required',
        ]);

        foreach ($this->mediaPhotos as $photo) {
            $path = $photo->store('media-assets', 'public');
            
            $tags = ['operations', 'dokumentasi'];
            if($this->mediaTags) {
                $tags = array_merge($tags, array_map('trim', explode(',', $this->mediaTags)));
            }

            MediaAsset::create([
                'file_path' => $path,
                'file_type' => 'image',
                'umrah_package_id' => $this->selectedPackageId,
                'tags' => $tags,
                'uploaded_by' => auth()->id(),
                'title' => $photo->getClientOriginalName(),
            ]);
        }

        $this->reset(['mediaPhotos', 'mediaTags']);
        Notification::make()->title('Dokumentasi Terupload 📷')->success()->send();
        $this->showMediaModal = false; 
    }

    // ACTION: KIRIM REQUEST KE MEDIA
    public function saveContentRequest()
    {
        $this->validate([
            'reqTitle' => 'required',
            'reqDesc' => 'required',
            'reqDeadline' => 'nullable|date',
        ]);

        ContentRequest::create([
            'requester_id' => auth()->id(),
            'title' => $this->reqTitle,
            'description' => $this->reqDesc,
            'deadline' => $this->reqDeadline,
            'priority' => $this->reqPriority,
            'status' => 'pending'
        ]);

        $this->reset(['reqTitle', 'reqDesc', 'reqDeadline', 'reqPriority']);
        Notification::make()->title('Request Terkirim ke Tim Media 📝')->success()->send();
        $this->showMediaModal = false;
    }


};
?>

<div class="flex flex-col h-full w-full relative bg-slate-50 dark:bg-[#09090b]" 
     x-data="{ 
        mobileMenuOpen: false,
        showPnrModal: @entangle('showPnrModal'),
        showMediaModal: @entangle('showMediaModal'),
        showRundownModal: @entangle('showRundownModal'),
        showMassRundownModal: @entangle('showMassRundownModal')
     }">
    <div class="absolute -bottom-24 -right-24 w-128 h-128 opacity-40 dark:opacity-40 pointer-events-none transform">
        <img src="{{ asset('images/icons/kabah1.png') }}" alt="Kabah Decoration" class="w-full h-full object-contain">
    </div>
    <nav class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md px-4 py-2.5 flex justify-between items-center border-b border-slate-200 dark:border-white/5 shrink-0 z-50 relative"
        style="
            background-image: url('/images/ornaments/arabesque.png');
            background-repeat: repeat;
            background-size: 150px 150px;
        ">
        
        <div class="flex items-center gap-4">

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-emerald-600 to-teal-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">
                    <x-heroicon-s-paper-airplane class="w-6 h-6" />
                </div>
                <div class="flex flex-col">
                    <span class="font-black text-sm md:text-base tracking-tight leading-none uppercase">
                        Operations <span class="text-emerald-600 dark:text-emerald-400">Center</span>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="text-[9px] font-bold text-slate-400 dark:text-zinc-500 tracking-widest uppercase">
                            Manifest & Logistik
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 md:gap-4">
            <button @click="toggleTheme()" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-zinc-800 transition">
                <x-heroicon-s-moon class="w-5 h-5" x-show="!darkMode" />
                <x-heroicon-s-sun class="w-6 h-6 text-yellow-500" x-show="darkMode" x-cloak />
            </button>

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center gap-2 p-1 pr-3 rounded-full bg-slate-100 dark:bg-zinc-800 hover:ring-2 hover:ring-emerald-500/30 transition-all cursor-pointer">
                    <div class="h-7 w-7 rounded-full bg-emerald-600 flex items-center justify-center text-white font-black text-[10px] shadow-sm">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <span class="text-xs font-bold hidden md:block">{{ explode(' ', auth()->user()->name ?? 'User')[0] }}</span>
                </button>

                <div x-show="open" 
                     @click.outside="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     class="absolute right-0 mt-3 w-56 bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-slate-100 dark:border-white/5 py-2 z-50 origin-top-right overflow-hidden"
                     style="display: none;" x-cloak>
                    
                    <div class="px-4 py-3 bg-slate-50 dark:bg-white/5 mb-2">
                        <p class="text-xs font-black text-slate-900 dark:text-white truncate uppercase tracking-wider">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                    </div>

                    <a href="/admin" class="group flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-white transition-all">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-zinc-800 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-all">
                            <x-heroicon-s-squares-2x2 class="w-4 h-4" />
                        </div>
                        Admin Panel
                    </a>

                    <div class="border-t border-slate-100 dark:border-white/5 my-1"></div>

                    <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                        @csrf
                        <button type="submit" class="w-full group flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                            <div class="w-7 h-7 rounded-lg bg-red-50 dark:bg-red-500/10 flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition-all">
                                <x-heroicon-s-arrow-right-on-rectangle class="w-4 h-4" />
                            </div>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex-1 flex overflow-hidden relative">

        <aside class="hidden md:flex w-24 bg-white dark:bg-zinc-900 border-r border-slate-200 dark:border-white/5 flex-col items-center py-8 gap-6 z-30 shadow-sm shrink-0">
            
            <button wire:click="setTab('manifest')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'manifest' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'manifest' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-emerald-500/10' }}">
                    <x-heroicon-s-document-text class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Dokumen</span>
                @if($activeTab === 'manifest') <div class="absolute -right-[25px] w-1.5 h-8 bg-emerald-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="setTab('rooming')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'rooming' ? 'text-pink-600 dark:text-pink-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'rooming' ? 'bg-pink-600 text-white shadow-lg shadow-pink-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-pink-500/10' }}">
                    <x-heroicon-s-home-modern class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Rooming</span>
                @if($activeTab === 'rooming') <div class="absolute -right-[25px] w-1.5 h-8 bg-pink-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="setTab('logistics')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'logistics' ? 'text-orange-600 dark:text-orange-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'logistics' ? 'bg-orange-600 text-white shadow-lg shadow-orange-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-orange-500/10' }}">
                    <x-heroicon-s-cube class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Logistik</span>
                @if($activeTab === 'logistics') <div class="absolute -right-[25px] w-1.5 h-8 bg-orange-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="setTab('rundown')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'rundown' ? 'text-purple-600 dark:text-purple-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'rundown' ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-purple-500/10' }}">
                    <x-heroicon-s-calendar-days class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Rundown</span>
                @if($activeTab === 'rundown') <div class="absolute -right-[25px] w-1.5 h-8 bg-purple-600 rounded-l-full"></div> @endif
            </button>

        </aside>

        <main class="flex-1 h-full overflow-y-auto custom-scrollbar px-4 md:px-8 pb-28 md:pb-8 pt-0 relative">
            
            <div class="mb-8 sticky top-0 bg-slate-50/90 dark:bg-zinc-950/90 backdrop-blur-md z-20 py-4 -mx-4 px-4 md:-mx-8 md:px-8 border-b border-transparent transition-all mt-4"
                 :class="{ 'border-slate-200 dark:border-white/5 shadow-sm !mt-0 !pt-4': $el.closest('main').scrollTop > 0 }">
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    
                    <div class="flex items-center gap-3">
                         <div class="p-2.5 rounded-2xl transition-colors shadow-sm
                            @if($activeTab == 'manifest') bg-emerald-500/10
                            @elseif($activeTab == 'rooming') bg-pink-500/10
                            @elseif($activeTab == 'logistics') bg-orange-500/10
                            @elseif($activeTab == 'rundown') bg-purple-500/10
                            @endif
                            ">
                            
                            @if($activeTab == 'manifest') <x-heroicon-s-document-text class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                            @elseif($activeTab == 'rooming') <x-heroicon-s-home-modern class="w-6 h-6 text-pink-600 dark:text-pink-400" />
                            @elseif($activeTab == 'logistics') <x-heroicon-s-cube class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            @elseif($activeTab == 'rundown') <x-heroicon-s-calendar-days class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            @endif
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                                @if($activeTab == 'manifest') Data Dokumen
                                @elseif($activeTab == 'rooming') Rooming List
                                @elseif($activeTab == 'logistics') Distribusi Logistik
                                @elseif($activeTab == 'rundown') Itinerary Kegiatan
                                @endif
                            </h1>
                            <p class="text-[10px] md:text-xs text-slate-500 dark:text-zinc-500 font-bold uppercase tracking-widest">
                                Manage Group Operations
                            </p>
                        </div>
                    </div>

                    <div class="w-full md:w-auto relative group">
                        <select wire:model.live="selectedPackageId" 
                                class="w-full pl-4 pr-10 py-3 bg-white dark:bg-zinc-900 border-2 border-slate-200 dark:border-white/10 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-emerald-500/50 focus:ring-4 focus:ring-emerald-500/10 outline-none transition-all appearance-none cursor-pointer uppercase tracking-wider shadow-sm">
                            
                            @foreach($this->packages as $pkg)
                                <option value="{{ $pkg->id }}">
                                    ✈️ {{ $pkg->name }} ({{ \Carbon\Carbon::parse($pkg->departure_date)->format('d M Y') }})
                                </option>
                            @endforeach
                            
                            @if($this->packages->isEmpty())
                                <option value="">-- Tidak ada paket aktif --</option>
                            @endif
                        </select>
                        <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    </div>
                    
                </div>
            </div>

            <div class="space-y-8 min-h-[500px]">
                @if(!$selectedPackageId)
                    <div class="flex flex-col items-center justify-center h-96 border-2 border-dashed border-slate-200 dark:border-white/5 rounded-[3rem]">
                        <div class="p-6 bg-slate-50 dark:bg-zinc-800 rounded-full mb-4 animate-pulse">
                            <x-heroicon-o-paper-airplane class="w-12 h-12 text-slate-300 dark:text-zinc-600" />
                        </div>
                        <h3 class="text-lg font-black text-slate-700 dark:text-white uppercase tracking-tight mb-1">Pilih Paket Keberangkatan</h3>
                        <p class="text-sm text-slate-400 dark:text-zinc-500">Silakan pilih grup di pojok kanan atas untuk memulai.</p>
                    </div>
                @else
                    
                    @if($activeTab === 'manifest')
                    <div class="animate-fade-in space-y-6">

                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 overflow-hidden">
                            
                            <div class="p-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div class="flex flex-col sm:flex-row gap-4 sm:items-center">
                                    <div class="inline-flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider">
                                        <x-heroicon-s-users class="w-4 h-4" />
                                        Total Jamaah: {{ count($this->manifestData) }}
                                    </div>
                                    <div class="text-xs text-slate-400 dark:text-zinc-500 italic flex items-center gap-1.5">
                                        <x-heroicon-o-light-bulb class="w-4 h-4 text-yellow-500" />
                                        <span>Tips: Ketik langsung di tabel untuk edit. Data tersimpan otomatis.</span>
                                    </div>
                                </div>
                                
                                <div class="flex gap-2 w-full sm:w-auto">
                                    <button wire:click="exportPdf" wire:loading.attr="disabled" class="flex-1 sm:flex-none justify-center px-4 py-2.5 rounded-xl border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition shadow-sm">
                                        <x-heroicon-s-document-arrow-down class="w-4 h-4" />
                                        PDF
                                    </button>
                                    <button wire:click="exportExcel" wire:loading.attr="disabled" class="flex-1 sm:flex-none justify-center px-4 py-2.5 rounded-xl border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-400 font-bold text-xs flex items-center gap-2 transition shadow-sm">
                                        <x-heroicon-s-table-cells class="w-4 h-4" />
                                        Excel
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-x-auto custom-scrollbar">
                                <table class="w-full text-sm text-left whitespace-nowrap">
                                    <thead class="text-xs text-slate-500 dark:text-zinc-400 uppercase bg-slate-100 dark:bg-zinc-950 border-b border-slate-200 dark:border-white/5 font-black tracking-wider">
                                        <tr>
                                            <th class="px-4 py-4 w-12 text-center sticky left-0 bg-slate-100 dark:bg-zinc-950 z-20">#</th>
                                            <th class="px-6 py-4 min-w-[220px] sticky left-12 bg-slate-100 dark:bg-zinc-950 z-20 border-r border-slate-200 dark:border-white/5 shadow-[4px_0_10px_-2px_rgba(0,0,0,0.05)]">Jamaah</th>
                                            
                                            <th class="px-2 py-4 w-16 text-center">KTP</th>
                                            <th class="px-2 py-4 w-16 text-center">KK</th>
                                            <th class="px-2 py-4 w-16 text-center">Akta</th>
                                            <th class="px-2 py-4 w-16 text-center">Buku Nikah</th>
                                            <th class="px-2 py-4 w-16 text-center border-r border-slate-200 dark:border-white/5">Paspor</th>

                                            <th class="px-4 py-4 min-w-[140px]">No. Paspor</th>
                                            <th class="px-4 py-4 w-36">Expired</th>
                                            <th class="px-4 py-4 w-20 text-center border-r border-slate-200 dark:border-white/5">Scan</th>

                                            <th class="px-4 py-4 min-w-[140px] bg-amber-50/50 dark:bg-amber-900/10">No. Visa</th>
                                            <th class="px-4 py-4 w-36 bg-amber-50/50 dark:bg-amber-900/10">Issued</th>
                                            <th class="px-4 py-4 w-36 bg-amber-50/50 dark:bg-amber-900/10">Expired</th>
                                            
                                            <th class="px-4 py-4 w-40">Vaksin</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                        @foreach($this->manifestData as $index => $booking)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition group">
                                            
                                            <td class="px-4 py-3 text-center text-slate-400 sticky left-0 bg-white dark:bg-zinc-900 group-hover:bg-slate-50 dark:group-hover:bg-white/5 z-10">
                                                {{ $loop->iteration }}
                                            </td>
                                            
                                            <td class="px-6 py-3 sticky left-12 bg-white dark:bg-zinc-900 border-r border-slate-100 dark:border-white/5 group-hover:bg-slate-50 dark:group-hover:bg-white/5 z-10 shadow-[4px_0_10px_-2px_rgba(0,0,0,0.05)]">
                                                <p class="font-bold text-slate-900 dark:text-white">{{ $booking->jamaah->name }}</p>
                                                <p class="text-[10px] text-slate-500 dark:text-zinc-500 font-mono mt-0.5">{{ $booking->jamaah->nik ?? '-' }}</p>
                                            </td>

                                            @foreach(['ktp', 'kk', 'akta', 'buku_nikah'] as $docType)
                                            <td class="px-2 py-3 text-center cursor-pointer" wire:click="toggleDocument({{ $booking->id }}, '{{ $docType }}')">
                                                <div class="w-6 h-6 mx-auto rounded-lg flex items-center justify-center transition-all duration-300 transform active:scale-95
                                                    {{ $booking->documentCheck?->$docType 
                                                        ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 shadow-sm' 
                                                        : 'bg-slate-100 text-slate-300 dark:bg-white/5 dark:text-zinc-600 hover:bg-slate-200 dark:hover:bg-zinc-800' }}">
                                                    <x-heroicon-s-check class="w-4 h-4 {{ $booking->documentCheck?->$docType ? 'opacity-100' : 'opacity-0' }}" />
                                                </div>
                                            </td>
                                            @endforeach
                                            
                                            <td class="px-2 py-3 text-center border-r border-slate-100 dark:border-white/5 cursor-pointer" wire:click="toggleDocument({{ $booking->id }}, 'passport')">
                                                <div class="w-6 h-6 mx-auto rounded-lg flex items-center justify-center transition-all duration-300 transform active:scale-95
                                                    {{ ($booking->documentCheck?->passport_status ?? 'missing') === 'received' 
                                                        ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 shadow-sm' 
                                                        : 'bg-slate-100 text-slate-300 dark:bg-white/5 dark:text-zinc-600 hover:bg-slate-200 dark:hover:bg-zinc-800' }}">
                                                    <x-heroicon-s-book-open class="w-3.5 h-3.5 {{ ($booking->documentCheck?->passport_status ?? 'missing') === 'received' ? 'opacity-100' : 'opacity-0' }}" />
                                                </div>
                                            </td>

                                            <td class="px-4 py-3">
                                                <input type="text" 
                                                    value="{{ $booking->jamaah->passport_number }}" 
                                                    wire:change="updateJamaahField({{ $booking->jamaah->id }}, 'passport_number', $event.target.value)" 
                                                    class="w-full bg-transparent border-b border-transparent hover:border-slate-300 focus:border-emerald-500 focus:ring-0 text-xs font-mono font-bold p-1 dark:text-white placeholder-slate-300 dark:placeholder-zinc-700 transition-colors uppercase" 
                                                    placeholder="NO PASPOR">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="date" 
                                                    value="{{ $booking->jamaah->passport_expiry }}" 
                                                    wire:change="updateJamaahField({{ $booking->jamaah->id }}, 'passport_expiry', $event.target.value)" 
                                                    class="w-full bg-transparent border-b border-transparent hover:border-slate-300 focus:border-emerald-500 focus:ring-0 text-xs font-medium p-1 dark:text-white text-slate-600 transition-colors">
                                            </td>
                                            <td class="px-2 py-3 text-center border-r border-slate-100 dark:border-white/5">
                                                <div class="relative group/upload">
                                                    <input type="file" wire:model="uploads.passport_scan.{{ $booking->jamaah->id }}" id="file-{{ $booking->jamaah->id }}" class="hidden">
                                                    <label for="file-{{ $booking->jamaah->id }}" class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-slate-100 dark:hover:bg-zinc-800 transition">
                                                        @if($booking->jamaah->passport_scan)
                                                            <x-heroicon-s-document-check class="w-5 h-5 text-emerald-500" />
                                                        @else
                                                            <x-heroicon-o-arrow-up-tray class="w-4 h-4 text-slate-400 group-hover/upload:text-emerald-500" />
                                                        @endif
                                                    </label>
                                                    <div wire:loading wire:target="uploads.passport_scan.{{ $booking->jamaah->id }}" class="absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-zinc-900/80 rounded-full">
                                                        <x-heroicon-o-arrow-path class="w-4 h-4 text-emerald-500 animate-spin" />
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="px-4 py-3 bg-amber-50/30 dark:bg-amber-900/5">
                                                <input type="text" 
                                                    value="{{ $booking->documentCheck->visa_number ?? '' }}" 
                                                    wire:change="updateDocumentField({{ $booking->id }}, 'visa_number', $event.target.value)" 
                                                    class="w-full bg-transparent border-b border-transparent hover:border-amber-300 focus:border-amber-500 focus:ring-0 text-xs font-mono font-bold p-1 dark:text-white placeholder-slate-300 dark:placeholder-zinc-700 transition-colors uppercase" 
                                                    placeholder="NO VISA">
                                            </td>
                                            <td class="px-4 py-3 bg-amber-50/30 dark:bg-amber-900/5">
                                                <input type="date" 
                                                    value="{{ $booking->documentCheck->visa_issue_date ?? '' }}" 
                                                    wire:change="updateDocumentField({{ $booking->id }}, 'visa_issue_date', $event.target.value)" 
                                                    class="w-full bg-transparent border-b border-transparent hover:border-amber-300 focus:border-amber-500 focus:ring-0 text-xs font-medium p-1 dark:text-white text-slate-600 transition-colors">
                                            </td>
                                            <td class="px-4 py-3 bg-amber-50/30 dark:bg-amber-900/5">
                                                <input type="date" 
                                                    value="{{ $booking->documentCheck->visa_expiry_date ?? '' }}" 
                                                    wire:change="updateDocumentField({{ $booking->id }}, 'visa_expiry_date', $event.target.value)" 
                                                    class="w-full bg-transparent border-b border-transparent hover:border-amber-300 focus:border-amber-500 focus:ring-0 text-xs font-medium p-1 dark:text-white text-slate-600 transition-colors">
                                            </td>

                                            <td class="px-4 py-3">
                                                <select wire:change="updateJamaahField({{ $booking->jamaah->id }}, 'vaccine_status', $event.target.value)" 
                                                    class="w-full bg-transparent border-b border-transparent hover:border-slate-300 focus:border-emerald-500 focus:ring-0 text-xs font-bold p-1 dark:text-white cursor-pointer transition-colors">
                                                    <option value="" class="dark:bg-zinc-800 text-slate-300">- Pilih Status -</option>
                                                    <option value="Meningitis" @selected($booking->jamaah->vaccine_status == 'Meningitis') class="dark:bg-zinc-800">💉 Meningitis</option>
                                                    <option value="Full" @selected($booking->jamaah->vaccine_status == 'Full') class="dark:bg-zinc-800">✅ Lengkap</option>
                                                </select>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                        
                    @elseif($activeTab === 'rooming')

                    <div class="space-y-8 animate-fade-in">
                        
                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 relative overflow-hidden">
                            
                            <div class="flex flex-col md:flex-row justify-between items-end gap-6 relative z-10">
                                <div class="w-full md:w-1/2 space-y-3">
                                    <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 uppercase tracking-tight text-lg">
                                        <div class="p-2 bg-pink-50 dark:bg-pink-500/10 rounded-xl text-pink-600 dark:text-pink-400">
                                            <x-heroicon-s-building-office class="w-6 h-6"/>
                                        </div>
                                        Manifest Hotel
                                    </h3>
                                    
                                    <div class="relative group">
                                        <select wire:model.live="selectedHotelName" 
                                            class="w-full pl-4 pr-10 py-3 bg-pink-50/50 dark:bg-zinc-800 border-2 border-pink-100 dark:border-pink-900/30 rounded-xl text-pink-900 dark:text-pink-100 font-bold focus:ring-4 focus:ring-pink-500/10 focus:border-pink-500 outline-none transition-all appearance-none cursor-pointer">
                                            <option value="">-- Pilih Hotel --</option>
                                            @foreach($this->hotelList as $hotel)
                                                <option value="{{ $hotel->hotel_name }}">{{ $hotel->hotel_name }} ({{ $hotel->city }})</option>
                                            @endforeach
                                        </select>
                                        <x-heroicon-s-chevron-down class="w-5 h-5 text-pink-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    </div>

                                    @if($this->hotelList->isEmpty())
                                        <p class="text-xs text-red-500 font-bold ml-1">*Belum ada data hotel. Input di Master Paket dulu.</p>
                                    @endif
                                </div>

                                <div class="w-full md:w-auto">
                                    <button wire:click="exportRoomingPdf" class="w-full md:w-auto justify-center px-6 py-3 rounded-xl border-2 border-red-100 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white dark:border-red-900/30 dark:bg-red-900/10 dark:text-red-400 dark:hover:bg-red-900/50 font-black text-xs uppercase tracking-widest flex items-center gap-2 transition shadow-sm">
                                        <x-heroicon-s-document-arrow-down class="w-5 h-5" />
                                        Export Rooming List
                                    </button>
                                </div>
                            </div>

                            <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-pink-500/5 to-rose-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                        </div>

                        @if(!$selectedHotelName)
                            <div class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-200 dark:border-white/5 rounded-[3rem] bg-slate-50/50 dark:bg-zinc-900/30">
                                <div class="p-6 bg-white dark:bg-zinc-800 rounded-full mb-4 shadow-sm">
                                    <x-heroicon-o-building-office class="w-12 h-12 text-slate-300 dark:text-zinc-600" />
                                </div>
                                <p class="text-lg font-black text-slate-700 dark:text-white uppercase tracking-tight">Pilih Hotel Terlebih Dahulu</p>
                                <p class="text-sm text-slate-400 dark:text-zinc-500 mt-1">Pilih hotel di atas untuk mulai mengatur pembagian kamar.</p>
                            </div>
                        @else
                            
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start h-auto lg:h-[700px]">
                                
                                <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border border-slate-200 dark:border-white/5 flex flex-col overflow-hidden h-[500px] lg:h-full">
                                    <div class="p-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/80 dark:bg-zinc-900/80 backdrop-blur-sm sticky top-0 z-10">
                                        <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 uppercase tracking-tight text-sm">
                                            <x-heroicon-s-users class="w-5 h-5 text-slate-400" />
                                            Jamaah Tanpa Kamar
                                            <span class="bg-red-100 text-red-600 px-2 py-0.5 rounded-lg text-[10px] font-bold ml-auto">{{ count($this->unassignedJamaah) }}</span>
                                        </h3>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-2">Centang jamaah -> Buat/Masuk Kamar</p>
                                    </div>
                                    
                                    <div class="flex-1 overflow-y-auto p-4 custom-scrollbar space-y-2">
                                        @forelse($this->unassignedJamaah as $booking)
                                        <label class="flex items-start gap-3 p-3 hover:bg-pink-50 dark:hover:bg-pink-900/10 rounded-xl cursor-pointer border border-transparent hover:border-pink-200 dark:hover:border-pink-800 transition group select-none">
                                            <div class="relative flex items-center mt-1">
                                                <input type="checkbox" wire:model.live="selectedBookingIds" value="{{ $booking->id }}" 
                                                    class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border-2 border-slate-300 transition-all checked:border-pink-500 checked:bg-pink-500 hover:border-pink-400">
                                                <x-heroicon-s-check class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100" />
                                            </div>
                                            <div>
                                                <p class="font-bold text-sm text-slate-700 dark:text-zinc-200 group-hover:text-pink-700 dark:group-hover:text-pink-400 transition-colors">{{ $booking->jamaah->name }}</p>
                                                <span class="inline-block mt-1 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 dark:bg-zinc-800 text-slate-500">
                                                    {{ ucfirst($booking->jamaah->gender) }}
                                                </span>
                                            </div>
                                        </label>
                                        @empty
                                        <div class="flex flex-col items-center justify-center h-full text-center p-6 opacity-50">
                                            <x-heroicon-o-check-circle class="w-12 h-12 text-slate-300 mb-2" />
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Semua jamaah sudah dapat kamar.</p>
                                        </div>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="lg:col-span-2 flex flex-col gap-6 h-full">
                                    
                                    <div class="bg-white dark:bg-zinc-900 p-5 rounded-[2rem] shadow-sm border border-slate-200 dark:border-white/5">
                                        <div class="flex flex-col sm:flex-row gap-4 items-end">
                                            <div class="flex-1 w-full space-y-1">
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nomor Kamar</label>
                                                <input wire:model="newRoomNumber" type="text" placeholder="Contoh: 101" 
                                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-pink-500 focus:ring-0 outline-none transition-all placeholder:text-slate-300">
                                            </div>
                                            <div class="w-full sm:w-40 space-y-1">
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tipe</label>
                                                <div class="relative">
                                                    <select wire:model="newRoomType" class="w-full pl-4 pr-8 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-pink-500 focus:ring-0 outline-none transition-all appearance-none cursor-pointer">
                                                        <option value="quad">Quad (4)</option>
                                                        <option value="triple">Triple (3)</option>
                                                        <option value="double">Double (2)</option>
                                                    </select>
                                                    <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                                </div>
                                            </div>
                                            <button wire:click="createRoom" class="w-full sm:w-auto bg-pink-600 hover:bg-pink-700 text-white px-6 py-3.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-pink-500/30 transition transform active:scale-95 whitespace-nowrap">
                                                + Buat Kamar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar -mr-2">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pb-4">
                                            @foreach($this->rooms as $roomNum => $assignments)
                                            @php 
                                                $first = $assignments->first(); 
                                                $capacity = match($first->room_type) { 'quad'=>4, 'triple'=>3, 'double'=>2, default=>1 };
                                                $filled = $assignments->count();
                                                $isFull = $filled >= $capacity;
                                            @endphp
                                            
                                            <div class="bg-white dark:bg-zinc-900 rounded-2xl border-2 {{ $isFull ? 'border-emerald-100 dark:border-emerald-900/30' : 'border-slate-100 dark:border-white/5' }} shadow-sm relative group hover:border-pink-200 dark:hover:border-pink-900/30 transition-all">
                                                
                                                <div class="p-4 border-b border-slate-100 dark:border-white/5 flex justify-between items-center {{ $isFull ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : 'bg-slate-50/50 dark:bg-zinc-900/50' }}">
                                                    <div>
                                                        <h4 class="font-black text-slate-800 dark:text-white text-lg">Room {{ $roomNum }}</h4>
                                                        <span class="text-[10px] uppercase font-bold tracking-wider {{ $isFull ? 'text-emerald-600' : 'text-slate-400' }}">{{ ucfirst($first->room_type) }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-center w-10 h-10 rounded-xl {{ $isFull ? 'bg-emerald-100 text-emerald-700' : 'bg-white border border-slate-200 text-slate-400' }}">
                                                        <span class="font-black text-sm">{{ $filled }}/{{ $capacity }}</span>
                                                    </div>
                                                </div>

                                                <div class="p-4 space-y-3 min-h-[120px]">
                                                    @foreach($assignments as $a)
                                                    <div class="flex justify-between items-center group/item">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                                                            <span class="text-sm font-bold text-slate-600 dark:text-zinc-300 truncate w-32 md:w-40">{{ $a->booking->jamaah->name }}</span>
                                                        </div>
                                                        <button wire:click="removeFromRoom({{ $a->id }})" class="text-red-400 hover:text-red-600 opacity-0 group-hover/item:opacity-100 transition p-1 bg-red-50 rounded-lg">
                                                            <x-heroicon-s-x-mark class="w-3 h-3" />
                                                        </button>
                                                    </div>
                                                    @endforeach
                                                </div>

                                                <div class="p-3 border-t border-slate-100 dark:border-white/5 flex justify-between bg-slate-50/30 dark:bg-white/5 rounded-b-xl">
                                                    <button wire:click="deleteRoom('{{ $roomNum }}')" wire:confirm="Hapus kamar ini? Jamaah akan kembali ke list unassigned." class="text-[10px] font-bold text-red-400 hover:text-red-600 hover:bg-red-50 px-2 py-1 rounded transition uppercase tracking-wider">
                                                        Hapus Room
                                                    </button>
                                                    @if(!$isFull)
                                                    <button wire:click="assignToExistingRoom('{{ $roomNum }}', '{{ $first->room_type }}')" class="text-[10px] font-bold bg-pink-100 text-pink-700 px-3 py-1 rounded-lg hover:bg-pink-200 transition uppercase tracking-wider">
                                                        + Isi Disini
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 overflow-hidden mt-12">
                            
                            <div class="p-6 border-b border-slate-100 dark:border-white/5 bg-indigo-50/50 dark:bg-zinc-900/50 flex flex-col md:flex-row justify-between items-center gap-6">
                                
                                <div class="w-full md:w-1/2">
                                    <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 mb-3 uppercase tracking-tight text-sm">
                                        <x-heroicon-s-paper-airplane class="w-5 h-5 text-indigo-500"/>
                                        Manifest Penerbangan
                                    </h3>
                                    
                                    <div class="relative">
                                        <select wire:model.live="selectedFlightId" class="w-full pl-4 pr-10 py-3 bg-white dark:bg-zinc-800 border-2 border-indigo-100 dark:border-indigo-900/30 rounded-xl text-sm font-bold text-indigo-900 dark:text-indigo-100 focus:border-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                                            <option value="">-- Pilih Rute Penerbangan --</option>
                                            @foreach($this->flightList as $flight)
                                                <option value="{{ $flight->id }}">
                                                    ✈️ {{ $flight->airline }} ({{ $flight->flight_number }}) : {{ $flight->depart_airport }} ➡ {{ $flight->arrival_airport }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-heroicon-s-chevron-down class="w-5 h-5 text-indigo-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    </div>
                                    
                                    @if($this->flightList->isEmpty())
                                        <p class="text-[10px] text-red-500 font-bold mt-2 ml-1">*Input data penerbangan di Master Paket dulu.</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2 w-full md:w-auto justify-end items-end">
                                    <button wire:click="exportFlightPdf" wire:loading.attr="disabled" class="px-4 py-2.5 rounded-xl border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition shadow-sm">
                                        <x-heroicon-s-document-arrow-down class="w-4 h-4" /> Export PDF
                                    </button>
                                    <button wire:click="exportFlightExcel" wire:loading.attr="disabled" class="px-4 py-2.5 rounded-xl border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-400 font-bold text-xs flex items-center gap-2 transition shadow-sm">
                                        <x-heroicon-s-table-cells class="w-4 h-4" /> Export Excel
                                    </button>
                                    
                                    @if($selectedFlightId)
                                    <div class="flex gap-2 items-center w-full md:w-auto mt-2 md:mt-0 pl-4 border-l border-slate-200 dark:border-white/10">
                                        <input wire:model.live="bulkPnr" type="text" placeholder="PNR MASSAL..." class="w-32 px-3 py-2.5 bg-white dark:bg-zinc-800 border-2 border-indigo-100 dark:border-white/10 rounded-xl text-xs font-mono font-bold uppercase text-slate-700 dark:text-white outline-none focus:border-indigo-500 transition">
                                        <button wire:click="askToApplyBulkPnr" class="text-xs bg-indigo-600 text-white px-4 py-2.5 rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition whitespace-nowrap uppercase tracking-wider">
                                            Apply All
                                        </button>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div class="overflow-x-auto custom-scrollbar">
                                @if(!$selectedFlightId)
                                    <div class="p-12 text-center text-slate-400">
                                        <x-heroicon-o-paper-airplane class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-zinc-700" />
                                        <p class="font-bold text-sm uppercase tracking-widest">Pilih Rute Penerbangan di atas</p>
                                    </div>
                                @else
                                    <table class="w-full text-sm text-left whitespace-nowrap">
                                        <thead class="text-[10px] text-slate-500 dark:text-zinc-400 uppercase bg-slate-50 dark:bg-zinc-950 border-b border-slate-200 dark:border-white/5 font-black tracking-wider">
                                            <tr>
                                                <th class="px-6 py-4 w-12 text-center">No</th>
                                                <th class="px-6 py-4">Nama Jamaah</th>
                                                <th class="px-6 py-4 w-48 bg-indigo-50/50 dark:bg-indigo-900/10 border-l border-indigo-100 dark:border-white/5">Kode PNR</th>
                                                <th class="px-6 py-4 w-64 bg-indigo-50/50 dark:bg-indigo-900/10">No. Tiket</th>
                                                <th class="px-6 py-4 border-l border-indigo-100 dark:border-white/5">Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                            @foreach($this->manifestData as $index => $booking)
                                            @php
                                                $flightData = $booking->bookingFlights->where('package_flight_id', $selectedFlightId)->first();
                                            @endphp
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition group">
                                                <td class="px-6 py-3 text-center text-slate-400 font-mono">{{ $loop->iteration }}</td>
                                                
                                                <td class="px-6 py-3">
                                                    <p class="font-bold text-slate-900 dark:text-white">{{ $booking->jamaah->name }}</p>
                                                    <span class="text-[10px] text-slate-400 font-mono">{{ $booking->jamaah->passport_number ?? '-' }}</span>
                                                </td>
                                                
                                                <td class="px-4 py-3 bg-indigo-50/30 dark:bg-indigo-900/5 border-l border-slate-100 dark:border-white/5">
                                                    <input 
                                                        type="text" 
                                                        value="{{ $flightData->pnr_code ?? '' }}" 
                                                        wire:change="updateFlightField({{ $booking->id }}, 'pnr_code', $event.target.value)" 
                                                        class="w-full bg-transparent border-b border-transparent hover:border-indigo-300 focus:border-indigo-500 focus:ring-0 text-xs font-mono font-bold uppercase text-slate-700 dark:text-indigo-200 placeholder-indigo-200/50 transition-colors" 
                                                        placeholder="PNR CODE"
                                                    >
                                                </td>
                                                
                                                <td class="px-4 py-3 bg-indigo-50/30 dark:bg-indigo-900/5">
                                                    <input 
                                                        type="text" 
                                                        value="{{ $flightData->ticket_number ?? '' }}" 
                                                        wire:change="updateFlightField({{ $booking->id }}, 'ticket_number', $event.target.value)" 
                                                        class="w-full bg-transparent border-b border-transparent hover:border-indigo-300 focus:border-indigo-500 focus:ring-0 text-xs font-mono text-slate-700 dark:text-zinc-300 placeholder-slate-300 transition-colors" 
                                                        placeholder="No. Tiket"
                                                    >
                                                </td>

                                                <td class="px-4 py-3 border-l border-slate-100 dark:border-white/5">
                                                    <input 
                                                        type="text" 
                                                        value="{{ $flightData->notes ?? '' }}" 
                                                        wire:change="updateFlightField({{ $booking->id }}, 'notes', $event.target.value)" 
                                                        class="w-full bg-transparent border-b border-transparent hover:border-slate-300 focus:border-indigo-500 focus:ring-0 text-xs text-slate-600 dark:text-zinc-400 placeholder-slate-300 transition-colors" 
                                                        placeholder="Ct: Kursi Roda..."
                                                    >
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>

                        @if($showPnrModal)
                        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" wire:click="$set('showPnrModal', false)"></div>
                            <div class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-2xl max-w-sm w-full p-8 relative z-10 border-t-4 border-indigo-500 overflow-hidden">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/20 rounded-full flex items-center justify-center mx-auto mb-4 text-indigo-500">
                                        <x-heroicon-s-paper-airplane class="w-8 h-8" />
                                    </div>
                                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Apply PNR Massal?</h3>
                                    <p class="text-sm text-slate-500 dark:text-zinc-400 mt-2 mb-6 leading-relaxed">
                                        Kode PNR <span class="font-mono font-bold text-indigo-600 bg-indigo-100 px-2 py-0.5 rounded text-xs mx-1">{{ $bulkPnr }}</span> 
                                        akan diterapkan ke semua jamaah di list ini yang belum memiliki PNR.
                                    </p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button wire:click="$set('showPnrModal', false)" class="px-4 py-3 bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-200 transition">Batal</button>
                                        <button wire:click="processBulkPnr" class="px-4 py-3 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition transform active:scale-95">Ya, Proses</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>

                    @elseif($activeTab === 'logistics')

                    <div class="animate-fade-in space-y-6">

                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 overflow-hidden">
                            <div class="p-6 flex flex-col md:flex-row justify-between items-center gap-4 bg-orange-50/50 dark:bg-orange-900/10">
                                
                                <div class="flex items-center gap-3 w-full md:w-auto">
                                    <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-xl text-orange-600 dark:text-orange-400">
                                        <x-heroicon-s-cube class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-tight">Distribusi Logistik</h3>
                                        <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Kelola Serah Terima Perlengkapan</p>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                    <div class="flex-1 sm:flex-none relative group">
                                        <select wire:model="bulkLogisticsItem" class="w-full sm:w-64 pl-4 pr-10 py-2.5 bg-white dark:bg-zinc-800 border-2 border-orange-100 dark:border-orange-900/30 rounded-xl text-xs font-bold text-slate-700 dark:text-zinc-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all appearance-none cursor-pointer uppercase tracking-wide">
                                            <option value="">-- Pilih Barang (Massal) --</option>
                                            @foreach($this->inventoryItems as $item)
                                                <option value="{{ $item->id }}">
                                                    {{ $item->name }} ({{ ucfirst($item->type) }}) - Stok: {{ $item->stock_quantity }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-heroicon-s-chevron-down class="w-4 h-4 text-orange-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    </div>
                                    
                                    <button wire:click="saveBulkHandover" wire:confirm="Yakin bagikan barang ini ke jamaah terpilih?" class="px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-bold text-xs uppercase tracking-widest shadow-lg shadow-orange-500/30 transition transform active:scale-95 whitespace-nowrap">
                                        Bagikan Massal
                                    </button>

                                    <button wire:click="exportLogisticsPdf" class="px-4 py-2.5 bg-white dark:bg-zinc-800 border-2 border-orange-100 dark:border-orange-900/30 text-orange-600 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-xl font-bold text-xs uppercase tracking-widest transition flex items-center gap-2">
                                        <x-heroicon-s-document-text class="w-4 h-4" />
                                        Laporan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-auto lg:h-[700px]">
                            
                            <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border border-slate-200 dark:border-white/5 flex flex-col overflow-hidden h-[500px] lg:h-full">
                                <div class="p-5 border-b border-slate-100 dark:border-white/5 bg-slate-50/80 dark:bg-zinc-900/80 backdrop-blur-sm sticky top-0 z-10">
                                    <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 uppercase tracking-tight text-sm">
                                        <x-heroicon-s-users class="w-5 h-5 text-slate-400" />
                                        Pilih Jamaah
                                    </h3>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1">Klik nama untuk proses handover</p>
                                </div>

                                <div class="flex-1 overflow-y-auto p-3 custom-scrollbar space-y-2">
                                    @foreach($this->logisticsData as $booking)
                                    <div class="group flex items-center gap-3 p-3 rounded-2xl border transition-all cursor-pointer select-none
                                        {{ $activeLogisticsJamaahId == $booking->id 
                                            ? 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800 ring-1 ring-orange-200 dark:ring-orange-800' 
                                            : 'bg-white dark:bg-zinc-900 border-transparent hover:bg-slate-50 dark:hover:bg-white/5 hover:border-slate-200 dark:hover:border-white/10' }}">
                                        
                                        <div class="relative flex items-center">
                                            <input type="checkbox" wire:model.live="selectedBookingIds" value="{{ $booking->id }}" 
                                                class="peer h-5 w-5 cursor-pointer appearance-none rounded-lg border-2 border-slate-300 transition-all checked:border-orange-500 checked:bg-orange-500 hover:border-orange-400">
                                            <x-heroicon-s-check class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100" />
                                        </div>
                                        
                                        <div class="flex-1" wire:click="selectJamaahForLogistics({{ $booking->id }})">
                                            <p class="font-bold text-sm text-slate-800 dark:text-white group-hover:text-orange-600 transition-colors">
                                                {{ $booking->jamaah->name }}
                                            </p>
                                            
                                            <div class="flex flex-wrap gap-1 mt-1.5">
                                                @forelse($booking->inventoryMovements as $move)
                                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 text-[9px] font-bold uppercase tracking-wide">
                                                        <x-heroicon-s-check-circle class="w-3 h-3" />
                                                        {{ $move->inventoryItem->name ?? 'Item' }}
                                                    </span>
                                                @empty
                                                    <span class="text-[9px] font-bold text-slate-400 italic">Belum ambil barang</span>
                                                @endforelse
                                            </div>
                                        </div>

                                        <x-heroicon-m-chevron-right class="w-4 h-4 text-slate-300 group-hover:text-orange-400 transition-transform group-hover:translate-x-1" />
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="lg:col-span-2 h-full">
                                @if($activeLogisticsJamaahId && $this->activeJamaahLogistics)
                                    <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-xl border border-orange-100 dark:border-orange-900/30 h-full flex flex-col relative overflow-hidden">
                                        
                                        <div class="p-6 border-b border-orange-100 dark:border-white/5 bg-orange-50/50 dark:bg-orange-900/10 flex justify-between items-center">
                                            <div>
                                                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $this->activeJamaahLogistics->jamaah->name }}</h2>
                                                <p class="text-xs font-bold text-orange-600 dark:text-orange-400 uppercase tracking-widest mt-1">Formulir Serah Terima</p>
                                            </div>
                                            <button wire:click="$set('activeLogisticsJamaahId', null)" class="p-2 rounded-full hover:bg-slate-200 dark:hover:bg-white/10 text-slate-400 transition">
                                                <x-heroicon-s-x-mark class="w-6 h-6" />
                                            </button>
                                        </div>

                                        <div class="p-8 flex-1 overflow-y-auto custom-scrollbar space-y-8">
                                            
                                            <div>
                                                <h4 class="font-black text-xs text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                                    <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-zinc-700 flex items-center justify-center text-slate-600 dark:text-zinc-300 text-[10px]">1</span>
                                                    Pilih Barang Yang Diambil
                                                </h4>
                                                
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    @foreach($this->inventoryItems as $item)
                                                        @php
                                                            $alreadyTaken = $this->activeJamaahLogistics->inventoryMovements->contains('inventory_item_id', $item->id);
                                                        @endphp
                                                        <label class="group flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer select-none
                                                            {{ $alreadyTaken 
                                                                ? 'bg-slate-50 dark:bg-zinc-800/50 border-slate-100 dark:border-zinc-800 opacity-60 cursor-not-allowed' 
                                                                : 'bg-white dark:bg-zinc-900 border-slate-100 dark:border-white/5 hover:border-orange-400 hover:shadow-md' 
                                                            }}">
                                                            
                                                            <input type="checkbox" wire:model="logisticsItems" value="{{ $item->id }}" 
                                                                class="peer sr-only"
                                                                {{ $alreadyTaken ? 'disabled checked' : '' }}>
                                                            
                                                            <div class="h-5 w-5 rounded border-2 border-slate-300 mr-3 flex items-center justify-center transition-colors peer-checked:bg-orange-500 peer-checked:border-orange-500">
                                                                <x-heroicon-s-check class="w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100" />
                                                            </div>

                                                            <div class="flex-1">
                                                                <p class="font-bold text-sm text-slate-700 dark:text-white group-hover:text-orange-600 transition">{{ $item->name }}</p>
                                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Sisa Stok: {{ $item->stock_quantity }}</p>
                                                            </div>
                                                            
                                                            @if($alreadyTaken)
                                                                <span class="text-[9px] font-black text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 px-2 py-1 rounded-lg uppercase">Sudah</span>
                                                            @endif
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div>
                                                <h4 class="font-black text-xs text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                                    <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-zinc-700 flex items-center justify-center text-slate-600 dark:text-zinc-300 text-[10px]">2</span>
                                                    Nama Penerima
                                                </h4>
                                                <input wire:model="receiverName" type="text" placeholder="Nama yang mengambil barang..." 
                                                    class="w-full p-4 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:border-orange-500 focus:ring-0 outline-none transition-all">
                                            </div>

                                            <div x-data="signaturePad(@entangle('signature'))">
                                                <h4 class="font-black text-xs text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                                    <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-zinc-700 flex items-center justify-center text-slate-600 dark:text-zinc-300 text-[10px]">3</span>
                                                    Tanda Tangan Digital
                                                </h4>
                                                
                                                <div class="border-2 border-dashed border-slate-300 dark:border-zinc-700 rounded-2xl bg-white dark:bg-zinc-800 touch-none relative overflow-hidden h-48 group hover:border-orange-400 transition-colors">
                                                    <canvas x-ref="canvas" class="w-full h-full block cursor-crosshair"></canvas>
                                                    
                                                    <div x-show="!hasSigned" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-slate-300 dark:text-zinc-600">
                                                        <x-heroicon-o-pencil class="w-8 h-8 mb-2" />
                                                        <span class="text-xs font-bold uppercase tracking-widest">Tanda tangan di sini</span>
                                                    </div>

                                                    <button @click.prevent="clear()" class="absolute top-3 right-3 p-2 bg-slate-100 dark:bg-zinc-700 hover:bg-red-50 hover:text-red-500 text-slate-500 rounded-lg shadow-sm transition" title="Hapus Tanda Tangan">
                                                        <x-heroicon-s-trash class="w-4 h-4" />
                                                    </button>
                                                </div>
                                                <p class="text-[10px] text-slate-400 font-bold mt-2 ml-1">*Gunakan mouse atau jari (layar sentuh).</p>
                                            </div>

                                        </div>

                                        <div class="p-6 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-zinc-900/50 flex justify-end gap-4">
                                            <button wire:click="$set('activeLogisticsJamaahId', null)" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-200 dark:text-zinc-400 dark:hover:bg-zinc-800 text-xs uppercase tracking-widest transition">
                                                Batal
                                            </button>
                                            <button wire:click="saveHandover" class="px-8 py-3 rounded-xl font-black text-white bg-orange-600 hover:bg-orange-700 shadow-lg shadow-orange-500/30 text-xs uppercase tracking-widest transition transform hover:scale-105 active:scale-95 flex items-center gap-2">
                                                <x-heroicon-s-check-circle class="w-5 h-5" />
                                                Simpan & Serah Terima
                                            </button>
                                        </div>

                                    </div>
                                @else
                                    <div class="h-full flex flex-col items-center justify-center text-center p-10 border-2 border-dashed border-slate-200 dark:border-white/5 rounded-[2.5rem] bg-slate-50/50 dark:bg-zinc-900/30">
                                        <div class="p-6 bg-white dark:bg-zinc-800 rounded-full shadow-sm mb-6 animate-pulse">
                                            <x-heroicon-o-cube-transparent class="w-16 h-16 text-orange-200 dark:text-orange-900" />
                                        </div>
                                        <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Siap Serah Terima</h3>
                                        <p class="text-sm text-slate-400 dark:text-zinc-500 mt-2 max-w-xs leading-relaxed">
                                            Pilih nama jamaah di sebelah kiri untuk memulai proses pengambilan barang & tanda tangan digital.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @elseif($activeTab === 'rundown')

                    <div class="h-full flex flex-col animate-fade-in space-y-6">
                        
                        <div class="bg-white dark:bg-zinc-900 p-5 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 flex flex-col md:flex-row justify-between items-center gap-4">
                            
                            <div class="flex p-1.5 bg-slate-100 dark:bg-zinc-800/50 rounded-2xl w-full md:w-auto overflow-x-auto">
                                @foreach(['pre'=>'🛫 Pra', 'during'=>'🕋 Umrah', 'post'=>'🛬 Pasca'] as $key => $label)
                                <button wire:click="$set('rundownPhase', '{{ $key }}')" 
                                    class="px-6 py-2.5 text-xs font-black rounded-xl transition-all duration-300 relative overflow-hidden flex-1 md:flex-none whitespace-nowrap
                                    {{ $rundownPhase === $key 
                                        ? 'bg-white dark:bg-zinc-700 text-purple-600 dark:text-white shadow-md ring-1 ring-black/5' 
                                        : 'text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200' 
                                    }}">
                                    {{ $label }}
                                </button>
                                @endforeach
                            </div>

                            <div class="flex gap-3 w-full md:w-auto">
                                <button wire:click="openMassRundown" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-3 bg-purple-50 dark:bg-purple-900/10 text-purple-700 dark:text-purple-300 border border-purple-100 dark:border-purple-800/30 hover:bg-purple-100 dark:hover:bg-purple-900/20 rounded-xl font-bold text-xs uppercase tracking-wide transition">
                                    <x-heroicon-s-table-cells class="w-4 h-4" /> 
                                    <span class="hidden sm:inline">Input</span> Massal
                                </button>

                                <button wire:click="$set('showRundownModal', true)" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold text-xs uppercase tracking-wide shadow-lg shadow-purple-500/20 transition transform active:scale-95">
                                    <x-heroicon-s-plus class="w-4 h-4" /> 
                                    Kegiatan Baru
                                </button>
                            </div>
                            
                        </div>

                        <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
                            <div class="max-w-4xl mx-auto">
                                @forelse($this->rundowns as $day => $activities)
                                    <div class="relative pl-8 md:pl-12 py-2 group/day">
                                        
                                        <div class="absolute left-[11px] md:left-[15px] top-0 bottom-0 w-0.5 bg-gradient-to-b from-purple-200 via-purple-100 to-transparent dark:from-purple-900 dark:via-purple-900/20 group-last/day:h-4"></div>
                                        
                                        <div class="absolute -left-2 top-0 w-10 h-10 md:w-12 md:h-12 bg-white dark:bg-zinc-900 rounded-2xl border-4 border-purple-50 dark:border-purple-900/20 flex flex-col items-center justify-center text-purple-700 dark:text-purple-400 font-black shadow-sm z-10">
                                            @if($rundownPhase === 'during')
                                                <span class="text-[8px] uppercase text-slate-400 leading-none mb-0.5">Hari</span>
                                                <span class="text-lg md:text-xl leading-none">{{ $day }}</span>
                                            @else
                                                <span class="text-xs md:text-sm">{{ \Carbon\Carbon::parse($day)->format('d') }}</span>
                                                <span class="text-[8px] uppercase">{{ \Carbon\Carbon::parse($day)->format('M') }}</span>
                                            @endif
                                        </div>
                                        
                                        <div class="mb-4 pl-4 md:pl-6 pt-1">
                                            <h3 class="text-lg font-black text-slate-800 dark:text-white flex items-center gap-3">
                                                @if($rundownPhase === 'during')
                                                    Hari Ke-{{ $day }}
                                                @else
                                                    {{ \Carbon\Carbon::parse($day)->translatedFormat('l, d F Y') }}
                                                @endif
                                                <span class="h-px flex-1 bg-slate-100 dark:bg-white/5"></span>
                                            </h3>
                                        </div>

                                        <div class="space-y-3 pl-4 md:pl-6 pb-8">
                                            @foreach($activities as $act)
                                            <div class="group relative bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-slate-100 dark:border-white/5 shadow-sm hover:shadow-md hover:border-purple-200 dark:hover:border-purple-800 transition-all duration-300">
                                                
                                                <div class="flex gap-4 items-start">
                                                    <div class="w-16 pt-1 flex flex-col items-center justify-center border-r border-slate-100 dark:border-white/5 pr-4">
                                                        <span class="text-lg font-black text-slate-700 dark:text-white leading-none">
                                                            {{ \Carbon\Carbon::parse($act->time_start)->format('H:i') }}
                                                        </span>
                                                        <div class="mt-1 px-2 py-0.5 bg-slate-100 dark:bg-white/5 rounded text-[9px] font-bold text-slate-500 uppercase">
                                                            WIB
                                                        </div>
                                                    </div>

                                                    <div class="flex-1 min-w-0">
                                                        <h4 class="font-bold text-slate-900 dark:text-white text-base mb-1 truncate">{{ $act->activity }}</h4>
                                                        
                                                        @if($act->location)
                                                        <div class="flex items-center gap-1.5 text-xs font-bold text-purple-600 dark:text-purple-400 mb-2">
                                                            <x-heroicon-s-map-pin class="w-3.5 h-3.5" />
                                                            {{ $act->location }}
                                                        </div>
                                                        @endif

                                                        @if($act->description)
                                                        <p class="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed bg-slate-50 dark:bg-zinc-800/50 p-2.5 rounded-lg border border-slate-100 dark:border-white/5">
                                                            {{ $act->description }}
                                                        </p>
                                                        @endif
                                                    </div>

                                                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity flex gap-2 bg-white dark:bg-zinc-900 shadow-sm rounded-lg p-1 border border-slate-100 dark:border-white/10">
                                                        <button wire:click="editRundown({{ $act->id }})" class="p-1.5 text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-md transition" title="Edit">
                                                            <x-heroicon-s-pencil-square class="w-4 h-4" />
                                                        </button>
                                                        <button wire:click="deleteRundown({{ $act->id }})" wire:confirm="Hapus kegiatan ini?" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition" title="Hapus">
                                                            <x-heroicon-s-trash class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-200 dark:border-white/5 rounded-[3rem]">
                                        <div class="w-20 h-20 bg-purple-50 dark:bg-purple-900/10 rounded-full flex items-center justify-center mb-4 text-purple-300 dark:text-purple-700">
                                            <x-heroicon-s-calendar-days class="w-10 h-10" />
                                        </div>
                                        <h3 class="text-lg font-black text-slate-700 dark:text-white uppercase tracking-tight">Belum Ada Kegiatan</h3>
                                        <p class="text-sm text-slate-400 dark:text-zinc-500 mt-1">Tambahkan kegiatan untuk fase <span class="font-bold text-purple-500">{{ ucfirst($rundownPhase) }}</span> ini.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div x-show="$wire.showRundownModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
                        
                        <div wire:click="$set('showRundownModal', false)" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>

                        <div class="relative bg-white dark:bg-zinc-900 w-full max-w-md rounded-[2.5rem] shadow-2xl flex flex-col border border-white/10 overflow-hidden" x-transition.scale>
                            
                            <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm">
                                <div>
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                        {{ $rdId ? 'Edit Kegiatan' : 'Kegiatan Baru' }}
                                    </h3>
                                    <p class="text-xs font-bold text-purple-500 uppercase tracking-widest mt-0.5">Fase: {{ ucfirst($rundownPhase) }}</p>
                                </div>
                                <button wire:click="$set('showRundownModal', false)" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                                    <x-heroicon-s-x-mark class="w-6 h-6" />
                                </button>
                            </div>

                            <div class="p-6 space-y-5">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        @if($rundownPhase === 'during')
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Hari Ke-</label>
                                            <input wire:model="rdDay" type="number" class="w-full bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl px-4 py-2.5 text-sm font-bold focus:border-purple-500 focus:ring-0 outline-none dark:text-white text-center" placeholder="1">
                                        @else
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Tanggal</label>
                                            <input wire:model="rdDate" type="date" class="w-full bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl px-4 py-2.5 text-sm font-bold focus:border-purple-500 focus:ring-0 outline-none dark:text-white">
                                        @endif
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Jam Mulai</label>
                                        <input wire:model="rdTime" type="time" class="w-full bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl px-4 py-2.5 text-sm font-bold focus:border-purple-500 focus:ring-0 outline-none dark:text-white text-center">
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Nama Kegiatan</label>
                                    <input wire:model="rdActivity" type="text" placeholder="Contoh: Ziarah Kota Madinah" 
                                        class="w-full bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl px-4 py-3 text-sm font-bold focus:border-purple-500 focus:ring-0 outline-none dark:text-white placeholder-slate-400">
                                </div>

                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Lokasi</label>
                                    <div class="relative">
                                        <input wire:model="rdLoc" type="text" placeholder="Contoh: Masjid Quba" 
                                            class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold focus:border-purple-500 focus:ring-0 outline-none dark:text-white placeholder-slate-400">
                                        <x-heroicon-s-map-pin class="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Catatan / Deskripsi</label>
                                    <textarea wire:model="rdDesc" rows="3" placeholder="Info tambahan untuk jamaah..." 
                                        class="w-full bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500 focus:ring-0 outline-none dark:text-white placeholder-slate-400 resize-none"></textarea>
                                </div>

                                <button wire:click="saveRundown" class="w-full py-3.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-purple-500/30 transition transform active:scale-95">
                                    Simpan Kegiatan
                                </button>
                            </div>

                        </div>
                    </div>
                    @endif

                @endif
            </div>

            <div x-show="$wire.showMassRundownModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
    
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" wire:click="$set('showMassRundownModal', false)"></div>

                <div class="relative bg-white dark:bg-zinc-900 w-full max-w-6xl rounded-[2.5rem] shadow-2xl flex flex-col max-h-[90vh] border border-white/10 overflow-hidden" x-transition.move.up>
                    
                    <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-20">
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-3 uppercase tracking-tight">
                                <div class="p-2 bg-purple-50 dark:bg-purple-500/10 rounded-xl text-purple-600 dark:text-purple-400">
                                    <x-heroicon-s-table-cells class="w-6 h-6" />
                                </div>
                                Input Rundown Massal
                            </h3>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-1 ml-14 uppercase tracking-widest">
                                Fase Kegiatan: <span class="text-purple-600 dark:text-purple-400">{{ $rundownPhase }}</span>
                            </p>
                        </div>
                        <button wire:click="$set('showMassRundownModal', false)" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                            <x-heroicon-s-x-mark class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="p-0 overflow-auto custom-scrollbar flex-1 bg-white dark:bg-zinc-900">
                        <table class="w-full text-sm text-left whitespace-nowrap">
                            <thead class="text-[10px] text-slate-500 dark:text-zinc-400 uppercase bg-slate-50 dark:bg-zinc-950/80 sticky top-0 z-10 font-black tracking-wider border-b border-slate-200 dark:border-white/5">
                                <tr>
                                    <th class="px-4 py-4 w-12 text-center">No</th>
                                    
                                    <th class="px-4 py-4 w-40 bg-purple-50/50 dark:bg-purple-900/10 border-l border-r border-purple-100 dark:border-white/5">
                                        {{ $rundownPhase === 'during' ? 'Hari Ke-' : 'Tanggal' }}
                                    </th>
                                    
                                    <th class="px-4 py-4 w-32 bg-purple-50/50 dark:bg-purple-900/10 border-r border-purple-100 dark:border-white/5">Jam Mulai</th>
                                    <th class="px-4 py-4 min-w-[250px]">Nama Kegiatan <span class="text-red-500">*</span></th>
                                    <th class="px-4 py-4 w-64">Lokasi</th>
                                    <th class="px-4 py-4 min-w-[250px]">Deskripsi / Catatan</th>
                                    <th class="px-4 py-4 w-16 text-center border-l border-slate-100 dark:border-white/5">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                @foreach($massRows as $index => $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition group">
                                    <td class="px-4 py-3 text-center text-slate-400 font-mono text-xs">{{ $index + 1 }}</td>
                                    
                                    <td class="px-2 py-2 bg-purple-50/20 dark:bg-purple-900/5 border-x border-purple-50 dark:border-white/5">
                                        @if($rundownPhase === 'during')
                                            <input type="number" wire:model="massRows.{{ $index }}.day_or_date" 
                                                class="w-full bg-transparent border-b-2 border-transparent focus:border-purple-500 focus:ring-0 text-center font-bold text-purple-700 dark:text-purple-300 placeholder-purple-300 p-2 transition-colors outline-none" 
                                                placeholder="1">
                                        @else
                                            <input type="date" wire:model="massRows.{{ $index }}.day_or_date" 
                                                class="w-full bg-transparent border-b-2 border-transparent focus:border-purple-500 focus:ring-0 text-sm font-bold text-purple-700 dark:text-purple-300 p-2 transition-colors outline-none cursor-pointer">
                                        @endif
                                    </td>

                                    <td class="px-2 py-2 bg-purple-50/20 dark:bg-purple-900/5 border-r border-purple-50 dark:border-white/5">
                                        <input type="time" wire:model="massRows.{{ $index }}.time_start" 
                                            class="w-full bg-transparent border-b-2 border-transparent focus:border-purple-500 focus:ring-0 text-center font-bold text-slate-700 dark:text-white p-2 transition-colors outline-none cursor-pointer">
                                    </td>

                                    <td class="px-4 py-2">
                                        <input type="text" wire:model="massRows.{{ $index }}.activity" 
                                            class="w-full bg-transparent border-b border-slate-200 dark:border-white/10 focus:border-purple-500 focus:ring-0 font-bold text-slate-800 dark:text-white placeholder-slate-300 dark:placeholder-zinc-600 p-2 transition-colors text-sm" 
                                            placeholder="Nama Kegiatan...">
                                        @error("massRows.{$index}.activity") <span class="text-[9px] text-red-500 font-bold block mt-1 ml-2">Wajib diisi</span> @enderror
                                    </td>

                                    <td class="px-4 py-2">
                                        <input type="text" wire:model="massRows.{{ $index }}.location" 
                                            class="w-full bg-transparent border-b border-slate-200 dark:border-white/10 focus:border-purple-500 focus:ring-0 text-slate-600 dark:text-zinc-300 placeholder-slate-300 dark:placeholder-zinc-600 p-2 transition-colors text-sm" 
                                            placeholder="Lokasi...">
                                    </td>

                                    <td class="px-4 py-2">
                                        <input type="text" wire:model="massRows.{{ $index }}.description" 
                                            class="w-full bg-transparent border-b border-slate-200 dark:border-white/10 focus:border-purple-500 focus:ring-0 text-slate-500 dark:text-zinc-400 placeholder-slate-300 dark:placeholder-zinc-600 p-2 transition-colors text-xs" 
                                            placeholder="Keterangan tambahan...">
                                    </td>

                                    <td class="px-2 py-2 text-center border-l border-slate-100 dark:border-white/5">
                                        <button wire:click="removeMassRow({{ $index }})" class="p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                            <x-heroicon-s-trash class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        
                        <div class="p-6 text-center border-t border-dashed border-slate-200 dark:border-white/10 bg-slate-50/30 dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 transition cursor-pointer" wire:click="addMassRow">
                            <button class="text-xs font-black text-purple-600 dark:text-purple-400 flex items-center justify-center gap-2 mx-auto uppercase tracking-widest">
                                <x-heroicon-s-plus-circle class="w-5 h-5" /> Tambah Baris Baru
                            </button>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-zinc-900/80 flex justify-between items-center shrink-0">
                        <p class="text-xs text-slate-400 dark:text-zinc-500 italic font-medium flex items-center gap-1.5">
                            <x-heroicon-s-information-circle class="w-4 h-4" />
                            Pastikan data sudah benar sebelum disimpan.
                        </p>
                        <div class="flex gap-3">
                            <button wire:click="$set('showMassRundownModal', false)" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-200 dark:text-zinc-400 dark:hover:bg-zinc-800 text-xs uppercase tracking-widest transition">
                                Batal
                            </button>
                            <button wire:click="saveMassRundown" class="px-8 py-3 rounded-xl font-black text-white bg-purple-600 hover:bg-purple-700 shadow-lg shadow-purple-500/30 text-xs uppercase tracking-widest transition transform hover:scale-105 active:scale-95 flex items-center gap-2">
                                <x-heroicon-s-check-circle class="w-5 h-5" />
                                Simpan Semua ({{ count($massRows) }})
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div x-show="showMediaModal" class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-6" style="display: none;" x-transition.opacity>
    
                <div @click="showMediaModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>

                <div class="relative bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh] border border-white/10 overflow-hidden" x-transition.move.bottom>
                    
                    <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                                <div class="p-2 bg-indigo-50 dark:bg-indigo-500/10 rounded-xl text-indigo-600 dark:text-indigo-400">
                                    <x-heroicon-s-swatch class="w-5 h-5" />
                                </div>
                                Creative Support
                            </h3>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-1 ml-12 uppercase tracking-widest">
                                Upload & Request Desain
                            </p>
                        </div>
                        <button @click="showMediaModal = false" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                            <x-heroicon-s-x-mark class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="p-2 mx-6 mt-4 bg-slate-100 dark:bg-zinc-800 rounded-2xl flex shrink-0">
                        <button wire:click="$set('mediaTab', 'upload')" 
                            class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition flex justify-center items-center gap-2 
                            {{ $mediaTab === 'upload' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                            <x-heroicon-s-arrow-up-tray class="w-4 h-4" /> Upload Aset
                        </button>
                        <button wire:click="$set('mediaTab', 'request')" 
                            class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition flex justify-center items-center gap-2 
                            {{ $mediaTab === 'request' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                            <x-heroicon-s-pencil-square class="w-4 h-4" /> Request Desain
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
                        
                        <div x-show="$wire.mediaTab === 'upload'" x-transition:enter.duration.300ms>
                            <div class="space-y-5">
                                
                                <div class="bg-indigo-50 dark:bg-indigo-900/10 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-800/30 flex items-start gap-3">
                                    <x-heroicon-s-information-circle class="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-xs font-bold text-indigo-900 dark:text-indigo-100 uppercase tracking-wide mb-1">Target Upload</p>
                                        <p class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                                            {{ $this->packages->find($selectedPackageId)?->name ?? 'Belum ada grup yang dipilih' }}
                                        </p>
                                    </div>
                                </div>

                                @if(!$selectedPackageId)
                                    <div class="flex flex-col items-center justify-center py-8 text-center border-2 border-dashed border-red-200 bg-red-50/50 rounded-2xl">
                                        <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-red-400 mb-2" />
                                        <p class="text-xs font-bold text-red-500 uppercase tracking-widest">Pilih Grup Dulu!</p>
                                        <p class="text-[10px] text-red-400 mt-1">Silakan pilih grup keberangkatan di menu utama.</p>
                                    </div>
                                @else
                                    <div class="relative group cursor-pointer">
                                        <div class="border-2 border-dashed border-slate-300 dark:border-zinc-700 rounded-2xl p-8 text-center bg-slate-50 dark:bg-zinc-800/50 hover:border-indigo-500 dark:hover:border-indigo-500 transition-colors">
                                            <input type="file" wire:model="mediaPhotos" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                            
                                            <div class="space-y-3 pointer-events-none">
                                                <div wire:loading wire:target="mediaPhotos">
                                                    <x-heroicon-o-arrow-path class="w-12 h-12 mx-auto text-indigo-500 animate-spin" />
                                                    <p class="text-xs text-indigo-600 font-bold uppercase tracking-widest mt-2">Sedang Mengupload...</p>
                                                </div>
                                                <div wire:loading.remove wire:target="mediaPhotos">
                                                    <div class="w-14 h-14 bg-white dark:bg-zinc-700 shadow-sm rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition text-indigo-500">
                                                        <x-heroicon-s-camera class="w-7 h-7" />
                                                    </div>
                                                    <p class="text-sm font-bold text-slate-700 dark:text-zinc-200 group-hover:text-indigo-600 transition">Ambil Foto / Pilih File</p>
                                                    <p class="text-[10px] text-slate-400 font-medium mt-1">Bisa upload banyak sekaligus</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Tags Tambahan</label>
                                        <div class="relative">
                                            <input wire:model="mediaTags" type="text" placeholder="Contoh: manasik, bandara, hotel..." 
                                                class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all placeholder:text-slate-400">
                                            <x-heroicon-s-tag class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                        </div>
                                    </div>

                                    <button wire:click="saveMediaAssets" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-500/30 mt-4 flex justify-center gap-2 transition transform active:scale-95 text-xs uppercase tracking-widest">
                                        <x-heroicon-s-cloud-arrow-up class="w-4 h-4" />
                                        Kirim Dokumentasi
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div x-show="$wire.mediaTab === 'request'" x-transition:enter.duration.300ms>
                            <div class="space-y-5">
                                
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Judul Request</label>
                                    <input wire:model="reqTitle" type="text" placeholder="Contoh: Desain ID Card Jamaah" 
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all placeholder:text-slate-400">
                                </div>

                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Deskripsi Detail</label>
                                    <textarea wire:model="reqDesc" rows="4" placeholder="Jelaskan kebutuhan warna, teks, referensi, dll..." 
                                        class="w-full p-4 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-medium text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all resize-none placeholder:text-slate-400"></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Deadline</label>
                                        <div class="relative">
                                            <input wire:model="reqDeadline" type="date" 
                                                class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all cursor-pointer">
                                            <x-heroicon-s-calendar class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Prioritas</label>
                                        <div class="relative">
                                            <select wire:model="reqPriority" class="w-full pl-4 pr-10 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all appearance-none cursor-pointer">
                                                <option value="low">☕ Low (Santai)</option>
                                                <option value="medium">📝 Medium (Standar)</option>
                                                <option value="high">🔥 High (Urgent)</option>
                                            </select>
                                            <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                        </div>
                                    </div>
                                </div>

                                <button wire:click="saveContentRequest" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-500/30 mt-4 flex justify-center gap-2 transition transform active:scale-95 text-xs uppercase tracking-widest">
                                    <x-heroicon-s-paper-airplane class="w-4 h-4" />
                                    Kirim Request
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </main>

        <nav class="md:hidden fixed bottom-6 left-4 right-4 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-lg border border-slate-200 dark:border-white/5 flex justify-around items-center py-3 z-40 rounded-3xl shadow-[0_10px_30px_-10px_rgba(0,0,0,0.2)]"
            style="
                background-image: url('/images/ornaments/arabesque.png');
                background-repeat: repeat;
                background-size: 150px 150px;
            ">
            
            <button wire:click="setTab('manifest')" 
                class="relative flex flex-col items-center gap-1 w-16 transition-all duration-300 {{ $activeTab === 'manifest' ? 'text-emerald-600 dark:text-emerald-400 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                @if($activeTab === 'manifest') <div class="absolute -top-3 w-1 h-1 bg-emerald-600 rounded-full"></div> @endif
                <x-heroicon-s-document-text class="w-6 h-6" />
                <span class="text-[9px] font-black uppercase tracking-tighter">Dokumen</span>
            </button>

            <button wire:click="setTab('rooming')" 
                class="relative flex flex-col items-center gap-1 w-16 transition-all duration-300 {{ $activeTab === 'rooming' ? 'text-pink-600 dark:text-pink-400 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                @if($activeTab === 'rooming') <div class="absolute -top-3 w-1 h-1 bg-pink-600 rounded-full"></div> @endif
                <x-heroicon-s-home-modern class="w-6 h-6" />
                <span class="text-[9px] font-black uppercase tracking-tighter">Rooming</span>
            </button>

            <button wire:click="setTab('logistics')" 
                class="relative flex flex-col items-center gap-1 w-16 transition-all duration-300 {{ $activeTab === 'logistics' ? 'text-orange-600 dark:text-orange-400 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                @if($activeTab === 'logistics') <div class="absolute -top-3 w-1 h-1 bg-orange-600 rounded-full"></div> @endif
                <x-heroicon-s-cube class="w-6 h-6" />
                <span class="text-[9px] font-black uppercase tracking-tighter">Logistik</span>
            </button>

            <button wire:click="setTab('rundown')" 
                class="relative flex flex-col items-center gap-1 w-16 transition-all duration-300 {{ $activeTab === 'rundown' ? 'text-purple-600 dark:text-purple-400 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                @if($activeTab === 'rundown') <div class="absolute -top-3 w-1 h-1 bg-purple-600 rounded-full"></div> @endif
                <x-heroicon-s-calendar-days class="w-6 h-6" />
                <span class="text-[9px] font-black uppercase tracking-tighter">Rundown</span>
            </button>

        </nav>

    </div>
</div>
