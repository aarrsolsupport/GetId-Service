<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory;
    protected $fillable = [
        'url',
        'admin_url',
        'is_landing_page',
        'business_type',
        'templete',
        'country_code',
        'website_logo',
        'website_fevicon',
        'website_apk',
        'admin_css',
        'front_css',
    ];
}
