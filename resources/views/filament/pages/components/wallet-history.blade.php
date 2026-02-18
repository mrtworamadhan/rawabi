<div class="p-2">
    <h3 class="text-lg font-bold mb-4">10 Transaksi Terakhir</h3>
    
    <div class="relative overflow-x-auto border rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-2">Tanggal</th>
                    <th scope="col" class="px-4 py-2">Keterangan</th>
                    <th scope="col" class="px-4 py-2 text-right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($wallet->transactions()->latest()->limit(10)->get() as $trx)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-4 py-2">
                            {{ $trx->transaction_date->format('d M Y') }}
                        </td>
                        <td class="px-4 py-2">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ $trx->description }}
                            </div>
                            <span class="text-xs {{ $trx->type === 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $trx->type === 'deposit' ? 'Uang Masuk' : 'Uang Keluar' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right font-bold {{ $trx->type === 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $trx->type === 'deposit' ? '+' : '-' }} 
                            {{ number_format($trx->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-4 text-center text-gray-400">
                            Belum ada transaksi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4 text-xs text-gray-400 text-center">
        *Hanya menampilkan 10 transaksi terakhir. Untuk lengkapnya, masuk ke menu Edit.
    </div>
</div>