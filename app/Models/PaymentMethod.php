<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;


class PaymentMethod extends Model
{
    use HasFactory;
    protected $connection   =   'mongodb';
    protected $collection   =   'payment_methods';
    protected $fillable = [
        'name',
        'country_id',
        'icon',
        'is_active'
    ];

    public function getIconAttribute($value){
        if($value){
            $prefix = 'https://victorybucket-new.s3.ap-south-1.amazonaws.com/staging/payment-method/';
            return $prefix.''.$value;
        }else{
            return public_path('images/no_image.png');
        }
    }
}
