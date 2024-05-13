<?php

namespace Modules\UserManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WithdrawMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'method_name',
        'method_fields',
        'is_default',
        'is_active',
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'method_fields' => 'json',
        'is_default'=>'boolean',
        'is_active'=>'boolean',
    ];

    protected static function newFactory()
    {
        return \Modules\UserManagement\Database\factories\WithdrawMethodFactory::new();
    }
}
