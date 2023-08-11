<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class CheaterUser extends Model
{
    use HasFactory;
    protected $connection   =   'mongodb';
    protected $collection   =   'cheater_users';
    protected $fillable = [
        'user_id',
        'user_name',
        'parent_id',
        'parent_name',
        'mobile_no',
    ];
}
