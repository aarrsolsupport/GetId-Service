<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class UserFirstWithdrawDepositRequest extends Model
{
    use HasFactory;
    protected $connection   = 'mongodb';
    protected $collection   = 'user_first_wd_depo_request_getid';
    protected $guarded     = [];


}
