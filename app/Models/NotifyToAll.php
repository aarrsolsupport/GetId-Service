<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Support\Str;

class NotifyToAll extends Model
{

    protected $connection   =   'mongodb';
    protected $collection   =   'notify_to_all';

    protected $fillable = [
        'title',
        'url',
        'body',
        'user_id',
    ];
}
