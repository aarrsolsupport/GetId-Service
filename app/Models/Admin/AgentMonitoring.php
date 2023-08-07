<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class AgentMonitoring extends Model
{
    use HasFactory;
    protected $connection   =   'mongodb';
    protected $collection   =   '';
}
