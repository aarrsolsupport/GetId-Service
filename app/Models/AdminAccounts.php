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
        'type',            // 1=bank, 2=phonepay,3=paytm,4=other
        'account_access', // 1=> Deposit, 2=> Withdrawal, 3=> Deposit and Withdrawal
        'is_deleted',
        'qrCode',
        'max_amount',
        'total_request',
        'phone',
        'wallet_id',
        'payment_method_id',
        'bank_id',
        'label1',
        'label2',
        'label3',
        'label4',
    ];

}
