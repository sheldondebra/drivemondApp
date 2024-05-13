<?php

namespace Modules\UserManagement\Http\Controllers;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TransactionManagement\Interfaces\TransactionInterface;
use Modules\TransactionManagement\Repositories\TransactionRepository;
use Modules\TransactionManagement\Traits\TransactionTrait;
use Modules\UserManagement\Interfaces\DriverInterface;

class CashCollectController extends Controller
{
    use TransactionTrait;

    public function __construct(
        private DriverInterface      $driver,
        private TransactionInterface $transaction
    )
    {
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $driver = $this->driver->getBy('id', $id);
        $transactions = $this->transaction->get(limit: paginationLimit(), offset: 1, attributes: ['user_id' => $id, 'transaction_type' => 'admin_cash_collect']);

        return view('usermanagement::admin.driver.withdraw.collect-cash', compact('driver', 'transactions'));

    }


    public function collect(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|gt:0'
        ]);

        $driver = $this->driver->getBy('id', $id, ['relations' => 'userAccount']);
        if ($request->amount > $driver->userAccount->payable_balance) {

            Toastr::error(AMOUNT_400['message']);
            return back();
        }
        $this->collectCashTransaction($driver, $request->amount);

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }


}
