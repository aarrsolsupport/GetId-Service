<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AdminAccounts extends Model
{
    protected $fillable = [
        'user_id',
        'country',
        'account_no',
        'ifsc_code',
        'holder_name',
        'upi_id',
        'minDepo',
        'maxDepo',
        'minWdl',
        'maxWdl',
        'type',
        'account_access', // 1=> Deposit, 2=> Withdrawal, 3=> Deposit and Withdrawal
        'is_deleted',
        'qrCode',
        'max_amount',
        'total_request',
        'phone',
    ];
}
