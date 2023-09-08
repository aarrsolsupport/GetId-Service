<?php

namespace App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Jenssegers\Mongodb\Eloquent\Model;
use App\Models\User;

class UserFirstWithdrawDepositRequest extends Model
{
    use HasFactory,HybridRelations;
    protected $connection   = 'mongodb';
    protected $collection   = 'user_first_wd_depo_request_getid';
    protected $guarded     = [];

    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function parent(){
        return $this->belongsTo(User::class, 'parent_id');
    }

}
