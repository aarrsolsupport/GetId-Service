<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;
use App\Models\User;

use Jenssegers\Mongodb\Eloquent\HybridRelations;

class UserRequestForGetId extends Model
{
  /**
   * [$connection description]
   * @var string
   */
  use HybridRelations;
  protected $connection   =   'mongodb';
  protected $collection   =   'user_request_for_getids';
  // public    $timestamps   =   false;

  protected $fillable = [
    'domain',
    'user_id',
    'parent_id',
    'website',
    'username',
    'balance',
    'admin_balance',
    'userid',
    'password',
    'payment_bill',
    'from',
    'to',
    'from_username',
    'to_username',
    'site',
    'account',
    'status',  // 0- Pending, 1- Approved, 2- Rejected, 3- Cancelled
    'remark',
    'user_ip',
    'upline_ip',
    'user_get_id',
    'contact_no',
    'whatsapp_no',
    'type', // 1- Get ID, 2- Deposit, 3- Withdrawal, 4- Transfer fund, 5- Forgot Password
    'accept_reject_time',
    'request_time'
  ];

  public function userRecord()
  {
    return $this->belongsTo(User::class, 'user_id', 'id');
  }
}
