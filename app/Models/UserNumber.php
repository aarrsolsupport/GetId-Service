<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Support\Str;
class UserNumber extends Model
{

    protected $connection   =   'mongodb';
    protected $collection   =   'user_numbers';

    protected $fillable = [
        'name',
        'user_id',
        'country_id',
        'state_id',
        'city_id',
        'promo_code',
        'is_saved',
        'is_called',
        'created_at',
        'updated_at'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
