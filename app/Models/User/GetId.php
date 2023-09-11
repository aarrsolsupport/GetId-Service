<?php

namespace App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User\UserFirstWithdrawDepositRequest;
use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Jenssegers\Mongodb\Eloquent\Model;
use App\Models\AdminAccounts;
use App\Models\User;

class GetId extends Model
{
    use HasFactory,HybridRelations;
    protected $connection   = 'mongodb';
    protected $collection   = 'user_request_for_getids';
    protected $guarded     = [];


    public function getDocumentAttribute($value)
    {
        if ($value) {
            $prefix = 'https://victorybucket-new.s3.ap-south-1.amazonaws.com/staging/userDeposit/';
            return $prefix . '' . $value;
        } else {
            return public_path('images/no_image.png');
        }
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function adminBank()
    {
        return $this->belongsTo(AdminAccounts::class, 'admin_account_id', 'id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'parent_id', 'id');
        return $this->belongsTo(AdminAccounts::class, 'bank_account_id', 'id');
    }
}
