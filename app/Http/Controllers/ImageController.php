<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function index()
    {
        // Берем все картинки из базы данных (можно отсортировать по новизне)
        $images = Image::latest()->get();

        // Каждая картинка уже будет содержать id и url
        return response()->json([
            'images' => $images
        ], 200);
    }

    // Отправка (измененный метод)
    public function upload(Request $request) {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        $savedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('uploads', 'public');

                // обовязково створити sim-link: sail artisan storage:link
                $imageModel = Image::create([
                    'path' => $path
                ]);
                $savedImages[] = [
                    'id' => $imageModel->id,
                    'url' => $imageModel->url
                ];
            }
        }
        return response()->json(['images' => $savedImages], 200);
    }

// Удаление с сервера
    public function destroy($id) {
        $image = Image::findOrFail($id);

        // Удаляем физический файл из хранилища storage
        $relativePath = str_replace('/storage/', '', $image->url);
        Storage::disk('public')->delete($relativePath);

        // Удаляем запись из БД
        $image->delete();

        return response()->json(['message' => 'Успешно удалено'], 200);
    }

// Редактирование (замена) файла
    public function update(Request $request, $id) {
        $request->validate(['image' => 'required|image|max:2048']);
        $image = \App\Models\Image::findOrFail($id);

        // Удаляем старый файл
        $oldPath = str_replace('/storage/', '', $image->url);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);

        // Сохраняем новый
        $newPath = $request->file('image')->store('uploads', 'public');

        $image->update([
            'path' => $newPath,
            'url' => \Illuminate\Support\Facades\Storage::url($newPath)
        ]);

        return response()->json(['url' => $image->url], 200);
    }
}
