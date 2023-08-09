<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class WalletLimit extends Model
{
    use HasFactory;
    protected $connection   =   'mongodb';
    protected $collection   =   'wallet_limits';
    protected $fillable = [
        'minimum_deposit_limit',
        'maximum_deposit_limit',
        'minimum_withdraw_limit',
        'maximum_withdraw_limit',
    ];
}
