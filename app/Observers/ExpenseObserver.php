<?php

namespace App\Observers;

use App\Models\CashTransaction;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        if ($expense->office_wallet_id) {
            CashTransaction::create([
                'office_wallet_id' => $expense->office_wallet_id, 
                'expense_id' => $expense->id, 
                'type' => 'withdrawal', 
                'amount' => $expense->amount,
                'transaction_date' => $expense->transaction_date,
                'description' => 'Expense: ' . $expense->name,
                'user_id' => Auth::id() ?? 1, 
            ]);
        }
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        if ($expense->office_wallet_id) {
            CashTransaction::where('expense_id', $expense->id)->delete();
        }
    }

    /**
     * Handle the Expense "restored" event.
     */
    public function restored(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "force deleted" event.
     */
    public function forceDeleted(Expense $expense): void
    {
        //
    }
}
