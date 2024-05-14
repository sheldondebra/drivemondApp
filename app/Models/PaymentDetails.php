<?php

namespace App\Models;


use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class PaymentDetails extends Model
{
    use HasFactory;

    protected $table = 'payment_details';

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
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
