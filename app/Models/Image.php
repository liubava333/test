<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    // Разрешаем массовое заполнение поля path
    protected $fillable = ['path'];// путь до файла на компі

    // Указываем Laravel, что нужно автоматически добавлять поле 'url' в JSON-ответы
    protected $appends = ['url'];// путь до файла в браузері

    /**
     * Создаем виртуальное поле 'url'
     * Теперь при вызове $image->url будет возвращаться полноценная ссылка
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
