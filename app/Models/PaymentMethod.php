<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use App\Models\AdminAccounts;
use Hamcrest\Core\HasToString;
use Jenssegers\Mongodb\Eloquent\HybridRelations;

class PaymentMethod extends Model
{
    use HasFactory;
    use HybridRelations;
    protected $connection   =   'mongodb';
    protected $collection   =   'payment_methods';
    protected $fillable = [
        'name',
        'country_id',
        'icon',
        'is_active',
        'p_id',
    ];

    public function getIconAttribute($value)
    {
        if ($value) {
            $prefix = 'https://victorybucket-new.s3.ap-south-1.amazonaws.com/staging/payment-method/';
            return $prefix . '' . $value;
        } else {
            return public_path('images/no_image.png');
        }
    }

    public function adminAccount()
    {
        return $this->hasOne(AdminAccounts::class, 'payment_method_id', '_id');
    }
}
