<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['status', 'progress', 'parsed_data', 'type'];

    protected $casts = [// преобразует (приводит) типы данных
        'parsed_data' => 'array', // Автоматически превращает JSON из БД в массив PHP
    ];
}
