<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class SocialMedia extends Model
{
    use HasFactory;
    protected $connection   =   'mongodb';
    protected $collection   =   'social_medias';
    protected $fillable = [
        'contact_no1',
        'contact_no2',
        'whatsapp1',
        'whatsapp2',
        'email',
        'telegram_link',
        'instagram_link',
        'facebook_link',
    ];
}
