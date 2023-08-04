<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class Bank extends Model
{
    protected $connection   =   'mongodb';
    protected $collection   =   'banks';

    protected $fillable = [
        'bank_name',
        'country',
        'icon',
        'created_at'
    ];
}
