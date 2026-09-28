<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

Class Test extends Model {
    protected $table = 'images';
    protected $fillable = ['path'];
    protected $appends = ['url'];

    public function getUrlAttribute(): string {
        return Storage::url($this->path);
    }
}
