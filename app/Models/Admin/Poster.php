<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class Poster extends Model
{
    use HasFactory;
    protected $connection   =   'mongodb';
    protected $collection   =   'posters';
    protected $fillable = [
        'type',
        'image',
        'is_active'
    ];

    protected $types = array(
        1   => 'Main Slider',
        2   => 'Sports Images',
        3   => 'Provider Images',
        4   => 'Transaction Images',
        5   => 'Payment Images',
        6   => 'Video Promotion',
        7   => 'First Login Popup',
        8   => 'After Login Banner',
        9   => 'Promotion Image',
        10  => 'Live Casino Image',
        11  => 'About Us Video',
    );

    public function getTypeAttribute($value){
        return $this->types[$value];
    }

    public function getImageAttribute($value){
        if($value){
            $prefix = 'https://victorybucket-new.s3.ap-south-1.amazonaws.com/staging/posters/';
            return $prefix.''.$value;
        }else{
            return public_path('images/no_image.png');
        }
    }

}
