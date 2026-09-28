<?php
namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

Class TestController extends Controller{
    public function index() {
        $images = Test::latest()->get();
        return response()->json([
            'images' => $images
        ], 200);
    }
    public function destroy($id) {
        $image = Test::findOrFail($id);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json([
           'message' => 'deleted'
        ],200);
    }
    public function upload(Request $request) {
        $request->validate([
            'images.*' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048'
        ]);

        $savedImages = [];
        foreach($request->file('images') as $file) {
            $path = $file->store('uploads', 'public');
            $image = Test::create([
                'path' => $path
            ]);
            $savedImages[] = [
                'id' => $image->id,
                'url' => $image->url
            ];
        }
        return response()->json([
            'images' => $savedImages
        ], 201);
    }
    public function update(Request $request, $id) {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg'
        ]);
        $image = Test::findOrFail($id);
        Storage::disk('public')->delete($image->path);

        $newPath = $request->file('image')->store('uploads','public');
        $image->update([
            'path' => $newPath
        ]);
        return response()->json([
            'url' => $image->url
        ], 200);
    }
}
