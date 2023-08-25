<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PaymentMethod;
use App\Models\Bank;
use Jenssegers\Mongodb\Eloquent\HybridRelations;



class AdminAccounts extends Model
{

    use HybridRelations;
    protected $connection = 'mysql';

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
        'type',            // 1=bank, 2=phonepay,3=paytm,4=other
        'account_access', // 1=> Deposit, 2=> Withdrawal, 3=> Deposit and Withdrawal
        'is_deleted',
        'qrCode',
        'max_amount',
        'total_request',
        'phone',
        'payment_method_id',
        'wallet_id',
        'payment_method_id',
        'bank_id',
        'label1',
        'label2',
        'label3',
        'label4',
    ];


    public function payment()
    {
        //dd(PaymentMethod::with('')->where('_id', '64d0d8a83146000069002def')->get());
        return $this->belongsTo(PaymentMethod::class,  'payment_method_id', '_id');
    }

    public function banks()
    {
        return $this->belongsTo(Bank::class, 'bank_id', '_id');
    }
}
