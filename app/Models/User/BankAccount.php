<?php

namespace App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use App\Models\Bank;
class BankAccount extends Model
{
    use HasFactory;
    protected $connection   = 'mongodb';
    protected $collection   = 'bank_accounts';
    protected $guarded      = [];

    public function bank(){
        return $this->belongsTo(Bank::class);
    }

    public function getDocumentAttribute($value){
        if($value){
            $prefix = 'https://victorybucket-new.s3.ap-south-1.amazonaws.com/staging/bank/';
            return $prefix.''.$value;
        }else{
            return public_path('images/no_image.png');
        }
    }
}
