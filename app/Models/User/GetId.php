<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class GetId extends Model
{
    use HasFactory;
    protected $connection   = 'mongodb';
    protected $collection   = 'user_request_for_getids';
    protected $guarded     = [];


    public function getDocumentAttribute($value){
        if($value){
            $prefix = 'https://victorybucket-new.s3.ap-south-1.amazonaws.com/staging/userDeposit/';
            return $prefix.''.$value;
        }else{
            return public_path('images/no_image.png');
        }
    }
}
