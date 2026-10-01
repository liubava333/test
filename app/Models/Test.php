<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

Class Test extends Model {

    protected $fillable = ['status', 'progress','parsed_data', 'type'];

//    protected $casts = ['parsed_data' => 'array'];
}
