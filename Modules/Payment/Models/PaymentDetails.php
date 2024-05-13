<?php

namespace Modules\Payment\Models;


use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class PaymentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'email',
        'user_id',
    ];

    /**
     * Get the donation associated with the payment detail.
     */
    public function Payment()
    {
        return $this->belongsTo(User::class);
    }
}
