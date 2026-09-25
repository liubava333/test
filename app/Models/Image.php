<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    // Разрешаем массовое заполнение поля path
    protected $fillable = ['path'];

    // Указываем Laravel, что нужно автоматически добавлять поле 'url' в JSON-ответы
    protected $appends = ['url'];

    /**
     * Создаем виртуальное поле 'url'
     * Теперь при вызове $image->url будет возвращаться полноценная ссылка
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
