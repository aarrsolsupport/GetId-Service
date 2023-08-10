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
        'support_no1',
        'support_no2',
        'whatsapp_no1',
        'whatsapp_no2',
        'email',
        'telegram_link',
        'instagram_link',
        'facebook_link',
    ];
}
