<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\OfficeWallet;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PrintController extends Controller
{
    // 1. CETAK KUITANSI PEMBAYARAN (Per Transaksi)
    public function printInvoice($id)
    {
        $payment = Payment::with(['booking.jamaah', 'booking.umrahPackage', 'officeWallet'])->findOrFail($id);
        
        $totalBayar = $payment->booking->payments()
            ->where('created_at', '<=', $payment->created_at)
            ->whereNotNull('verified_at')
            ->sum('amount');
            
        $sisaTagihan = $payment->booking->total_price - $totalBayar;

        $pdf = Pdf::loadView('pdf.invoice', [
            'payment' => $payment,
            'sisaTagihan' => $sisaTagihan,
            'terbilang' => $this->terbilang($payment->amount) . ' Rupiah',
        ]);

        return $pdf->stream('Kuitansi-' . $payment->booking->booking_code . '.pdf');
    }

    public function printDailyReport()
    {
        $date = today();

        $todayIncome = Payment::whereDate('created_at', $date)->whereNotNull('verified_at')->sum('amount');
        
        $laciBalance = OfficeWallet::where('type', 'cashier')->sum('balance');
        $pettyBalance = OfficeWallet::where('type', 'petty_cash')->sum('balance');
        
        $bankWallets = OfficeWallet::where('type', 'bank')
            ->withSum(['payments' => fn($q) => $q->whereDate('created_at', $date)->whereNotNull('verified_at')], 'amount')
            ->get();

        $expenses = Expense::with(['category', 'wallet', 'approver'])
            ->whereDate('transaction_date', $date)
            ->where('status', 'approved')
            ->get();

        $totalExpense = $expenses->sum('amount');
        $expenseOperasional = $expenses->filter(fn($e) => $e->wallet && $e->wallet->type === 'petty_cash')->sum('amount');
        $expenseHpp = $expenses->filter(fn($e) => $e->wallet && $e->wallet->type !== 'petty_cash')->sum('amount');

        $pdf = Pdf::loadView('pdf.daily_report', compact(
            'date', 'todayIncome', 'laciBalance', 'pettyBalance', 
            'bankWallets', 'expenses', 'totalExpense', 
            'expenseOperasional', 'expenseHpp'
        ));

        return $pdf->setPaper('a4', 'portrait')->stream('Laporan-Harian-' . $date->format('Y-m-d') . '.pdf');
    }

    private function terbilang($nilai) {
        $nilai = abs($nilai);
        $huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " ". $huruf[$nilai];
        } else if ($nilai <20) {
            $temp = $this->terbilang($nilai - 10). " Belas";
        } else if ($nilai < 100) {
            $temp = $this->terbilang($nilai/10)." Puluh". $this->terbilang($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " Seratus" . $this->terbilang($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = $this->terbilang($nilai/100) . " Ratus" . $this->terbilang($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " Seribu" . $this->terbilang($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = $this->terbilang($nilai/1000) . " Ribu" . $this->terbilang($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = $this->terbilang($nilai/1000000) . " Juta" . $this->terbilang($nilai % 1000000);
        }
        return $temp;
    }
}