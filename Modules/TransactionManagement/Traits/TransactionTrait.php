<?php

namespace Modules\TransactionManagement\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAccount;
use Modules\TransactionManagement\Entities\Transaction;
use Modules\UserManagement\Lib\LevelHistoryManagerTrait;

trait TransactionTrait
{

    use LevelHistoryManagerTrait;

    public function digitalPaymentTransaction($trip): void
    {
        $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;

        DB::beginTransaction();
        $admin_commission = $trip->fee->admin_commission; //30
        $admin_received = $admin_commission; //30
        $admin_payable = $trip->paid_fare - $admin_received; //70
        if ($trip->coupon_id !== null && $trip->coupon_amount > 0) {
            $admin_payable += $trip->coupon_amount;
        }

        //Admin account update
        $account = UserAccount::query()->firstWhere('user_id', $admin_user_id);
        $account->payable_balance += $admin_payable; //70
        $account->received_balance += $admin_received; //30
        $account->save();

        //Admin transaction 1
        $primary_transaction = new Transaction();
        $primary_transaction->attribute = 'driver_earning';
        $primary_transaction->attribute_id = $trip->id;
        $primary_transaction->credit = $admin_payable;
        $primary_transaction->balance = $account->payable_balance;
        $primary_transaction->user_id = $admin_user_id;
        $primary_transaction->account = 'payable_balance';
        $primary_transaction->save();

        //Admin transaction 2
        $transaction = new Transaction();
        $transaction->attribute = 'admin_commission';
        $transaction->attribute_id = $trip->id;
        $transaction->credit = $admin_received;
        $transaction->balance = $account->received_balance;
        $transaction->user_id = $admin_user_id;
        $transaction->account = 'received_balance';
        $transaction->trx_ref_id = $primary_transaction->id;
        $transaction->save();

        //Rider account update
        $rider_account = UserAccount::query()->firstWhere('user_id', $trip->driver->id);
        $rider_account->receivable_balance += $admin_payable; //70
        $rider_account->save();

        $this->amountChecker(User::query()->firstWhere('id', $trip->driver->id), $admin_payable);

        //Rider transaction
        $rider_trx = new Transaction();
        $rider_trx->attribute = 'driver_earning';
        $rider_trx->attribute_id = $trip->id;
        $rider_trx->credit = $admin_payable;
        $rider_trx->balance = $rider_account->receivable_balance;
        $rider_trx->user_id = $trip->driver->id;
        $rider_trx->account = 'receivable_balance';
        $rider_trx->trx_ref_id = $primary_transaction->id;
        $rider_trx->save();
        DB::commit();

    }

    public function cashTransaction($trip): void
    {
        $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;
        DB::beginTransaction();
        $admin_received = $trip->fee->admin_commission;
        $trip_balance_after_remove_commission = $trip->paid_fare - $trip->fee->admin_commission; //70

        //Rider account update
        $rider_account = UserAccount::where('user_id', $trip->driver->id)->first();
        $rider_account->payable_balance += $admin_received; //30
        $rider_account->received_balance += $trip_balance_after_remove_commission; //70
        $rider_account->receivable_balance += $trip->coupon_amount; //70
        $rider_account->save();

        //Rider transaction 1
        $primary_transaction = new Transaction();
        $primary_transaction->attribute = 'driver_earning';
        $primary_transaction->attribute_id = $trip->id;
        $primary_transaction->credit = $trip_balance_after_remove_commission;
        $primary_transaction->balance = $rider_account->received_balance;
        $primary_transaction->user_id = $trip->driver->id;
        $primary_transaction->account = 'received_balance';
        $primary_transaction->save();

        //Rider transaction 2
        $transaction = new Transaction();
        $transaction->attribute = 'admin_commission';
        $transaction->attribute_id = $trip->id;
        $transaction->credit = $admin_received;
        $transaction->balance = $rider_account->payable_balance;
        $transaction->user_id = $trip->driver->id;
        $transaction->account = 'payable_balance';
        $transaction->trx_ref_id = $primary_transaction->id;
        $transaction->save();

        if ($trip->coupon_id !== null && $trip->coupon_amount > 0){
            //Rider transaction 3
            $primary_transaction = new Transaction();
            $primary_transaction->attribute = 'driver_earning';
            $primary_transaction->attribute_id = $trip->id;
            $primary_transaction->credit = $trip->coupon_amount;
            $primary_transaction->balance = $rider_account->receivable_balance;
            $primary_transaction->user_id = $trip->driver->id;
            $primary_transaction->account = 'receivable_balance';
            $primary_transaction->save();
        }

        //Admin account update
        $admin_account = UserAccount::where('user_id', $admin_user_id)->first();
        $admin_account->receivable_balance += $admin_received; //30
        if ($trip->coupon_id !== null && $trip->coupon_amount > 0) {
            $admin_account->payable_balance += $trip->coupon_amount;
        }
        $admin_account->save();

        //Admin transaction 1
        $admin_trx = new Transaction();
        $admin_trx->attribute = 'admin_commission';
        $admin_trx->attribute_id = $trip->id;
        $admin_trx->credit = $admin_received;
        $admin_trx->balance = $admin_account->receivable_balance;
        $admin_trx->user_id = $admin_user_id;
        $admin_trx->account = 'receivable_balance';
        $admin_trx->trx_ref_id = $primary_transaction->id;
        $admin_trx->save();

        if ($trip->coupon_id !== null && $trip->coupon_amount > 0){
            //Admin transaction 2
            $primary_transaction = new Transaction();
            $primary_transaction->attribute = 'driver_earning';
            $primary_transaction->attribute_id = $trip->id;
            $primary_transaction->credit = $trip->coupon_amount;
            $primary_transaction->balance = $admin_account->payable_balance;
            $primary_transaction->user_id = $admin_user_id;
            $primary_transaction->account = 'payable_balance';
            $primary_transaction->save();
        }

        $this->amountChecker(User::query()->firstWhere('id', $trip->driver->id), $trip_balance_after_remove_commission);

        DB::commit();
    }

    public function walletTransaction($trip): void
    {
        $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;

        DB::beginTransaction();
        $admin_received = $trip->fee->admin_commission;
        $trip_balance_after_remove_commission = $trip->paid_fare - $trip->fee->admin_commission; //70
        if ($trip->coupon_id !== null && $trip->coupon_amount > 0) {
            $trip_balance_after_remove_commission += $trip->coupon_amount;
        }


        //customer account debit
        $customerAccount = UserAccount::where('user_id', $trip->customer->id)->first();
        $customerAccount->wallet_balance -= $trip->paid_fare;
        $customerAccount->save();

        //customer transaction (debit)
        $primary_transaction = new Transaction();
        $primary_transaction->attribute = 'wallet_payment';
        $primary_transaction->attribute_id = $trip->id;
        $primary_transaction->debit = $trip->paid_fare;
        $primary_transaction->balance = $customerAccount->wallet_balance;
        $primary_transaction->user_id = $trip->customer->id;
        $primary_transaction->account = 'wallet_balance';
        $primary_transaction->save();

        //Admin account update (payable and wallet balance +)
        $admin_account = UserAccount::query()->firstWhere('user_id', $admin_user_id);
        $admin_account->payable_balance += $trip_balance_after_remove_commission;
        $admin_account->received_balance += $admin_received;
        $admin_account->save();

        //Admin transaction 1 (payable)
        $admin_trx = new Transaction();
        $admin_trx->attribute = 'driver_earning';
        $admin_trx->attribute_id = $trip->id;
        $admin_trx->credit = $trip_balance_after_remove_commission;
        $admin_trx->balance = $admin_account->payable_balance;
        $admin_trx->user_id = $admin_user_id;
        $admin_trx->account = 'payable_balance';
        $admin_trx->trx_ref_id = $primary_transaction->id;
        $admin_trx->save();

        //Admin transaction 2 ( + received balance)
        $admin_trx_2 = new Transaction();
        $admin_trx_2->attribute = 'admin_commission';
        $admin_trx_2->attribute_id = $trip->id;
        $admin_trx_2->credit = $admin_received;
        $admin_trx_2->balance = $admin_account->received_balance;
//            $admin_trx_2->wallet_balance = $admin_account->wallet_balance;
        $admin_trx_2->user_id = $admin_user_id;
        $admin_trx_2->account = 'received_balance';
        $admin_trx_2->trx_ref_id = $primary_transaction->id;
        $admin_trx_2->save();

        //Rider account update (+ receivable_balance)
        $rider_account = UserAccount::query()->firstWhere('user_id', $trip->driver->id);
        $rider_account->receivable_balance += $trip_balance_after_remove_commission; //70
        $rider_account->save();

        $this->amountChecker(User::query()->firstWhere('id', $trip->driver->id), $trip_balance_after_remove_commission);

        //Rider transaction 1
        $primary_transaction = new Transaction();
        $primary_transaction->attribute = 'driver_earning';
        $primary_transaction->attribute_id = $trip->id;
        $primary_transaction->credit = $trip_balance_after_remove_commission;
        $primary_transaction->balance = $rider_account->receivable_balance;
        $primary_transaction->user_id = $trip->driver->id;
        $primary_transaction->account = 'receivable_balance';
        $primary_transaction->save();
        DB::commit();

    }

    public function customerLoyaltyPointsTransaction($user, $amount): Model|Builder|null
    {
        DB::beginTransaction();
        //Customer account update
        $customer = UserAccount::query()->firstWhere('user_id', $user->id);
        $customer->wallet_balance += $amount;
        $customer->save();

        //customer transaction (credit)
        $primary_transaction = new Transaction();
        $primary_transaction->attribute = 'point_conversion';
        $primary_transaction->credit = $amount;
        $primary_transaction->balance = $customer->wallet_balance;
//        $primary_transaction->wallet_balance = $customer->wallet_balance;
        $primary_transaction->user_id = $user->id;
        $primary_transaction->account = 'wallet_balance';
        $primary_transaction->save();

        DB::commit();

        return $customer;
    }

    public function driverLoyaltyPointsTransaction($user, $amount): Model|Builder|null
    {
        DB::beginTransaction();
        //Customer account update
        $driver = UserAccount::query()->firstWhere('user_id', $user->id);
        $driver->receivable_balance += $amount;
        $driver->save();

        //Driver transaction (credit)
        $primary_transaction = new Transaction();
        $primary_transaction->attribute = 'point_conversion';
        $primary_transaction->credit = $amount;
        $primary_transaction->balance = $driver->receivable_balance;
//        $primary_transaction->wallet_balance = $customer->wallet_balance;
        $primary_transaction->user_id = $user->id;
        $primary_transaction->account = 'receivable_balance';
        $primary_transaction->save();

        DB::commit();

        return $driver;
    }

    public function withdrawRequestTransaction($user, $amount, $attribute)
    {
        DB::beginTransaction();
        //Driver account update
        $driver = UserAccount::where('user_id', $user->id)->first();
        $driver->receivable_balance -= $amount;
        $driver->pending_balance += $amount;
        $driver->save();
        //Admin account update
        $admin = User::query()->where('user_type', 'super-admin')->first();
        $admin_user = UserAccount::query()->where('user_id', $admin->id)->first();
        $admin_user->payable_balance += $amount;
        $admin_user->save();

        //customer transaction (debit)
//        $first_trx = new Transaction();
//        $first_trx->attribute = 'withdraw_requested';
//        $first_trx->attribute_id = $attribute->id;
//        $first_trx->debit = $amount;
//        $first_trx->balance = $driver->receivable_balance;
////        $first_trx->wallet_balance = $driver->wallet_balance;
//        $first_trx->user_id = $user->id;
//        $first_trx->account = 'receivable_balance';
//        $first_trx->save();

        //Driver transaction (credit)
        $second_trx = new Transaction();
        $second_trx->attribute = 'pending_withdrawn';
        $second_trx->attribute_id = $attribute->id;
        $second_trx->credit = $amount;
        $second_trx->balance = $driver->pending_balance;
//        $second_trx->pending_balance = $driver->pending_balance;
        $second_trx->user_id = $user->id;
        $second_trx->account = 'pending_withdraw_balance';
//        $second_trx->trx_ref_id = $first_trx->id;
        $second_trx->save();

        DB::commit();

        return $driver;
    }

    public function withdrawRequestCancelTransaction($user, $amount, $attribute)
    {
        DB::beginTransaction();
        //Driver account update
        $driver = UserAccount::where('user_id', $user->id)->first();
        $driver->receivable_balance += $amount;
        $driver->pending_balance -= $amount;
        $driver->save();

        //Driver transaction (credit)
//        $first_trx = new Transaction();
//        $first_trx->attribute = 'withdraw_request_cancelled';
//        $first_trx->attribute_id = $attribute->id;
//        $first_trx->credit = $amount;
//        $first_trx->balance = $driver->receivable_balance;
////        $first_trx->wallet_balance = $customer->wallet_balance;
//        $first_trx->user_id = $user->id;
//        $first_trx->account = 'receivable_balance';
//        $first_trx->save();

        //Driver transaction (debit)
        $second_trx = new Transaction();
        $second_trx->attribute = 'pending_withdraw_revoked';
        $second_trx->attribute_id = $attribute->id;
        $second_trx->debit = $amount;
        $second_trx->balance = $driver->pending_balance;
//        $second_trx->pending_balance = $driver->pending_balance;
        $second_trx->user_id = $user->id;
        $second_trx->account = 'withdraw_balance_rejected';
//        $second_trx->trx_ref_id = $first_trx->id;
        $second_trx->save();

        DB::commit();
        return $driver;
    }

    public function withdrawRequestAcceptTransaction($user, $amount, $attribute)
    {
        DB::beginTransaction();
        //Customer account update
        $customer = UserAccount::where('user_id', $user->id)->first();
        $customer->pending_balance -= $amount;
        $customer->total_withdrawn += $amount;
        $customer->save();

        //customer transaction (credit)
//        $first_trx = new Transaction();
//        $first_trx->attribute = 'pending_withdraw_proceeded';
//        $first_trx->attribute_id = $attribute->id;
//        $first_trx->debit = $amount;
//        $first_trx->balance = $customer->pending_balance;
//        $first_trx->user_id = $user->id;
//        $first_trx->account = 'pending_balance';
//        $first_trx->save();

        //customer transaction (debit)
        $second_trx = new Transaction();
        $second_trx->attribute = 'withdraw_request_accepted';
        $second_trx->attribute_id = $attribute->id;
        $second_trx->credit = $amount;
        $second_trx->balance = $customer->total_withdrawn;
        $second_trx->user_id = $user->id;
        $second_trx->account = 'received_withdraw_balance';
//        $second_trx->trx_ref_id = $first_trx->id;
        $second_trx->save();


        //Admin account update
        $admin = User::query()->where('user_type', 'super-admin')->first();
        $admin_user = UserAccount::query()->where('user_id', $admin->id)->first();
        $admin_user->payable_balance -= $amount;
        $admin_user->save();

        //admin transaction (credit)
        $third_trx = new Transaction();
        $third_trx->attribute = 'withdraw_request_approved';
        $third_trx->attribute_id = $attribute->id;
        $third_trx->debit = $amount;
        $third_trx->balance = $admin_user->payable_balance;
        $third_trx->user_id = $admin->id;
        $third_trx->account = 'withdraw_balance_paid';
        $third_trx->trx_ref_id = $second_trx->id;
        $third_trx->save();

        DB::commit();

        return $customer;
    }


    public function customerLevelRewardTransaction($user, $amount): void
    {
        DB::beginTransaction();
        //Customer account update
        $customer = UserAccount::query()->firstWhere('user_id', $user->id);
        $customer->wallet_balance += $amount;
        $customer->save();

        //customer transaction (credit)
        $primary_transaction = new Transaction();
        $primary_transaction->attribute = 'level_reward';
        $primary_transaction->credit = $amount;
        $primary_transaction->balance = $customer->wallet_balance;
//        $primary_transaction->wallet_balance = $customer->wallet_balance;
        $primary_transaction->user_id = $user->id;
        $primary_transaction->account = 'wallet_balance';
        $primary_transaction->save();

        DB::commit();
    }

    public function driverLevelRewardTransaction($user, $amount): void
    {
        DB::beginTransaction();
        //Customer account update
        $driver = UserAccount::query()->firstWhere('user_id', $user->id);
        $driver->receivable_balance += $amount;
        $driver->save();

        //customer transaction (credit)
        $primary_transaction = new Transaction();
        $primary_transaction->attribute = 'level_reward';
        $primary_transaction->credit = $amount;
        $primary_transaction->balance = $driver->receivable_balance;
//        $primary_transaction->wallet_balance = $driver->wallet_balance;
        $primary_transaction->user_id = $user->id;
        $primary_transaction->account = 'receivable_balance';
        $primary_transaction->save();

        DB::commit();
    }

    public function collectCashTransaction($user, $amount)
    {
        DB::beginTransaction();

        //Driver account update
        $driver = UserAccount::query()->firstWhere('user_id', $user->id);
        $driver->payable_balance -= $amount;
        $driver->save();

        //Admin account update
        $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;
        $account = UserAccount::query()->firstWhere('user_id', $admin_user_id);
        $account->received_balance += $amount;
        $account->receivable_balance -= $amount;
        $account->save();

        //Driver transaction (debit)
        $driver_transaction = new Transaction();
        $driver_transaction->attribute = 'admin_cash_collect';
        $driver_transaction->debit = $amount;
        $driver_transaction->balance = $driver->payable_balance;
        $driver_transaction->user_id = $user->id;
        $driver_transaction->account = 'payable_balance';
        $driver_transaction->save();

        //Admin transaction (credit)
        $admin_transaction = new Transaction();
        $admin_transaction->attribute = 'admin_cash_collect';
        $admin_transaction->credit = $amount;
        $admin_transaction->balance = $driver->received_balance;
        $admin_transaction->user_id = $admin_user_id;
        $admin_transaction->account = 'received_balance';
        $admin_transaction->trx_ref_id = $driver_transaction->id;
        $admin_transaction->save();

        //Admin transaction 2 (debit)
        $admin_transaction_2 = new Transaction();
        $admin_transaction_2->attribute = 'admin_cash_collect';
        $admin_transaction_2->debit = $amount;
        $admin_transaction_2->balance = $driver->receivable_balance;
        $admin_transaction_2->user_id = $admin_user_id;
        $admin_transaction_2->account = 'receivable_balance';
        $admin_transaction_2->trx_ref_id = $driver_transaction->id;
        $admin_transaction_2->save();

        DB::commit();
    }


}
