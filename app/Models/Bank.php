<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Support\Str;
class Bank extends Model
{

    protected $connection   =   'mongodb';
    protected $collection   =   'banks';

    protected $fillable = [
        'bank_name',
        'country',
        'icon',
        'slug',
        'labels',
        'created_at',
        'is_active'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $slug = str_slug($model->bank_name);
            $counter = 0;
            
            while (self::where('slug', $slug)->where('_id', '!=', $model->_id)->exists()) {
                $counter++;
                $slug = str_slug($model->bank_name) . '-' . $counter;
            }

            $model->slug = $slug;
        });
    }

    public function getIconAttribute($value){
        if($value){
            $prefix = 'https://victorybucket-new.s3.ap-south-1.amazonaws.com/staging/bank/';
            return $prefix.''.$value;
        }else{
            return public_path('images/no_image.png');
        }
    }


}
